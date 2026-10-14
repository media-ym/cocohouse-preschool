<?php

require_once __DIR__ . '/store.php';

function gallery_path(): string
{
    return COCO_STORAGE . '/gallery.json';
}

function gallery_default(): array
{
    return [
        'categories' => [
            [
                'id' => 'preschool',
                'name' => 'Preschool',
                'items' => [
                    [
                        'id' => 'campus',
                        'type' => 'image',
                        'src' => '/assets/images/campus.jpg',
                        'title' => 'Preschool & Daycare Building',
                        'caption' => 'Our beautiful preschool & daycare in Mulund East',
                    ],
                    [
                        'id' => 'joyful',
                        'type' => 'image',
                        'src' => '/assets/images/joyful-learning.webp',
                        'title' => 'Joyful Learning',
                        'caption' => 'Children learning through play',
                    ],
                    [
                        'id' => 'principal',
                        'type' => 'image',
                        'src' => '/assets/images/principal.webp',
                        'title' => 'Our Principal',
                        'caption' => 'Mrs. Pratheeksha Darak, Founder',
                    ],
                ],
            ],
            [
                'id' => 'testimonials',
                'name' => 'Testimonials',
                'items' => [
                    [
                        'id' => 'nitisha',
                        'type' => 'youtube',
                        'src' => 'tMTFDT_VD_o',
                        'title' => 'Nitisha & Vijay',
                        'caption' => '',
                    ],
                    [
                        'id' => 'anirudh',
                        'type' => 'youtube',
                        'src' => 'WQ4MrEJBD8E',
                        'title' => 'Anirudh & Shilpa',
                        'caption' => '',
                    ],
                ],
            ],
        ],
    ];
}

function gallery_data(): array
{
    $data = read_json(gallery_path(), gallery_default());
    $categories = [];
    foreach (is_array($data['categories'] ?? null) ? $data['categories'] : [] as $category) {
        if (!is_array($category)) {
            continue;
        }
        $id = gallery_slug((string) ($category['id'] ?? $category['name'] ?? ''));
        $name = trim((string) ($category['name'] ?? ''));
        if ($id === '' || $name === '') {
            continue;
        }
        $items = [];
        foreach (is_array($category['items'] ?? null) ? $category['items'] : [] as $item) {
            $clean = gallery_clean_item(is_array($item) ? $item : []);
            if ($clean) {
                $items[] = $clean;
            }
        }
        $categories[] = ['id' => $id, 'name' => $name, 'items' => $items];
    }
    return ['categories' => $categories];
}

function gallery_save(array $data): void
{
    write_json(gallery_path(), ['categories' => array_values($data['categories'] ?? [])]);
}

function gallery_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function gallery_clip(string $value, int $max): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function gallery_clean_item(array $item): ?array
{
    $type = (string) ($item['type'] ?? '');
    $src = trim((string) ($item['src'] ?? ''));
    $title = gallery_clip((string) ($item['title'] ?? ''), 80);
    if ($title === '' || $src === '') {
        return null;
    }
    if ($type === 'image' && (str_starts_with($src, '/assets/images/') || str_starts_with($src, 'https://'))) {
        return [
            'id' => gallery_slug((string) ($item['id'] ?? '')) ?: bin2hex(random_bytes(4)),
            'type' => 'image',
            'src' => $src,
            'title' => $title,
            'caption' => gallery_clip((string) ($item['caption'] ?? ''), 160),
        ];
    }
    if ($type === 'youtube' && preg_match('/^[A-Za-z0-9_-]{11}$/', $src)) {
        return [
            'id' => gallery_slug((string) ($item['id'] ?? '')) ?: bin2hex(random_bytes(4)),
            'type' => 'youtube',
            'src' => $src,
            'title' => $title,
            'caption' => gallery_clip((string) ($item['caption'] ?? ''), 160),
        ];
    }
    if ($type === 'video' && str_starts_with($src, '/assets/videos/uploads/')) {
        return [
            'id' => gallery_slug((string) ($item['id'] ?? '')) ?: bin2hex(random_bytes(4)),
            'type' => 'video',
            'src' => $src,
            'title' => $title,
            'caption' => gallery_clip((string) ($item['caption'] ?? ''), 160),
        ];
    }
    return null;
}

