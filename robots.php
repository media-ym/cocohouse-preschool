<?php

require_once __DIR__ . '/includes/seo.php';

$settings = seo_settings();
header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /storage/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /api/\n";
echo "\nSitemap: " . rtrim($settings['public_url'], '/') . "/sitemap.xml\n";
