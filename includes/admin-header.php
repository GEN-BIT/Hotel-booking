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
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/themes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
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
        <a href="<?= BASE_URL ?>admin/coupons/index.php">Coupons</a>
        <a href="<?= BASE_URL ?>admin/reviews/index.php">Reviews</a>
        <a href="<?= BASE_URL ?>admin/notifications/index.php">Notifications</a>
        <a href="<?= BASE_URL ?>admin/reports/occupancy.php">Occupancy</a>
        <a href="<?= BASE_URL ?>admin/reports/revenue.php">Revenue</a>
        <a href="<?= BASE_URL ?>admin/reports/arrivals.php">Arrivals</a>
        <a href="<?= BASE_URL ?>admin/reports/departures.php">Departures</a>
        <a href="<?= BASE_URL ?>admin/reports/room-performance.php">Room Performance</a>
        <a href="<?= BASE_URL ?>admin/reports/cancellations.php">Cancellations</a>
        <a href="<?= BASE_URL ?>admin/activity-log.php">Activity Log</a>
        <?php if (current_role() === 'admin'): ?>
        <?php
        $pendingCount = (int)$pdo->query('SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ("staff","admin") AND u.approval_status = "pending"')->fetchColumn();
        $staffLabel = 'Staff' . ($pendingCount > 0 ? ' <span style="color:#ff8a93;">(' . $pendingCount . ')</span>' : '');
        ?>
        <a href="<?= BASE_URL ?>admin/staff/index.php"><?= $staffLabel ?></a>
        <a href="<?= BASE_URL ?>admin/settings/index.php">Settings</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>admin/change-password.php">Change Password</a>
        <a href="<?= BASE_URL ?>admin/profile.php">My Profile</a>
        <a href="<?= BASE_URL ?>auth/logout.php">Logout</a>
    </nav>
        <div class="theme-switcher">
            <select id="theme-select" class="theme-select" aria-label="Theme">
                <option value="luxury">Luxury</option>
                <option value="dark">Dark</option>
            </select>
        </div>
</aside>
<main class="admin-main">
