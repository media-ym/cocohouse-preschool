<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_login();

admin_header('Pages', 'pages');
?>
<h1>Website pages</h1>
<p class="lead">Edit the words on each public page. Program cards on Our Programs are edited from that page, and they also update the home page cards.</p>
<section class="page-grid">
  <?php foreach (page_catalog() as $id => $page): ?>
    <a class="page-link" href="/admin/page-edit.php?page=<?= e($id) ?>">
      <strong><?= e($page['label']) ?></strong>
      <span>Edit content</span>
    </a>
  <?php endforeach; ?>
</section>
<?php admin_footer();