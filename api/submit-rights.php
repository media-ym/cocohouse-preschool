<?php

require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/../includes/privacy.php';
require_once __DIR__ . '/../includes/captcha.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload) || strlen((string) $raw) > 20000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (trim((string) ($payload['company'] ?? '')) !== '') {
    echo json_encode(['success' => true]);
    exit;
}

$allowed = ['access', 'correct', 'erase', 'withdraw'];
$request = (string) ($payload['request'] ?? '');
$name = trim((string) ($payload['name'] ?? ''));
$phone = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? '')) ?? '';
$email = trim((string) ($payload['email'] ?? ''));
$detail = trim((string) ($payload['detail'] ?? ''));
if (!in_array($request, $allowed, true) || $name === '' || strlen($phone) < 10 || $detail === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Add your name, a valid mobile number, the request type, and what we should look up.']);
    exit;
}
if (!dpdp_consent_granted($payload, 'consent_rights')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tick the box that lets us use your contact details for this request.']);
    exit;
}
if (!captcha_verify((string) ($payload['captcha_token'] ?? ''), (string) ($payload['captcha_answer'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Type the code shown in the picture. If it expired, use the new code.']);
    exit;
}

$record = [
    'id' => 'rgt_' . bin2hex(random_bytes(4)),
    'request' => $request,
    'name' => mb_substr($name, 0, 120),
    'phone' => mb_substr($phone, 0, 15),
    'email' => mb_substr($email, 0, 120),
    'detail' => mb_substr($detail, 0, 2000),
    'notice_version' => dpdp_notice_version(),
    'status' => 'new',
    'created_at' => date('Y-m-d H:i:s'),
];
$g = dpdp_grievance();
$labels = [
    'access' => 'Access',
    'correct' => 'Correction',
    'erase' => 'Erasure',
    'withdraw' => 'Withdraw consent',
];
$plain = "Data rights request\nType: " . $labels[$request] . "\nName: {$record['name']}\nPhone: {$record['phone']}\nEmail: " . ($record['email'] !== '' ? $record['email'] : '—') . "\nDetail: {$record['detail']}\nId: {$record['id']}\n";
$record['emailed'] = false;
try {
    save_rights_request($record);
} catch (RuntimeException $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not store this securely. Please call the school.']);
    exit;
}
if (can_send_mail()) {
    $headers = 'From: Cocohouse Preschool <' . mail_header($g['email']) . ">\r\nContent-Type: text/plain; charset=UTF-8";
    $sent = true;
    foreach (enquiry_recipients() as $to) {
        $sent = mail($to, mail_header('Cocohouse data rights | ' . $labels[$request]), $plain, $headers) && $sent;
    }
    if ($sent && enquiry_recipients() !== []) {
        try {
            $saved = load_rights_requests();
            foreach ($saved['items'] as $index => $existing) {
                if (($existing['id'] ?? '') === $record['id']) {
                    $saved['items'][$index]['emailed'] = true;
                    break;
                }
            }
            write_private_json(COCO_STORAGE . '/data-rights.json', $saved);
        } catch (RuntimeException $error) {
            // The request is already stored.
        }
    }
}

echo json_encode(['success' => true]);
