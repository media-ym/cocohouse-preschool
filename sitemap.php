<?php

require_once __DIR__ . '/includes/seo.php';

header('Content-Type: application/xml; charset=UTF-8');
$stamp = date('Y-m-d', filemtime(COCO_STORAGE . '/pages.json') ?: time());
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (seo_public_paths() as $url) {
    echo '  <url><loc>' . e($url) . '</loc><lastmod>' . e($stamp) . '</lastmod></url>' . "\n";
}
echo '</urlset>' . "\n";
