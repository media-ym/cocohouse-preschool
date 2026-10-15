<?php

require_once __DIR__ . '/crypto.php';

function captcha_key(): string
{
    return hash('sha256', data_key() . '|captcha', true);
}

function captcha_issue(): array
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $answer = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < 5; $i++) {
        $answer .= $alphabet[random_int(0, $max)];
    }
    $exp = time() + 600;
    $nonce = bin2hex(random_bytes(8));
    $mac = hash_hmac('sha256', $nonce . '|' . $answer . '|' . $exp, captcha_key());
    $token = rtrim(strtr(base64_encode(json_encode(['e' => $exp, 'n' => $nonce, 'm' => $mac], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    return ['token' => $token, 'image' => captcha_image($answer)];
}

function captcha_image(string $answer): string
{
    $width = 156;
    $height = 48;
    $image = imagecreatetruecolor($width, $height);
    if ($image === false) {
        throw new RuntimeException('Could not prepare the captcha.');
    }
    $paper = imagecolorallocate($image, 255, 248, 225);
    $ink = imagecolorallocate($image, 24, 74, 150);
    $line = imagecolorallocate($image, 244, 208, 0);
    imagefilledrectangle($image, 0, 0, $width, $height, $paper);
    for ($i = 0; $i < 6; $i++) {
        imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $line);
    }
    $x = 14;
    $length = strlen($answer);
    for ($i = 0; $i < $length; $i++) {
        imagestring($image, 5, $x, random_int(12, 20), $answer[$i], $ink);
        $x += 26;
    }
    for ($i = 0; $i < 40; $i++) {
        imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $ink);
    }
    ob_start();
    imagepng($image);
    $png = ob_get_clean();
    imagedestroy($image);
    if (!is_string($png) || $png === '') {
        throw new RuntimeException('Could not prepare the captcha.');
    }
    return 'data:image/png;base64,' . base64_encode($png);
}

function captcha_verify(string $token, string $answer): bool
{
    $pad = strtr($token, '-_', '+/');
    $pad .= str_repeat('=', (4 - strlen($pad) % 4) % 4);
    $json = base64_decode($pad, true);
    $data = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($data)) {
        return false;
    }
    $exp = (int) ($data['e'] ?? 0);
    $nonce = (string) ($data['n'] ?? '');
    $mac = (string) ($data['m'] ?? '');
    $answer = strtoupper((string) preg_replace('/\s+/', '', $answer));
    if ($exp < time() || !preg_match('/^[a-f0-9]{16}$/', $nonce) || !preg_match('/^[A-Z0-9]{5}$/', $answer)) {
        return false;
    }
    $expect = hash_hmac('sha256', $nonce . '|' . $answer . '|' . $exp, captcha_key());
    if (!hash_equals($expect, $mac)) {
        return false;
    }
    return captcha_consume($nonce, $exp);
}

function captcha_consume(string $nonce, int $exp): bool
{
    $path = COCO_STORAGE . '/captcha-used.json';
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        return false;
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            return false;
        }
        $raw = stream_get_contents($handle);
        $items = json_decode(is_string($raw) ? $raw : '', true);
        if (!is_array($items)) {
            $items = [];
        }
        $now = time();
        $fresh = [];
        foreach ($items as $row) {
            if (!is_array($row) || (int) ($row['e'] ?? 0) <= $now) {
                continue;
            }
            if ((string) ($row['n'] ?? '') === $nonce) {
                return false;
            }
            $fresh[] = $row;
        }
        $fresh[] = ['n' => $nonce, 'e' => $exp];
        $encoded = json_encode(array_slice($fresh, -500));
        if ($encoded === false) {
            return false;
        }
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $encoded);
        fflush($handle);
        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
