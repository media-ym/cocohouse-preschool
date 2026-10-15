<?php
require_once dirname(__DIR__) . '/includes/privacy.php';
render_legal_page(
    'Terms & Conditions | Cocohouse Preschool',
    'Terms for using the Cocohouse website, including how personal data from enquiries is handled.',
    '/terms-and-conditions',
    dpdp_terms_body()
);
