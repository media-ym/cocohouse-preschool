<?php

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/site.php';

function seo_settings(): array
{
    $data = read_json(COCO_STORAGE . '/pages.json', []);
    $site = is_array($data['site'] ?? null) ? $data['site'] : [];
    $stored = is_array($site['seo'] ?? null) ? $site['seo'] : [];
    $defaults = [
        'site_name' => 'Cocohouse Preschool & Daycare',
        'public_url' => 'https://cocohouse.in',
        'description' => 'Best preschool and daycare in Mulund East, Thane East, Kopri and Hari Om Nagar. Playgroup, nursery, KG, daycare till 8 PM and special education.',
        'image' => '/assets/images/campus.jpg',
        'google_verification' => '',
        'bing_verification' => '',
        'analytics_id' => '',
        'twitter' => '',
        'facebook' => '',
        'instagram' => '',
        'youtube' => '',
        'linktree' => '',
        'areas' => 'Mulund, Mulund East, Thane, Thane East, Kopri, Hari Om Nagar',
    ];
    $merged = $defaults;
    foreach ($defaults as $key => $value) {
        if (isset($stored[$key]) && is_string($stored[$key])) {
            $merged[$key] = trim($stored[$key]);
        }
    }
    if ($merged['site_name'] === '') {
        $merged['site_name'] = $defaults['site_name'];
    }
    if (!preg_match('#^https://[a-z0-9.-]+(?::\d+)?$#i', $merged['public_url'])) {
        $merged['public_url'] = $defaults['public_url'];
    }
    if ($merged['image'] === '') {
        $merged['image'] = $defaults['image'];
    }
    $merged['phone'] = (string) ($site['phone'] ?? '');
    $merged['email'] = (string) ($site['email'] ?? '');
    $merged['address'] = (string) ($site['address'] ?? '');
    return $merged;
}