function gallery_youtube_id(string $value): string
{
    $value = trim($value);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $value, $match)) {
        return $match[1];
    }
    return '';
}

function gallery_category_index(array $data, string $id): int
{
    foreach ($data['categories'] as $index => $category) {
        if (($category['id'] ?? '') === $id) {
            return $index;
        }
    }
    return -1;
}

function gallery_add_category(string $name): ?string
{
    $name = gallery_clip($name, 40);
    if ($name === '') {
        return 'Type a category name.';
    }
    $data = gallery_data();
    $id = gallery_slug($name);
    if ($id === '') {
        return 'Use letters or numbers in the category name.';
    }
    foreach ($data['categories'] as $category) {
        if (strcasecmp($category['name'], $name) === 0 || $category['id'] === $id) {
            return 'That category is already there.';
        }
    }
    $base = $id;
    $n = 2;
    $ids = array_column($data['categories'], 'id');
    while (in_array($id, $ids, true)) {
        $id = $base . '-' . $n;
        $n++;
    }
    $data['categories'][] = ['id' => $id, 'name' => $name, 'items' => []];
    gallery_save($data);
    return null;
}

function gallery_rename_category(string $id, string $name): ?string
{
    $name = gallery_clip($name, 40);
    if ($name === '') {
        return 'Type a category name.';
    }
    $data = gallery_data();
    $index = gallery_category_index($data, $id);
    if ($index < 0) {
        return 'That category was not found.';
    }
    foreach ($data['categories'] as $i => $category) {
        if ($i !== $index && strcasecmp($category['name'], $name) === 0) {
            return 'That category is already there.';
        }
    }
    $data['categories'][$index]['name'] = $name;
    gallery_save($data);
    return null;
}

function gallery_delete_category(string $id): ?string
{
    $data = gallery_data();
    $index = gallery_category_index($data, $id);
    if ($index < 0) {
        return 'That category was not found.';
    }
    foreach ($data['categories'][$index]['items'] as $item) {
        gallery_delete_upload((string) ($item['src'] ?? ''), (string) ($item['type'] ?? ''));
    }
    array_splice($data['categories'], $index, 1);
    gallery_save($data);
    return null;
}

function gallery_move_category(string $id, string $dir): ?string
{
    $data = gallery_data();
    $index = gallery_category_index($data, $id);
    $swap = $dir === 'up' ? $index - 1 : $index + 1;
    if ($index < 0 || !isset($data['categories'][$swap])) {
        return null;
    }
    [$data['categories'][$index], $data['categories'][$swap]] = [$data['categories'][$swap], $data['categories'][$index]];
    gallery_save($data);
    return null;
}

function gallery_file_list(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }
    if (!is_array($files['name'])) {
        return [$files];
    }
    $list = [];
    foreach ($files['name'] as $i => $name) {
        $list[] = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $list;
}

function gallery_add_item(string $categoryId, string $title, string $caption, string $youtube, array $files): ?string
{
    $data = gallery_data();
    $index = gallery_category_index($data, $categoryId);
    if ($index < 0) {
        return 'Choose a category first.';
    }
    $caption = gallery_clip($caption, 160);
    $title = gallery_clip($title, 80);
    $uploads = [];
    foreach (gallery_file_list($files) as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $uploads[] = $file;
    }
    $youtubeId = gallery_youtube_id($youtube);
    if ($youtube !== '' && $youtubeId === '') {
        return 'That YouTube link is not valid.';
    }
    if (!$uploads && $youtubeId === '') {
        return 'Upload a photo or video, or paste a YouTube link.';
    }
    if ($uploads && $youtubeId !== '') {
        return 'Upload files or paste a YouTube link, one at a time.';
    }
    if (count($uploads) > 20) {
        return 'Upload up to 20 files at a time.';
    }
    if ($youtubeId !== '') {
        $data['categories'][$index]['items'][] = [
            'id' => bin2hex(random_bytes(4)),
            'type' => 'youtube',
            'src' => $youtubeId,
            'title' => $title !== '' ? $title : 'Video',
            'caption' => $caption,
        ];
        gallery_save($data);
        return null;
    }
    $added = 0;
    foreach ($uploads as $file) {
        $saved = gallery_store_upload($file);
        if ($saved === null) {
            continue;
        }
        if ($saved === '') {
            if ($added) {
                gallery_save($data);
            }
            return $added ? 'Some files were added. A later file was too large or not a photo or video.' : 'Upload a JPG, PNG, WEBP, GIF, MP4, WEBM or MOV. Videos must be under 40 MB.';
        }
        $itemTitle = count($uploads) === 1 && $title !== '' ? $title : gallery_title_from_filename((string) $file['name']);
        $data['categories'][$index]['items'][] = [
            'id' => bin2hex(random_bytes(4)),
            'type' => $saved['type'],
            'src' => $saved['src'],
            'title' => $itemTitle,
            'caption' => count($uploads) === 1 ? $caption : '',
        ];
        $added++;
    }
    if (!$added) {
        return 'Nothing was uploaded.';
    }
    gallery_save($data);
    return null;
}

