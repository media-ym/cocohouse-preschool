<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string) ($_POST['id'] ?? '');
    $data = load_blog();
    $data['items'] = array_values(array_filter($data['items'], function ($post) use ($id) {
        return ($post['id'] ?? '') !== $id;
    }));
    save_blog($data);
    flash('Blog post deleted.');
    header('Location: /admin/blog.php');
    exit;
}

$posts = load_blog()['items'];
usort($posts, function ($a, $b) {
    return strcmp($b['published_at'] ?? '', $a['published_at'] ?? '');
});
admin_header('Blog', 'blog');
?>
<div class="row">
  <div>
    <h1>Blog posts</h1>
    <p class="lead">Published posts appear on the website blog. Author: <a href="/author/nikhil-yelligetti" target="_blank" rel="noopener">Nikhil Yelligetti</a></p>
  </div>
  <a class="btn btn-primary" href="/admin/blog-edit.php">New post</a>
</div>
<div class="posts">
<?php if (!$posts): ?>
  <section class="card empty">No posts yet.</section>
<?php endif; ?>
<?php foreach ($posts as $post): ?>
  <article class="post">
    <img src="<?= e($post['featured_img'] ?? '/assets/images/joyful-learning.webp') ?>" alt="">
    <div>
      <h2><?= e($post['title'] ?? 'Untitled') ?></h2>
      <p>
        <span class="badge <?= ($post['status'] ?? '') === 'published' ? '' : 'draft' ?>"><?= e(ucfirst($post['status'] ?? 'draft')) ?></span>
        <?= e($post['category_name'] ?? '') ?>
        · <?= e(blog_author_name($post)) ?>
        <?php if (!empty($post['is_featured'])): ?> · Featured<?php endif; ?>
      </p>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/blog-edit.php?id=<?= e($post['id'] ?? '') ?>">Edit</a>
      <?php if (($post['status'] ?? '') === 'published'): ?>
        <a class="btn btn-ghost" href="/blog-post/<?= e(rawurlencode($post['slug'] ?? '')) ?>" target="_blank" rel="noopener">View</a>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Delete this post?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($post['id'] ?? '') ?>">
        <button class="btn btn-danger" type="submit">Delete</button>
      </form>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php admin_footer();