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
if ($raw === false || strlen($raw) > 20000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid enquiry']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid enquiry']);
    exit;
}

if (trim((string) ($payload['company'] ?? '')) !== '') {
    echo json_encode(['success' => true]);
    exit;
}

$types = array_keys(enquiry_labels());
$type = (string) ($payload['type'] ?? 'contact');
if (!in_array($type, $types, true)) {
    $type = 'contact';
}

$phone = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? '')) ?? '';
$parent = trim((string) ($payload['parentName'] ?? ''));
$child = trim((string) ($payload['childName'] ?? ''));
if (strlen($phone) < 10 || ($parent === '' && $child === '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please add a name and a valid mobile number.']);
    exit;
}
if (!dpdp_consent_granted($payload, 'consent_enquiry')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tick the box that agrees to this enquiry. It is separate from updates and analytics.']);
    exit;
}
if ($type === 'special_education' && !dpdp_consent_granted($payload, 'consent_support')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tick the box about the support you describe before this form is sent.']);
    exit;
}
if (!captcha_verify((string) ($payload['captcha_token'] ?? ''), (string) ($payload['captcha_answer'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Type the code shown in the picture. If it expired, use the new code.']);
    exit;
}

$item = [
    'id' => 'enq_' . bin2hex(random_bytes(4)),
    'type' => $type,
    'parentName' => mb_substr($parent, 0, 120),
    'childName' => mb_substr($child, 0, 120),
    'childAge' => mb_substr(trim((string) ($payload['childAge'] ?? '')), 0, 40),
    'program' => mb_substr(trim((string) ($payload['program'] ?? '')), 0, 80),
    'phone' => mb_substr($phone, 0, 15),
    'email' => mb_substr(trim((string) ($payload['email'] ?? '')), 0, 120),
    'message' => mb_substr(trim((string) ($payload['message'] ?? '')), 0, 2000),
    'status' => 'new',
    'created_at' => date('Y-m-d H:i:s'),
];
$ads = [];
foreach (['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing'] as $key) {
    $value = trim(str_replace(["\r", "\n"], ' ', (string) ($payload[$key] ?? '')));
    $value = preg_replace('/[^A-Za-z0-9._:\/ -]/', '', $value) ?? '';
    if ($value !== '') {
        $ads[$key] = mb_substr($value, 0, 180);
    }
}
if ($ads) {
    $item['ads'] = $ads;
}
$purposes = [
    'enquiry_response' => true,
    'child_details' => true,
    'support_details' => $type === 'special_education',
    'admission_updates' => dpdp_consent_granted($payload, 'consent_updates'),
];
$consent = dpdp_consent_record($item['id'], $type, $purposes);
$item['consent'] = $consent;
$item['emailed'] = false;
try {
    save_consent_record($consent);
    $data = load_enquiries();
    array_unshift($data['items'], $item);
    save_enquiries($data);
} catch (RuntimeException $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not store this securely. Please call the school.']);
    exit;
}
if (send_enquiry_emails($item)) {
    try {
        $data = load_enquiries();
        foreach ($data['items'] as $index => $existing) {
            if (($existing['id'] ?? '') === $item['id']) {
                $data['items'][$index]['emailed'] = true;
                break;
            }
        }
        save_enquiries($data);
    } catch (RuntimeException $error) {
        // The enquiry is already stored.
    }
}

echo json_encode(['success' => true]);
