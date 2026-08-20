<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking System</title>
    <script>
      (function(){
        try {
          var t = localStorage.getItem('hb-theme') || 'luxury';
          document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
      })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/themes.css?v=2">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=2">
    <script src="<?= BASE_URL ?>assets/js/language.js"></script>
    <?php if (!empty($extraCSS)): ?>
      <?php foreach ((array)$extraCSS as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
      <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <a href="<?= BASE_URL ?>rooms/index.php" class="logo"><?= trans('site_name') ?></a>
    <nav>
        <a href="<?= BASE_URL ?>rooms/index.php"><?= trans('rooms') ?></a>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>account/index.php"><?= trans('my_account') ?></a>
            <a href="<?= BASE_URL ?>auth/logout.php"><?= trans('logout') ?></a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>auth/login.php"><?= trans('sign_in') ?></a>
        <?php endif; ?>
        <select id="language-select" class="language-select" aria-label="<?= trans('language') ?>">
            <option value="en" <?= current_lang() === 'en' ? 'selected' : '' ?>><?= trans('english') ?></option>
            <option value="rw" <?= current_lang() === 'rw' ? 'selected' : '' ?>><?= trans('kinyarwanda') ?></option>
            <option value="fr" <?= current_lang() === 'fr' ? 'selected' : '' ?>><?= trans('french') ?></option>
        </select>
        <div class="theme-switcher">
            <select id="theme-select" class="theme-select" aria-label="<?= trans('theme') ?>">
                <option value="luxury"><?= trans('luxury') ?></option>
                <option value="dark"><?= trans('dark') ?></option>
            </select>
        </div>
    </nav>
</header>
<main class="site-main">