function seo_page_defaults(string $pageId): array
{
    $pages = [
        'home' => [
            'title' => 'Best Preschool in Mulund & Thane | Cocohouse',
            'description' => 'Best preschool in Mulund East, Thane East, Kopri and Hari Om Nagar. Daycare till 8 PM and a special education preschool with its own teacher. Call 7039152173.',
            'keywords' => 'best preschool in thane, best preschool in mulund, best preschool in kopri, best preschool in mulund east, best preschool in thane east, best preschool in hari om nagar, best preschool near me, best daycare near me, best daycare in thane, best daycare in mulund, best daycare in hari om nagar, best daycare in kopri, special education preschool near me',
        ],
        'about' => [
            'title' => 'Best Preschool in Hari Om Nagar | Cocohouse',
            'description' => 'Cocohouse is a thematic preschool in Hari Om Nagar, Mulund East. Families from Mulund, Thane, Thane East and Kopri visit for playgroup, nursery and KG.',
            'keywords' => 'best preschool in hari om nagar, best preschool in mulund east, best preschool in mulund, preschool Hari Om Nagar, Cocohouse about',
        ],
        'programs' => [
            'title' => 'Best Preschool in Mulund East | Cocohouse Programs',
            'description' => 'Playgroup, Nursery, Junior KG and Senior KG at Cocohouse, a preschool in Mulund East for families from Thane East, Kopri and Hari Om Nagar.',
            'keywords' => 'best preschool in mulund east, best preschool in thane east, best preschool in kopri, playgroup Mulund, nursery Mulund East, junior KG, senior KG',
        ],
        'daycare' => [
            'title' => 'Best Daycare in Mulund & Thane | Cocohouse',
            'description' => 'Best daycare in Mulund, Thane, Thane East, Kopri and Hari Om Nagar. Open till 8 PM for children up to 10, with meals, rest and homework help. Call 7039152173.',
            'keywords' => 'best daycare in mulund, best daycare in thane, best daycare in thane east, best daycare in kopri, best daycare in hari om nagar, best daycare near me, daycare Mulund East',
        ],
        'gallery' => [
            'title' => 'Preschool & Daycare Photos, Mulund East | Cocohouse',
            'description' => 'Photos and parent videos from Cocohouse preschool and daycare in Hari Om Nagar, Mulund East, near Thane East and Kopri.',
            'keywords' => 'preschool photos Mulund East, daycare photos Hari Om Nagar, Cocohouse gallery, best preschool in mulund east',
        ],
        'blog' => [
            'title' => 'Preschool & Daycare Blog for Mulund & Thane | Cocohouse',
            'description' => 'Parenting articles from Cocohouse for families looking at preschool and daycare in Mulund East, Thane East, Kopri and Hari Om Nagar.',
            'keywords' => 'best preschool in mulund, best daycare in thane east, preschool blog Mulund, daycare tips Thane',
        ],
        'admissions' => [
            'title' => 'Preschool Admission in Mulund East 2026 | Cocohouse',
            'description' => 'Admissions open for preschool and daycare in Mulund East, Thane East, Kopri and Hari Om Nagar. Mid-term seats, flat 60% off. Call 7039152173.',
            'keywords' => 'best preschool in mulund east, best preschool in thane east, preschool admission 2026, daycare admission Mulund, best daycare in hari om nagar',
        ],
        'special-education' => [
            'title' => 'Special Education Preschool near Mulund | Cocohouse',
            'description' => 'Special education preschool near Mulund East, Thane, Thane East, Kopri and Hari Om Nagar. Separate time, separate teacher, admissions up to 10 years. Call 7039152173.',
            'keywords' => 'special education preschool near me, special education preschool Mulund, special education Thane East, inclusive preschool Hari Om Nagar, best preschool near me',
        ],
        'contact' => [
            'title' => 'Preschool near Hari Om Nagar | Contact Cocohouse',
            'description' => 'Visit Cocohouse at Row House No 3, Millenium Park, Hari Om Nagar, Mulund East, Mumbai 400081. Near Kopri and Thane East. Call +91-7039152173.',
            'keywords' => 'best preschool near me, best daycare near me, best preschool in hari om nagar, preschool Mulund East address, daycare Kopri, Cocohouse contact',
        ],
    ];
    $base = $pages[$pageId] ?? [
        'title' => 'Cocohouse Preschool & Daycare',
        'description' => '',
        'keywords' => '',
    ];
    return $base + [
        'og_title' => '',
        'og_description' => '',
        'og_image' => '',
        'canonical' => '',
        'robots' => 'index,follow',
        'sitemap' => '1',
        'extra_schema' => '',
    ];
}

function seo_page_bundle(string $pageId): array
{
    $bundle = seo_page_defaults($pageId);
    $data = read_json(COCO_STORAGE . '/pages.json', []);
    $page = is_array($data['pages'][$pageId] ?? null) ? $data['pages'][$pageId] : [];
    $stored = is_array($page['seo'] ?? null) ? $page['seo'] : [];
    foreach ($bundle as $key => $value) {
        if (array_key_exists($key, $stored) && is_string($stored[$key])) {
            $bundle[$key] = $stored[$key];
        }
    }
    return $bundle;
}

function seo_clip(string $value, int $max): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function seo_absolute(string $url, string $base): string
{
    if (preg_match('#^https://#i', $url)) {
        return $url;
    }
    $base = rtrim($base, '/');
    if (str_starts_with($url, '/')) {
        return $base . $url;
    }
    return $base . '/' . ltrim($url, '/');
}

