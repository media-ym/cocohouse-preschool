<?php

require_once __DIR__ . '/store.php';

function page_default_slugs(): array
{
    return [
        'home' => '',
        'about' => 'about',
        'programs' => 'programs',
        'daycare' => 'daycare',
        'gallery' => 'gallery',
        'blog' => 'blog',
        'contact' => 'contact',
        'admissions' => 'admissions',
        'special-education' => 'special-education',
    ];
}

function clean_page_slug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function page_slugs(): array
{
    $defaults = page_default_slugs();
    $data = read_json(COCO_STORAGE . '/pages.json', []);
    $pages = is_array($data['pages'] ?? null) ? $data['pages'] : [];
    $out = [];
    $used = [];
    foreach ($defaults as $id => $default) {
        if ($id === 'home') {
            $out[$id] = '';
            continue;
        }
        $raw = clean_page_slug((string) ($pages[$id]['slug'] ?? $default));
        if ($raw === '' || slug_problem($id, $raw, $used) !== '') {
            $raw = $default;
        }
        $used[$raw] = $id;
        $out[$id] = $raw;
    }
    return $out;
}

function slug_problem(string $pageId, string $slug, ?array $used = null): string
{
    if ($pageId === 'home') {
        return '';
    }
    if (!preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*$#', $slug)) {
        return 'Use letters, numbers and hyphens for the page address.';
    }
    $reserved = ['admin', 'api', 'assets', 'storage', 'includes', 'blog-post', 'author', 'data', 'sitemap', 'robots', 'index', 'uploads', 'post'];
    if (in_array($slug, $reserved, true)) {
        return 'That address is reserved.';
    }
    if ($used === null) {
        $used = [];
        foreach (page_slugs() as $id => $current) {
            if ($id !== $pageId && $current !== '') {
                $used[$current] = $id;
            }
        }
    }
    if (isset($used[$slug])) {
        return 'That address is already used by another page.';
    }
    foreach (page_default_slugs() as $id => $default) {
        if ($id !== $pageId && $default !== '' && $default === $slug) {
            return 'That address belongs to another page folder. Pick a different one.';
        }
    }
    return '';
}

function page_url(string $id): string
{
    $slug = page_slugs()[$id] ?? '';
    if ($id === 'home' || $slug === '') {
        return '/';
    }
    return '/' . $slug;
}

function enforce_page_slug(string $pageId): void
{
    if ($pageId === 'home') {
        return;
    }
    $slug = page_slugs()[$pageId] ?? '';
    $folder = page_default_slugs()[$pageId] ?? '';
    $request = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
    if ($slug !== '' && $slug !== $folder && ($request === $folder || $request === $folder . '/index.php')) {
        header('Location: /' . $slug, true, 301);
        exit;
    }
}

function dispatch_custom_slug(string $uri): bool
{
    $path = trim($uri, '/');
    if ($path === '' || str_contains($path, '/')) {
        return false;
    }
    foreach (page_slugs() as $id => $slug) {
        if ($id === 'home' || $slug === '' || $slug === ($id === 'home' ? '' : page_default_slugs()[$id])) {
            continue;
        }
        if ($path === $slug) {
            require COCO_ROOT . '/' . $id . '/index.php';
            return true;
        }
    }
    return false;
}

function rewrite_page_links(string $html): string
{
    foreach (page_slugs() as $id => $slug) {
        $folder = page_default_slugs()[$id] ?? '';
        if ($id === 'home' || $folder === '' || $slug === $folder) {
            continue;
        }
        $html = preg_replace('#(?<=["\'])/' . preg_quote($folder, '#') . '(?=["\'\#\?])#', '/' . $slug, $html) ?? $html;
    }
    return $html;
}

