<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_section('enquiries');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string) ($_POST['id'] ?? '');
    $action = (string) ($_POST['action'] ?? '');
    $data = load_enquiries();
    $next = [];
    $found = false;
    foreach ($data['items'] as $item) {
        if (($item['id'] ?? '') !== $id) {
            $next[] = $item;
            continue;
        }
        $found = true;
        if ($action === 'delete') {
            continue;
        }
        if ($action === 'contacted') {
            $item['status'] = 'contacted';
        } elseif ($action === 'new') {
            $item['status'] = 'new';
        }
        $next[] = $item;
    }
    if ($found) {
        $data['items'] = $next;
        save_enquiries($data);
        flash($action === 'delete' ? 'Enquiry deleted.' : 'Enquiry updated.');
    }
    $back = '/admin/enquiries.php';
    $returnProgram = trim((string) ($_POST['return_program'] ?? ''));
    if ($returnProgram !== '') {
        $back .= '?program=' . rawurlencode($returnProgram);
    }
    header('Location: ' . $back);
    exit;
}

$items = load_enquiries()['items'];
$labels = enquiry_labels();
$known = ['Playgroup', 'Nursery', 'Junior KG', 'Senior KG', 'Daycare', 'Special Education'];
$counts = [];
foreach ($items as $item) {
    $name = trim((string) ($item['program'] ?? ''));
    if ($name === '') {
        $name = 'No program';
    }
    $counts[$name] = ($counts[$name] ?? 0) + 1;
}
$filters = [];
foreach ($known as $name) {
    if (!empty($counts[$name])) {
        $filters[$name] = $counts[$name];
    }
}
foreach ($counts as $name => $count) {
    if (!isset($filters[$name])) {
        $filters[$name] = $count;
    }
}
$active = trim((string) ($_GET['program'] ?? ''));
if ($active !== '' && !isset($filters[$active])) {
    $active = '';
}
$visible = $items;
if ($active !== '') {
    $visible = array_values(array_filter($items, static function ($item) use ($active) {
        $name = trim((string) ($item['program'] ?? ''));
        if ($name === '') {
            $name = 'No program';
        }
        return $name === $active;
    }));
}
admin_header('Enquiries', 'enquiries');
?>
<h1>Enquiries</h1>
<p class="lead"><?= count($items) ?> saved from the website forms. Each one is also emailed to cocohousepreschool@gmail.com and info@cocohouse.in.</p>
<nav class="filters">
  <a class="<?= $active === '' ? 'active' : '' ?>" href="/admin/enquiries.php">All <b><?= count($items) ?></b></a>
  <?php foreach ($filters as $name => $count): ?>
    <a class="<?= $active === $name ? 'active' : '' ?>" href="/admin/enquiries.php?program=<?= e(rawurlencode($name)) ?>"><?= e($name) ?> <b><?= (int) $count ?></b></a>
  <?php endforeach; ?>
</nav>
<?php if (!$visible): ?>
  <section class="card empty"><?= $items ? 'No enquiries for this program.' : 'No enquiries yet.' ?></section>
<?php endif; ?>
<?php foreach ($visible as $item): ?>
  <article class="enquiry">
    <header>
      <div>
        <span class="badge"><?= e($labels[$item['type'] ?? ''] ?? 'Enquiry') ?></span>
        <span class="badge <?= e($item['status'] ?? 'new') ?>"><?= e(ucfirst($item['status'] ?? 'new')) ?></span>
        <?php if (empty($item['emailed'])): ?><span class="badge draft">Email not sent</span><?php endif; ?>
      </div>
      <time><?= e(format_when($item['created_at'] ?? '')) ?></time>
    </header>
    <dl class="meta">
      <dt>Parent</dt><dd><?= e($item['parentName'] ?? '—') ?></dd>
      <dt>Child</dt><dd><?= e($item['childName'] ?? '—') ?><?= !empty($item['childAge']) ? ' · ' . e($item['childAge']) : '' ?></dd>
      <dt>Program</dt><dd><?= e($item['program'] ?? '—') ?></dd>
      <dt>Phone</dt><dd><?php if (!empty($item['phone'])): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $item['phone'])) ?>"><?= e($item['phone']) ?></a><?php else: ?>—<?php endif; ?></dd>
      <dt>Email</dt><dd><?= e($item['email'] ?? '—') ?></dd>
      <?php if (!empty($item['message'])): ?>
        <dt>Message</dt><dd><?= e($item['message']) ?></dd>
      <?php endif; ?>
      <?php if (!empty($item['ads']) && is_array($item['ads'])): ?>
        <dt>Ad</dt><dd><?= e(trim(($item['ads']['utm_source'] ?? '') . ' ' . ($item['ads']['utm_campaign'] ?? '')) ?: ($item['ads']['landing'] ?? 'Landing page')) ?></dd>
      <?php endif; ?>
    </dl>
    <div class="actions">
      <?php if (($item['status'] ?? 'new') !== 'contacted'): ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
          <input type="hidden" name="return_program" value="<?= e($active) ?>">
          <input type="hidden" name="action" value="contacted">
          <button class="btn btn-yellow" type="submit">Mark contacted</button>
        </form>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
          <input type="hidden" name="return_program" value="<?= e($active) ?>">
          <input type="hidden" name="action" value="new">
          <button class="btn btn-ghost" type="submit">Mark as new</button>
        </form>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Delete this enquiry?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
        <input type="hidden" name="return_program" value="<?= e($active) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-danger" type="submit">Delete</button>
      </form>
    </div>
  </article>
<?php endforeach; ?>
<?php admin_footer();