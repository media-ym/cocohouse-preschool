<?php
require_once dirname(__DIR__) . '/includes/privacy.php';
render_legal_page(
    'Your data rights | Cocohouse Preschool',
    'Ask Cocohouse to access, correct, or erase personal data, or to withdraw consent.',
    '/data-rights',
    dpdp_rights_body()
);
