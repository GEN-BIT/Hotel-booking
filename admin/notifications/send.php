<?php require __DIR__ . '/../../includes/admin-header.php';

$guests = $pdo->query(
    'SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name="guest" ORDER BY u.full_name'
)->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$userId || !$title || !$message) {
        $error = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $title, $message]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Send Notification</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Guest
        <select name="user_id" required>
            <option value="">-- Select Guest --</option>
            <?php foreach ($guests as $g): ?>
            <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Title <input type="text" name="title" required></label>
    <label>Message <textarea name="message" required></textarea></label>
    <button type="submit">Send</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
