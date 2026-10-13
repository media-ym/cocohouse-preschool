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
        'description' => 'Cocohouse is Mulund and Thane\'s first thematic preschool and daycare, with playgroup, nursery, kindergarten and inclusive special education in Mulund East.',
        'image' => '/assets/images/campus.jpg',
        'google_verification' => '',
        'bing_verification' => '',
        'analytics_id' => '',
        'twitter' => '',
        'facebook' => '',
        'instagram' => '',
        'youtube' => '',
        'linktree' => '',
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
            'title' => '1st Thematic Preschool in Thane | Cocohouse Preschool & Daycare',
            'description' => 'Cocohouse is Mulund and Thane\'s first thematic preschool. Playgroup, Nursery, Junior KG, Senior KG, daycare and inclusive special education in Mulund East.',
            'keywords' => 'preschool in Mulund, daycare Mulund East, thematic preschool Thane, playgroup admission, Cocohouse',
        ],
        'about' => [
            'title' => 'About Us | Cocohouse Preschool & Daycare',
            'description' => 'The story of Cocohouse Preschool in Mulund East — a NEP and NCF 2022 aligned thematic preschool built on the Panchakosha philosophy.',
            'keywords' => 'about Cocohouse, preschool Mulund East, Panchakosha preschool, NEP 2022 preschool',
        ],
        'programs' => [
            'title' => 'Our Programs | Cocohouse Preschool & Daycare',
            'description' => 'Playgroup, Nursery, Junior KG, Senior KG, daycare and inclusive special education at Cocohouse Preschool, Mulund East.',
            'keywords' => 'playgroup Mulund, nursery school, junior KG, senior KG, special education preschool',
        ],
        'daycare' => [
            'title' => 'Daycare in Mulund East | Cocohouse Preschool',
            'description' => 'Flexible daycare in Mulund East for children aged 1.5 to 10 years, with meals, rest, homework help and CCTV. Ask Cocohouse for packages.',
            'keywords' => 'daycare Mulund East, preschool daycare, after school care Mulund, Cocohouse daycare',
        ],
        'gallery' => [
            'title' => 'Gallery | Cocohouse Preschool & Daycare',
            'description' => 'Photos from Cocohouse Preschool & Daycare in Mulund East — classrooms, events and everyday learning.',
            'keywords' => 'Cocohouse gallery, preschool photos Mulund, daycare photos',
        ],
        'blog' => [
            'title' => 'Blogs | Cocohouse Preschool & Daycare',
            'description' => 'Early learning and parenting articles from Cocohouse Preschool & Daycare in Mulund East.',
            'keywords' => 'preschool blog, parenting tips, early learning Mulund',
        ],
        'admissions' => [
            'title' => 'Admissions Open 2026 | Cocohouse Preschool & Daycare',
            'description' => 'Mid-term admissions open for 2026-27 at Cocohouse Preschool and Daycare, Hari Om Nagar. Playgroup, nursery, KG and full day daycare. Flat 60% off. Call 7039152173.',
            'keywords' => 'preschool admission 2026, daycare admission Thane East, playgroup admission, Cocohouse admissions, mid term admission',
        ],
        'contact' => [
            'title' => 'Contact Us | Cocohouse Preschool & Daycare',
            'description' => 'Visit Cocohouse Preschool & Daycare at Millenium Park, Mulund East, or call +91-7039152173 to book a school visit.',
            'keywords' => 'Cocohouse contact, preschool Mulund East address, school visit Mulund',
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
        '@type' => ['Preschool', 'EducationalOrganization'],
        '@id' => rtrim($settings['public_url'], '/') . '/#school',
        'name' => $settings['site_name'],
        'url' => rtrim($settings['public_url'], '/') . '/',
        'telephone' => $settings['phone'],
        'email' => $settings['email'],
        'image' => seo_absolute($settings['image'], $settings['public_url']),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $settings['address'],
            'addressLocality' => 'Mumbai',
            'addressRegion' => 'Maharashtra',
            'postalCode' => '400081',
            'addressCountry' => 'IN',
        ],
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