function gallery_title_from_filename(string $name): string
{
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = str_replace(['_', '-'], ' ', $base);
    $base = gallery_clip($base, 80);
    return $base !== '' ? $base : 'Photo';
}

function gallery_store_upload(array $file): ?array
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || $error !== UPLOAD_ERR_OK) {
        return '';
    }
    $imageInfo = @getimagesize($file['tmp_name'] ?? '');
    if (is_array($imageInfo)) {
        $image = save_uploaded_image($file, 8 * 1024 * 1024);
        return $image ? ['type' => 'image', 'src' => $image] : '';
    }
    if (!is_file($file['tmp_name'] ?? '') || ($file['size'] ?? 0) > 40 * 1024 * 1024) {
        return '';
    }
    $mime = '';
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    }
    $types = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
    $ext = $types[$mime] ?? null;
    if (!$ext) {
        return '';
    }
    $dir = COCO_ROOT . '/assets/videos/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return '';
    }
    $name = 'video-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return '';
    }
    return ['type' => 'video', 'src' => '/assets/videos/uploads/' . $name];
}

function gallery_delete_item(string $categoryId, string $itemId): ?string
{
    $data = gallery_data();
    $index = gallery_category_index($data, $categoryId);
    if ($index < 0) {
        return 'That category was not found.';
    }
    foreach ($data['categories'][$index]['items'] as $i => $item) {
        if (($item['id'] ?? '') !== $itemId) {
            continue;
        }
        gallery_delete_upload((string) $item['src'], (string) $item['type']);
        array_splice($data['categories'][$index]['items'], $i, 1);
        gallery_save($data);
        return null;
    }
    return 'That item was not found.';
}

function gallery_move_item(string $categoryId, string $itemId, string $dir): ?string
{
    $data = gallery_data();
    $index = gallery_category_index($data, $categoryId);
    if ($index < 0) {
        return null;
    }
    $items = $data['categories'][$index]['items'];
    foreach ($items as $i => $item) {
        if (($item['id'] ?? '') !== $itemId) {
            continue;
        }
        $swap = $dir === 'up' ? $i - 1 : $i + 1;
        if (!isset($items[$swap])) {
            return null;
        }
        [$items[$i], $items[$swap]] = [$items[$swap], $items[$i]];
        $data['categories'][$index]['items'] = $items;
        gallery_save($data);
        return null;
    }
    return null;
}

function gallery_delete_upload(string $src, string $type): void
{
    $prefix = $type === 'video' ? '/assets/videos/uploads/' : '/assets/images/uploads/';
    if (!str_starts_with($src, $prefix)) {
        return;
    }
    $path = COCO_ROOT . $src;
    $real = realpath($path);
    $root = realpath(COCO_ROOT . rtrim($prefix, '/'));
    if ($real && $root && str_starts_with($real, $root . DIRECTORY_SEPARATOR) && is_file($real)) {
        unlink($real);
    }
}

function gallery_play_icon(): string
{
    return '<span class="gallery-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22"><polygon points="7 5 19 12 7 19" fill="#184a96"/></svg></span>';
}

