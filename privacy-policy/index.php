<?php
require_once dirname(__DIR__) . '/includes/privacy.php';
render_legal_page(
    'Privacy policy | Cocohouse Preschool',
    'How Cocohouse Preschool and Daycare handles personal data, cookies, children\'s details, and grievances.',
    '/privacy-policy',
    dpdp_policy_body()
);
