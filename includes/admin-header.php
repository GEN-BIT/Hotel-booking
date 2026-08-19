<?php require_once __DIR__ . '/../config/config.php';
require_role_any(['admin', 'staff']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Hotel Booking System</title>
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
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=2">
</head>
<body>
<div class="admin-layout">
<aside class="admin-sidebar">
    <a href="<?= BASE_URL ?>admin/dashboard.php" class="logo">Admin Panel</a>
    <nav>
        <a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>admin/rooms/index.php">Rooms</a>
        <a href="<?= BASE_URL ?>admin/room-types/index.php">Room Types</a>
        <a href="<?= BASE_URL ?>admin/amenities/index.php">Amenities</a>
        <a href="<?= BASE_URL ?>admin/bookings/index.php">Bookings</a>
        <a href="<?= BASE_URL ?>admin/guests/index.php">Guests</a>
        <a href="<?= BASE_URL ?>admin/payments/index.php">Payments</a>
        <a href="<?= BASE_URL ?>admin/reviews/index.php">Reviews</a>
        <a href="<?= BASE_URL ?>admin/reports/occupancy.php">Reports</a>
        <?php if (current_role() === 'admin'): ?>
        <a href="<?= BASE_URL ?>admin/staff/index.php">Staff</a>
        <a href="<?= BASE_URL ?>admin/settings/index.php">Settings</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>auth/logout.php">Logout</a>
    </nav>
        <div class="theme-switcher">
            <select id="theme-select" class="theme-select" aria-label="Theme">
                <option value="luxury">Luxury</option>
                <option value="minimalist">Minimalist</option>
                <option value="vibrant">Vibrant</option>
                <option value="dark">Dark</option>
            </select>
        </div>
</aside>
<main class="admin-main">