function banner_settings(): array
{
    $data = read_json(COCO_STORAGE . '/pages.json', []);
    $site = is_array($data['site'] ?? null) ? $data['site'] : [];
    $stored = is_array($site['banner'] ?? null) ? $site['banner'] : [];
    $enabled = array_key_exists('enabled', $stored) ? (string) $stored['enabled'] : '1';
    $text = trim((string) ($stored['text'] ?? 'Mid Term Admissions Open   ·   Flat 60% Off   ·   Contact Now'));
    $link = trim((string) ($stored['link'] ?? '/contact'));
    if ($link === '' || !preg_match('#^(https://|/)#', $link)) {
        $link = '/contact';
    }
    return [
        'enabled' => $enabled === '1' ? '1' : '0',
        'text' => $text,
        'link' => $link,
    ];
}

function sanitize_banner(array $posted): array
{
    $link = trim((string) ($posted['link'] ?? ''));
    if ($link === '' || !preg_match('#^(https://[^\s]+|/[^\s]*)$#', $link)) {
        $link = '/contact';
    }
    $text = trim(preg_replace('/\s+/', ' ', (string) ($posted['text'] ?? '')) ?? '');
    if (function_exists('mb_substr')) {
        $text = mb_substr($text, 0, 180);
    } else {
        $text = substr($text, 0, 180);
    }
    return [
        'enabled' => ((string) ($posted['enabled'] ?? '0')) === '1' ? '1' : '0',
        'text' => $text,
        'link' => $link,
    ];
}

function inject_banner(string $html): string
{
    $banner = banner_settings();
    if ($banner['enabled'] !== '1' || $banner['text'] === '' || !str_contains($html, '<header class="navbar"')) {
        return $html;
    }
    $piece = e($banner['text']);
    $chunks = str_repeat('<span>' . $piece . '</span>', 8);
    $block = '<style>'
        . '.top-marquee{position:fixed;top:0;left:0;right:0;z-index:1100;height:38px;background:#0e2a52;color:#fff;overflow:hidden;display:flex;align-items:center;}'
        . '.top-marquee a{color:#fff;text-decoration:none;display:block;width:100%;}'
        . '.marquee-track{display:flex;width:max-content;animation:coco-marquee 22s linear infinite;}'
        . '.top-marquee:hover .marquee-track{animation-play-state:paused;}'
        . '.marquee-track span{display:inline-flex;align-items:center;height:38px;padding:0 28px;font-weight:600;font-size:.92rem;letter-spacing:.02em;white-space:nowrap;}'
        . '.marquee-track span::before{content:"";display:inline-block;width:8px;height:8px;margin-right:14px;border-radius:50%;background:#f4d000;transform:translateY(-1px);}'
        . '@keyframes coco-marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}'
        . 'body{padding-top:38px !important;}'
        . '.navbar{top:38px !important;}'
        . '.mobile-drawer{top:38px;height:calc(100% - 38px);}'
        . '</style>'
        . '<div class="top-marquee"><a href="' . e($banner['link']) . '"><div class="marquee-track">' . $chunks . '</div></a></div>';
    return preg_replace('#<header class="navbar"#', $block . '<header class="navbar"', $html, 1) ?? $html;
}

