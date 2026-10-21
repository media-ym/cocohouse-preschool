<?php

require_once __DIR__ . '/store.php';

function dpdp_notice_version(): string
{
    return '2026-10-15-draft';
}

function dpdp_grievance(): array
{
    return [
        'officer' => 'Mrs. Pratheeksha Darak',
        'email' => 'info@cocohouse.in',
        'phone' => '+91-7039152173',
        'phone_href' => 'tel:+917039152173',
        'address' => 'Row House No 3, Millenium Park, Hari Om Nagar, Thane East, Mumbai-400081',
    ];
}

function dpdp_updated_line(): string
{
    return '<p class="legal-updated">Last updated: August 26, 2026</p>';
}

function dpdp_consent_granted(array $payload, string $key): bool
{
    return (string) ($payload[$key] ?? '') === '1';
}

function dpdp_consent_record(string $enquiryId, string $form, array $purposes): array
{
    return [
        'id' => 'cns_' . bin2hex(random_bytes(4)),
        'enquiry_id' => $enquiryId,
        'form' => $form,
        'notice_version' => dpdp_notice_version(),
        'purposes' => $purposes,
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

function load_consent_log(): array
{
    $data = read_private_json(COCO_STORAGE . '/consents.json', ['items' => []]);
    $data['items'] = array_values(is_array($data['items'] ?? null) ? $data['items'] : []);
    return $data;
}

function save_consent_record(array $record): void
{
    $data = load_consent_log();
    array_unshift($data['items'], $record);
    $data['items'] = array_slice($data['items'], 0, 5000);
    write_private_json(COCO_STORAGE . '/consents.json', $data);
}

function load_rights_requests(): array
{
    $data = read_private_json(COCO_STORAGE . '/data-rights.json', ['items' => []]);
    $data['items'] = array_values(is_array($data['items'] ?? null) ? $data['items'] : []);
    return $data;
}

function save_rights_request(array $record): void
{
    $data = load_rights_requests();
    array_unshift($data['items'], $record);
    $data['items'] = array_slice($data['items'], 0, 5000);
    write_private_json(COCO_STORAGE . '/data-rights.json', $data);
}

function dpdp_banner_html(): string
{
    return <<<'HTML'
<div class="cookie-card" id="cocoConsent" hidden>
  <strong>Cookies</strong>
  <p>We use cookies to run the site. Analytics and recordings are optional. <a href="/privacy-notice">Notice</a></p>
  <div class="cookie-actions">
    <button type="button" class="cookie-reject" id="cocoConsentReject">Reject</button>
    <button type="button" class="cookie-accept" id="cocoConsentAccept">Accept</button>
    <button type="button" class="cookie-link" id="cocoConsentCustomize">Customize</button>
  </div>
  <div class="cookie-more" id="cocoConsentMore" hidden>
    <label><input type="checkbox" id="cocoConsentAnalytics"> Analytics and recordings</label>
    <button type="button" class="cookie-accept" id="cocoConsentSave">Save</button>
  </div>
</div>
HTML;
}

function inject_privacy_chrome(string $html): string
{
    if (str_contains($html, '<h4>Quick Links</h4>') && !str_contains($html, 'class="footer-policies"')) {
        $column = '<div class="footer-policies"><h4>Policies</h4><ul>'
            . '<li><a href="/privacy-notice">Privacy notice</a></li>'
            . '<li><a href="/privacy-policy">Privacy policy</a></li>'
            . '<li><a href="/terms-and-conditions">Terms &amp; Conditions</a></li>'
            . '<li><a href="/data-rights">Your data rights</a></li>'
            . '</ul></div>';
        $html = preg_replace(
            '#(<h4>Quick Links</h4>\s*<ul>.*?</ul>\s*</div>)#s',
            '$1' . $column,
            $html,
            1
        ) ?? $html;
    }
    if (str_contains($html, '</body>') && !str_contains($html, 'id="cocoConsent"')) {
        $html = str_replace('</body>', dpdp_banner_html() . '</body>', $html);
    }
    return $html;
}

function render_legal_page(string $title, string $description, string $path, string $body): void
{
    require_once __DIR__ . '/seo.php';
    $settings = seo_settings();
    $canonical = rtrim($settings['public_url'], '/') . $path;
    $meta = seo_meta_html([
        'title' => $title,
        'description' => $description,
        'keywords' => '',
        'canonical' => $canonical,
        'robots' => 'index,follow',
        'image' => seo_absolute($settings['image'], $settings['public_url']),
        'og_title' => $title,
        'og_description' => $description,
        'og_type' => 'website',
    ], $settings);
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . e($title) . '</title>'
        . '<link rel="icon" href="/assets/images/logo.webp">'
        . '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="/assets/css/style.css?v=20261021i">'
        . $meta
        . '</head><body>'
        . legal_nav()
        . '<main class="legal-page"><div class="container">' . $body . '</div></main>'
        . legal_footer()
        . '<script src="/assets/js/main.js?v=20261021f"></script></body></html>';
    echo public_finish($html);
}

function legal_nav(): string
{
    return <<<'HTML'
<header class="navbar" id="navbar">
  <div class="container">
    <a href="/"><img src="/assets/images/logo.webp" alt="Cocohouse Preschool" class="navbar-logo"></a>
    <ul class="nav-menu">
      <li><a href="/">Home</a></li>
      <li><a href="/privacy-notice">Privacy notice</a></li>
      <li><a href="/privacy-policy">Privacy policy</a></li>
      <li><a href="/terms-and-conditions">Terms &amp; Conditions</a></li>
      <li><a href="/data-rights">Your data rights</a></li>
      <li><a href="/contact">Contact</a></li>
    </ul>
    <button class="nav-toggle" id="navToggle" type="button" aria-label="Open menu"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
  </div>
</header>
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
  <div class="mobile-drawer-header"><strong>Cocohouse</strong><button type="button" id="drawerClose" aria-label="Close menu">×</button></div>
  <nav>
    <a href="/">Home</a>
    <a href="/privacy-notice">Privacy notice</a>
    <a href="/privacy-policy">Privacy policy</a>
    <a href="/terms-and-conditions">Terms &amp; Conditions</a>
    <a href="/data-rights">Your data rights</a>
    <a href="/contact">Contact</a>
  </nav>
</div>
HTML;
}

function legal_footer(): string
{
    return '<footer class="footer"><div class="container"><div class="footer-bottom">'
        . '<p>&copy; 2026 Cocohouse Preschool &amp; Daycare | All Rights Reserved</p>'
        . '<p>Designed By <a href="https://yunickmedia.com" target="_blank" rel="noopener">Yunick Media</a></p>'
        . '</div></div></footer>';
}

function dpdp_notice_body(): string
{
    $g = dpdp_grievance();
    $address = e($g['address']);
    $email = e($g['email']);
    $phone = e($g['phone']);
    $href = e($g['phone_href']);
    return '<h1>Privacy notice</h1>'
        . dpdp_updated_line()
        . '<p>Cocohouse Preschool &amp; Daycare, ' . $address . ', is the Data Fiduciary for personal data collected on this website. This notice says what is collected, why, how long it is kept, who else receives it, and how you can use your rights.</p>'
        . '<h2>What we collect</h2><ul>'
        . '<li>Parent or guardian name, mobile number, and email, if you type them into an enquiry form.</li>'
        . '<li>Child\'s name and age, when the form asks for them. This is a child\'s personal data. Only a parent or legal guardian should submit it.</li>'
        . '<li>The program you ask about. On the special education form, that can include a condition you choose to name, such as speech or learning support.</li>'
        . '<li>The message you write, the page you sent it from, and the date and time.</li>'
        . '<li>If you allow analytics, Google and Microsoft may receive your IP address, device, and pages viewed. Form fields for name, phone, and email are not sent in those analytics events.</li>'
        . '</ul><h2>Why</h2><ul>'
        . '<li><strong>Reply to your enquiry.</strong> To call or write back about admission, daycare, a school visit, or special education. The required consent box covers this.</li>'
        . '<li><strong>Child details.</strong> To know which age group should call you. The same required box covers the child\'s name and age.</li>'
        . '<li><strong>Support you describe.</strong> Only on the special education form, a separate required box covers a condition you name, and only so the special educator can prepare for the call.</li>'
        . '<li><strong>Admission updates.</strong> Phone, email, or WhatsApp updates. This box starts unticked. The form can be sent without it.</li>'
        . '<li><strong>Analytics.</strong> To see which pages are used. A separate banner asks for this. Rejecting it does not block the enquiry.</li>'
        . '</ul><h2>How long</h2>'
        . '<p>Proposed retention, still to be confirmed by the school and a lawyer: enquiry and consent records are kept while the enquiry is open, and not longer than 24 months after the last contact, unless a law requires a longer period. A data-rights request is kept until it is answered, and then for up to 24 months as a record of that answer. Analytics retention is set inside Google and Microsoft accounts and is not fixed by this website.</p>'
        . '<h2>Who else receives it</h2><ul>'
        . '<li>Cocohouse staff who answer enquiries, at ' . $email . ', cocohousepreschool@gmail.com, pratheeksha.msw@gmail.com, and yunickmedia@gmail.com.</li>'
        . '<li>The hosting company stores the website files, including enquiry records, on the server.</li>'
        . '<li>If the site email fails, your browser may open WhatsApp with the same enquiry text. WhatsApp is operated by Meta.</li>'
        . '<li>Google Fonts loads the typeface. Google Analytics and Tag Manager load only after you accept analytics.</li>'
        . '<li>Videos and YouTube thumbnails on this site are published with the parent\'s consent. The thumbnail image is fetched by the Cocohouse server, then shown here, so your browser does not call YouTube for that picture. Pressing play loads the video from YouTube.</li>'
        . '<li>Microsoft Clarity loads only after you allow analytics. It can record how a page is used. It is not given the name, phone, or email typed into an enquiry.</li>'
        . '</ul><h2>Your rights</h2>'
        . '<p>You may ask to access, correct, or erase personal data, and you may withdraw consent. Withdrawal does not undo a reply already sent. Use <a href="/data-rights">Your data rights</a> or write to <a href="mailto:' . $email . '">' . $email . '</a>.</p>'
        . '<h2>Grievance contact</h2>'
        . '<p>Grievance Officer: ' . e($g['officer']) . '. Email <a href="mailto:' . $email . '">' . $email . '</a> or call <a href="' . $href . '">' . $phone . '</a>.</p>'
        . '<p><a href="/privacy-policy">Read the privacy policy</a> · <a href="/terms-and-conditions">Terms &amp; Conditions</a></p>';
}

function dpdp_policy_body(): string
{
    $g = dpdp_grievance();
    $email = e($g['email']);
    $phone = e($g['phone']);
    $href = e($g['phone_href']);
    return '<h1>Privacy policy</h1>'
        . dpdp_updated_line()
        . '<p>This policy explains how Cocohouse Preschool &amp; Daycare handles personal data on cocohouse.in. The shorter notice is on the <a href="/privacy-notice">privacy notice</a> page.</p>'
        . '<h2>Who we are</h2>'
        . '<p>Cocohouse Preschool &amp; Daycare, ' . e($g['address']) . '. For this website, the school is the Data Fiduciary.</p>'
        . '<h2>Children</h2>'
        . '<p>Enquiry forms ask for a child\'s name and age. The person submitting must confirm they are the parent or legal guardian. That checkbox is a consent step on this website. It is not, by itself, the verifiable parental-consent process the DPDP Act and its rules may require. The school must confirm that process with a lawyer before relying on it.</p>'
        . '<p>Videos uploaded on this website, and the YouTube thumbnails shown with them, are published with the parent\'s consent. The thumbnail is loaded through Cocohouse. The video itself plays from YouTube only after you press play.</p>'
        . '<h2>Consent</h2>'
        . '<p>Enquiry consent and update consent are separate. Update consent starts unticked and is not required to send the form. Analytics consent is a separate banner. You can reject analytics and still use the site.</p>'
        . '<h2>Cookies and similar tools</h2>'
        . '<ul>'
        . '<li>Admin login uses a first-party session cookie. It is only set after a staff login.</li>'
        . '<li>A first-party cookie named coco_blog_seen remembers that this browser opened a blog article, for 24 hours, so the view count does not rise on every refresh. It does not store a name.</li>'
        . '<li>Google Analytics, Google Tag Manager, and Microsoft Clarity are stored only after you press Accept, or turn them on under Customize. The choice is kept in this browser as coco_consent.</li>'
        . '</ul>'
        . '<h2>Security</h2>'
        . '<p>Enquiry, consent, and data-rights files are encrypted on the server before they are written. If encryption is not available, the form is not saved. Staff passwords are stored as hashes. A picture code on each form has to be typed before the form is accepted. Mail sent to the school inbox is ordinary email.</p>'
        . '<h2>Transfers</h2>'
        . '<p>Google, Microsoft, Meta (WhatsApp), and the host may process technical data outside India. The school has not completed a transfer assessment. That is an open item for the lawyer.</p>'
        . '<h2>Rights and grievance</h2>'
        . '<p>Ask for access, correction, erasure, or withdrawal at <a href="/data-rights">Your data rights</a>. Grievance Officer: ' . e($g['officer']) . ', <a href="mailto:' . $email . '">' . $email . '</a>, <a href="' . $href . '">' . $phone . '</a>.</p>';
}

function dpdp_terms_body(): string
{
    $g = dpdp_grievance();
    return '<h1>Terms &amp; Conditions</h1>'
        . dpdp_updated_line()
        . '<p>These terms cover use of the Cocohouse website. They do not replace an admission contract.</p>'
        . '<h2>Using the site</h2>'
        . '<p>The pages describe preschool, daycare, and special education at ' . e($g['address']) . '. A seat, a fee, or a discount is confirmed by the school on the phone. A page, including a mid-term offer, is not a promise that a seat is held.</p>'
        . '<h2>Enquiries</h2>'
        . '<p>If you send a form, you confirm the details are yours to share, or that you are the parent or legal guardian of the child named. The school may call the number you give in order to answer the enquiry.</p>'
        . '<h2>Data protection</h2>'
        . '<p>Personal data from this website is handled as described in the <a href="/privacy-notice">privacy notice</a> and the <a href="/privacy-policy">privacy policy</a>. Cocohouse is the Data Fiduciary for that data. You may ask for access, correction, erasure, or withdrawal of consent through <a href="/data-rights">Your data rights</a>. Grievance Officer: ' . e($g['officer']) . ', <a href="mailto:' . e($g['email']) . '">' . e($g['email']) . '</a>, <a href="' . e($g['phone_href']) . '">' . e($g['phone']) . '</a>.</p>'
        . '<p>Consent for a reply, consent for later updates, and consent for analytics are separate. Refusing updates or analytics does not by itself cancel an enquiry you already asked the school to answer.</p>'
        . '<p>The school does not sell personal data. Staff, the host, email delivery, and any tool you allow (analytics, or WhatsApp if the browser opens it) are the recipients named in the privacy notice.</p>'
        . '<h2>Other sites</h2>'
        . '<p>Links to Google Maps, WhatsApp, Facebook, Instagram, and YouTube leave this website. Those sites have their own terms.</p>'
        . '<h2>Liability</h2>'
        . '<p>The school will try to keep the pages accurate. These terms do not set a damages cap.</p>'
        . '<h2>Contact</h2>'
        . '<p><a href="mailto:' . e($g['email']) . '">' . e($g['email']) . '</a> · <a href="' . e($g['phone_href']) . '">' . e($g['phone']) . '</a></p>';
}

function dpdp_rights_body(): string
{
    $g = dpdp_grievance();
    $email = e($g['email']);
    $phone = e($g['phone']);
    $href = e($g['phone_href']);
    return '<div class="rights-layout">'
        . '<div class="rights-copy">'
        . '<h1>Your data rights</h1>'
        . dpdp_updated_line()
        . '<p>Ask Cocohouse to see, correct, or erase personal data from this website, or to withdraw consent.</p>'
        . '<ul class="rights-points">'
        . '<li><strong>Access</strong><span>Ask what we hold for you.</span></li>'
        . '<li><strong>Correct</strong><span>Ask us to fix a name, number, or other detail.</span></li>'
        . '<li><strong>Erase</strong><span>Ask us to delete what we no longer need.</span></li>'
        . '<li><strong>Withdraw</strong><span>Take back consent for updates or for the enquiry.</span></li>'
        . '</ul>'
        . '<p class="rights-contact">Grievance Officer: ' . e($g['officer']) . '<br><a href="mailto:' . $email . '">' . $email . '</a><br><a href="' . $href . '">' . $phone . '</a></p>'
        . '</div>'
        . '<form id="rightsForm" class="rights-card">'
        . '<h2>Send a request</h2>'
        . '<p class="rights-card-note">We use these details only to handle this request.</p>'
        . '<input type="text" name="company" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0;">'
        . '<div class="form-group"><label class="form-label">Your name *</label><input class="form-input" name="name" required autocapitalize="words"></div>'
        . '<div class="form-row"><div class="form-group"><label class="form-label">Mobile *</label><input class="form-input" type="tel" name="phone" data-validate="phone" required></div>'
        . '<div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email"></div></div>'
        . '<div class="form-group"><label class="form-label">Request *</label><select class="form-select" name="request" required>'
        . '<option value="">Choose one</option><option value="access">Access my data</option><option value="correct">Correct my data</option><option value="erase">Erase my data</option><option value="withdraw">Withdraw consent</option>'
        . '</select></div>'
        . '<div class="form-group"><label class="form-label">What should we look up? *</label><textarea class="form-textarea" name="detail" rows="4" required placeholder="The mobile number used on the enquiry, and what to change or delete."></textarea></div>'
        . '<label class="consent-line"><input type="checkbox" name="consent_rights" value="1" required><span>Required. Use my name and contact details only to handle this request. <a href="/privacy-notice">Privacy notice</a>.</span></label>'
        . '<button class="btn-submit" type="submit">Send request</button>'
        . '</form></div>';
}
