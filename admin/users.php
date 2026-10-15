<?php

require_once __DIR__ . '/../includes/admin-layout.php';
require_section('users');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $data = users_data();
    $me = current_admin();
    $error = '';
    if ($action === 'add') {
        $name = user_clean_name((string) ($_POST['name'] ?? ''));
        $username = user_clean_username((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'reception');
        if ($username === '') {
            $error = 'Username must be 2–31 letters or numbers, and can include . _ -';
        } elseif (user_by_username($username)) {
            $error = 'That username is already used.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!isset(user_roles()[$role])) {
            $error = 'Choose a role.';
        } else {
            $data['items'][] = [
                'id' => 'user_' . bin2hex(random_bytes(4)),
                'name' => $name,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            users_save($data);
            flash('User added.');
        }
    } elseif ($action === 'save') {
        $id = (string) ($_POST['id'] ?? '');
        $name = user_clean_name((string) ($_POST['name'] ?? ''));
        $role = (string) ($_POST['role'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (!isset(user_roles()[$role])) {
            $error = 'Choose a role.';
        } elseif ($password !== '' && strlen($password) < 8) {
            $error = 'A new password must be at least 8 characters.';
        } else {
            $found = false;
            foreach ($data['items'] as $index => $item) {
                if ($item['id'] !== $id) {
                    continue;
                }
                $found = true;
                $next = $data['items'];
                $next[$index]['role'] = $role;
                if (user_admin_count($next) < 1) {
                    $error = 'Keep at least one Super Admin.';
                    break;
                }
                $data['items'][$index]['name'] = $name;
                $data['items'][$index]['role'] = $role;
                if ($password !== '') {
                    $data['items'][$index]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    user_sync_owner_password($data['items'][$index]);
                }
                break;
            }
            if (!$found && $error === '') {
                $error = 'That user was not found.';
            }
            if ($error === '') {
                users_save($data);
                flash('User updated.');
            }
        }
    } elseif ($action === 'save-roles') {
        $posted = $_POST['roles'] ?? [];
        $roles = [];
        foreach (user_editable_roles() as $role) {
            $picked = is_array($posted[$role] ?? null) ? $posted[$role] : [];
            $roles[$role] = user_normalize_sections($picked);
        }
        $data['roles'] = $roles;
        users_save($data);
        flash('Role access updated.');
    } elseif ($action === 'delete') {
        $id = (string) ($_POST['id'] ?? '');
        $remaining = [];
        $removed = null;
        foreach ($data['items'] as $item) {
            if ($item['id'] === $id) {
                $removed = $item;
                continue;
            }
            $remaining[] = $item;
        }
        if (!$removed) {
            $error = 'That user was not found.';
        } elseif (($me['username'] ?? '') === $removed['username']) {
            $error = 'You cannot remove your own login.';
        } elseif (user_admin_count($remaining) < 1) {
            $error = 'Keep at least one Super Admin.';
        } else {
            users_save(['items' => $remaining]);
            flash('User removed.');
        }
    }
    if ($error !== '') {
        flash($error, 'error');
    }
    header('Location: /admin/users.php');
    exit;
}

$users = users_data()['items'];
$me = current_admin();
admin_header('Users', 'users');
?>
<h1>Users</h1>
<p class="lead">Choose a role from the menu to see what it can open. Everyone with that role gets the same access.</p>

<section class="card">
  <h2>Role access</h2>
  <p class="help" style="margin-top:0;">Pick a role. Dashboard and Password stay on for every role. Super Admin always has every section.</p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save-roles">
    <div class="field">
      <label for="role-access">Role</label>
      <select id="role-access">
        <?php foreach (user_roles() as $key => $label): ?>
          <option value="<?= e($key) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php foreach (user_roles() as $role => $label): ?>
      <div class="role-panel" data-role="<?= e($role) ?>">
        <div class="perm-list">
          <?php foreach (user_section_choices() as $key => $sectionLabel): ?>
            <label class="check">
              <input type="checkbox" name="roles[<?= e($role) ?>][]" value="<?= e($key) ?>" <?= user_can($role, $key) ? 'checked' : '' ?> <?= $role === 'admin' ? 'disabled' : '' ?>>
              <?= e($sectionLabel) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="actions">
      <button class="btn btn-primary" type="submit">Save role access</button>
    </div>
  </form>
</section>

<section class="card">
  <h2>New user</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="split">
      <div class="field">
        <label for="new-name">Name</label>
        <input id="new-name" name="name" maxlength="40" required placeholder="Priya">
      </div>
      <div class="field">
        <label for="new-username">Username</label>
        <input id="new-username" name="username" maxlength="31" required placeholder="priya" autocapitalize="none">
      </div>
    </div>
    <div class="split">
      <div class="field">
        <label for="new-password">Password</label>
        <input id="new-password" name="password" type="password" minlength="8" required autocomplete="new-password">
      </div>
      <div class="field">
        <label for="new-role">Role</label>
        <select id="new-role" name="role">
          <?php foreach (user_roles() as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $key === 'reception' ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Add user</button>
  </form>
</section>

<?php foreach ($users as $user): ?>
  <section class="card">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= e($user['id']) ?>">
      <div class="row" style="margin-bottom:12px;">
        <h2 style="margin:0;"><?= e($user['username']) ?></h2>
        <?php if (($me['username'] ?? '') === $user['username']): ?>
          <span class="badge">You</span>
        <?php endif; ?>
      </div>
      <div class="split">
        <div class="field">
          <label for="name-<?= e($user['id']) ?>">Name</label>
          <input id="name-<?= e($user['id']) ?>" name="name" maxlength="40" required value="<?= e($user['name']) ?>">
        </div>
        <div class="field">
          <label for="role-<?= e($user['id']) ?>">Role</label>
          <select id="role-<?= e($user['id']) ?>" name="role">
            <?php foreach (user_roles() as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $user['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label for="pass-<?= e($user['id']) ?>">New password</label>
        <input id="pass-<?= e($user['id']) ?>" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep the current password">
      </div>
      <div class="actions">
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
    <?php if (($me['username'] ?? '') !== $user['username']): ?>
      <form method="post" style="margin-top:10px;" onsubmit="return confirm('Remove this login?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= e($user['id']) ?>">
        <button class="btn btn-danger" type="submit">Remove</button>
      </form>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<script>
(function () {
  var select = document.getElementById('role-access');
  var panels = document.querySelectorAll('.role-panel');
  if (!select || !panels.length) return;
  function show() {
    panels.forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-role') !== select.value;
    });
  }
  select.addEventListener('change', show);
  show();
})();
</script>
<?php admin_footer();