function render_gallery_board(): string
{
    $categories = gallery_data()['categories'];
    $filters = '<div class="gallery-filters" role="tablist"><button type="button" class="is-active" data-filter="all" aria-pressed="true">All</button>';
    $cards = '';
    $count = 0;
    foreach ($categories as $category) {
        $filters .= '<button type="button" data-filter="' . e($category['id']) . '" aria-pressed="false">' . e($category['name']) . '</button>';
        foreach ($category['items'] as $item) {
            $count++;
            $kind = $item['type'] === 'youtube' ? 'youtube' : ($item['type'] === 'video' ? 'video' : 'image');
            $thumb = $item['src'];
            if ($item['type'] === 'youtube') {
                $thumb = 'https://img.youtube.com/vi/' . rawurlencode($item['src']) . '/hqdefault.jpg';
            }
            $cards .= '<article class="gallery-card" data-cat="' . e($category['id']) . '">';
            $cards .= '<button type="button" class="gallery-shot" aria-label="' . e($item['title']) . '" data-kind="' . e($kind) . '" data-src="' . e($item['src']) . '" data-title="' . e($item['title']) . '">';
            if ($item['type'] === 'video') {
                $cards .= '<video src="' . e($item['src']) . '" muted preload="metadata"></video>';
            } else {
                $cards .= '<img src="' . e($thumb) . '" alt="' . e($item['title']) . '" loading="lazy">';
            }
            if ($kind !== 'image') {
                $cards .= gallery_play_icon();
            }
            $cards .= '</button><div class="gallery-meta"><strong>' . e($item['title']) . '</strong>';
            if ($item['caption'] !== '') {
                $cards .= '<p>' . e($item['caption']) . '</p>';
            }
            $cards .= '</div></article>';
        }
    }
    $filters .= '</div>';
    $empty = '<p class="gallery-empty" id="galleryEmpty"' . ($count ? ' hidden' : '') . '>Nothing in this category yet.</p>';
    $script = <<<'HTML'
<script>
document.querySelectorAll('.gallery-filters button').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var filter = btn.getAttribute('data-filter');
    document.querySelectorAll('.gallery-filters button').forEach(function (other) {
      var on = other === btn;
      other.classList.toggle('is-active', on);
      other.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    var shown = 0;
    document.querySelectorAll('.gallery-card').forEach(function (card) {
      var ok = filter === 'all' || card.getAttribute('data-cat') === filter;
      card.hidden = !ok;
      if (ok) shown++;
    });
    var empty = document.getElementById('galleryEmpty');
    if (empty) empty.hidden = shown > 0;
  });
});
document.getElementById('galleryGrid').addEventListener('click', function (event) {
  var shot = event.target.closest('.gallery-shot');
  if (!shot) return;
  var kind = shot.getAttribute('data-kind');
  var src = shot.getAttribute('data-src') || '';
  var title = shot.getAttribute('data-title') || '';
  if (kind === 'youtube') openVideoModal(src);
  else if (kind === 'video') openFileVideo(src);
  else openLightbox(src, title);
});
</script>
HTML;
    return $filters . '<div class="gallery-grid" id="galleryGrid">' . $cards . '</div>' . $empty . $script;
}

function render_home_gallery(int $limit = 4): string
{
    $html = '';
    $shown = 0;
    foreach (gallery_data()['categories'] as $category) {
        foreach ($category['items'] as $item) {
            if ($shown >= $limit) {
                break 2;
            }
            $thumb = $item['src'];
            if ($item['type'] === 'youtube') {
                $thumb = 'https://img.youtube.com/vi/' . rawurlencode($item['src']) . '/hqdefault.jpg';
            }
            $html .= '<a class="home-shot" href="/gallery">';
            if ($item['type'] === 'video') {
                $html .= '<video src="' . e($item['src']) . '" muted preload="metadata"></video>';
            } else {
                $html .= '<img src="' . e($thumb) . '" alt="' . e($item['title']) . '" loading="lazy">';
            }
            if ($item['type'] !== 'image') {
                $html .= gallery_play_icon();
            }
            $html .= '</a>';
            $shown++;
        }
    }
    return $html;
}
