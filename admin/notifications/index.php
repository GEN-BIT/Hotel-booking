<?php require __DIR__ . '/../../includes/admin-header.php';

$stmt = $pdo->query(
    'SELECT n.*, u.full_name FROM notifications n JOIN users u ON n.user_id = u.id
     ORDER BY n.created_at DESC LIMIT 100'
);
$notifications = $stmt->fetchAll();
?>
<h1>Notifications Sent</h1>
<a href="send.php" class="cta">+ Send Notification</a>
<table class="data-table">
    <tr><th>Guest</th><th>Title</th><th>Read</th><th>Sent</th></tr>
    <?php foreach ($notifications as $n): ?>
    <tr>
        <td><?= htmlspecialchars($n['full_name']) ?></td>
        <td><?= htmlspecialchars($n['title']) ?></td>
        <td><?= $n['is_read'] ? 'Yes' : 'No' ?></td>
        <td><?= htmlspecialchars($n['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
