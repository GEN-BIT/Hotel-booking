<?php require_once __DIR__ . '/../config/config.php';
require_role_any(['admin', 'staff']);
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('dashboard') ?> — <?= trans('site_name') ?></title>
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
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/themes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <script src="<?= BASE_URL ?>assets/js/language.js"></script>
</head>
<body>
<div class="admin-layout">
<aside class="admin-sidebar">
    <a href="<?= BASE_URL ?>admin/dashboard.php" class="logo"><?= trans('dashboard') ?></a>
    <nav>
        <a href="<?= BASE_URL ?>admin/dashboard.php"><?= trans('dashboard') ?></a>
        <a href="<?= BASE_URL ?>admin/rooms/index.php"><?= trans('rooms') ?></a>
        <a href="<?= BASE_URL ?>admin/room-types/index.php"><?= trans('room_types') ?></a>
        <a href="<?= BASE_URL ?>admin/amenities/index.php"><?= trans('amenities') ?></a>
        <a href="<?= BASE_URL ?>admin/services/index.php"><?= trans('services') ?></a>
        <a href="<?= BASE_URL ?>admin/bookings/index.php"><?= trans('bookings') ?></a>
        <a href="<?= BASE_URL ?>admin/guests/index.php"><?= trans('guests') ?></a>
        <a href="<?= BASE_URL ?>admin/payments/index.php"><?= trans('payments') ?></a>
        <a href="<?= BASE_URL ?>admin/coupons/index.php"><?= trans('coupons') ?></a>
        <a href="<?= BASE_URL ?>admin/reviews/index.php"><?= trans('reviews') ?></a>
        <a href="<?= BASE_URL ?>admin/service-orders/index.php"><?= trans('service_orders') ?></a>
        <a href="<?= BASE_URL ?>admin/reports/occupancy.php"><?= trans('occupancy') ?></a>
        <a href="<?= BASE_URL ?>admin/reports/revenue.php"><?= trans('revenue') ?></a>
        <a href="<?= BASE_URL ?>admin/reports/arrivals.php"><?= trans('arrivals') ?></a>
        <a href="<?= BASE_URL ?>admin/reports/departures.php"><?= trans('departures') ?></a>
        <a href="<?= BASE_URL ?>admin/reports/room-performance.php"><?= trans('room_performance') ?></a>
        <a href="<?= BASE_URL ?>admin/reports/cancellations.php"><?= trans('cancellations') ?></a>
        <a href="<?= BASE_URL ?>admin/activity-log.php"><?= trans('activity_log') ?></a>
        <?php if (current_role() === 'admin'): ?>
        <a href="<?= BASE_URL ?>admin/staff/index.php"><?= trans('staff') ?></a>
        <a href="<?= BASE_URL ?>admin/settings/index.php"><?= trans('settings') ?></a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>admin/change-password.php"><?= trans('change_password') ?></a>
        <a href="<?= BASE_URL ?>auth/logout.php"><?= trans('logout') ?></a>
    </nav>
</aside>
<div class="admin-main-wrapper">
<header class="admin-topbar">
    <select id="language-select" class="language-select" aria-label="<?= trans('language') ?>">
        <option value="en" <?= current_lang() === 'en' ? 'selected' : '' ?>><?= trans('english') ?></option>
        <option value="rw" <?= current_lang() === 'rw' ? 'selected' : '' ?>><?= trans('kinyarwanda') ?></option>
        <option value="fr" <?= current_lang() === 'fr' ? 'selected' : '' ?>><?= trans('french') ?></option>
    </select>
    <select id="theme-select" class="theme-select theme-select-top" aria-label="<?= trans('theme') ?>">
        <option value="luxury"><?= trans('luxury') ?></option>
        <option value="dark"><?= trans('dark') ?></option>
    </select>
</header>
<main class="admin-main">