<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/seo.php';
require_section('pages');

$notes = [
    'home' => 'Hero, programs and the first screen parents see.',
    'about' => 'School story and the about page.',
    'programs' => 'Playgroup, nursery and KG cards.',
    'daycare' => 'Daycare hours and the daycare page.',
    'gallery' => 'Gallery intro. Photos live under Gallery.',
    'blog' => 'Blog heading. Posts live under Blog.',
    'contact' => 'Contact page words.',
    'admissions' => 'Admissions 2026 page.',
    'special-education' => 'Inclusive education page.',
];

admin_header('Pages', 'pages');
?>
<h1>Website pages</h1>
<p class="lead">Pick a page to edit its words. The number is that page's SEO score.</p>
<section class="page-grid">
  <?php foreach (page_catalog() as $id => $page): ?>
    <?php
      $bundle = seo_page_bundle($id);
      $indexable = !str_starts_with($bundle['robots'], 'noindex');
      $score = seo_score($bundle['title'], $bundle['description'], $bundle['keywords'], $indexable);
      $gaps = seo_score_notes($bundle['title'], $bundle['description'], $bundle['keywords'], $indexable);
      $path = (string) ($page['view'] ?? '/');
    ?>
    <a class="page-card" href="/admin/page-edit.php?page=<?= e($id) ?>">
      <div class="page-card-top">
        <strong><?= e($page['label']) ?></strong>
        <span class="seo-score <?= e(seo_score_class($score)) ?>"><?= $score ?></span>
      </div>
      <span class="page-note"><?= e($notes[$id] ?? 'Edit the words on this page.') ?></span>
      <span class="page-path"><?= e($path === '/' ? 'cocohouse.in' : $path) ?></span>
      <?php if ($gaps): ?>
        <ul class="score-notes">
          <?php foreach ($gaps as $gap): ?>
            <li><?= e($gap) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="score-clear">Nothing missing.</p>
      <?php endif; ?>
      <em>Edit content</em>
    </a>
  <?php endforeach; ?>
</section>
<?php admin_footer();
