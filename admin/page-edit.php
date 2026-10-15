<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/seo.php';
require_section('pages');

$pageId = (string) ($_GET['page'] ?? $_POST['page'] ?? '');
$catalog = page_catalog();
if (!isset($catalog[$pageId])) {
    flash('That page was not found.', 'error');
    header('Location: /admin/pages.php');
    exit;
}

$colors = [
    '#f4d000' => 'Yellow',
    '#184a96' => 'Blue',
    '#ff6600' => 'Orange',
    '#9333ea' => 'Purple',
    '#25d366' => 'Green',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = cms_data();
    $current = is_array($data['pages'][$pageId] ?? null) ? $data['pages'][$pageId] : [];
    $postedFields = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];
    foreach (page_fields($pageId) as $key => $field) {
        $current[$key] = trim((string) ($postedFields[$key] ?? ''));
    }

    $slugNote = '';
    if ($pageId !== 'home' && isset($_POST['slug'])) {
        $wanted = clean_page_slug((string) $_POST['slug']);
        $problem = slug_problem($pageId, $wanted);
        if ($problem === '') {
            $current['slug'] = $wanted;
        } else {
            $slugNote = $problem;
        }
    }

    $cleanSeo = ['warning' => ''];
    if (is_array($_POST['seo'] ?? null)) {
        $cleanSeo = sanitize_page_seo($_POST['seo']);
        $current['seo'] = $cleanSeo['data'];
    }

    if ($pageId === 'programs') {
        $items = [];
        $posted = is_array($_POST['programs'] ?? null) ? $_POST['programs'] : [];
        $existingItems = array_values(is_array($current['items'] ?? null) ? $current['items'] : []);
        foreach ($posted as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $previous = is_array($existingItems[$index] ?? null) ? $existingItems[$index] : [];
            $color = (string) ($row['color'] ?? '#184a96');
            if (!isset($colors[$color])) {
                $color = '#184a96';
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $name = 'New program';
            }
            $items[] = [
                'emoji' => trim((string) ($row['emoji'] ?? '⭐')) ?: '⭐',
                'name' => $name,
                'age' => trim((string) ($row['age'] ?? '')),
                'duration' => trim((string) ($row['duration'] ?? '')),
                'color' => $color,
                'description' => trim((string) ($row['description'] ?? '')),
                'outcomes' => lines_to_list((string) ($row['outcomes'] ?? '')),
                'schedule' => array_key_exists('schedule', $row)
                    ? lines_to_schedule((string) $row['schedule'])
                    : (is_array($previous['schedule'] ?? null) ? $previous['schedule'] : []),
                'button' => trim((string) ($row['button'] ?? '')) ?: 'Enquire Now →',
                'link' => trim((string) ($row['link'] ?? '')) ?: '/contact',
            ];
        }
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete') {
            $index = (int) ($_POST['delete_index'] ?? -1);
            if (isset($items[$index])) {
                array_splice($items, $index, 1);
            }
        }
        if ($action === 'add') {
            $items[] = [
                'emoji' => '⭐',
                'name' => 'New program',
                'age' => '',
                'duration' => '',
                'color' => '#184a96',
                'description' => '',
                'outcomes' => [],
                'schedule' => [],
                'button' => 'Enquire Now →',
                'link' => '/contact',
            ];
        }
        $current['items'] = array_values($items);
    }

    if ($pageId === 'contact') {
        $data['site']['phone'] = $current['phone'] ?? '';
        $data['site']['email'] = $current['email'] ?? '';
        $data['site']['address'] = $current['address'] ?? '';
    }

    $data['pages'][$pageId] = $current;
    cms_save($data);
    $message = $pageId === 'programs' && (($_POST['action'] ?? '') === 'add') ? 'Program added. Fill it in and save.' : 'Page saved.';
    if (!empty($cleanSeo['warning'])) {
        $message .= ' ' . $cleanSeo['warning'];
    }
    if ($slugNote !== '') {
        $message .= ' ' . $slugNote;
    }
    flash($message);
    header('Location: /admin/page-edit.php?page=' . rawurlencode($pageId));
    exit;
}

$page = cms_page($pageId);
$fields = page_fields($pageId);
$info = $catalog[$pageId];
admin_header($info['label'], 'pages');
?>
<div class="row">
  <div>
    <h1><?= e($info['label']) ?></h1>
    <p class="lead"><a href="/admin/pages.php">All pages</a></p>
  </div>
  <a class="btn btn-ghost" href="<?= e($info['view']) ?>" target="_blank" rel="noopener">View page</a>
</div>

