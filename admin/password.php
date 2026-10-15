<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_section('password');

$error = '';
$account = current_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = (string) ($_POST['current'] ?? '');
    $next = (string) ($_POST['next'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    $stored = user_by_username((string) ($account['username'] ?? ''));
    if (!$stored || !password_verify($current, (string) ($stored['password_hash'] ?? ''))) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($next) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($next !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $data = users_data();
        foreach ($data['items'] as $index => $item) {
            if ($item['username'] !== $stored['username']) {
                continue;
            }
            $data['items'][$index]['password_hash'] = password_hash($next, PASSWORD_DEFAULT);
            user_sync_owner_password($data['items'][$index]);
            break;
        }
        users_save($data);
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