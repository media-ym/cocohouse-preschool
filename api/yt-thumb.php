<?php

require_once __DIR__ . '/../includes/store.php';

$id = (string) ($_GET['id'] ?? '');
if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
    http_response_code(404);
    exit;
}

$dir = COCO_STORAGE . '/yt-thumbs';
$file = $dir . '/' . $id . '.jpg';
if (!is_file($file)) {
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        http_response_code(404);
        exit;
    }
    $url = 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
    $binary = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
        ]);
        if (defined('CURLPROTO_HTTPS')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }
        $binary = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code !== 200) {
            $binary = false;
        }
    }
    if (!is_string($binary) || strlen($binary) < 500 || @getimagesizefromstring($binary) === false) {
        http_response_code(404);
        exit;
    }
    file_put_contents($file, $binary, LOCK_EX);
}

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
readfile($file);
