<?php require_once __DIR__ . '/../config/config.php';
require_login();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ("pending","confirmed","checked_in")');
$stmt->execute([$_SESSION['user_id']]);
$upcomingCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = (int)$stmt->fetchColumn();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('welcome_name', ['name' => htmlspecialchars($_SESSION['full_name'])]) ?></h1>
<ul class="account-menu">
    <li><a href="reservations.php"><?= trans('my_reservations') ?></a> (<?= $upcomingCount ?> <?= trans('upcoming') ?>)</li>
    <li><a href="wishlist.php"><?= trans('my_wishlist') ?></a></li>
    <li><a href="invoices.php"><?= trans('invoices') ?></a></li>
    <li><a href="notifications.php"><?= trans('notifications_label') ?></a> (<?= $unreadCount ?> <?= trans('unread') ?>)</li>
    <li><a href="profile.php"><?= trans('profile') ?></a></li>
    <li><a href="change-password.php"><?= trans('change_password') ?></a></li>
</ul>
<?php require __DIR__ . '/../includes/footer.php'; ?>
