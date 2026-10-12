<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site.php';

send_security_headers();
start_admin_session();

if (!empty($_SESSION['admin_user'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $config = admin_config();

    if (login_is_locked()) {
        $error = 'Too many attempts. Please wait 15 minutes and try again.';
    } elseif (
        $username !== ''
        && hash_equals((string) ($config['username'] ?? ''), $username)
        && password_verify($password, (string) ($config['password_hash'] ?? ''))
    ) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $username;
        login_succeeded();
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        login_failed();
        $error = login_is_locked()
            ? 'Too many attempts. Please wait 15 minutes and try again.'
            : 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Login | Cocohouse</title>
  <link rel="icon" href="/assets/images/logo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="login-page">
  <div class="login-card">
    <img src="<?= e(media_src('logo')) ?>" alt="Cocohouse">
    <h1>Admin login</h1>
    <p class="lead">Manage enquiries and blog posts.</p>
    <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" autocomplete="username" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary" type="submit">Log in</button>
    </form>
  </div>
</body>
</html>