function media_catalog(): array
{
    return [
        'logo' => ['label' => 'Logo', 'src' => '/assets/images/logo.webp', 'where' => 'Header and footer on every page'],
        'story' => ['label' => 'Joyful learning', 'src' => '/assets/images/joyful-learning.webp', 'where' => 'Home and About'],
        'campus' => ['label' => 'Preschool & Daycare', 'src' => '/assets/images/campus.jpg', 'where' => 'Home, Daycare, Gallery and Special education'],
        'se-session' => ['label' => 'One-to-one session', 'src' => '/assets/images/se-session.jpg', 'where' => 'Special education'],
        'se-talk' => ['label' => 'Picture-based learning', 'src' => '/assets/images/se-talk.jpg', 'where' => 'Special education'],
        'se-play' => ['label' => 'Hands-on play', 'src' => '/assets/images/se-play.jpg', 'where' => 'Special education'],
        'se-down' => ['label' => 'Down syndrome session', 'src' => '/assets/images/se-down.jpg', 'where' => 'Special education'],
        'principal' => ['label' => 'Principal', 'src' => '/assets/images/principal.webp', 'where' => 'Home and Gallery'],
        'yt-nitisha' => ['label' => 'Nitisha & Vijay', 'src' => '/api/yt-thumb.php?id=tMTFDT_VD_o', 'where' => 'Home testimonial'],
        'yt-anirudh' => ['label' => 'Anirudh & Shilpa', 'src' => '/api/yt-thumb.php?id=WQ4MrEJBD8E', 'where' => 'Home testimonial'],
        'yt-darshana' => ['label' => 'Darshana & Rahul', 'src' => '/api/yt-thumb.php?id=ptSo4Vg7zGY', 'where' => 'Home testimonial'],
        'yt-nikita' => ['label' => 'Nikita & Amit', 'src' => '/api/yt-thumb.php?id=w5twGdXxz3s', 'where' => 'Home testimonial'],
    ];
}

function media_map(): array
{
    $data = read_json(COCO_STORAGE . '/pages.json', []);
    $site = is_array($data['site'] ?? null) ? $data['site'] : [];
    $map = is_array($site['media'] ?? null) ? $site['media'] : [];
    $clean = [];
    foreach (media_catalog() as $key => $item) {
        $value = trim((string) ($map[$key] ?? ''));
        if ($value !== '' && str_starts_with($value, '/assets/images/')) {
            $clean[$key] = $value;
        }
    }
    return $clean;
}

function media_src(string $key): string
{
    $catalog = media_catalog();
    $original = (string) ($catalog[$key]['src'] ?? '');
    $swap = media_map()[$key] ?? '';
    return $swap !== '' ? $swap : $original;
}

function apply_media_swaps(string $html): string
{
    foreach (media_catalog() as $key => $item) {
        $swap = media_map()[$key] ?? '';
        if ($swap !== '' && $swap !== $item['src']) {
            $html = str_replace($item['src'], $swap, $html);
        }
    }
    return $html;
}

function image_file_info(string $url): array
{
    $info = ['bytes' => 0, 'width' => 0, 'height' => 0, 'local' => false];
    if (!str_starts_with($url, '/assets/')) {
        return $info;
    }
    $path = COCO_ROOT . $url;
    if (!is_file($path)) {
        return $info;
    }
    $dim = @getimagesize($path);
    $info['local'] = true;
    $info['bytes'] = (int) filesize($path);
    $info['width'] = (int) ($dim[0] ?? 0);
    $info['height'] = (int) ($dim[1] ?? 0);
    return $info;
}

function format_file_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return (string) (int) round($bytes / 1024) . ' KB';
    }
    if ($bytes > 0) {
        return $bytes . ' B';
    }
    return 'External';
}

function public_finish(string $html): string
{
    require_once __DIR__ . '/privacy.php';
    $html = inject_banner($html);
    $html = rewrite_page_links($html);
    $html = inject_entry_popup($html);
    $html = inject_privacy_chrome($html);
    return apply_media_swaps($html);
}

