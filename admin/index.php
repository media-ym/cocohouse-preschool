<?php

require_once __DIR__ . '/../includes/auth.php';

send_security_headers();
start_admin_session();

header('Location: ' . (current_admin() ? '/admin/dashboard.php' : '/login/'));
exit;
