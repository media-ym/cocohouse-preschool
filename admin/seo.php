<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/seo.php';
require_section('seo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = cms_data();
    $posted = is_array($_POST['seo'] ?? null) ? $_POST['seo'] : [];
    $previousSeo = is_array($data['site']['seo'] ?? null) ? $data['site']['seo'] : [];
    $data['site']['seo'] = sanitize_site_seo($posted);
    foreach (array_keys(seo_clean_tracking([])) as $key) {
        if (isset($previousSeo[$key]) && is_string($previousSeo[$key])) {
            $data['site']['seo'][$key] = $previousSeo[$key];
        }
    }
    $bannerPosted = is_array($_POST['banner'] ?? null) ? $_POST['banner'] : [];
    $data['site']['banner'] = sanitize_banner($bannerPosted);
    $pagePosted = is_array($_POST['pages'] ?? null) ? $_POST['pages'] : [];
    $warning = '';
    foreach (page_catalog() as $pageId => $info) {
        if (!is_array($pagePosted[$pageId] ?? null)) {
            continue;
        }
        $clean = sanitize_page_seo($pagePosted[$pageId]);
        if (!is_array($data['pages'][$pageId] ?? null)) {
            $data['pages'][$pageId] = [];
        }
        $data['pages'][$pageId]['seo'] = $clean['data'];
        if ($warning === '' && $clean['warning'] !== '') {
            $warning = $info['label'] . ': ' . $clean['warning'];
        }
    }
    cms_save($data);
    flash($warning !== '' ? $warning : 'SEO settings saved.', $warning !== '' ? 'error' : 'ok');
    header('Location: /admin/seo.php');
    exit;
}

$seo = seo_settings();
$banner = banner_settings();
$host = parse_url($seo['public_url'], PHP_URL_HOST) ?: 'cocohouse.in';
admin_header('SEO', 'seo');
?>
<h1>SEO</h1>
<p class="lead">Each card is one job. The number on a page is its SEO score: 80 and above is strong, 55 to 79 needs a tighter title or description.</p>

<form method="post">
  <?= csrf_field() ?>
  <div class="seo-setup">
  <section class="editor-block">
    <h2>Top banner</h2>
    <p class="help">The yellow line above the menu.</p>
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
    <div class="split">
      <div class="field">
        <label for="site-name">Site name</label>
        <input id="site-name" name="seo[site_name]" maxlength="80" value="<?= e($seo['site_name']) ?>">
      </div>
      <div class="field">
        <label for="public-url">Public website URL</label>
        <input id="public-url" name="seo[public_url]" value="<?= e($seo['public_url']) ?>" placeholder="https://cocohouse.in">
      </div>
    </div>
    <div class="field">
      <label for="default-description">Default description</label>
      <textarea id="default-description" name="seo[description]" maxlength="320"><?= e($seo['description']) ?></textarea>
    </div>
    <div class="field">
      <label for="areas">Areas you serve</label>
      <input id="areas" name="seo[areas]" maxlength="300" value="<?= e($seo['areas']) ?>">
    </div>
    <div class="field">
      <label for="default-image">Default share image</label>
      <input id="default-image" name="seo[image]" value="<?= e($seo['image']) ?>">
    </div>
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
    <div class="split">
      <div class="field">
        <label for="twitter">X / Twitter handle</label>
        <input id="twitter" name="seo[twitter]" value="<?= e($seo['twitter']) ?>" placeholder="cocohouse">
      </div>
      <div class="field">
        <label for="youtube">YouTube</label>
        <input id="youtube" name="seo[youtube]" value="<?= e($seo['youtube']) ?>" placeholder="https://youtube.com/...">
      </div>
    </div>
    <div class="field">
      <label for="linktree">Linktree</label>
      <input id="linktree" name="seo[linktree]" value="<?= e($seo['linktree']) ?>" placeholder="https://linktr.ee/...">
    </div>
  </section>
  </div>

  <button class="btn btn-primary" type="submit">Save SEO</button>

  <h2 style="margin:28px 0 8px;">Page SEO</h2>
  <p class="lead">Two pages per row. Open Advanced SEO only when you need the social image, canonical link or sitemap switch.</p>
  <?php seo_admin_pages_markup($host); ?>
  <?php
    $posts = load_blog()['items'];
    usort($posts, function ($a, $b) {
        return strcmp($b['published_at'] ?? '', $a['published_at'] ?? '');
    });
  ?>
  <section class="editor-block">
    <h2>Blog posts</h2>
    <p class="help">Each post uses its summary as the Google description. Set a custom search title and keywords from the post editor.</p>
    <?php if (!$posts): ?>
      <p class="empty">No posts yet.</p>
    <?php else: ?>
      <div class="seo-posts">
      <?php foreach ($posts as $post): ?>
        <?php
          $postTitle = trim((string) ($post['seo_title'] ?? '')) !== '' ? (string) $post['seo_title'] : (string) ($post['title'] ?? '');
          $postScore = seo_score($postTitle, (string) ($post['excerpt'] ?? ''), (string) ($post['seo_keywords'] ?? ''), ($post['status'] ?? '') === 'published');
        ?>
        <a class="seo-post" href="/admin/blog-edit.php?id=<?= e($post['id'] ?? '') ?>">
          <div class="seo-score <?= e(seo_score_class($postScore)) ?>"><?= $postScore ?></div>
          <div>
            <strong><?= e($post['title'] ?? 'Untitled') ?></strong>
            <p class="help"><?= e(($post['seo_title'] ?? '') !== '' ? $post['seo_title'] : 'Search title follows the article title') ?></p>
          </div>
        </a>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <button class="btn btn-primary" type="submit">Save SEO</button>
</form>
<?php seo_admin_pages_script(); ?>
<?php admin_footer();