<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="page" value="<?= e($pageId) ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="delete_index" value="">
  <section class="editor-block">
    <h2>Page address</h2>
    <?php if ($pageId === 'home'): ?>
      <p class="help">The homepage stays at <?= e((parse_url(seo_settings()['public_url'], PHP_URL_HOST) ?: 'cocohouse.in')) ?>/.</p>
    <?php else: ?>
      <div class="field">
        <label for="page-slug">Slug</label>
        <div class="slug-field">
          <span><?= e((parse_url(seo_settings()['public_url'], PHP_URL_HOST) ?: 'cocohouse.in')) ?>/</span>
          <input id="page-slug" name="slug" value="<?= e(page_slugs()[$pageId] ?? '') ?>" required>
        </div>
        <p class="help">This is the page link. The previous link redirects to the new one.</p>
      </div>
    <?php endif; ?>
  </section>

  <section class="editor-block">
    <h2>Page text</h2>
    <?php foreach ($fields as $key => $field): ?>
      <div class="field">
        <label for="field-<?= e($key) ?>"><?= e($field['label']) ?></label>
        <?php if (($field['type'] ?? '') === 'textarea'): ?>
          <textarea class="<?= strlen((string) ($page[$key] ?? '')) > 180 ? 'tall' : '' ?>" id="field-<?= e($key) ?>" name="fields[<?= e($key) ?>]"><?= e($page[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input id="field-<?= e($key) ?>" name="fields[<?= e($key) ?>]" value="<?= e($page[$key] ?? '') ?>">
        <?php endif; ?>
        <?php if (!empty($field['hint'])): ?><p class="help"><?= e($field['hint']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if ($pageId === 'contact'): ?>
      <p class="help">Phone, email and address here also update the footer across the website.</p>
    <?php endif; ?>
  </section>

  <?php
    $seoBundle = seo_page_bundle($pageId);
    $seoHost = parse_url(seo_settings()['public_url'], PHP_URL_HOST) ?: 'cocohouse.in';
    seo_editor_markup($seoBundle, $seoHost);
  ?>

  <?php if ($pageId === 'programs'): ?>
    <?php foreach (($page['items'] ?? []) as $index => $item): ?>
      <section class="editor-block">
        <div class="row">
          <h2><span class="swatch" style="background:<?= e($item['color'] ?? '#184a96') ?>"></span><?= e($item['name'] ?? 'Program') ?></h2>
          <button class="btn btn-danger" type="submit" name="action" value="delete" onclick="this.form.delete_index.value='<?= (int) $index ?>'">Remove</button>
        </div>
        <div class="split">
          <div class="field">
            <label>Name</label>
            <input name="programs[<?= (int) $index ?>][name]" value="<?= e($item['name'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label>Emoji</label>
            <input name="programs[<?= (int) $index ?>][emoji]" value="<?= e($item['emoji'] ?? '') ?>">
          </div>
        </div>
        <div class="split">
          <div class="field">
            <label>Age</label>
            <input name="programs[<?= (int) $index ?>][age]" value="<?= e($item['age'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Duration</label>
            <input name="programs[<?= (int) $index ?>][duration]" value="<?= e($item['duration'] ?? '') ?>">
          </div>
        </div>
        <div class="split">
          <div class="field">
            <label>Colour</label>
            <select name="programs[<?= (int) $index ?>][color]">
              <?php foreach ($colors as $hex => $label): ?>
                <option value="<?= e($hex) ?>" <?= ($item['color'] ?? '') === $hex ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Button link</label>
            <input name="programs[<?= (int) $index ?>][link]" value="<?= e($item['link'] ?? '/contact') ?>">
          </div>
        </div>
        <div class="field">
          <label>Button text</label>
          <input name="programs[<?= (int) $index ?>][button]" value="<?= e($item['button'] ?? 'Enquire Now →') ?>">
        </div>
        <div class="field">
          <label>Description</label>
          <textarea class="tall" name="programs[<?= (int) $index ?>][description]"><?= e($item['description'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label>Learning outcomes</label>
          <textarea name="programs[<?= (int) $index ?>][outcomes]"><?= e(implode("\n", $item['outcomes'] ?? [])) ?></textarea>
          <p class="help">One outcome per line.</p>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <button class="btn btn-primary" type="submit">Save page</button>
</form>
<?php seo_editor_script(); ?>

<?php if ($pageId === 'programs'): ?>
  <form method="post" style="margin-top:12px;">
    <?= csrf_field() ?>
    <input type="hidden" name="page" value="programs">
    <input type="hidden" name="action" value="add">
    <?php foreach (page_fields('programs') as $key => $field): ?>
      <input type="hidden" name="fields[<?= e($key) ?>]" value="<?= e($page[$key] ?? '') ?>">
    <?php endforeach; ?>
    <?php foreach (($page['items'] ?? []) as $index => $item): ?>
      <input type="hidden" name="programs[<?= (int) $index ?>][name]" value="<?= e($item['name'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][emoji]" value="<?= e($item['emoji'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][age]" value="<?= e($item['age'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][duration]" value="<?= e($item['duration'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][color]" value="<?= e($item['color'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][description]" value="<?= e($item['description'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][outcomes]" value="<?= e(implode("\n", $item['outcomes'] ?? [])) ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][button]" value="<?= e($item['button'] ?? '') ?>">
      <input type="hidden" name="programs[<?= (int) $index ?>][link]" value="<?= e($item['link'] ?? '') ?>">
    <?php endforeach; ?>
    <button class="btn btn-ghost" type="submit">Add program</button>
  </form>
<?php endif; ?>
<?php admin_footer();