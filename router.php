<?php

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

if ($uri === '/robots.txt') {
    require __DIR__ . '/robots.php';
    return true;
}

if (str_starts_with($uri, '/storage') || str_starts_with($uri, '/includes')) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

require_once __DIR__ . '/includes/site.php';
if (dispatch_custom_slug($uri)) {
    return true;
}

if (preg_match('#^/author/([^/]+)/?$#', $uri, $matches) && $matches[1] !== 'index.php') {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/author/index.php';
    return true;
}

if (preg_match('#^/blog-post/([^/]+)/?$#', $uri, $matches) && $matches[1] !== 'index.php') {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/blog-post/index.php';
    return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

if (is_dir($file)) {
    $indexPhp = rtrim($file, '/') . '/index.php';
    $indexHtml = rtrim($file, '/') . '/index.html';
    if (is_file($indexPhp)) {
        require $indexPhp;
        return true;
    }
    if (is_file($indexHtml)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($indexHtml);
        return true;
    }
}

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/404.html');
return true;
