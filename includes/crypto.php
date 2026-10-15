<?php

if (!defined('COCO_STORAGE')) {
    require_once __DIR__ . '/store.php';
}

function data_key_path(): string
{
    return COCO_STORAGE . '/data.key';
}

function data_key(): string
{
    $path = data_key_path();
    if (is_file($path)) {
        $key = file_get_contents($path);
        if (!is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException('The data key cannot be used. Enquiry files were not opened.');
        }
        return $key;
    }
    if (!function_exists('openssl_encrypt')) {
        throw new RuntimeException('Encryption is not available. The enquiry was not saved.');
    }
    $key = random_bytes(32);
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not prepare storage for encryption.');
    }
    if (file_put_contents($path, $key, LOCK_EX) !== 32) {
        throw new RuntimeException('Could not create the data key. The enquiry was not saved.');
    }
    @chmod($path, 0600);
    return $key;
}

function encrypt_payload(string $plain): string
{
    if (!function_exists('openssl_encrypt')) {
        throw new RuntimeException('Encryption is not available. The enquiry was not saved.');
    }
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', data_key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if (!is_string($cipher) || strlen($tag) !== 16) {
        throw new RuntimeException('Could not encrypt. The enquiry was not saved.');
    }
    return 'COCO1.' . base64_encode($iv . $tag . $cipher);
}

function decrypt_payload(string $stored): string
{
    $stored = trim($stored);
    if (!str_starts_with($stored, 'COCO1.')) {
        throw new RuntimeException('Stored file is not encrypted.');
    }
    $raw = base64_decode(substr($stored, 6), true);
    if (!is_string($raw) || strlen($raw) < 29) {
        throw new RuntimeException('Stored file could not be read.');
    }
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', data_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if (!is_string($plain)) {
        throw new RuntimeException('Stored file could not be unlocked.');
    }
    return $plain;
}

function write_private_json(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Could not save data.');
    }
    $stored = encrypt_payload($json);
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not save data.');
    }
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $stored . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Could not save data.');
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Could not save data.');
    }
}

function read_private_json(string $path, array $default): array
{
    if (!is_file($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return $default;
    }
    $trim = ltrim($raw);
    if ($trim[0] === '{' || $trim[0] === '[') {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Stored file could not be read.');
        }
        write_private_json($path, $data);
        return $data;
    }
    $data = json_decode(decrypt_payload($trim), true);
    if (!is_array($data)) {
        throw new RuntimeException('Stored file could not be read.');
    }
    return $data;
}
