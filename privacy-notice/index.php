<?php
require_once dirname(__DIR__) . '/includes/privacy.php';
render_legal_page(
    'Privacy notice | Cocohouse Preschool',
    'What personal data Cocohouse collects on this website, why, how long it is kept, and how to use your rights.',
    '/privacy-notice',
    dpdp_notice_body()
);
