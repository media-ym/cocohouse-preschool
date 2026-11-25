<?php

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/site.php';
require_once __DIR__ . '/gallery.php';

function cms_path(): string
{
    return COCO_STORAGE . '/pages.json';
}

function cms_data(): array
{
    $data = read_json(cms_path(), ['site' => [], 'pages' => []]);
    $data['site'] = is_array($data['site'] ?? null) ? $data['site'] : [];
    $data['pages'] = is_array($data['pages'] ?? null) ? $data['pages'] : [];
    return $data;
}

function cms_save(array $data): void
{
    write_json(cms_path(), $data);
}

function cms_page(string $id): array
{
    $pages = cms_data()['pages'];
    return is_array($pages[$id] ?? null) ? $pages[$id] : [];
}

function page_catalog(): array
{
    return [
        'home' => ['label' => 'Home', 'file' => '/index.html', 'view' => page_url('home')],
        'about' => ['label' => 'About Us', 'file' => '/about/index.html', 'view' => page_url('about')],
        'programs' => ['label' => 'Our Programs', 'file' => '/programs/index.html', 'view' => page_url('programs')],
        'daycare' => ['label' => 'Daycare', 'file' => '/daycare/index.html', 'view' => page_url('daycare')],
        'gallery' => ['label' => 'Gallery', 'file' => '/gallery/index.html', 'view' => page_url('gallery')],
        'blog' => ['label' => 'Blog', 'file' => '/blog/index.html', 'view' => page_url('blog')],
        'contact' => ['label' => 'Contact', 'file' => '/contact/index.html', 'view' => page_url('contact')],
        'admissions' => ['label' => 'Admissions 2026', 'file' => '/admissions/index.html', 'view' => page_url('admissions')],
        'special-education' => ['label' => 'Special Education', 'file' => '/special-education/index.html', 'view' => page_url('special-education')],
    ];
}

function page_fields(string $id): array
{
    $text = ['type' => 'text'];
    $area = ['type' => 'textarea'];
    $map = [
        'home' => [
            'hero_badge' => $text + ['label' => 'Hero badge'],
            'hero_title' => $area + ['label' => 'Hero title', 'hint' => 'Use a new line where you want a line break.'],
            'hero_text' => $area + ['label' => 'Hero paragraph'],
            'story_kicker' => $text + ['label' => 'Story label'],
            'story_title' => $text + ['label' => 'Story heading'],
            'story_p1' => $area + ['label' => 'Story paragraph 1'],
            'story_p2' => $area + ['label' => 'Story paragraph 2'],
            'programs_kicker' => $text + ['label' => 'Programs label'],
            'programs_title' => $text + ['label' => 'Programs heading'],
            'programs_text' => $area + ['label' => 'Programs intro'],
        ],
        'about' => [
            'hero_title' => $text + ['label' => 'Page title'],
            'hero_text' => $area + ['label' => 'Intro'],
            'story_kicker' => $text + ['label' => 'Story label'],
            'story_title' => $text + ['label' => 'Story heading'],
            'story_p1' => $area + ['label' => 'Story paragraph 1'],
            'story_p2' => $area + ['label' => 'Story paragraph 2'],
            'story_p3' => $area + ['label' => 'Story paragraph 3'],
            'mission' => $area + ['label' => 'Mission'],
            'vision' => $area + ['label' => 'Vision'],
        ],
        'programs' => [
            'hero_title' => $text + ['label' => 'Page title'],
            'hero_text' => $area + ['label' => 'Intro'],
            'kicker' => $text + ['label' => 'Section label'],
            'heading' => $text + ['label' => 'Section heading'],
        ],
        'daycare' => [
            'tagline' => $text + ['label' => 'Tagline under the name'],
            'hero_text' => $area + ['label' => 'Intro'],
            'packages_title' => $text + ['label' => 'Packages heading'],
            'packages_text' => $area + ['label' => 'Packages intro'],
        ],
        'gallery' => [
            'hero_title' => $text + ['label' => 'Page title'],
            'hero_text' => $area + ['label' => 'Intro'],
            'photos_title' => $text + ['label' => 'Photos heading'],
            'photos_text' => $text + ['label' => 'Photos intro'],
        ],
        'blog' => [
            'hero_title' => $text + ['label' => 'Page title'],
            'hero_text' => $area + ['label' => 'Intro'],
        ],
        'admissions' => [
            'hero_badge' => $text + ['label' => 'Badge'],
            'hero_title' => $area + ['label' => 'Main title', 'hint' => 'Use a new line where you want a line break.'],
            'hero_text' => $area + ['label' => 'Intro'],
            'offer_title' => $text + ['label' => 'Offer heading'],
            'offer_text' => $area + ['label' => 'Offer details'],
        ],
        'special-education' => [
            'hero_badge' => $text + ['label' => 'Badge'],
            'hero_title' => $area + ['label' => 'Main title', 'hint' => 'Use a new line where you want a line break.'],
            'hero_text' => $area + ['label' => 'Intro'],
        ],
        'contact' => [
            'hero_title' => $text + ['label' => 'Page title'],
            'hero_text' => $area + ['label' => 'Intro'],
            'phone' => $text + ['label' => 'Phone'],
            'email' => $text + ['label' => 'Email'],
            'hours' => $text + ['label' => 'Working hours'],
            'address' => $area + ['label' => 'Address'],
            'location_note' => $text + ['label' => 'Location note'],
        ],
    ];
    return $map[$id] ?? [];
}

function cms_text(string $value): string
{
    return nl2br(e($value), false);
}

