<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = (string) ($_POST['current'] ?? '');
    $next = (string) ($_POST['next'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    $config = admin_config();
    if (!password_verify($current, (string) ($config['password_hash'] ?? ''))) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($next) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($next !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $config['password_hash'] = password_hash($next, PASSWORD_DEFAULT);
        $config['password_changed'] = true;
        save_admin_config($config);
        flash('Password updated.');
        header('Location: /admin/password.php');
        exit;
    }
}

admin_header('Password', 'password');
?>
<h1>Change password</h1>
<p class="lead">Use a password only the school team knows.</p>
<?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
<form class="card" method="post" style="max-width:480px;">
  <?= csrf_field() ?>
  <div class="field">
    <label for="current">Current password</label>
    <input id="current" name="current" type="password" autocomplete="current-password" required>
  </div>
  <div class="field">
    <label for="next">New password</label>
    <input id="next" name="next" type="password" autocomplete="new-password" minlength="8" required>
  </div>
  <div class="field">
    <label for="confirm">Confirm new password</label>
    <input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="8" required>
  </div>
  <button class="btn btn-primary" type="submit">Update password</button>
</form>
<?php admin_footer();