<?php

require_once __DIR__ . '/../includes/seo.php';

$slug = (string) ($_GET['slug'] ?? '');
$posts = [];
foreach (load_blog()['items'] as $post) {
    if (($post['status'] ?? '') !== 'published') {
        continue;
    }
    if (blog_author_slug(blog_author_name($post)) !== $slug) {
        continue;
    }
    $posts[] = $post;
}

if (!$posts) {
    http_response_code(404);
    readfile(COCO_ROOT . '/404.html');
    exit;
}

usort($posts, function ($a, $b) {
    return strcmp($b['published_at'] ?? '', $a['published_at'] ?? '');
});

$author = blog_author_name($posts[0]);
$seoSettings = seo_settings();
$canonical = rtrim($seoSettings['public_url'], '/') . '/author/' . rawurlencode($slug);
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($author) ?> | Cocohouse, Thane East</title>
  <?php
    echo seo_meta_html([
        'title' => $author . ' | Cocohouse, Thane East',
        'description' => $author . ' writes the Cocohouse blog for parents looking at preschool and daycare in Thane East, Kopri and Hari Om Nagar.',
        'keywords' => 'best preschool in mulund, best daycare in thane east, best daycare in mulund, daycare Hari Om Nagar',
        'canonical' => $canonical,
        'robots' => 'index,follow',
        'image' => seo_absolute($seoSettings['image'], $seoSettings['public_url']),
        'og_type' => 'profile',
        'schema' => [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'name' => $author,
            'url' => $canonical,
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $author,
                'url' => $canonical,
                'worksFor' => ['@id' => rtrim($seoSettings['public_url'], '/') . '/#school'],
            ],
        ],
    ], $seoSettings);
  ?>
  <link rel="icon" href="/assets/images/logo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=20261021b">
  <link rel="stylesheet" href="/assets/css/blog.css?v=20261016">
</head>
<body>
<header class="navbar" id="navbar">
  <div class="container">
    <a href="/"><img src="/assets/images/logo.webp" alt="Cocohouse Preschool & Daycare" class="navbar-logo"></a>
    <ul class="nav-menu">
      <li><a href="/">Home</a></li>
      <li><a href="/about">About Us</a></li>
      <li><a href="/programs">Program</a></li>
      <li><a href="/daycare">Daycare</a></li>
      <li><a href="/inclusive-special-education">Inclusive</a></li>
      <li><a href="/gallery">Gallery</a></li>
      <li><a href="/blog" style="color:#ff6b6b;">Blogs</a></li>
      <li><a href="/contact">Contact Us</a></li>
      <li><a href="/adminssion-open-2026" class="nav-cta">Admissions Open</a></li>
    </ul>
    <button class="nav-toggle" id="navToggle" aria-label="Open menu">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
</header>
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
  <div class="mobile-drawer-header">
    <img src="/assets/images/logo.webp" alt="Cocohouse" style="height:32px;">
    <button class="nav-toggle mobile-drawer-close" aria-label="Close menu">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <nav>
    <a href="/">Home</a>
    <a href="/about">About Us</a>
    <a href="/programs">Program</a>
    <a href="/daycare">Daycare</a>
    <a href="/inclusive-special-education">Inclusive</a>
    <a href="/gallery">Gallery</a>
    <a href="/blog">Blogs</a>
    <a href="/contact">Contact Us</a>
    <a href="/adminssion-open-2026" class="btn btn-yellow" style="margin:16px;">Admissions Open</a>
  </nav>
</div>

<section class="blog-post-hero">
  <div class="container blog-post-hero-inner">
    <a href="/blog" class="blog-back-link">← Back to Blogs</a>
    <h1 class="blog-post-title"><?= e($author) ?></h1>
    <div class="blog-meta"><span><?= count($posts) ?> articles</span></div>
  </div>
</section>

