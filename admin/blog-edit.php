<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_login();

$id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
$post = $id !== '' ? find_post($id) : null;
if ($id !== '' && !$post && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('That post was not found.', 'error');
    header('Location: /admin/blog.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim((string) ($_POST['title'] ?? ''));
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $content = normalize_content((string) ($_POST['content'] ?? ''));
    $category = (string) ($_POST['category_name'] ?? '');
    $categories = blog_categories();
    if (!isset($categories[$category])) {
        $category = array_key_first($categories);
    }
    $uploaded = save_uploaded_image($_FILES['image'] ?? []);
    $visible = trim(str_replace("\xc2\xa0", '', html_entity_decode(strip_tags($content))));
    if ($uploaded === '') {
        $error = 'Image must be a JPG, PNG, WEBP, or GIF under 2 MB.';
    } elseif ($title === '' || $excerpt === '' || $visible === '') {
        $error = 'Title, summary, and article are required.';
    } else {
        $now = date('Y-m-d H:i:s');
        $record = $post ?: [];
        $record['id'] = $record['id'] ?? ('post_' . bin2hex(random_bytes(4)));
        $record['title'] = $title;
        $record['author'] = trim((string) ($_POST['author'] ?? '')) ?: default_blog_author();
        $record['slug'] = unique_slug((string) ($_POST['slug'] ?? $title), $record['id']);
        $record['excerpt'] = $excerpt;
        $record['content'] = $content;
        $record['category_name'] = $category;
        $record['category_color'] = $categories[$category];
        $record['featured_img'] = $uploaded ?: clean_image_url((string) ($_POST['featured_img'] ?? ''));
        $record['read_minutes'] = max(1, (int) ($_POST['read_minutes'] ?? 3));
        $record['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
        $record['status'] = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
        $record['published_at'] = $record['published_at'] ?? $now;
        if ($record['status'] === 'published' && empty($post['published_at'])) {
            $record['published_at'] = $now;
        }
        $record['updated_at'] = $now;

        $data = load_blog();
        $replaced = false;
        foreach ($data['items'] as $index => $existing) {
            if (($existing['id'] ?? '') === $record['id']) {
                $data['items'][$index] = $record;
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            array_unshift($data['items'], $record);
        }
        save_blog($data);
        flash($record['status'] === 'published' ? 'Post published.' : 'Draft saved.');
        header('Location: /admin/blog.php');
        exit;
    }
    $post = array_merge($post ?: [], [
        'title' => $title ?? '',
        'author' => trim((string) ($_POST['author'] ?? '')) ?: default_blog_author(),
        'slug' => (string) ($_POST['slug'] ?? ''),
        'excerpt' => $excerpt ?? '',
        'content' => (string) ($_POST['content'] ?? ''),
        'category_name' => $category ?? '',
        'featured_img' => (string) ($_POST['featured_img'] ?? ''),
        'read_minutes' => (int) ($_POST['read_minutes'] ?? 3),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'status' => (string) ($_POST['status'] ?? 'draft'),
    ]);
}

$categories = blog_categories();
admin_header($post ? 'Edit post' : 'New post', 'blog');
?>
<h1><?= $post && !empty($post['id']) && find_post($post['id']) ? 'Edit post' : 'New post' ?></h1>
<p class="lead"><a href="/admin/blog.php">Back to posts</a></p>
<?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
<form class="editor-layout" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($post['id'] ?? '') ?>">
  <div class="card">
    <div class="field">
      <label for="title">Title</label>
      <input id="title" name="title" required value="<?= e($post['title'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="excerpt">Short summary</label>
      <textarea id="excerpt" name="excerpt" style="min-height:90px;" required><?= e($post['excerpt'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label for="content">Article</label>
      <textarea id="content" name="content"><?= e($post['content'] ?? '') ?></textarea>
      <p class="help">Bold, headings, lists, and links. No HTML needed.</p>
    </div>
  </div>
  <aside class="editor-side">
    <div class="card">
      <h2>Publish</h2>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
          <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
      </div>
      <div class="field">
        <label for="read_minutes">Minutes to read</label>
        <input id="read_minutes" name="read_minutes" type="number" min="1" max="60" value="<?= e((string) ($post['read_minutes'] ?? 4)) ?>">
      </div>
      <div class="field">
        <label class="check"><input type="checkbox" name="is_featured" <?= !empty($post['is_featured']) ? 'checked' : '' ?>> Featured story</label>
      </div>
      <button class="btn btn-primary" type="submit">Save post</button>
    </div>
    <div class="card">
      <h2>Details</h2>
      <div class="field">
        <label for="category_name">Category</label>
        <select id="category_name" name="category_name">
          <?php foreach ($categories as $name => $color): ?>
            <option value="<?= e($name) ?>" <?= ($post['category_name'] ?? '') === $name ? 'selected' : '' ?>><?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="author">Author</label>
        <input id="author" name="author" required value="<?= e($post['author'] ?? default_blog_author()) ?>">
      </div>
      <div class="field">
        <label for="slug">Web address</label>
        <input id="slug" name="slug" placeholder="auto-from-title" value="<?= e($post['slug'] ?? '') ?>">
      </div>
    </div>
    <div class="card">
      <h2>Cover photo</h2>
      <?php if (!empty($post['featured_img'])): ?>
        <img src="<?= e($post['featured_img']) ?>" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:12px;margin-bottom:12px;">
      <?php endif; ?>
      <div class="field">
        <label for="image">Upload photo</label>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
      </div>
      <div class="field">
        <label for="featured_img">Or image address</label>
        <input id="featured_img" name="featured_img" value="<?= e($post['featured_img'] ?? '/assets/images/joyful-learning.webp') ?>">
      </div>
    </div>
  </aside>
</form>
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: '#content',
  license_key: 'gpl',
  menubar: false,
  branding: false,
  promotion: false,
  statusbar: false,
  height: 460,
  plugins: 'lists link autoresize',
  toolbar: 'undo redo | blocks | bold italic | bullist numlist | link blockquote | removeformat',
  block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
  skin_url: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1/skins/ui/oxide',
  content_css: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1/skins/content/default/content.min.css',
  content_style: 'body { font-family: Poppins, sans-serif; font-size: 16px; line-height: 1.6; color: #1f2937; }',
  setup: function (editor) {
    editor.on('change input undo redo', function () { editor.save(); });
  }
});
</script>
<?php admin_footer();