function sanitize_site_seo(array $posted): array
{
    $url = trim((string) ($posted['public_url'] ?? ''));
    $url = rtrim($url, '/');
    if (!preg_match('#^https://[a-z0-9.-]+(?::\d+)?$#i', $url)) {
        $url = 'https://cocohouse.in';
    }
    $analytics = strtoupper(trim((string) ($posted['analytics_id'] ?? '')));
    if (!preg_match('#^(G-[A-Z0-9]{4,20}|GTM-[A-Z0-9]{4,20})$#', $analytics)) {
        $analytics = '';
    }
    $code = static function (string $value): string {
        $value = trim($value);
        return preg_match('#^[A-Za-z0-9_-]{0,80}$#', $value) ? $value : '';
    };
    $social = static function (string $value): string {
        $value = trim($value);
        return preg_match('#^https://[^\s]{0,180}$#', $value) ? $value : '';
    };
    $image = trim((string) ($posted['image'] ?? ''));
    if ($image !== '' && !preg_match('#^(https://|/)#', $image)) {
        $image = '';
    }
    $twitter = trim((string) ($posted['twitter'] ?? ''));
    $twitter = ltrim($twitter, '@');
    if (!preg_match('#^[A-Za-z0-9_]{0,15}$#', $twitter)) {
        $twitter = '';
    }
    return [
        'site_name' => seo_clip((string) ($posted['site_name'] ?? ''), 80),
        'public_url' => $url,
        'description' => seo_clip((string) ($posted['description'] ?? ''), 320),
        'image' => seo_clip($image, 240),
        'google_verification' => $code((string) ($posted['google_verification'] ?? '')),
        'bing_verification' => $code((string) ($posted['bing_verification'] ?? '')),
        'analytics_id' => $analytics,
        'twitter' => $twitter,
        'facebook' => $social((string) ($posted['facebook'] ?? '')),
        'instagram' => $social((string) ($posted['instagram'] ?? '')),
        'youtube' => $social((string) ($posted['youtube'] ?? '')),
        'linktree' => $social((string) ($posted['linktree'] ?? '')),
        'areas' => seo_clip((string) ($posted['areas'] ?? ''), 300),
    ];
}

function sanitize_page_seo(array $posted): array
{
    $robots = (string) ($posted['robots'] ?? 'index,follow');
    $allowed = ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'];
    if (!in_array($robots, $allowed, true)) {
        $robots = 'index,follow';
    }
    $canonical = trim((string) ($posted['canonical'] ?? ''));
    if ($canonical !== '' && !preg_match('#^(https://[^\s]+|/[\w\-./]*)$#', $canonical)) {
        $canonical = '';
    }
    $image = trim((string) ($posted['og_image'] ?? ''));
    if ($image !== '' && !preg_match('#^(https://|/)#', $image)) {
        $image = '';
    }
    $extra = trim((string) ($posted['extra_schema'] ?? ''));
    $warning = '';
    if ($extra !== '') {
        $decoded = json_decode($extra, true);
        if (!is_array($decoded)) {
            $extra = '';
            $warning = 'Extra structured data was skipped because it is not valid JSON.';
        } else {
            $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $extra = $encoded === false ? '' : $encoded;
        }
    }
    return [
        'warning' => $warning,
        'data' => [
            'title' => seo_clip((string) ($posted['title'] ?? ''), 120),
            'description' => seo_clip((string) ($posted['description'] ?? ''), 320),
            'keywords' => seo_clip((string) ($posted['keywords'] ?? ''), 400),
            'og_title' => seo_clip((string) ($posted['og_title'] ?? ''), 120),
            'og_description' => seo_clip((string) ($posted['og_description'] ?? ''), 320),
            'og_image' => seo_clip($image, 240),
            'canonical' => seo_clip($canonical, 240),
            'robots' => $robots,
            'sitemap' => ((string) ($posted['sitemap'] ?? '0')) === '1' ? '1' : '0',
            'extra_schema' => $extra,
        ],
    ];
}

function seo_area_places(string $areas): array
{
    $names = preg_split('/\s*,\s*/', trim($areas)) ?: [];
    $places = [];
    foreach ($names as $name) {
        $name = trim($name);
        if ($name === '') {
            continue;
        }
        $places[] = ['@type' => 'Place', 'name' => seo_clip($name, 60)];
    }
    if (!$places) {
        foreach (['Mulund', 'Mulund East', 'Thane', 'Thane East', 'Kopri', 'Hari Om Nagar'] as $name) {
            $places[] = ['@type' => 'Place', 'name' => $name];
        }
    }
    return $places;
}

