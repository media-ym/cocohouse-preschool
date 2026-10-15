<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_section('dashboard');

$enquiries = load_enquiries()['items'];
$posts = load_blog()['items'];
$gallery = gallery_data();
$labels = enquiry_labels();
$newCount = 0;
foreach ($enquiries as $item) {
    if (($item['status'] ?? 'new') === 'new') {
        $newCount++;
    }
}
$published = 0;
foreach ($posts as $post) {
    if (($post['status'] ?? '') === 'published') {
        $published++;
    }
}
$mediaCount = 0;
foreach ($gallery['categories'] as $category) {
    $mediaCount += count($category['items']);
}

$role = (string) (current_admin()['role'] ?? '');
$canEnquiries = user_can($role, 'enquiries');
$canBlog = user_can($role, 'blog');
$canGallery = user_can($role, 'gallery');
$recent = $posts;
usort($recent, static function ($a, $b) {
    return strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? ''));
});
$recent = array_slice($recent, 0, 6);
$featuredPost = null;
$drafts = 0;
foreach ($posts as $post) {
    if (($post['status'] ?? '') === 'draft') {
        $drafts++;
    }
    if ($featuredPost === null && !empty($post['is_featured'])) {
        $featuredPost = $post;
    }
}
$tiles = array_values(array_filter([
    user_can($role, 'pages') ? ['Pages', '/admin/pages.php', count(page_catalog()) . ' public pages'] : null,
    user_can($role, 'seo') ? ['SEO', '/admin/seo.php', 'Titles, Analytics, Clarity'] : null,
    user_can($role, 'media') ? ['Images', '/admin/media.php', 'Logo and page photos'] : null,
    $canGallery ? ['Gallery', '/admin/gallery.php', $mediaCount . ' photos and videos'] : null,
    $canEnquiries ? ['Enquiries', '/admin/enquiries.php', $newCount . ' new · ' . count($enquiries) . ' total'] : null,
    $canBlog ? ['Blog', '/admin/blog.php', $published . ' published posts'] : null,
    user_can($role, 'users') ? ['Users', '/admin/users.php', 'Logins and roles'] : null,
    user_can($role, 'password') ? ['Password', '/admin/password.php', 'Change your login'] : null,
]));
admin_header('Dashboard', 'dashboard');
?>
<div class="dash-head">
  <div>
    <h1>Dashboard</h1>
    <p class="lead"><?= e(date('l, j M Y')) ?> · Cocohouse Preschool &amp; Daycare</p>
  </div>
  <div class="dash-head-actions">
    <?php if ($canBlog): ?>
      <a class="btn btn-primary" href="/admin/blog-edit.php">New post</a>
    <?php endif; ?>
    <a class="btn btn-yellow" href="/blogs" target="_blank" rel="noopener">View blog</a>
  </div>
</div>

<section class="stats">
  <?php if ($canEnquiries): ?>
    <a class="stat stat-link" href="/admin/enquiries.php">
      <b><?= $newCount ?></b>
      <span>New enquiries</span>
      <em>Open inbox</em>
    </a>
    <a class="stat stat-link" href="/admin/enquiries.php">
      <b><?= count($enquiries) ?></b>
      <span>Total enquiries</span>
      <em>All forms</em>
    </a>
  <?php endif; ?>
  <?php if ($canBlog): ?>
    <a class="stat stat-link" href="/admin/blog.php">
      <b><?= $published ?></b>
      <span>Published posts</span>
      <em><?= $drafts ?> draft<?= $drafts === 1 ? '' : 's' ?></em>
    </a>
  <?php endif; ?>
  <?php if ($canGallery): ?>
    <a class="stat stat-link" href="/admin/gallery.php">
      <b><?= $mediaCount ?></b>
      <span>Gallery photos &amp; videos</span>
      <em><?= count($gallery['categories']) ?> categories</em>
    </a>
  <?php endif; ?>
</section>

