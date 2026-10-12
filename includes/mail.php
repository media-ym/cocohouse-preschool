<?php

require_once __DIR__ . '/auth.php';

function mail_header(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function can_send_mail(): bool
{
    if (!function_exists('mail')) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('mail', $disabled, true)) {
        return false;
    }
    $sendmail = trim((string) ini_get('sendmail_path'));
    $binary = strtok($sendmail, ' ');
    return is_string($binary) && $binary !== '' && is_executable($binary);
}

function enquiry_recipients(): array
{
    $config = admin_config();
    $emails = $config['notify_emails'] ?? [];
    if (!is_array($emails)) {
        return [];
    }
    $clean = [];
    foreach ($emails as $email) {
        $email = mail_header((string) $email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $clean[] = $email;
        }
    }
    return array_values(array_unique($clean));
}

function enquiry_message(array $item): array
{
    $labels = enquiry_labels();
    $type = $labels[$item['type'] ?? ''] ?? 'Enquiry';
    $who = trim((string) ($item['parentName'] ?? ''));
    if ($who === '') {
        $who = trim((string) ($item['childName'] ?? '')) ?: 'Parent';
    }
    $subject = mail_header('Cocohouse enquiry: ' . $type . ' — ' . $who);
    $rows = [
        'New enquiry from the Cocohouse website',
        '',
        'Type: ' . $type,
        'Parent: ' . (($item['parentName'] ?? '') !== '' ? $item['parentName'] : '—'),
        'Child: ' . (($item['childName'] ?? '') !== '' ? $item['childName'] : '—'),
        'Age: ' . (($item['childAge'] ?? '') !== '' ? $item['childAge'] : '—'),
        'Program: ' . (($item['program'] ?? '') !== '' ? $item['program'] : '—'),
        'Mobile: ' . (($item['phone'] ?? '') !== '' ? $item['phone'] : '—'),
        'Email: ' . (($item['email'] ?? '') !== '' ? $item['email'] : '—'),
        'Message: ' . (($item['message'] ?? '') !== '' ? $item['message'] : '—'),
        'Received: ' . format_when((string) ($item['created_at'] ?? '')),
    ];
    return [$subject, implode("\n", $rows)];
}

function send_enquiry_emails(array $item): bool
{
    $recipients = enquiry_recipients();
    if (!$recipients || !can_send_mail()) {
        return false;
    }

    $config = admin_config();
    $fromEmail = mail_header((string) ($config['from_email'] ?? 'info@cocohouse.in'));
    $fromName = mail_header((string) ($config['from_name'] ?? 'Cocohouse Preschool'));
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'info@cocohouse.in';
    }
    $reply = mail_header((string) ($item['email'] ?? ''));
    if (!filter_var($reply, FILTER_VALIDATE_EMAIL)) {
        $reply = $fromEmail;
    }

    [$subject, $body] = enquiry_message($item);
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $reply,
    ]);

    $sentAll = true;
    foreach ($recipients as $to) {
        $sent = @mail($to, $encodedSubject, $body, $headers, '-f ' . $fromEmail);
        if (!$sent) {
            $sentAll = false;
        }
    }
    return $sentAll;
}
