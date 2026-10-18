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
    return !in_array('mail', $disabled, true);
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

function enquiry_field(array $item, string $key): string
{
    $value = trim((string) ($item[$key] ?? ''));
    return $value !== '' ? $value : '—';
}

function enquiry_ad(array $item, string $key): string
{
    $ads = is_array($item['ads'] ?? null) ? $item['ads'] : [];
    $value = trim((string) ($ads[$key] ?? ''));
    return $value !== '' ? $value : '—';
}

function enquiry_admin_url(): string
{
    $data = read_json(COCO_STORAGE . '/pages.json', []);
    $seo = is_array($data['site']['seo'] ?? null) ? $data['site']['seo'] : [];
    $url = trim((string) ($seo['public_url'] ?? ''));
    if (!preg_match('#^https://#i', $url)) {
        $url = 'https://cocohouse.in';
    }
    return rtrim($url, '/') . '/admin/enquiries.php';
}

function enquiry_mail_text(string $value): string
{
    return e($value);
}

function enquiry_mail_link(string $href, string $label): string
{
    return '<a href="' . e($href) . '" style="color:#184a96;text-decoration:underline;">' . e($label) . '</a>';
}

function enquiry_mail_row(string $label, string $valueHtml): string
{
    return '<tr>'
        . '<td width="180" style="width:180px;background:#f3f6fb;padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.4;font-weight:700;color:#3d4b63;border-bottom:1px solid #ffffff;vertical-align:middle;">'
        . e($label)
        . '</td>'
        . '<td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.45;color:#1c2433;border-bottom:1px solid #eef1f6;vertical-align:middle;word-break:break-word;">'
        . $valueHtml
        . '</td>'
        . '</tr>';
}

function enquiry_message(array $item): array
{
    $labels = enquiry_labels();
    $type = $labels[$item['type'] ?? ''] ?? 'Enquiry';
    $program = enquiry_field($item, 'program');
    $parent = enquiry_field($item, 'parentName');
    $child = enquiry_field($item, 'childName');
    $age = enquiry_field($item, 'childAge');
    $phone = enquiry_field($item, 'phone');
    $email = enquiry_field($item, 'email');
    $message = enquiry_field($item, 'message');
    $page = enquiry_ad($item, 'landing');
    $source = enquiry_ad($item, 'utm_source');
    $medium = enquiry_ad($item, 'utm_medium');
    $campaign = enquiry_ad($item, 'utm_campaign');
    $gclid = enquiry_ad($item, 'gclid');
    $who = $parent !== '—' ? $parent : ($child !== '—' ? $child : '—');
    $subjectProgram = $program;
    $subject = mail_header('Cocohouse Enquiry | ' . $type . ' | ' . $subjectProgram . ' | ' . $who);

    $created = (string) ($item['created_at'] ?? '');
    $ts = strtotime($created);
    $when = $ts ? date('M j, Y', $ts) . ' · ' . date('g:i A', $ts) : format_when($created);
    $received = format_when($created);

    $plain = implode("\n", [
        'New enquiry from the Cocohouse website',
        '',
        'Type: ' . $type,
        'Parent: ' . $parent,
        'Child: ' . $child,
        'Age: ' . $age,
        'Program: ' . $program,
        'Mobile: ' . $phone,
        'Email: ' . $email,
        'Message: ' . $message,
        'Page: ' . $page,
        'Source: ' . $source,
        'Medium: ' . $medium,
        'Campaign: ' . $campaign,
        'Google click: ' . $gclid,
        'Received: ' . $received,
        '',
        'Admin: ' . enquiry_admin_url(),
    ]);

    $phoneHtml = $phone === '—'
        ? enquiry_mail_text('—')
        : enquiry_mail_link('tel:' . preg_replace('/[^\d+]/', '', $phone), $phone);
    $emailHtml = filter_var($email, FILTER_VALIDATE_EMAIL)
        ? enquiry_mail_link('mailto:' . $email, $email)
        : enquiry_mail_text($email);
    $adminUrl = enquiry_admin_url();

    $rows = enquiry_mail_row('Parent Name', enquiry_mail_text($parent))
        . enquiry_mail_row('Child Name', enquiry_mail_text($child))
        . enquiry_mail_row('Child Age', enquiry_mail_text($age))
        . enquiry_mail_row('Phone', $phoneHtml)
        . enquiry_mail_row('Email', $emailHtml)
        . enquiry_mail_row('Program', enquiry_mail_text($program))
        . enquiry_mail_row('Message', enquiry_mail_text($message))
        . enquiry_mail_row('Page', enquiry_mail_text($page))
        . ($source === '—' ? '' : enquiry_mail_row('Source', enquiry_mail_text($source)))
        . ($medium === '—' ? '' : enquiry_mail_row('Medium', enquiry_mail_text($medium)))
        . ($campaign === '—' ? '' : enquiry_mail_row('Campaign', enquiry_mail_text($campaign)))
        . ($gclid === '—' ? '' : enquiry_mail_row('Google click', enquiry_mail_text($gclid)));

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#eef2f6;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">New enquiry from ' . e($who) . ' — ' . e($program) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f6;margin:0;padding:0;">'
        . '<tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:560px;max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;">'
        . '<tr><td style="background:#184a96;padding:28px 28px 24px;">'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:26px;line-height:1.25;font-weight:700;color:#ffffff;">&#128276; New Enquiry Received</div>'
        . '<div style="margin-top:8px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.4;color:#dbe7fb;">' . e($type) . ' · ' . e($when) . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:22px 22px 6px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
        . $rows
        . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:16px 22px 8px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff8e6;border-left:4px solid #f4d000;border-radius:8px;">'
        . '<tr><td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.45;color:#8a5a12;">'
        . '&#128222; <strong style="color:#8a4b08;">Action needed:</strong> Please respond within 24 hours.'
        . '</td></tr></table>'
        . '</td></tr>'
        . '<tr><td align="center" style="padding:18px 22px 28px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="background:#184a96;border-radius:10px;">'
        . '<a href="' . e($adminUrl) . '" style="display:inline-block;padding:12px 22px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">View in Admin Panel →</a>'
        . '</td></tr></table>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr></table>'
        . '</body></html>';

    return [$subject, $plain, $html];
}

