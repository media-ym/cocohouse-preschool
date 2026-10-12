<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/seo.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = cms_data();
    $posted = is_array($_POST['seo'] ?? null) ? $_POST['seo'] : [];
    $data['site']['seo'] = sanitize_site_seo($posted);
    $bannerPosted = is_array($_POST['banner'] ?? null) ? $_POST['banner'] : [];
    $data['site']['banner'] = sanitize_banner($bannerPosted);
    cms_save($data);
    flash('SEO settings saved.');
    header('Location: /admin/seo.php');
    exit;
}

$seo = seo_settings();
$banner = banner_settings();
$host = parse_url($seo['public_url'], PHP_URL_HOST) ?: 'cocohouse.in';
admin_header('SEO', 'seo');
?>
<h1>SEO</h1>
<p class="lead">Site-wide search setup. Each page also has its own title, description and advanced fields under Pages.</p>

<form method="post">
  <?= csrf_field() ?>
  <section class="editor-block">
    <h2>Top banner</h2>
    <p class="help">Scrolling line above the menu on every page.</p>
    <input type="hidden" name="banner[enabled]" value="0">
    <label class="check"><input type="checkbox" name="banner[enabled]" value="1" <?= $banner['enabled'] === '1' ? 'checked' : '' ?>> Show banner</label>
    <div class="field" style="margin-top:14px;">
      <label for="banner-text">Banner text</label>
      <input id="banner-text" name="banner[text]" maxlength="180" value="<?= e($banner['text']) ?>">
    </div>
    <div class="field">
      <label for="banner-link">Banner link</label>
      <input id="banner-link" name="banner[link]" value="<?= e($banner['link']) ?>" placeholder="/contact">
    </div>
  </section>

  <section class="editor-block">
    <h2>Website</h2>
    <div class="field">
      <label for="site-name">Site name</label>
      <input id="site-name" name="seo[site_name]" maxlength="80" value="<?= e($seo['site_name']) ?>">
    </div>
    <div class="field">
      <label for="public-url">Public website URL</label>
      <input id="public-url" name="seo[public_url]" value="<?= e($seo['public_url']) ?>" placeholder="https://cocohouse.in">
      <p class="help">Used for the canonical link, sitemap and social share address. Keep the live https address, even while you preview on localhost.</p>
    </div>
    <div class="field">
      <label for="default-description">Default description</label>
      <textarea id="default-description" name="seo[description]" maxlength="320"><?= e($seo['description']) ?></textarea>
      <p class="help">Used only when a page has no description of its own.</p>
    </div>
    <div class="field">
      <label for="default-image">Default share image</label>
      <input id="default-image" name="seo[image]" value="<?= e($seo['image']) ?>">
      <p class="help">Shown when a page is shared and that page has no social image.</p>
    </div>
  </section>

  <section class="editor-block">
    <h2>Search engines</h2>
    <div class="field">
      <label for="google-verification">Google Search Console code</label>
      <input id="google-verification" name="seo[google_verification]" value="<?= e($seo['google_verification']) ?>" placeholder="verification code only">
    </div>
    <div class="field">
      <label for="bing-verification">Bing Webmaster code</label>
      <input id="bing-verification" name="seo[bing_verification]" value="<?= e($seo['bing_verification']) ?>">
    </div>
    <div class="field">
      <label for="analytics-id">Google Analytics or Tag Manager ID</label>
      <input id="analytics-id" name="seo[analytics_id]" value="<?= e($seo['analytics_id']) ?>" placeholder="G-XXXXXXXX or GTM-XXXXXXX">
    </div>
    <p class="help">Sitemap: <a href="/sitemap.xml" target="_blank" rel="noopener"><?= e($host) ?>/sitemap.xml</a><br>Robots: <a href="/robots.txt" target="_blank" rel="noopener"><?= e($host) ?>/robots.txt</a></p>
  </section>

  <section class="editor-block">
    <h2>Social profiles</h2>
    <div class="field">
      <label for="facebook">Facebook page</label>
      <input id="facebook" name="seo[facebook]" value="<?= e($seo['facebook']) ?>" placeholder="https://facebook.com/...">
    </div>
    <div class="field">
      <label for="instagram">Instagram</label>
      <input id="instagram" name="seo[instagram]" value="<?= e($seo['instagram']) ?>" placeholder="https://instagram.com/...">
    </div>
    <div class="field">
      <label for="twitter">X / Twitter handle</label>
      <input id="twitter" name="seo[twitter]" value="<?= e($seo['twitter']) ?>" placeholder="cocohouse">
    </div>
    <div class="field">
      <label for="youtube">YouTube</label>
      <input id="youtube" name="seo[youtube]" value="<?= e($seo['youtube']) ?>" placeholder="https://youtube.com/...">
    </div>
    <div class="field">
      <label for="linktree">Linktree</label>
      <input id="linktree" name="seo[linktree]" value="<?= e($seo['linktree']) ?>" placeholder="https://linktr.ee/...">
    </div>
  </section>

  <button class="btn btn-primary" type="submit">Save SEO</button>
</form>
<?php admin_footer();