function seo_json(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    return $json === false ? '' : $json;
}

function seo_schema_graph(string $pageId, array $bundle, array $settings, string $canonical): array
{
    $sameAs = array_values(array_filter([
        $settings['facebook'],
        $settings['instagram'],
        $settings['youtube'] ?? '',
        $settings['linktree'] ?? '',
    ]));
    $org = [
        '@type' => ['Preschool', 'ChildCare', 'EducationalOrganization'],
        '@id' => rtrim($settings['public_url'], '/') . '/#school',
        'name' => $settings['site_name'],
        'description' => $settings['description'],
        'url' => rtrim($settings['public_url'], '/') . '/',
        'telephone' => $settings['phone'],
        'email' => $settings['email'],
        'image' => seo_absolute($settings['image'], $settings['public_url']),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Row House No 3, Millenium Park, Hari Om Nagar',
            'addressLocality' => 'Mulund East',
            'addressRegion' => 'Maharashtra',
            'postalCode' => '400081',
            'addressCountry' => 'IN',
        ],
        'areaServed' => seo_area_places($settings['areas'] ?? ''),
    ];
    if ($sameAs) {
        $org['sameAs'] = $sameAs;
    }
    $page = [
        '@type' => 'WebPage',
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => $bundle['title'],
        'description' => $bundle['description'],
        'isPartOf' => ['@id' => rtrim($settings['public_url'], '/') . '/#website'],
        'about' => ['@id' => $org['@id']],
    ];
    $website = [
        '@type' => 'WebSite',
        '@id' => rtrim($settings['public_url'], '/') . '/#website',
        'url' => rtrim($settings['public_url'], '/') . '/',
        'name' => $settings['site_name'],
        'publisher' => ['@id' => $org['@id']],
    ];
    $graph = [$website, $org, $page];
    if ($pageId === 'programs') {
        $data = read_json(COCO_STORAGE . '/pages.json', []);
        $items = is_array($data['pages']['programs']['items'] ?? null) ? $data['pages']['programs']['items'] : [];
        $list = [];
        $position = 1;
        foreach ($items as $item) {
            if (!is_array($item) || trim((string) ($item['name'] ?? '')) === '') {
                continue;
            }
            $list[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'item' => [
                    '@type' => 'Course',
                    'name' => (string) $item['name'],
                    'description' => (string) ($item['description'] ?? ''),
                    'provider' => ['@id' => $org['@id']],
                ],
            ];
            $position++;
        }
        if ($list) {
            $graph[] = [
                '@type' => 'ItemList',
                'name' => 'Cocohouse programs',
                'itemListElement' => $list,
            ];
        }
    }
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

