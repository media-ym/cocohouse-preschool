<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/gallery.php';
require_section('gallery');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $error = null;
    if ($action === 'add-category') {
        $error = gallery_add_category((string) ($_POST['name'] ?? ''));
        if ($error === null) {
            flash('Category added. It now shows on the gallery page.');
        }
    } elseif ($action === 'rename-category') {
        $error = gallery_rename_category((string) ($_POST['id'] ?? ''), (string) ($_POST['name'] ?? ''));
        if ($error === null) {
            flash('Category name updated.');
        }
    } elseif ($action === 'delete-category') {
        $error = gallery_delete_category((string) ($_POST['id'] ?? ''));
        if ($error === null) {
            flash('Category removed.');
        }
    } elseif ($action === 'move-category') {
        gallery_move_category((string) ($_POST['id'] ?? ''), (string) ($_POST['dir'] ?? ''));
    } elseif ($action === 'add-item') {
        $error = gallery_add_item(
            (string) ($_POST['category'] ?? ''),
            (string) ($_POST['title'] ?? ''),
            (string) ($_POST['caption'] ?? ''),
            (string) ($_POST['youtube'] ?? ''),
            $_FILES['files'] ?? []
        );
        if ($error === null) {
            flash('Added to the gallery.');
        }
    } elseif ($action === 'delete-item') {
        $error = gallery_delete_item((string) ($_POST['category'] ?? ''), (string) ($_POST['item'] ?? ''));
        if ($error === null) {
            flash('Removed from the gallery.');
        }
    } elseif ($action === 'move-item') {
        gallery_move_item((string) ($_POST['category'] ?? ''), (string) ($_POST['item'] ?? ''), (string) ($_POST['dir'] ?? ''));
    }
    if ($error) {
        flash($error, 'error');
    }
    header('Location: /admin/gallery.php');
    exit;
}

$data = gallery_data();
admin_header('Gallery', 'gallery');
?>
<h1>Gallery</h1>
<p class="lead">Add a category such as Annual Day, Preschool, or Daycare. It becomes a button on the gallery page. Photos and videos you upload sit together inside that category.</p>

<div class="admin-duo">
<section class="card">
  <h2>New category</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add-category">
    <div class="field">
      <label for="categoryName">Name</label>
      <input id="categoryName" name="name" maxlength="40" placeholder="Annual Day" required>
    </div>
    <button class="btn btn-primary" type="submit">Add category</button>
  </form>
</section>

<section class="card">
  <h2>Upload</h2>
  <?php if (!$data['categories']): ?>
    <p class="empty">Add a category before uploading.</p>
  <?php else: ?>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add-item">
      <div class="split">
        <div class="field">
          <label for="uploadCategory">Category</label>
          <select id="uploadCategory" name="category" required>
            <?php foreach ($data['categories'] as $category): ?>
              <option value="<?= e($category['id']) ?>"><?= e($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="uploadTitle">Title</label>
          <input id="uploadTitle" name="title" maxlength="80" placeholder="Optional for a single file">
        </div>
      </div>
      <div class="field">
        <label for="uploadCaption">Caption</label>
        <input id="uploadCaption" name="caption" maxlength="160" placeholder="Optional">
      </div>
      <div class="field">
        <label for="uploadFiles">Photos or videos</label>
        <input id="uploadFiles" type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" multiple>
        <p class="help">You can select several photos at once. A video can be MP4, WEBM or MOV, up to 40 MB.</p>
      </div>
      <div class="field">
        <label for="uploadYoutube">Or a YouTube link</label>
        <input id="uploadYoutube" name="youtube" placeholder="https://www.youtube.com/watch?v=...">
      </div>
      <button class="btn btn-yellow" type="submit">Add to gallery</button>
    </form>
  <?php endif; ?>
</section>
</div>

<?php foreach ($data['categories'] as $category): ?>
  <?php $count = count($category['items']); ?>
  <section class="card">
    <div class="cat-head">
      <div>
        <h2 style="margin:0;"><?= e($category['name']) ?></h2>
        <p class="help" style="margin:4px 0 0;"><?= $count ?> <?= $count === 1 ? 'item' : 'items' ?> on the website menu</p>
      </div>
      <div class="actions" style="margin:0;">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="move-category">
          <input type="hidden" name="id" value="<?= e($category['id']) ?>">
          <input type="hidden" name="dir" value="up">
          <button class="btn btn-ghost" type="submit" aria-label="Move <?= e($category['name']) ?> up">Up</button>
        </form>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="move-category">
          <input type="hidden" name="id" value="<?= e($category['id']) ?>">
          <input type="hidden" name="dir" value="down">
          <button class="btn btn-ghost" type="submit" aria-label="Move <?= e($category['name']) ?> down">Down</button>
        </form>
        <form method="post" onsubmit="return confirm('Remove this category and everything in it?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete-category">
          <input type="hidden" name="id" value="<?= e($category['id']) ?>">
          <button class="btn btn-danger" type="submit">Delete</button>
        </form>
      </div>
    </div>
    <form method="post" class="cat-tools" style="margin-top:14px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="rename-category">
      <input type="hidden" name="id" value="<?= e($category['id']) ?>">
      <div class="field">
        <label>Menu name</label>
        <input name="name" value="<?= e($category['name']) ?>" maxlength="40" required>
      </div>
      <button class="btn btn-primary" type="submit">Save name</button>
    </form>
    <?php if (!$category['items']): ?>
      <p class="empty">No photos or videos in this category yet.</p>
    <?php else: ?>
      <div class="g-tiles">
        <?php foreach ($category['items'] as $item): ?>
          <?php
            $thumb = $item['src'];
            $kind = 'Photo';
            if ($item['type'] === 'youtube') {
                $thumb = '/api/yt-thumb.php?id=' . rawurlencode($item['src']);
                $kind = 'YouTube';
            } elseif ($item['type'] === 'video') {
                $kind = 'Video';
            }
          ?>
          <article class="g-tile">
            <?php if ($item['type'] === 'video'): ?>
              <video src="<?= e($item['src']) ?>" muted preload="metadata"></video>
            <?php else: ?>
              <img src="<?= e($thumb) ?>" alt="">
            <?php endif; ?>
            <div>
              <strong><?= e($item['title']) ?></strong>
              <p><?= e($kind) ?><?= $item['caption'] !== '' ? ' · ' . e($item['caption']) : '' ?></p>
              <div class="actions">
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="move-item">
                  <input type="hidden" name="category" value="<?= e($category['id']) ?>">
                  <input type="hidden" name="item" value="<?= e($item['id']) ?>">
                  <input type="hidden" name="dir" value="up">
                  <button class="btn btn-ghost" type="submit">Up</button>
                </form>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="move-item">
                  <input type="hidden" name="category" value="<?= e($category['id']) ?>">
                  <input type="hidden" name="item" value="<?= e($item['id']) ?>">
                  <input type="hidden" name="dir" value="down">
                  <button class="btn btn-ghost" type="submit">Down</button>
                </form>
                <form method="post" onsubmit="return confirm('Remove this from the gallery?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete-item">
                  <input type="hidden" name="category" value="<?= e($category['id']) ?>">
                  <input type="hidden" name="item" value="<?= e($item['id']) ?>">
                  <button class="btn btn-danger" type="submit">Delete</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php admin_footer();