<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/seo.php';
require_section('tracking');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = cms_data();
    $posted = is_array($_POST['seo'] ?? null) ? $_POST['seo'] : [];
    if (!is_array($data['site']['seo'] ?? null)) {
        $data['site']['seo'] = [];
    }
    foreach (seo_clean_tracking($posted) as $key => $value) {
        $data['site']['seo'][$key] = $value;
    }
    cms_save($data);
    flash('Tracking settings saved.');
    header('Location: /admin/tracking.php');
    exit;
}

$seo = seo_settings();
$host = parse_url($seo['public_url'], PHP_URL_HOST) ?: 'cocohouse.in';
admin_header('Tracking', 'tracking');
?>
<h1>Tracking</h1>
<p class="lead">Search Console, analytics and the Meta Pixel. These scripts load only after a visitor accepts the cookie banner.</p>

<form method="post">
  <?= csrf_field() ?>
  <div class="seo-setup">
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
    <p class="help">Paste the verification code only, not the full meta tag.<br>Sitemap: <a href="/sitemap.xml" target="_blank" rel="noopener"><?= e($host) ?>/sitemap.xml</a> · Robots: <a href="/robots.txt" target="_blank" rel="noopener"><?= e($host) ?>/robots.txt</a></p>
  </section>

  <section class="editor-block">
    <h2>Analytics</h2>
    <div class="field">
      <label for="analytics-id">Google Analytics ID</label>
      <input id="analytics-id" name="seo[analytics_id]" value="<?= e($seo['analytics_id']) ?>" placeholder="G-XXXXXXXX">
    </div>
    <div class="field">
      <label for="gtm-id">Google Tag Manager ID</label>
      <input id="gtm-id" name="seo[gtm_id]" value="<?= e($seo['gtm_id']) ?>" placeholder="GTM-XXXXXXX">
    </div>
    <div class="field">
      <label for="clarity-id">Microsoft Clarity ID</label>
      <input id="clarity-id" name="seo[clarity_id]" value="<?= e($seo['clarity_id']) ?>" placeholder="yy6paa12av" autocapitalize="off" spellcheck="false">
    </div>
    <p class="help">Analytics starts with G-. Tag Manager starts with GTM-. Clarity is the project ID from the Clarity snippet.</p>
  </section>

  <section class="editor-block">
    <h2>Meta Pixel</h2>
    <div class="field">
      <label for="meta-pixel">Meta Pixel ID</label>
      <input id="meta-pixel" name="seo[meta_pixel_id]" value="<?= e($seo['meta_pixel_id']) ?>" placeholder="1855187692188288" inputmode="numeric" autocapitalize="off" spellcheck="false">
    </div>
    <p class="help">The number from Events Manager. The site adds the pixel script. Leave this blank to turn the pixel off.<br>After a visitor accepts cookies: PageView on each page, Lead when an enquiry is sent, Contact on a call, email or WhatsApp, FindLocation on the map, and ViewContent on an admission link. Name, phone and email are not sent.</p>
  </section>
  </div>

  <button class="btn btn-primary" type="submit">Save tracking</button>
</form>
<?php admin_footer();