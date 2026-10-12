<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_login();

$enquiries = load_enquiries()['items'];
$posts = load_blog()['items'];
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

admin_header('Dashboard', 'dashboard');
?>
<h1>Dashboard</h1>
<p class="lead">New admission, visit, daycare, and contact forms land here. Page text is edited from Pages, and search settings from SEO.</p>
<section class="stats">
  <article class="stat"><b><?= $newCount ?></b><span>New enquiries</span></article>
  <article class="stat"><b><?= count($enquiries) ?></b><span>Total enquiries</span></article>
  <article class="stat"><b><?= $published ?></b><span>Published posts</span></article>
</section>
<section class="card">
  <div class="row">
    <h2 style="margin:0;color:#184a96;font-size:1.15rem;">Latest enquiries</h2>
    <a class="btn btn-ghost" href="/admin/enquiries.php">Open inbox</a>
  </div>
  <?php if (!$enquiries): ?>
    <p class="empty">No enquiries yet. When a parent submits a form on the website, it will show up here.</p>
  <?php else: ?>
    <?php foreach (array_slice($enquiries, 0, 5) as $item): ?>
      <p style="margin:14px 0 0;">
        <span class="badge <?= e($item['status'] ?? 'new') ?>"><?= e(ucfirst($item['status'] ?? 'new')) ?></span>
        <strong><?= e($item['parentName'] ?: 'Parent') ?></strong>
        · <?= e(enquiry_labels()[$item['type'] ?? ''] ?? 'Enquiry') ?>
        · <?= e(format_when($item['created_at'] ?? '')) ?>
      </p>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php admin_footer();