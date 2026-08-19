<?php require_once __DIR__ . '/../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([(int)$_POST['mark_read'], $_SESSION['user_id']]);
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Notifications</h1>
<?php if (!$notifications): ?>
    <p>No notifications.</p>
<?php else: ?>
<ul class="notification-list">
<?php foreach ($notifications as $n): ?>
    <li class="<?= $n['is_read'] ? 'read' : 'unread' ?>">
        <strong><?= htmlspecialchars($n['title']) ?></strong>
        <p><?= htmlspecialchars($n['message']) ?></p>
        <small><?= htmlspecialchars($n['created_at']) ?></small>
        <?php if (!$n['is_read']): ?>
        <form method="post">
            <input type="hidden" name="mark_read" value="<?= (int)$n['id'] ?>">
            <button type="submit" class="link-btn">Mark as read</button>
        </form>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