function inject_entry_popup(string $html): string
{
    $path = rtrim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
    if (in_array($path, ['/privacy', '/privacy-policy', '/privacy-notice', '/terms', '/terms-and-conditions', '/data-rights'], true)) {
        return $html;
    }
    if (!str_contains($html, '</body>') || str_contains($html, 'id="cocoPopup"')) {
        return $html;
    }
    $popup = <<<'HTML'
<div class="coco-popup" id="cocoPopup" aria-hidden="true">
  <div class="coco-popup-card" role="dialog" aria-modal="true" aria-labelledby="cocoPopupTitle">
    <button type="button" class="coco-popup-close" id="cocoPopupClose" aria-label="Close">×</button>
    <p class="coco-popup-kicker">Admissions Open 2026-27</p>
    <h2 id="cocoPopupTitle">Flat 60% Off on Mid-Term Seats</h2>
    <p class="coco-popup-note">Share your number. Cocohouse will call you within 24 hours.</p>
    <form id="cocoPopupForm">
      <input type="text" name="company" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0;">
      <div class="form-group"><input class="form-input" type="text" name="parentName" placeholder="Parent's Name *" required autocapitalize="words"></div>
      <div class="form-group"><input class="form-input" type="tel" name="phone" placeholder="Mobile Number *" data-validate="phone" required></div>
      <div class="form-group"><input class="form-input" type="text" name="childName" placeholder="Child's Name *" required autocapitalize="words"></div>
      <div class="form-group">
        <select class="form-select" name="program" required>
          <option value="">Program *</option>
          <option>Playgroup</option>
          <option>Nursery</option>
          <option>Junior KG</option>
          <option>Senior KG</option>
          <option>Daycare</option>
          <option>Special Education</option>
        </select>
      </div>
      <div class="consent-box">
        <label class="consent-line"><input type="checkbox" name="consent_enquiry" value="1" required checked><span>Required. I am the parent or guardian. Use my details and my child's name and age to reply. <a href="/privacy-notice">Privacy notice</a>.</span></label>
        <label class="consent-line"><input type="checkbox" name="consent_updates" value="1"><span>Optional. Send admission or daycare updates by phone, email, or WhatsApp.</span></label>
      </div>
      <button class="btn-submit" type="submit">Get a Call Back</button>
    </form>
  </div>
</div>
<style>
.coco-popup{position:fixed;inset:0;z-index:1400;background:rgba(14,42,82,.62);display:none;align-items:center;justify-content:center;padding:16px;}
.coco-popup.is-open{display:flex;}
.coco-popup-card{position:relative;width:min(420px,100%);max-height:calc(100vh - 32px);overflow:auto;background:#fff;border-radius:24px;padding:28px 22px 22px;box-shadow:0 24px 60px rgba(14,42,82,.28);}
.coco-popup-kicker{display:inline-block;background:#f4d000;color:#184a96;font-weight:800;font-size:.78rem;padding:6px 12px;border-radius:999px;margin-bottom:12px;}
.coco-popup-card h2{color:#184a96;font-size:1.45rem;line-height:1.2;margin-bottom:8px;}
.coco-popup-note{color:#4b5563;font-size:.9rem;margin-bottom:16px;}
.coco-popup-close{position:absolute;top:10px;right:12px;width:36px;height:36px;border-radius:50%;font-size:1.5rem;line-height:1;color:#184a96;background:#fff8e1;}
</style>
<script>
(function () {
  var box = document.getElementById('cocoPopup');
  var storageKey = document.body.classList.contains('ads-page') ? 'coco_ads_popup_closed' : 'coco_popup_closed';
  if (box && typeof setupForm === 'function') {
    setupForm('cocoPopupForm', '/api/submit-enquiry.php', {
      type: 'popup',
      successTitle: 'Enquiry received',
      successDesc: 'Thank you. Cocohouse will call you within 24 hours.',
      onSuccess: function () {
        box.classList.remove('is-open');
        box.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        sessionStorage.setItem(storageKey, '1');
      }
    });
  }
  if (!box || sessionStorage.getItem(storageKey) === '1') return;
  function closePopup() {
    box.classList.remove('is-open');
    box.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    sessionStorage.setItem(storageKey, '1');
  }
  document.getElementById('cocoPopupClose').addEventListener('click', closePopup);
  box.addEventListener('click', function (event) {
    if (event.target === box) closePopup();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && box.classList.contains('is-open')) closePopup();
  });
  window.setTimeout(function () {
    if (sessionStorage.getItem(storageKey) === '1') return;
    box.classList.add('is-open');
    box.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (typeof cocoEvent === 'function') cocoEvent('popup_open', { form_id: 'popup' });
  }, 600);
})();
</script>
HTML;
    return preg_replace('#</body>#', $popup . '</body>', $html, 1) ?? $html;
}
