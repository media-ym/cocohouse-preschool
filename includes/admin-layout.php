<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/site.php';

function admin_header(string $title, string $active): void
{
    $flash = take_flash();
    $config = admin_config();
    $account = current_admin();
    $role = (string) ($account['role'] ?? '');
    $nav = [
        'dashboard' => ['Dashboard', '/admin/dashboard.php'],
        'pages' => ['Pages', '/admin/pages.php'],
        'seo' => ['SEO', '/admin/seo.php'],
        'media' => ['Images', '/admin/media.php'],
        'gallery' => ['Gallery', '/admin/gallery.php'],
        'enquiries' => ['Enquiries', '/admin/enquiries.php'],
        'blog' => ['Blog', '/admin/blog.php'],
        'users' => ['Users', '/admin/users.php'],
        'password' => ['Password', '/admin/password.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> | Cocohouse Admin</title>
  <link rel="icon" href="/assets/images/logo.webp">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/admin.css?v=20261016">
</head>
<body>
  <div class="shell">
    <aside class="side">
      <div class="side-brand">
        <a href="/admin/dashboard.php">
          <img src="<?= e(media_src('logo')) ?>" alt="Cocohouse">
          <span><?= e(user_roles()[$role] ?? 'Admin') ?></span>
        </a>
        <button class="side-toggle" type="button" id="adminMenu" aria-label="Open admin menu">Menu</button>
      </div>
      <nav>
        <?php foreach ($nav as $key => $item): ?>
          <?php if (!user_can($role, $key)) { continue; } ?>
          <a href="<?= e($item[1]) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($item[0]) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="side-foot">
        <a href="/" target="_blank" rel="noopener">View website</a>
        <a class="logout" href="/admin/logout.php">Log out</a>
      </div>
    </aside>
    <div class="content">
      <div class="content-bar">
        <strong><?= e($title) ?></strong>
        <div class="bar-side">
          <?php if ($account): ?>
            <span class="who"><?= e($account['name']) ?></span>
          <?php endif; ?>
          <a class="btn btn-logout" href="/admin/logout.php">Log out</a>
          <a class="btn btn-ghost" href="/" target="_blank" rel="noopener">Open site</a>
        </div>
      </div>
      <main class="wrap">
        <?php if ($account && $account['username'] === (string) ($config['username'] ?? '') && empty($config['password_changed'])): ?>
          <div class="banner">This is still the starting password. Change it from the Password page.</div>
        <?php endif; ?>
        <?php if ($flash): ?>
          <div class="flash <?= ($flash['type'] ?? '') === 'error' ? 'error' : 'ok' ?>"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
      </main>
    </div>
  </div>
  <script>
  (function () {
    var button = document.getElementById('adminMenu');
    var side = document.querySelector('.side');
    if (!button || !side) return;
    button.addEventListener('click', function () {
      var open = side.classList.toggle('menu-open');
      button.setAttribute('aria-label', open ? 'Close admin menu' : 'Open admin menu');
      button.textContent = open ? 'Close' : 'Menu';
    });
  })();
  </script>
</body>
</html>
    <?php
}