function cms_render_file(string $file, string $pageId): string
{
    enforce_page_slug($pageId);
    $html = file_get_contents($file);
    if ($html === false) {
        return '';
    }
    $page = cms_page($pageId);
    foreach (page_fields($pageId) as $key => $field) {
        if (!array_key_exists($key, $page)) {
            continue;
        }
        $html = str_replace('{{' . $key . '}}', cms_text((string) $page[$key]), $html);
    }
    if ($pageId === 'home') {
        $html = str_replace('{{home_programs}}', render_home_programs(cms_page('programs')['items'] ?? []), $html);
        $html = str_replace('{{home_gallery}}', render_home_gallery(), $html);
    }
    if ($pageId === 'gallery') {
        $html = str_replace('{{gallery_board}}', render_gallery_board(), $html);
    }
    if ($pageId === 'programs') {
        $html = str_replace('{{programs_grid}}', render_program_board(cms_page('programs')['items'] ?? []), $html);
    }
    require_once __DIR__ . '/seo.php';
    return public_finish(seo_apply(apply_site_details($html), $pageId));
}

function apply_site_details(string $html): string
{
    $site = cms_data()['site'];
    $phone = (string) ($site['phone'] ?? '');
    $email = (string) ($site['email'] ?? '');
    $address = (string) ($site['address'] ?? '');
    if ($phone !== '') {
        $html = str_replace('+91-7039152173', e($phone), $html);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits !== '') {
            $html = str_replace('917039152173', e($digits), $html);
        }
    }
    if ($email !== '') {
        $html = str_replace('info@cocohouse.in', e($email), $html);
    }
    if ($address !== '') {
        $html = str_replace(
            'Row House No 3, Millenium Park, Hari Om Nagar, Thane East, Mumbai-400081',
            e($address),
            $html
        );
        $html = str_replace(
            'Row House No 3, Millenium Park, Hari Om Nagar, Thane East, Mumbai - 400081',
            e($address),
            $html
        );
    }
    return $html;
}

function tile_ink(string $color): string
{
    return strtolower($color) === '#f4d000' ? '#133a78' : '#ffffff';
}

function render_home_programs(array $items): string
{
    $html = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $color = (string) ($item['color'] ?? '#184a96');
        $ink = tile_ink($color);
        $bg = strtolower($color) === '#f4d000' ? '#fff8e1' : $color . '20';
        $html .= '<div class="program-card">';
        $html .= '<div class="program-emoji" style="background:' . e($bg) . ';">' . e($item['emoji'] ?? '') . '</div>';
        $html .= '<h3>' . e($item['name'] ?? '') . '</h3>';
        $html .= '<p class="program-age" style="color:' . e($color) . ';">' . e($item['age'] ?? '') . '</p>';
        $html .= '<ul class="program-points">';
        foreach (array_slice($item['outcomes'] ?? [], 0, 3) as $point) {
            $html .= '<li><span class="dot" style="background:' . e($color) . ';"></span><span>' . e((string) $point) . '</span></li>';
        }
        $html .= '</ul>';
        $cardLink = (string) ($item['link'] ?? '');
        if (!preg_match('#^(https://|/)#', $cardLink)) {
            $cardLink = page_url('programs');
        }
        $html .= '<a href="' . e($cardLink) . '" class="btn btn-sm" style="background:' . e($color) . ';color:' . e($ink) . ';">' . e($item['button'] ?? 'Enquire Now →') . '</a>';
        $html .= '</div>';
    }
    return $html;
}

function render_program_board(array $items): string
{
    $html = '<div class="program-board">';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $color = (string) ($item['color'] ?? '#184a96');
        $ink = tile_ink($color);
        $html .= '<article class="program-tile">';
        $html .= '<div class="program-tile-head" style="background:' . e($color) . ';color:' . e($ink) . ';">';
        $html .= '<div class="emoji">' . e($item['emoji'] ?? '') . '</div>';
        $html .= '<h3>' . e($item['name'] ?? '') . '</h3>';
        $html .= '<p><strong>Age:</strong> ' . e($item['age'] ?? '') . '</p>';
        $html .= '<p><strong>Duration:</strong> ' . e($item['duration'] ?? '') . '</p>';
        $link = (string) ($item['link'] ?? '/contact');
        if (!preg_match('#^(https://|/)#', $link)) {
            $link = '/contact';
        }
        $buttonInk = strtolower($color) === '#f4d000' ? '#133a78' : $color;
        $html .= '<a class="btn" style="background:#fff;color:' . e($buttonInk) . ';margin-top:12px;" href="' . e($link) . '">' . e($item['button'] ?? 'Enquire Now →') . '</a>';
        $html .= '</div><div class="program-tile-body">';
        $html .= '<p class="desc">' . e($item['description'] ?? '') . '</p>';
        if (!empty($item['outcomes'])) {
            $html .= '<h4>Learning outcomes</h4><ul>';
            foreach ($item['outcomes'] as $point) {
                $html .= '<li><i style="background:' . e($color) . ';"></i><span>' . e((string) $point) . '</span></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div></article>';
    }
    $html .= '</div>';
    return $html;
}

function lines_to_list(string $raw): array
{
    $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $items[] = $line;
        }
    }
    return $items;
}

function lines_to_schedule(string $raw): array
{
    $slots = [];
    foreach (lines_to_list($raw) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $slots[] = [
            'time' => $parts[0] ?? '',
            'label' => $parts[1] ?? '',
        ];
    }
    return $slots;
}

function schedule_to_lines(array $slots): string
{
    $lines = [];
    foreach ($slots as $slot) {
        if (!is_array($slot)) {
            continue;
        }
        $lines[] = trim((string) ($slot['time'] ?? '')) . ' | ' . trim((string) ($slot['label'] ?? ''));
    }
    return implode("\n", $lines);
}
