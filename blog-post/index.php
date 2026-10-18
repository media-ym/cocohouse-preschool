<?php

require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/seo.php';

$slug = (string) ($_GET['slug'] ?? '');
$post = $slug !== '' ? find_published_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
    readfile(COCO_ROOT . '/404.html');
    exit;
}

$ts = strtotime($post['published_at'] ?? '') ?: time();
$dateLabel = date('M j, Y', $ts);
$color = $post['category_color'] ?? '#184a96';
$title = $post['title'] ?? 'Blog';
$author = blog_author_name($post);
$authorSlug = blog_author_slug($author);
$authorUrl = '/author/' . rawurlencode($authorSlug);
$views = record_blog_view((string) ($post['slug'] ?? ''));
?>
<?php ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
    $seoTitle = trim((string) ($post['seo_title'] ?? ''));
    if ($seoTitle === '') {
        $seoTitle = $title . ' | Cocohouse, Thane East';
    }
    $seoKeywords = trim((string) ($post['seo_keywords'] ?? ''));
    if ($seoKeywords === '') {
        $seoKeywords = 'best daycare in thane east, best daycare in mulund, best preschool in thane east, daycare Hari Om Nagar';
    }
  ?>
  <title><?= e($seoTitle) ?></title>
  <?php
    $seoSettings = seo_settings();
    $canonical = rtrim($seoSettings['public_url'], '/') . '/blog-post/' . rawurlencode($slug);
    $image = trim((string) ($post['featured_img'] ?? '')) !== '' ? (string) $post['featured_img'] : $seoSettings['image'];
    echo seo_meta_html([
        'title' => $seoTitle,
        'description' => (string) ($post['excerpt'] ?? ''),
        'keywords' => $seoKeywords,
        'canonical' => $canonical,
        'robots' => 'index,follow',
        'image' => seo_absolute($image, $seoSettings['public_url']),
        'og_type' => 'article',
        'published' => date('c', $ts),
        'schema' => [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $title,
            'description' => (string) ($post['excerpt'] ?? ''),
            'datePublished' => date('c', $ts),
            'image' => seo_absolute($image, $seoSettings['public_url']),
            'mainEntityOfPage' => $canonical,
            'author' => [
                '@type' => 'Person',
                'name' => $author,
                'url' => rtrim($seoSettings['public_url'], '/') . $authorUrl,
            ],
            'publisher' => ['@id' => rtrim($seoSettings['public_url'], '/') . '/#school'],
        ],
    ], $seoSettings);
  ?>
  <link rel="icon" href="/assets/images/logo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=20261016">
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
    <?php if (!empty($post['category_name'])): ?>
      <span class="blog-category-badge" style="background:<?= e($color) ?>20;color:<?= e($color) ?>;"><?= e($post['category_name']) ?></span>
    <?php endif; ?>
    <h1 class="blog-post-title"><?= e($title) ?></h1>
    <div class="blog-meta">
      <span>By <a href="<?= e($authorUrl) ?>"><?= e($author) ?></a></span>
      <span><?= e($dateLabel) ?></span>
      <span><?= number_format($views) ?> views</span>
    </div>
  </div>
</section>

<?php if (!empty($post['featured_img'])): ?>
<section class="blog-featured-image-wrap">
  <div class="container">
    <div class="blog-featured-image">
      <img src="<?= e($post['featured_img']) ?>" alt="<?= e($title) ?>">
    </div>
  </div>
</section>
<?php endif; ?>

<article class="blog-article">
  <div class="container blog-content-wrap">
    <p class="blog-excerpt"><?= e($post['excerpt'] ?? '') ?></p>
    <div class="blog-content"><?= $post['content'] ?? '' ?></div>
    <div class="blog-cta-card">
      <?php if (($post['category_name'] ?? '') === 'Daycare'): ?>
        <h3>Book daycare that stays open till 8 PM</h3>
        <p>Cocohouse daycare in Thane East runs 8 AM to 8 PM, all 7 days, for busy working parents. Tell us your office hours and we will suggest a package.</p>
        <a href="/daycare" class="btn btn-yellow">View daycare</a>
        <a href="/contact" class="btn btn-ghost" style="margin-left:8px;background:#fff;color:#184a96;">Contact now</a>
      <?php else: ?>
        <h3>Visit Cocohouse Preschool</h3>
        <p>See our thematic learning in action. Book a preschool & daycare tour today!</p>
        <a href="/contact" class="btn btn-yellow">Book a Visit →</a>
      <?php endif; ?>
    </div>
    <p class="blog-excerpt">Cocohouse is in Hari Om Nagar, Thane East. Parents nearby also look for the best daycare in Thane East and the best preschool in Mulund.</p>
  </div>
</article>

<?php
$related = [];
foreach (load_blog()['items'] as $item) {
    if (($item['status'] ?? '') !== 'published' || ($item['slug'] ?? '') === $slug) {
        continue;
    }
    $related[] = $item;
}
$related = array_slice($related, 0, 3);
?>
<?php if ($related): ?>
<section class="blog-related-section">
  <div class="container">
    <h2 class="blog-related-title">Read more related blogs</h2>
    <div class="blog-related-grid">
      <?php foreach ($related as $item): ?>
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
<?php endif; ?>

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
<script src="/assets/js/main.js?v=20261018"></script>
</body>
</html>
<?php
echo public_finish(ob_get_clean());
