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
<h1>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
<ul class="account-menu">
    <li><a href="reservations.php">My Reservations</a> (<?= $upcomingCount ?> upcoming)</li>
    <li><a href="invoices.php">Invoices</a></li>
    <li><a href="notifications.php">Notifications</a> (<?= $unreadCount ?> unread)</li>
    <li><a href="profile.php">Profile</a></li>
    <li><a href="change-password.php">Change Password</a></li>
</ul>
<?php require __DIR__ . '/../includes/footer.php'; ?>
