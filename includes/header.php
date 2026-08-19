<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
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
</head>
<body>
<header class="site-header">
    <a href="<?= BASE_URL ?>rooms/index.php" class="logo">Hotel Booking</a>
    <nav>
        <a href="<?= BASE_URL ?>rooms/index.php">Rooms</a>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>account/index.php">My Account</a>
            <a href="<?= BASE_URL ?>auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>auth/login.php">Login</a>
            <a href="<?= BASE_URL ?>auth/register.php">Register</a>
        <?php endif; ?>
            <div class="theme-switcher">
                <select id="theme-select" class="theme-select" aria-label="Theme">
                    <option value="luxury">Luxury</option>
                    <option value="minimalist">Minimalist</option>
                    <option value="vibrant">Vibrant</option>
                    <option value="dark">Dark</option>
                </select>
            </div>
    </nav>
</header>
<main class="site-main">
