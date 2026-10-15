<?php

date_default_timezone_set('Asia/Kolkata');

define('COCO_ROOT', dirname(__DIR__));
define('COCO_STORAGE', COCO_ROOT . '/storage');

require_once __DIR__ . '/crypto.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function read_json(string $path, array $default): array
{
    if (!is_file($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw === false ? '' : $raw, true);
    return is_array($data) ? $data : $default;
}

function write_json(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Could not save data.');
    }
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Could not save data.');
    }
    rename($tmp, $path);
}

function blog_categories(): array
{
    return [
        'Parenting Tips' => '#184a96',
        'Early Education' => '#ff6600',
        'Activities & Crafts' => '#25d366',
        'School News' => '#9333ea',
        'Daycare' => '#ff6600',
    ];
}

function load_blog(): array
{
    $data = read_json(COCO_STORAGE . '/blog.json', ['items' => []]);
    $data['items'] = array_values(is_array($data['items'] ?? null) ? $data['items'] : []);
    return $data;
}

function save_blog(array $data): void
{
    $data['items'] = array_values($data['items'] ?? []);
    write_json(COCO_STORAGE . '/blog.json', $data);
    export_public_blog($data);
}

function default_blog_author(): string
{
    return 'Nikhil Yelligetti';
}

function blog_author_name(array $post): string
{
    $name = trim((string) ($post['author'] ?? ''));
    return $name !== '' ? $name : default_blog_author();
}

function blog_author_slug(string $name): string
{
    return slugify($name);
}

function blog_view_counts(): array
{
    $data = read_json(COCO_STORAGE . '/blog-views.json', []);
    return is_array($data) ? $data : [];
}

function blog_view_count(string $slug): int
{
    $counts = blog_view_counts();
    return max(0, (int) ($counts[$slug] ?? 0));
}

