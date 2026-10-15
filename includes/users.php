<?php

require_once __DIR__ . '/store.php';

function user_roles(): array
{
    return [
        'admin' => 'Super Admin',
        'subadmin' => 'Sub Admin',
        'editor' => 'Editor',
        'reception' => 'Reception',
    ];
}

function user_editable_roles(): array
{
    return ['subadmin', 'editor', 'reception'];
}

function user_section_choices(): array
{
    return [
        'pages' => 'Pages',
        'seo' => 'SEO',
        'media' => 'Images',
        'gallery' => 'Gallery',
        'enquiries' => 'Enquiries',
        'blog' => 'Blog',
        'users' => 'Users',
    ];
}

function user_default_sections(): array
{
    return [
        'admin' => ['dashboard', 'pages', 'seo', 'media', 'gallery', 'enquiries', 'blog', 'password', 'users'],
        'subadmin' => ['dashboard', 'pages', 'seo', 'media', 'gallery', 'enquiries', 'blog', 'password', 'users'],
        'editor' => ['dashboard', 'pages', 'seo', 'media', 'gallery', 'blog', 'password'],
        'reception' => ['dashboard', 'enquiries', 'password'],
    ];
}

function user_normalize_sections(array $picked): array
{
    $chosen = ['dashboard', 'password'];
    foreach ($picked as $section) {
        $section = (string) $section;
        if (!isset(user_section_choices()[$section]) || in_array($section, $chosen, true)) {
            continue;
        }
        $chosen[] = $section;
    }
    return $chosen;
}

function users_role_map(): array
{
    $defaults = user_default_sections();
    $stored = [];
    if (is_file(users_path())) {
        $data = read_json(users_path(), []);
        $stored = is_array($data['roles'] ?? null) ? $data['roles'] : [];
    }
    $map = [];
    foreach (user_editable_roles() as $role) {
        $picked = $stored[$role] ?? $defaults[$role];
        $map[$role] = user_normalize_sections(is_array($picked) ? $picked : $defaults[$role]);
    }
    return $map;
}

function user_sections(): array
{
    $defaults = user_default_sections();
    $map = users_role_map();
    return [
        'admin' => $defaults['admin'],
        'subadmin' => $map['subadmin'],
        'editor' => $map['editor'],
        'reception' => $map['reception'],
    ];
}

function user_can(string $role, string $section): bool
{
    $allowed = user_sections()[$role] ?? [];
    return in_array($section, $allowed, true);
}

function users_path(): string
{
    return COCO_STORAGE . '/users.json';
}

function users_data(): array
{
    if (!is_file(users_path())) {
        $config = admin_config();
        $username = trim((string) ($config['username'] ?? 'admin'));
        $data = [
            'items' => [[
                'id' => 'user_admin',
                'name' => 'Super Admin',
                'username' => $username !== '' ? $username : 'admin',
                'password_hash' => (string) ($config['password_hash'] ?? ''),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
            ]],
        ];
        users_save($data);
        return $data;
    }
    $data = read_json(users_path(), ['items' => []]);
    $items = [];
    foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $role = (string) ($item['role'] ?? '');
        if (!isset(user_roles()[$role])) {
            $role = 'reception';
        }
        $username = user_clean_username((string) ($item['username'] ?? ''));
        $hash = (string) ($item['password_hash'] ?? '');
        if ($username === '' || $hash === '') {
            continue;
        }
        $items[] = [
            'id' => (string) ($item['id'] ?? ('user_' . bin2hex(random_bytes(3)))),
            'name' => user_clean_name((string) ($item['name'] ?? $username)),
            'username' => $username,
            'password_hash' => $hash,
            'role' => $role,
            'created_at' => (string) ($item['created_at'] ?? ''),
        ];
    }
    return ['items' => $items];
}

function users_save(array $data): void
{
    $current = is_file(users_path()) ? read_json(users_path(), []) : [];
    $defaults = user_default_sections();
    $roles = $data['roles'] ?? ($current['roles'] ?? $defaults);
    $clean = [];
    foreach (user_editable_roles() as $role) {
        $picked = array_key_exists($role, $roles) ? $roles[$role] : ($defaults[$role] ?? []);
        $clean[$role] = user_normalize_sections(is_array($picked) ? $picked : []);
    }
    write_json(users_path(), [
        'roles' => $clean,
        'items' => array_values($data['items'] ?? []),
    ]);
}

function user_clean_name(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', strip_tags($name)) ?? '');
    if (function_exists('mb_substr')) {
        $name = mb_substr($name, 0, 40);
    } else {
        $name = substr($name, 0, 40);
    }
    return $name !== '' ? $name : 'Staff';
}

function user_clean_username(string $username): string
{
    $username = strtolower(trim($username));
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,30}$/', $username)) {
        return '';
    }
    return $username;
}

function user_by_username(string $username): ?array
{
    $username = strtolower(trim($username));
    foreach (users_data()['items'] as $item) {
        if ($item['username'] === $username) {
            return $item;
        }
    }
    return null;
}

function user_check_login(string $username, string $password): ?array
{
    $account = user_by_username($username);
    if (!$account || !password_verify($password, $account['password_hash'])) {
        return null;
    }
    unset($account['password_hash']);
    return $account;
}

function current_admin(): ?array
{
    start_admin_session();
    $username = (string) ($_SESSION['admin_user'] ?? '');
    if ($username === '') {
        return null;
    }
    $account = user_by_username($username);
    if (!$account) {
        return null;
    }
    $_SESSION['admin_role'] = $account['role'];
    unset($account['password_hash']);
    return $account;
}

function user_admin_count(array $items): int
{
    $count = 0;
    foreach ($items as $item) {
        if (($item['role'] ?? '') === 'admin') {
            $count++;
        }
    }
    return $count;
}

function user_sync_owner_password(array $user): void
{
    $config = admin_config();
    if (($user['username'] ?? '') !== (string) ($config['username'] ?? '')) {
        return;
    }
    $config['password_hash'] = (string) ($user['password_hash'] ?? '');
    $config['password_changed'] = true;
    save_admin_config($config);
}
