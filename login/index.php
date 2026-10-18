<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site.php';

send_security_headers();
start_admin_session();

if (current_admin()) {
    header('Location: /admin/dashboard.php');
    exit;
}
unset($_SESSION['admin_user'], $_SESSION['admin_role']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (login_is_locked()) {
        $error = 'Too many attempts. Please wait 15 minutes and try again.';
    } else {
        $account = user_check_login($username, $password);
        if ($account) {
            session_regenerate_id(true);
            $_SESSION['admin_user'] = $account['username'];
            $_SESSION['admin_role'] = $account['role'];
            login_succeeded();
            header('Location: /admin/dashboard.php');
            exit;
        }
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
  <link rel="stylesheet" href="/admin/admin.css?v=20261016">
</head>
<body class="login-page">
  <div class="login-card">
    <img src="<?= e(media_src('logo')) ?>" alt="Cocohouse">
    <h1>Admin login</h1>
    <p class="lead">Manage enquiries and blog posts.</p>
    <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/login/">
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
    <p style="margin-top:16px;font-size:0.75rem;line-height:1.6;color:#6b7280;">
      Grievance Officer: Mrs. Pratheeksha Darak<br>
      <a href="mailto:info@cocohouse.in">info@cocohouse.in</a> · <a href="tel:+917039152173">+91-7039152173</a><br>
      <a href="/privacy-notice">Privacy notice</a> · <a href="/privacy-policy">Privacy policy</a> · <a href="/terms-and-conditions">Terms &amp; Conditions</a>
    </p>
  </div>
</body>
</html>