function record_blog_view(string $slug): int
{
    if (!preg_match('/^[a-z0-9-]{1,80}$/', $slug)) {
        return 0;
    }
    $counts = blog_view_counts();
    $current = max(0, (int) ($counts[$slug] ?? 0));
    $seen = array_filter(explode(',', (string) ($_COOKIE['coco_blog_seen'] ?? '')), static function ($item) {
        return preg_match('/^[a-z0-9-]{1,80}$/', $item) === 1;
    });
    if (!in_array($slug, $seen, true)) {
        $current++;
        $counts[$slug] = $current;
        write_json(COCO_STORAGE . '/blog-views.json', $counts);
        $seen[] = $slug;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie('coco_blog_seen', implode(',', array_slice($seen, -40)), [
            'expires' => time() + 86400,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        export_public_blog(load_blog());
    }
    return $current;
}

function export_public_blog(array $data): void
{
    $views = blog_view_counts();
    $items = [];
    foreach ($data['items'] as $post) {
        if (($post['status'] ?? 'published') !== 'published') {
            continue;
        }
        $author = blog_author_name($post);
        $slug = (string) ($post['slug'] ?? '');
        $items[] = [
            'title' => $post['title'] ?? '',
            'slug' => $slug,
            'excerpt' => $post['excerpt'] ?? '',
            'featured_img' => $post['featured_img'] ?? '',
            'published_at' => $post['published_at'] ?? '',
            'read_minutes' => (int) ($post['read_minutes'] ?? 1),
            'views' => max(0, (int) ($views[$slug] ?? 0)),
            'is_featured' => !empty($post['is_featured']) ? 1 : 0,
            'category_name' => $post['category_name'] ?? '',
            'category_color' => $post['category_color'] ?? '#184a96',
            'author' => $author,
            'author_slug' => blog_author_slug($author),
            'tags' => [],
        ];
    }
    usort($items, function ($a, $b) {
        return strcmp($b['published_at'], $a['published_at']);
    });
    write_json(COCO_ROOT . '/data/blog-posts.json', ['items' => $items]);
}

function find_post(string $id): ?array
{
    foreach (load_blog()['items'] as $post) {
        if (($post['id'] ?? '') === $id) {
            return $post;
        }
    }
    return null;
}

function find_published_by_slug(string $slug): ?array
{
    foreach (load_blog()['items'] as $post) {
        if (($post['slug'] ?? '') === $slug && ($post['status'] ?? '') === 'published') {
            return $post;
        }
    }
    return null;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'post';
}

function unique_slug(string $slug, string $ignoreId = ''): string
{
    $base = slugify($slug);
    $candidate = $base;
    $n = 2;
    $taken = [];
    foreach (load_blog()['items'] as $post) {
        if (($post['id'] ?? '') === $ignoreId) {
            continue;
        }
        $taken[$post['slug'] ?? ''] = true;
    }
    while (isset($taken[$candidate])) {
        $candidate = $base . '-' . $n;
        $n++;
    }
    return $candidate;
}

function sanitize_html(string $html): string
{
    $html = strip_tags($html, '<p><h2><h3><ul><ol><li><strong><em><b><i><u><a><br><blockquote><span>');
    $html = preg_replace('/\sdata-mce-[a-z0-9-]+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;
    $html = preg_replace('/\s(class|id)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;
    $html = preg_replace_callback('/\sstyle\s*=\s*("|\')(.*?)\1/i', static function (array $match): string {
        $keep = [];
        foreach (explode(';', $match[2]) as $rule) {
            if (!str_contains($rule, ':')) {
                continue;
            }
            [$prop, $value] = array_map('trim', explode(':', $rule, 2));
            $prop = strtolower($prop);
            $value = strtolower($value);
            if (preg_match('/url|expression|javascript|@import/i', $value)) {
                continue;
            }
            if ($prop === 'font-family' && preg_match('/^[a-z0-9 ,\'"-]+$/', $value)) {
                $keep[] = 'font-family:' . $value;
            } elseif ($prop === 'font-size' && preg_match('/^\d+(?:\.\d+)?(?:px|pt|rem|em)$/', $value)) {
                $keep[] = 'font-size:' . $value;
            } elseif ($prop === 'color' && preg_match('/^(#[0-9a-f]{3,8}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/', $value)) {
                $keep[] = 'color:' . $value;
            } elseif ($prop === 'text-align' && in_array($value, ['left', 'center', 'right'], true)) {
                $keep[] = 'text-align:' . $value;
            } elseif ($prop === 'text-decoration' && in_array($value, ['underline', 'line-through', 'none'], true)) {
                $keep[] = 'text-decoration:' . $value;
            }
        }
        return $keep ? ' style="' . implode(';', $keep) . '"' : '';
    }, $html) ?? $html;
    $html = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*\1/i', 'href="#"', $html) ?? $html;
    return trim($html);
}

function normalize_content(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (!str_contains($raw, '<')) {
        $parts = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $html = '';
        foreach ($parts as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $html .= '<p>' . e($line) . '</p>';
        }
        return $html;
    }
    return sanitize_html($raw);
}

function clean_image_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '/assets/images/joyful-learning.webp';
    }
    if (str_starts_with($url, '/assets/images/')) {
        return $url;
    }
    if (preg_match('#^https://#i', $url)) {
        return $url;
    }
    return '/assets/images/joyful-learning.webp';
}

function save_uploaded_image(array $file, int $maxBytes = 2097152): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > $maxBytes) {
        return '';
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];
    $ext = $types[$info[2] ?? 0] ?? null;
    if (!$ext) {
        return '';
    }
    $dir = COCO_ROOT . '/assets/images/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'upload-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return '';
    }
    return '/assets/images/uploads/' . $name;
}

function load_enquiries(): array
{
    $data = read_private_json(COCO_STORAGE . '/enquiries.json', ['items' => []]);
    $data['items'] = array_values(is_array($data['items'] ?? null) ? $data['items'] : []);
    return $data;
}

function save_enquiries(array $data): void
{
    $data['items'] = array_values($data['items'] ?? []);
    write_private_json(COCO_STORAGE . '/enquiries.json', $data);
}

function enquiry_labels(): array
{
    return [
        'home_hero' => 'Home Hero Section Form',
        'admission_page' => 'Admission Page Form',
        'popup' => 'Popup Form',
        'school_visit' => 'School Visit Footer Form',
        'daycare' => 'Daycare Page Form',
        'contact' => 'Contact Page Form',
        'special_education' => 'Special Ed Form',
        'admission' => 'Admission',
    ];
}

function format_when(string $date): string
{
    $ts = strtotime($date);
    if (!$ts) {
        return $date;
    }
    return date('d M Y, g:i A', $ts);
}