<section class="dash-main">
  <?php if ($canBlog): ?>
  <article class="card dash-card">
    <div class="row">
      <h2>Recent posts</h2>
      <a class="btn btn-ghost" href="/admin/blog.php">All posts</a>
    </div>
    <?php if (!$recent): ?>
      <p class="dash-note">No posts yet. Start with New post.</p>
    <?php else: ?>
      <?php foreach ($recent as $post): ?>
        <a class="dash-post" href="/admin/blog-edit.php?id=<?= e($post['id'] ?? '') ?>">
          <img src="<?= e($post['featured_img'] ?? '/assets/images/joyful-learning.webp') ?>" alt="">
          <span>
            <strong><?= e($post['title'] ?? 'Untitled') ?></strong>
            <span><?= e($post['category_name'] ?? 'Blog') ?> · <?= e(date('j M Y', strtotime((string) ($post['published_at'] ?? '')) ?: time())) ?><?php if (!empty($post['is_featured'])): ?> · Featured<?php endif; ?></span>
          </span>
          <em><?= e(ucfirst((string) ($post['status'] ?? 'draft'))) ?></em>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </article>
  <?php endif; ?>

  <div class="dash-stack">
    <?php if ($canBlog && $featuredPost): ?>
    <article class="card dash-card">
      <div class="row">
        <h2>Featured story</h2>
        <a class="btn btn-ghost" href="/admin/blog-edit.php?id=<?= e($featuredPost['id'] ?? '') ?>">Change</a>
      </div>
      <a class="dash-feature" href="/admin/blog-edit.php?id=<?= e($featuredPost['id'] ?? '') ?>">
        <img src="<?= e($featuredPost['featured_img'] ?? '/assets/images/joyful-learning.webp') ?>" alt="">
        <strong><?= e($featuredPost['title'] ?? '') ?></strong>
        <span>This is the large card at the top of the blog. Open the post and tick Featured story to switch it.</span>
      </a>
    </article>
    <?php endif; ?>

    <?php if ($canGallery): ?>
    <article class="card dash-card">
      <div class="row">
        <h2>Gallery</h2>
        <a class="btn btn-ghost" href="/admin/gallery.php">Manage</a>
      </div>
      <?php foreach ($gallery['categories'] as $category): ?>
        <a class="dash-cat" href="/admin/gallery.php">
          <strong><?= e($category['name'] ?? 'Category') ?></strong>
          <span><?= count($category['items'] ?? []) ?></span>
        </a>
      <?php endforeach; ?>
    </article>
    <?php endif; ?>

    <?php if ($canEnquiries): ?>
    <article class="card dash-card">
      <div class="row">
        <h2>Latest enquiries</h2>
        <a class="btn btn-ghost" href="/admin/enquiries.php">Inbox</a>
      </div>
      <?php if (!$enquiries): ?>
        <p class="dash-note">No enquiries yet. A parent form will show up here.</p>
      <?php else: ?>
        <?php foreach (array_slice($enquiries, 0, 4) as $item): ?>
          <a class="inbox-row" href="/admin/enquiries.php">
            <div>
              <strong><?= e($item['parentName'] ?: 'Parent') ?></strong>
              <p>
                <?= e($labels[$item['type'] ?? ''] ?? 'Enquiry') ?>
                <?php if (!empty($item['phone'])): ?> · <?= e($item['phone']) ?><?php endif; ?>
                · <?= e(format_when($item['created_at'] ?? '')) ?>
              </p>
            </div>
            <span class="badge <?= e($item['status'] ?? 'new') ?>"><?= e(ucfirst($item['status'] ?? 'new')) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </article>
    <?php endif; ?>
  </div>
</section>

<?php if ($tiles): ?>
<h2 class="dash-label">Open a section</h2>
<section class="dash-tiles">
  <?php foreach ($tiles as $tile): ?>
    <a class="dash-tile" href="<?= e($tile[1]) ?>">
      <strong><?= e($tile[0]) ?></strong>
      <span><?= e($tile[2]) ?></span>
    </a>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php admin_footer();