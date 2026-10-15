<?php

require_once __DIR__ . '/../includes/captcha.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

try {
    $issued = captcha_issue();
} catch (RuntimeException $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'The check code could not be prepared.']);
    exit;
}

echo json_encode(['success' => true, 'token' => $issued['token'], 'image' => $issued['image']]);
