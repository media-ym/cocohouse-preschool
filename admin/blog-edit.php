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
    if ($uploaded === '') {
        $error = 'Image must be a JPG, PNG, WEBP, or GIF under 2 MB.';
    } elseif ($title === '' || $excerpt === '' || $content === '') {
        $error = 'Title, summary, and content are required.';
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
<form class="card" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($post['id'] ?? '') ?>">
  <div class="field">
    <label for="title">Title</label>
    <input id="title" name="title" required value="<?= e($post['title'] ?? '') ?>">
  </div>
  <div class="split">
    <div class="field">
      <label for="author">Author</label>
      <input id="author" name="author" required value="<?= e($post['author'] ?? default_blog_author()) ?>">
    </div>
    <div class="field">
      <label for="slug">Web address</label>
      <input id="slug" name="slug" placeholder="auto-from-title" value="<?= e($post['slug'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="category_name">Category</label>
      <select id="category_name" name="category_name">
        <?php foreach ($categories as $name => $color): ?>
          <option value="<?= e($name) ?>" <?= ($post['category_name'] ?? '') === $name ? 'selected' : '' ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="field">
    <label for="excerpt">Short summary</label>
    <textarea id="excerpt" name="excerpt" style="min-height:90px;" required><?= e($post['excerpt'] ?? '') ?></textarea>
  </div>
  <div class="field">
    <label for="content">Article</label>
    <textarea id="content" name="content" required><?= e($post['content'] ?? '') ?></textarea>
    <p class="help">Write normal paragraphs, one per line. Or use simple HTML such as &lt;p&gt;, &lt;h2&gt;, and &lt;ul&gt;&lt;li&gt;.</p>
  </div>
  <div class="split">
    <div class="field">
      <label for="image">Upload photo</label>
      <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
    </div>
    <div class="field">
      <label for="featured_img">Or image address</label>
      <input id="featured_img" name="featured_img" value="<?= e($post['featured_img'] ?? '/assets/images/joyful-learning.webp') ?>">
    </div>
  </div>
  <div class="split">
    <div class="field">
      <label for="read_minutes">Minutes to read</label>
      <input id="read_minutes" name="read_minutes" type="number" min="1" max="60" value="<?= e((string) ($post['read_minutes'] ?? 4)) ?>">
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
  </div>
  <div class="field">
    <label class="check"><input type="checkbox" name="is_featured" <?= !empty($post['is_featured']) ? 'checked' : '' ?>> Show as the featured story</label>
  </div>
  <button class="btn btn-primary" type="submit">Save post</button>
</form>
<?php admin_footer();