function mail_last_error(): string
{
    return (string) ($GLOBALS['coco_mail_error'] ?? '');
}

function mail_set_error(string $message): void
{
    $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');
    if ($message === '') {
        $message = 'The mail server did not accept this message.';
    }
    $GLOBALS['coco_mail_error'] = function_exists('mb_substr') ? mb_substr($message, 0, 240) : substr($message, 0, 240);
}

function mail_subject(string $subject): string
{
    $subject = mail_header($subject);
    if ($subject === '' || !preg_match('/[^\x20-\x7E]/', $subject)) {
        return $subject;
    }
    $parts = [];
    $buffer = '';
    $chars = preg_split('//u', $subject, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($chars)) {
        return $subject;
    }
    foreach ($chars as $char) {
        $next = $buffer . $char;
        $encoded = '=?UTF-8?B?' . base64_encode($next) . '?=';
        if (strlen($encoded) > 75 && $buffer !== '') {
            $parts[] = '=?UTF-8?B?' . base64_encode($buffer) . '?=';
            $buffer = $char;
            continue;
        }
        $buffer = $next;
    }
    if ($buffer !== '') {
        $parts[] = '=?UTF-8?B?' . base64_encode($buffer) . '?=';
    }
    return implode("\r\n ", $parts);
}

function deliver_mail(string $to, string $subject, string $plain, string $html, string $fromEmail, string $fromName, string $replyTo): bool
{
    $to = mail_header($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        mail_set_error('A notification address is not a valid email.');
        return false;
    }
    if (!can_send_mail()) {
        mail_set_error('PHP mail is turned off on this server.');
        return false;
    }
    $fromName = trim(str_replace('"', '', mail_header($fromName)));
    if ($fromName === '') {
        $fromName = 'Cocohouse Preschool';
    }
    $domain = 'cocohouse.in';
    $at = strrchr($fromEmail, '@');
    if (is_string($at) && strlen($at) > 1) {
        $domain = substr($at, 1);
    }
    $boundary = 'coco_' . bin2hex(random_bytes(8));
    $headers = [
        'MIME-Version: 1.0',
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . $domain . '>',
        'From: "' . $fromName . '" <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    $body = "--{$boundary}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
        . quoted_printable_encode($plain) . "\r\n"
        . "--{$boundary}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
        . quoted_printable_encode($html) . "\r\n"
        . "--{$boundary}--\r\n";
    $headerText = implode("\r\n", $headers);
    $encodedSubject = mail_subject($subject);
    $sent = @mail($to, $encodedSubject, $body, $headerText, '-f ' . $fromEmail);
    if (!$sent) {
        $sent = @mail($to, $encodedSubject, $body, $headerText);
    }
    if ($sent) {
        $GLOBALS['coco_mail_error'] = '';
        return true;
    }
    $last = error_get_last();
    $reason = is_array($last) ? (string) ($last['message'] ?? '') : '';
    mail_set_error(stripos($reason, 'mail') !== false ? $reason : 'The mail server did not accept this message.');
    return false;
}

function send_enquiry_emails(array $item): bool
{
    $recipients = enquiry_recipients();
    if (!$recipients) {
        mail_set_error('No notification email is saved in the site settings.');
        return false;
    }
    if (!can_send_mail()) {
        mail_set_error('PHP mail is turned off on this server.');
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

    [$subject, $plain, $html] = enquiry_message($item);
    $sentAll = true;
    foreach ($recipients as $to) {
        if (!deliver_mail($to, $subject, $plain, $html, $fromEmail, $fromName, $reply)) {
            $sentAll = false;
        }
    }
    return $sentAll;
}
