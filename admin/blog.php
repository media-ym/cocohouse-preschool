<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/seo.php';
require_section('blog');

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
    <p class="lead">Cards are the default. Switch to list when you want one post per row.</p>
  </div>
  <div class="row-actions">
    <div class="view-switch" role="group" aria-label="Post layout">
      <button type="button" class="active" data-view="grid">Cards</button>
      <button type="button" data-view="list">List</button>
    </div>
    <a class="btn btn-primary" href="/admin/blog-edit.php">New post</a>
  </div>
</div>
<div class="posts is-grid" id="adminPosts">
<?php if (!$posts): ?>
  <section class="card empty">No posts yet. Start with New post.</section>
<?php endif; ?>
<?php foreach ($posts as $post): ?>
  <article class="post">
    <img src="<?= e($post['featured_img'] ?? '/assets/images/joyful-learning.webp') ?>" alt="">
    <div>
      <?php
        $postTitle = trim((string) ($post['seo_title'] ?? '')) !== '' ? (string) $post['seo_title'] : (string) ($post['title'] ?? '');
        $postIndexable = ($post['status'] ?? '') === 'published';
        $postScore = seo_score($postTitle, (string) ($post['excerpt'] ?? ''), (string) ($post['seo_keywords'] ?? ''), $postIndexable);
        $postNotes = seo_score_notes($postTitle, (string) ($post['excerpt'] ?? ''), (string) ($post['seo_keywords'] ?? ''), $postIndexable);
      ?>
      <h2><?= e($post['title'] ?? 'Untitled') ?> <span class="seo-score <?= e(seo_score_class($postScore)) ?>" title="SEO score"><?= $postScore ?></span></h2>
      <p>
        <span class="badge <?= ($post['status'] ?? '') === 'published' ? '' : 'draft' ?>"><?= e(ucfirst($post['status'] ?? 'draft')) ?></span>
        <?= e($post['category_name'] ?? '') ?>
        · <?= e(blog_author_name($post)) ?>
        <?php if (!empty($post['is_featured'])): ?> · Featured<?php endif; ?>
      </p>
      <?php if (!empty($post['excerpt'])): ?>
        <p class="excerpt"><?= e($post['excerpt']) ?></p>
      <?php endif; ?>
      <?php if ($postNotes): ?>
        <ul class="score-notes">
          <?php foreach ($postNotes as $note): ?>
            <li><?= e($note) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
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
    </div>
  </article>
<?php endforeach; ?>
</div>
<script>
(function () {
  var box = document.getElementById('adminPosts');
  var bar = document.querySelector('.view-switch');
  if (!box || !bar) return;
  var saved = 'grid';
  try { saved = localStorage.getItem('coco-admin-blog-view') || 'grid'; } catch (error) { saved = 'grid'; }
  function apply(view) {
    var grid = view !== 'list';
    box.classList.toggle('is-grid', grid);
    bar.querySelectorAll('button').forEach(function (button) {
      button.classList.toggle('active', button.getAttribute('data-view') === (grid ? 'grid' : 'list'));
    });
    try { localStorage.setItem('coco-admin-blog-view', grid ? 'grid' : 'list'); } catch (error) {}
  }
  apply(saved);
  bar.addEventListener('click', function (event) {
    var button = event.target.closest('button[data-view]');
    if (!button) return;
    apply(button.getAttribute('data-view'));
  });
})();
</script>
<?php admin_footer();