function seo_meta_html(array $opts, array $settings): string
{
    $title = (string) ($opts['title'] ?? $settings['site_name']);
    $description = (string) ($opts['description'] ?? '');
    $canonical = (string) ($opts['canonical'] ?? '');
    $image = (string) ($opts['image'] ?? '');
    $robots = (string) ($opts['robots'] ?? 'index,follow');
    $html = '';
    if ($description !== '') {
        $html .= '<meta name="description" content="' . e($description) . '">' . "\n  ";
    }
    if (($opts['keywords'] ?? '') !== '') {
        $html .= '<meta name="keywords" content="' . e((string) $opts['keywords']) . '">' . "\n  ";
    }
    $html .= '<meta name="robots" content="' . e($robots) . '">' . "\n  ";
    if ($canonical !== '') {
        $html .= '<link rel="canonical" href="' . e($canonical) . '">' . "\n  ";
    }
    $html .= '<meta property="og:locale" content="en_IN">' . "\n  ";
    $html .= '<meta property="og:site_name" content="' . e($settings['site_name']) . '">' . "\n  ";
    $html .= '<meta property="og:type" content="' . e((string) ($opts['og_type'] ?? 'website')) . '">' . "\n  ";
    $html .= '<meta property="og:title" content="' . e((string) ($opts['og_title'] ?? $title)) . '">' . "\n  ";
    if ($description !== '') {
        $html .= '<meta property="og:description" content="' . e((string) ($opts['og_description'] ?? $description)) . '">' . "\n  ";
    }
    if ($canonical !== '') {
        $html .= '<meta property="og:url" content="' . e($canonical) . '">' . "\n  ";
    }
    if ($image !== '') {
        $html .= '<meta property="og:image" content="' . e($image) . '">' . "\n  ";
    }
    $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n  ";
    if ($settings['twitter'] !== '') {
        $html .= '<meta name="twitter:site" content="@' . e($settings['twitter']) . '">' . "\n  ";
    }
    $html .= '<meta name="twitter:title" content="' . e((string) ($opts['og_title'] ?? $title)) . '">' . "\n  ";
    if ($description !== '') {
        $html .= '<meta name="twitter:description" content="' . e((string) ($opts['og_description'] ?? $description)) . '">' . "\n  ";
    }
    if ($image !== '') {
        $html .= '<meta name="twitter:image" content="' . e($image) . '">' . "\n  ";
    }
    if (!empty($opts['published'])) {
        $html .= '<meta property="article:published_time" content="' . e((string) $opts['published']) . '">' . "\n  ";
    }
    if ($settings['google_verification'] !== '') {
        $html .= '<meta name="google-site-verification" content="' . e($settings['google_verification']) . '">' . "\n  ";
    }
    if ($settings['bing_verification'] !== '') {
        $html .= '<meta name="msvalidate.01" content="' . e($settings['bing_verification']) . '">' . "\n  ";
    }
    if (!empty($opts['schema'])) {
        $json = seo_json($opts['schema']);
        if ($json !== '') {
            $html .= '<script type="application/ld+json">' . $json . '</script>' . "\n  ";
        }
    }
    $extra = trim((string) ($opts['extra_schema'] ?? ''));
    if ($extra !== '') {
        $decoded = json_decode($extra, true);
        if (is_array($decoded)) {
            if (!isset($decoded['@context']) && !array_is_list($decoded)) {
                $decoded = ['@context' => 'https://schema.org'] + $decoded;
            } elseif (array_is_list($decoded)) {
                $decoded = ['@context' => 'https://schema.org', '@graph' => $decoded];
            }
            $json = seo_json($decoded);
            if ($json !== '') {
                $html .= '<script type="application/ld+json">' . $json . '</script>' . "\n  ";
            }
        }
    }
    $analytics = $settings['analytics_id'];
    if (str_starts_with($analytics, 'G-')) {
        $html .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($analytics) . '"></script>' . "\n  ";
        $html .= '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","' . e($analytics) . '");</script>' . "\n  ";
    } elseif (str_starts_with($analytics, 'GTM-')) {
        $html .= '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);})(window,document,"script","dataLayer","' . e($analytics) . '");</script>' . "\n  ";
    }
    return $html;
}

function seo_apply(string $html, string $pageId): string
{
    $settings = seo_settings();
    $bundle = seo_page_bundle($pageId);
    $path = page_url($pageId);
    $canonical = $bundle['canonical'] !== ''
        ? seo_absolute($bundle['canonical'], $settings['public_url'])
        : seo_absolute($path === '/' ? '/' : $path, $settings['public_url']);
    $imageSource = $bundle['og_image'] !== '' ? $bundle['og_image'] : $settings['image'];
    $title = $bundle['title'] !== '' ? $bundle['title'] : $settings['site_name'];
    $description = $bundle['description'] !== '' ? $bundle['description'] : $settings['description'];
    $meta = seo_meta_html([
        'title' => $title,
        'description' => $description,
        'keywords' => $bundle['keywords'],
        'canonical' => $canonical,
        'robots' => $bundle['robots'],
        'image' => seo_absolute($imageSource, $settings['public_url']),
        'og_title' => $bundle['og_title'] !== '' ? $bundle['og_title'] : $title,
        'og_description' => $bundle['og_description'] !== '' ? $bundle['og_description'] : $description,
        'og_type' => 'website',
        'schema' => seo_schema_graph($pageId, ['title' => $title, 'description' => $description], $settings, $canonical),
        'extra_schema' => $bundle['extra_schema'],
    ], $settings);
    $html = preg_replace('#<title>.*?</title>#s', '<title>' . e($title) . '</title>', $html, 1) ?? $html;
    return preg_replace('#</head>#', "  " . $meta . "</head>", $html, 1) ?? $html;
}

