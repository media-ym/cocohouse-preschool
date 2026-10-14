<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_login();

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

admin_header('Dashboard', 'dashboard');
?>
<h1>Dashboard</h1>
<p class="lead">Parent forms, gallery, pages, and blog posts. Open a card to go straight there.</p>

<section class="stats">
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
  <a class="stat stat-link" href="/admin/blog.php">
    <b><?= $published ?></b>
    <span>Published posts</span>
    <em>Blog</em>
  </a>
  <a class="stat stat-link" href="/admin/gallery.php">
    <b><?= $mediaCount ?></b>
    <span>Gallery photos &amp; videos</span>
    <em><?= count($gallery['categories']) ?> categories</em>
  </a>
</section>

<section class="dash-split">
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

  <article class="card">
    <h2 style="margin:0 0 4px;color:#184a96;font-size:1.15rem;">Shortcuts</h2>
    <a class="shortcut" href="/admin/gallery.php"><strong>Gallery</strong><span>Categories, photos, videos</span></a>
    <a class="shortcut" href="/admin/blog-edit.php"><strong>New blog post</strong><span>Write with the editor</span></a>
    <a class="shortcut" href="/admin/pages.php"><strong>Pages</strong><span><?= count(page_catalog()) ?> pages</span></a>
    <a class="shortcut" href="/admin/seo.php"><strong>SEO</strong><span>Search titles</span></a>
    <a class="shortcut" href="/admin/media.php"><strong>Images</strong><span>Logo and page photos</span></a>
  </article>
</section>
<?php admin_footer();