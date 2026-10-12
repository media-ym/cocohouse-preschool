<?php

require_once __DIR__ . '/includes/site.php';

$path = strtolower(trim((string) ($_GET['path'] ?? ''), '/'));
if ($path !== '' && dispatch_custom_slug('/' . $path)) {
    return;
}

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/404.html');
