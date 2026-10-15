<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_section('media');

$catalog = media_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $key = (string) ($_POST['key'] ?? '');
    $action = (string) ($_POST['action'] ?? '');
    if (!isset($catalog[$key])) {
        flash('That image was not found.', 'error');
        header('Location: /admin/media.php');
        exit;
    }
    $data = cms_data();
    $map = is_array($data['site']['media'] ?? null) ? $data['site']['media'] : [];
    if ($action === 'reset') {
        unset($map[$key]);
        $data['site']['media'] = $map;
        cms_save($data);
        flash('Original image restored.');
    } else {
        $uploaded = save_uploaded_image($_FILES['image'] ?? [], 8 * 1024 * 1024);
        if ($uploaded === null || $uploaded === '') {
            flash('Upload a JPG, PNG, WEBP or GIF under 8 MB.', 'error');
        } else {
            $map[$key] = $uploaded;
            $data['site']['media'] = $map;
            cms_save($data);
            flash('Image updated across the website.');
        }
    }
    header('Location: /admin/media.php');
    exit;
}

admin_header('Images', 'media');
?>
<h1>Images</h1>
<p class="lead">Every picture on the website. Size is the file on this server. Upload a new file to replace it everywhere it is used. Blog post photos stay in Blog.</p>
<section class="media-grid">
  <?php foreach ($catalog as $key => $item): ?>
    <?php
      $current = media_src($key);
      $info = image_file_info($current);
      $replaced = $current !== $item['src'];
    ?>
    <article class="media-card">
      <img src="<?= e($current) ?>" alt="<?= e($item['label']) ?>">
      <h2><?= e($item['label']) ?></h2>
      <p><?= e($item['where']) ?></p>
      <p class="size">
        <?php if ($info['width'] && $info['height']): ?>
          <?= (int) $info['width'] ?> × <?= (int) $info['height'] ?> px
          · <?= e(format_file_size($info['bytes'])) ?>
        <?php else: ?>
          External image
        <?php endif; ?>
        <?php if ($replaced): ?> · Replaced<?php endif; ?>
      </p>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <input type="hidden" name="action" value="replace">
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <button class="btn btn-primary" type="submit">Replace</button>
      </form>
      <?php if ($replaced): ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="key" value="<?= e($key) ?>">
          <input type="hidden" name="action" value="reset">
          <button class="btn btn-ghost" type="submit">Use original</button>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php admin_footer();