function seo_public_paths(): array
{
    $settings = seo_settings();
    $base = rtrim($settings['public_url'], '/');
    $paths = [];
    foreach (array_keys(page_default_slugs()) as $id) {
        $paths[$id] = page_url($id);
    }
    $urls = [];
    foreach ($paths as $id => $path) {
        $bundle = seo_page_bundle($id);
        if ($bundle['sitemap'] !== '1' || str_starts_with($bundle['robots'], 'noindex')) {
            continue;
        }
        $urls[] = $base . ($path === '/' ? '/' : $path);
    }
    foreach (load_blog()['items'] as $post) {
        if (($post['status'] ?? '') !== 'published' || trim((string) ($post['slug'] ?? '')) === '') {
            continue;
        }
        $urls[] = $base . '/blog-post/' . rawurlencode((string) $post['slug']);
        $authorSlug = blog_author_slug(blog_author_name($post));
        if ($authorSlug !== '') {
            $urls['author:' . $authorSlug] = $base . '/author/' . rawurlencode($authorSlug);
        }
    }
    return array_values($urls);
}

function seo_admin_pages_markup(string $host): void
{
    $robots = [
        'index,follow' => 'Index, follow',
        'index,nofollow' => 'Index, no follow',
        'noindex,follow' => 'No index, follow',
        'noindex,nofollow' => 'No index, no follow',
    ];
    $catalog = page_catalog();
    ?>
    <nav class="filters" aria-label="Pages">
      <?php foreach ($catalog as $pageId => $info): ?>
        <a href="#seo-page-<?= e($pageId) ?>"><?= e($info['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php foreach ($catalog as $pageId => $info): ?>
      <?php
        $seo = seo_page_bundle($pageId);
        $id = 'seo-' . $pageId;
        $path = (string) ($info['view'] ?? '/');
      ?>
      <section class="editor-block seo-page" id="seo-page-<?= e($pageId) ?>">
        <h2><?= e($info['label']) ?></h2>
        <p class="help">On-page SEO for <?= e($host . ($path === '/' ? '/' : $path)) ?></p>
        <div class="serp">
          <div class="serp-url"><?= e($host . ($path === '/' ? '/' : $path)) ?></div>
          <div class="serp-title" data-preview="title"></div>
          <div class="serp-desc" data-preview="description"></div>
        </div>
        <div class="field">
          <label for="<?= e($id) ?>-title">Search title</label>
          <input id="<?= e($id) ?>-title" name="pages[<?= e($pageId) ?>][title]" maxlength="120" data-count="60" data-field="title" value="<?= e($seo['title']) ?>">
          <p class="help"><span data-for="<?= e($id) ?>-title">0</span> characters. About 50–60 shows fully in Google.</p>
        </div>
        <div class="field">
          <label for="<?= e($id) ?>-description">Meta description</label>
          <textarea id="<?= e($id) ?>-description" name="pages[<?= e($pageId) ?>][description]" maxlength="320" data-count="160" data-field="description"><?= e($seo['description']) ?></textarea>
          <p class="help"><span data-for="<?= e($id) ?>-description">0</span> characters. About 150–160 shows fully in Google.</p>
        </div>
        <div class="field">
          <label for="<?= e($id) ?>-keywords">Keywords</label>
          <input id="<?= e($id) ?>-keywords" name="pages[<?= e($pageId) ?>][keywords]" maxlength="400" value="<?= e($seo['keywords']) ?>">
          <p class="help">Comma-separated phrases, such as best preschool in Mulund East.</p>
        </div>
        <details class="advanced">
          <summary>Advanced SEO</summary>
          <div class="field">
            <label for="<?= e($id) ?>-og-title">Social title</label>
            <input id="<?= e($id) ?>-og-title" name="pages[<?= e($pageId) ?>][og_title]" maxlength="120" value="<?= e($seo['og_title']) ?>">
            <p class="help">WhatsApp and Facebook. Leave blank to use the search title.</p>
          </div>
          <div class="field">
            <label for="<?= e($id) ?>-og-description">Social description</label>
            <textarea id="<?= e($id) ?>-og-description" name="pages[<?= e($pageId) ?>][og_description]" maxlength="320"><?= e($seo['og_description']) ?></textarea>
          </div>
          <div class="field">
            <label for="<?= e($id) ?>-og-image">Social image</label>
            <input id="<?= e($id) ?>-og-image" name="pages[<?= e($pageId) ?>][og_image]" value="<?= e($seo['og_image']) ?>" placeholder="/assets/images/campus.jpg">
          </div>
          <div class="field">
            <label for="<?= e($id) ?>-canonical">Canonical URL</label>
            <input id="<?= e($id) ?>-canonical" name="pages[<?= e($pageId) ?>][canonical]" value="<?= e($seo['canonical']) ?>" placeholder="Leave blank to use this page's own address">
          </div>
          <div class="split">
            <div class="field">
              <label for="<?= e($id) ?>-robots">Search visibility</label>
              <select id="<?= e($id) ?>-robots" name="pages[<?= e($pageId) ?>][robots]">
                <?php foreach ($robots as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= $seo['robots'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Sitemap</label>
              <input type="hidden" name="pages[<?= e($pageId) ?>][sitemap]" value="0">
              <label class="check"><input type="checkbox" name="pages[<?= e($pageId) ?>][sitemap]" value="1" <?= $seo['sitemap'] === '1' ? 'checked' : '' ?>> Include in sitemap.xml</label>
            </div>
          </div>
          <div class="field">
            <label for="<?= e($id) ?>-extra">Extra structured data (JSON-LD)</label>
            <textarea class="tall" id="<?= e($id) ?>-extra" name="pages[<?= e($pageId) ?>][extra_schema]" spellcheck="false"><?= e($seo['extra_schema']) ?></textarea>
            <p class="help">Optional. Paste one JSON object. Invalid JSON is skipped.</p>
          </div>
        </details>
      </section>
    <?php endforeach; ?>
    <?php
}

function seo_admin_pages_script(): void
{
    ?>
    <script>
    (function () {
      document.querySelectorAll('.seo-page').forEach(function (section) {
        function paint() {
          section.querySelectorAll('[data-count]').forEach(function (el) {
            var out = section.querySelector('[data-for="' + el.id + '"]');
            if (out) out.textContent = String(el.value.length);
          });
          var title = section.querySelector('[data-field="title"]');
          var desc = section.querySelector('[data-field="description"]');
          var titleOut = section.querySelector('[data-preview="title"]');
          var descOut = section.querySelector('[data-preview="description"]');
          if (titleOut) titleOut.textContent = title && title.value ? title.value : 'Page title';
          if (descOut) descOut.textContent = desc && desc.value ? desc.value : 'Meta description';
        }
        section.addEventListener('input', paint);
        paint();
      });
    })();
    </script>
    <?php
}

function seo_editor_markup(array $seo, string $host): void
{
    $robots = [
        'index,follow' => 'Index, follow',
        'index,nofollow' => 'Index, no follow',
        'noindex,follow' => 'No index, follow',
        'noindex,nofollow' => 'No index, no follow',
    ];
    ?>
    <section class="editor-block">
      <h2>On-page SEO</h2>
      <p class="help">This is the title and description Google shows for this page.</p>
      <div class="serp" id="serp-preview">
        <div class="serp-url"><?= e($host) ?></div>
        <div class="serp-title" data-preview="title"></div>
        <div class="serp-desc" data-preview="description"></div>
      </div>
      <div class="field">
        <label for="seo-title">Search title</label>
        <input id="seo-title" name="seo[title]" maxlength="120" data-count="60" value="<?= e($seo['title'] ?? '') ?>">
        <p class="help"><span data-for="seo-title">0</span> characters. About 50–60 shows fully in Google.</p>
      </div>
      <div class="field">
        <label for="seo-description">Meta description</label>
        <textarea id="seo-description" name="seo[description]" maxlength="320" data-count="160"><?= e($seo['description'] ?? '') ?></textarea>
        <p class="help"><span data-for="seo-description">0</span> characters. About 150–160 shows fully in Google.</p>
      </div>
      <div class="field">
        <label for="seo-keywords">Keywords</label>
        <input id="seo-keywords" name="seo[keywords]" maxlength="400" value="<?= e($seo['keywords'] ?? '') ?>">
        <p class="help">Comma-separated phrases parents would search for.</p>
      </div>
      <details class="advanced">
        <summary>Advanced SEO</summary>
        <div class="field">
          <label for="seo-og-title">Social title</label>
          <input id="seo-og-title" name="seo[og_title]" maxlength="120" value="<?= e($seo['og_title'] ?? '') ?>">
          <p class="help">Used on WhatsApp and Facebook. Leave blank to use the search title.</p>
        </div>
        <div class="field">
          <label for="seo-og-description">Social description</label>
          <textarea id="seo-og-description" name="seo[og_description]" maxlength="320"><?= e($seo['og_description'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label for="seo-og-image">Social image</label>
          <input id="seo-og-image" name="seo[og_image]" value="<?= e($seo['og_image'] ?? '') ?>" placeholder="/assets/images/campus.jpg">
          <p class="help">A full https link or a path starting with /. Leave blank for the site default.</p>
        </div>
        <div class="field">
          <label for="seo-canonical">Canonical URL</label>
          <input id="seo-canonical" name="seo[canonical]" value="<?= e($seo['canonical'] ?? '') ?>" placeholder="Leave blank to use this page's own address">
        </div>
        <div class="split">
          <div class="field">
            <label for="seo-robots">Search visibility</label>
            <select id="seo-robots" name="seo[robots]">
              <?php foreach ($robots as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($seo['robots'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Sitemap</label>
            <input type="hidden" name="seo[sitemap]" value="0">
            <label class="check"><input type="checkbox" name="seo[sitemap]" value="1" <?= ($seo['sitemap'] ?? '1') === '1' ? 'checked' : '' ?>> Include this page in sitemap.xml</label>
          </div>
        </div>
        <div class="field">
          <label for="seo-extra">Extra structured data (JSON-LD)</label>
          <textarea class="tall" id="seo-extra" name="seo[extra_schema]" spellcheck="false"><?= e($seo['extra_schema'] ?? '') ?></textarea>
          <p class="help">Optional. Paste one JSON object. Invalid JSON is ignored and the rest of the page still saves.</p>
        </div>
      </details>
    </section>
    <?php
}

function seo_editor_script(): void
{
    ?>
    <script>
    (function () {
      function count(el) {
        var out = document.querySelector('[data-for="' + el.id + '"]');
        if (out) out.textContent = String(el.value.length);
      }
      document.querySelectorAll('[data-count]').forEach(function (el) {
        count(el);
        el.addEventListener('input', function () { count(el); paint(); });
      });
      function paint() {
        var title = document.getElementById('seo-title');
        var desc = document.getElementById('seo-description');
        var titleOut = document.querySelector('[data-preview="title"]');
        var descOut = document.querySelector('[data-preview="description"]');
        if (title && titleOut) titleOut.textContent = title.value || 'Page title';
        if (desc && descOut) descOut.textContent = desc.value || 'Meta description';
      }
      paint();
    })();
    </script>
    <?php
}