<section class="blog-related-section">
  <div class="container">
    <div class="author-intro">
      <p><?= e($author) ?> writes for Cocohouse Preschool &amp; Daycare in Hari Om Nagar, Thane East. These guides are for parents looking at the best daycare in Thane East and the best preschool in Mulund, including the 8 AM to 8 PM window.</p>
    </div>
    <h2 class="blog-related-title">Articles by <?= e($author) ?></h2>
    <div class="blog-related-grid">
      <?php foreach ($posts as $item): ?>
        <a class="blog-card" href="/blog-post/<?= e(rawurlencode((string) $item['slug'])) ?>">
          <div class="blog-card-img">
            <?php if (!empty($item['featured_img'])): ?>
              <img src="<?= e($item['featured_img']) ?>" alt="<?= e($item['title'] ?? '') ?>">
            <?php else: ?>
              <div class="blog-card-img-placeholder">📖</div>
            <?php endif; ?>
          </div>
          <div class="blog-card-body">
            <h3><?= e($item['title'] ?? '') ?></h3>
            <p><?= e($item['excerpt'] ?? '') ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <img src="/assets/images/school-emblem.png" alt="Cocohouse Preschool" class="footer-logo">
        <h3>Cocohouse Preschool & Daycare</h3>
        <p>Nurturing Little Minds through Thematic Learning, Coding Phonics, CPA Maths & Holistic Development.</p>
        <p>Preschool and daycare in Hari Om Nagar, Thane East, for families in Mulund, Thane, Thane East and Kopri.</p>
      </div>
      <div>
        <h4>Quick Links</h4>
        <ul>
          <li><a href="/">Home</a></li>
          <li><a href="/about">About School</a></li>
          <li><a href="/programs">Programs</a></li>
          <li><a href="/daycare">Daycare</a></li>
          <li><a href="/gallery">Gallery</a></li>
          <li><a href="/blog">Blogs</a></li>
          <li><a href="/contact">Contact</a></li>
          <li><a href="/adminssion-open-2026">Admissions Open</a></li>
        </ul>
      </div>
      <div>
        <h4>Our Programs</h4>
        <ul>
          <li><a href="/programs">Playgroup</a></li>
          <li><a href="/programs">Nursery</a></li>
          <li><a href="/programs">Junior KG</a></li>
          <li><a href="/programs">Senior KG</a></li>
          <li><a href="/daycare">Daycare</a></li>
          <li><a href="/inclusive-special-education">Inclusive Special Education</a></li>
        </ul>
      </div>
      <div>
        <h4>Contact Us</h4>
        <p>Row House No 3, Millenium Park, Hari Om Nagar, Thane East, Mumbai-400081</p>
        <p><a href="tel:+917039152173">+91-7039152173</a></p>
        <p><a href="mailto:info@cocohouse.in">info@cocohouse.in</a></p>
        <a class="footer-admin" href="/login">Admin Login</a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 Cocohouse Preschool & Daycare | All Rights Reserved</p>
      <p>Designed By <a href="https://yunickmedia.com" target="_blank" rel="noopener">Yunick Media</a></p>
    </div>
  </div>
</footer>
<a href="https://wa.me/917039152173" class="whatsapp-float" target="_blank" aria-label="WhatsApp">
  <svg viewBox="0 0 32 32" fill="currentColor"><path d="M16.003 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.26.6 4.46 1.73 6.4l-1.83 6.67 6.83-1.79a12.74 12.74 0 0 0 6.07 1.55c7.06 0 12.8-5.74 12.8-12.8s-5.74-12.83-12.8-12.83zm0 23.04c-1.93 0-3.82-.52-5.47-1.5l-.39-.23-4.05 1.06 1.08-3.95-.25-.4a10.55 10.55 0 0 1-1.62-5.62c0-5.86 4.77-10.63 10.63-10.63s10.63 4.77 10.63 10.63-4.77 10.64-10.63 10.64z"/></svg>
</a>
<script src="/assets/js/main.js?v=20261021b"></script>
</body>
</html>
<?php
echo public_finish(ob_get_clean());
