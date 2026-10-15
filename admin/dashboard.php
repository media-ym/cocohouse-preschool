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
admin_header('Dashboard', 'dashboard');
?>
<h1>Dashboard</h1>
<p class="lead">Open a card to go to the part of the admin your role can use.</p>

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
  <?php if (user_can($role, 'blog')): ?>
    <a class="stat stat-link" href="/admin/blog.php">
      <b><?= $published ?></b>
      <span>Published posts</span>
      <em>Blog</em>
    </a>
  <?php endif; ?>
  <?php if (user_can($role, 'gallery')): ?>
    <a class="stat stat-link" href="/admin/gallery.php">
      <b><?= $mediaCount ?></b>
      <span>Gallery photos &amp; videos</span>
      <em><?= count($gallery['categories']) ?> categories</em>
    </a>
  <?php endif; ?>
</section>

<section class="dash-split">
  <?php if ($canEnquiries): ?>
  <article class="card">
    <div class="row">
      <h2 style="margin:0;color:#184a96;font-size:1.15rem;">Latest enquiries</h2>
      <a class="btn btn-ghost" href="/admin/enquiries.php">Open inbox</a>
    </div>
    <?php if (!$enquiries): ?>
      <p class="empty">No enquiries yet. When a parent submits a form, it shows up here.</p>
    <?php else: ?>
      <?php foreach (array_slice($enquiries, 0, 5) as $item): ?>
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

  <?php if (user_can($role, 'gallery') || user_can($role, 'blog') || user_can($role, 'pages') || user_can($role, 'users')): ?>
  <article class="card">
    <h2 style="margin:0 0 4px;color:#184a96;font-size:1.15rem;">Shortcuts</h2>
    <?php if (user_can($role, 'gallery')): ?>
      <a class="shortcut" href="/admin/gallery.php"><strong>Gallery</strong><span>Categories, photos, videos</span></a>
    <?php endif; ?>
    <?php if (user_can($role, 'blog')): ?>
      <a class="shortcut" href="/admin/blog-edit.php"><strong>New blog post</strong><span>Write with the editor</span></a>
    <?php endif; ?>
    <?php if (user_can($role, 'pages')): ?>
      <a class="shortcut" href="/admin/pages.php"><strong>Pages</strong><span><?= count(page_catalog()) ?> pages</span></a>
    <?php endif; ?>
    <?php if (user_can($role, 'seo')): ?>
      <a class="shortcut" href="/admin/seo.php"><strong>SEO</strong><span>Search titles</span></a>
    <?php endif; ?>
    <?php if (user_can($role, 'media')): ?>
      <a class="shortcut" href="/admin/media.php"><strong>Images</strong><span>Logo and page photos</span></a>
    <?php endif; ?>
    <?php if (user_can($role, 'users')): ?>
      <a class="shortcut" href="/admin/users.php"><strong>Users</strong><span>Logins and roles</span></a>
    <?php endif; ?>
  </article>
  <?php endif; ?>
</section>
<?php admin_footer();