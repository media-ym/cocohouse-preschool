<?php

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/users.php';

function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure,
    ]);
    session_name('coco_admin');
    session_start();
}

function admin_config(): array
{
    static $config = null;
    if ($config === null) {
        $loaded = include COCO_STORAGE . '/config.php';
        $config = is_array($loaded) ? $loaded : [];
    }
    return $config;
}

function save_admin_config(array $config): void
{
    $export = var_export($config, true);
    file_put_contents(COCO_STORAGE . '/config.php', "<?php\nreturn {$export};\n", LOCK_EX);
}

function csrf_token(): string
{
    start_admin_session();
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_admin_session();
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(400);
        exit('This form expired. Go back and try again.');
    }
}

function require_login(): void
{
    send_security_headers();
    start_admin_session();
    if (!current_admin()) {
        $_SESSION = [];
        header('Location: /login/');
        exit;
    }
}

function require_section(string $section): void
{
    require_login();
    $account = current_admin();
    if (!$account || !user_can((string) $account['role'], $section)) {
        flash('That section is not available for your role.', 'error');
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function flash(string $message, string $type = 'ok'): void
{
    start_admin_session();
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    start_admin_session();
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function client_key(): string
{
    return hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function login_is_locked(): bool
{
    $guard = read_json(COCO_STORAGE . '/login-guard.json', []);
    $row = $guard[client_key()] ?? null;
    if (!is_array($row)) {
        return false;
    }
    return (int) ($row['until'] ?? 0) > time();
}

function login_failed(): void
{
    $guard = read_json(COCO_STORAGE . '/login-guard.json', []);
    $key = client_key();
    $row = is_array($guard[$key] ?? null) ? $guard[$key] : ['count' => 0, 'until' => 0];
    if ((int) $row['until'] > time()) {
        return;
    }
    if ((int) $row['until'] !== 0 && (int) $row['until'] <= time()) {
        $row = ['count' => 0, 'until' => 0];
    }
    $row['count'] = (int) $row['count'] + 1;
    if ($row['count'] >= 5) {
        $row['until'] = time() + 15 * 60;
        $row['count'] = 0;
    }
    $guard[$key] = $row;
    write_json(COCO_STORAGE . '/login-guard.json', $guard);
}

function login_succeeded(): void
{
    $guard = read_json(COCO_STORAGE . '/login-guard.json', []);
    unset($guard[client_key()]);
    write_json(COCO_STORAGE . '/login-guard.json', $guard);
}
