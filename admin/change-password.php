<?php require_once __DIR__ . '/../config/config.php';
require_role_any(['admin', 'staff']);

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { if (!verify_csrf($_POST['csrf_token'] ?? '')) { $error = 'Invalid or expired CSRF token. Please try again.'; } else {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $_SESSION['user_id']]);
        session_regenerate_id(true);
        $success = 'Password changed successfully.';
    }
}
}

require __DIR__ . '/../includes/admin-header.php';
?>
<h1>Change Password</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Current Password <input type="password" name="current_password" required></label>
    <label>New Password <input type="password" name="new_password" required></label>
    <label>Confirm New Password <input type="password" name="confirm_password" required></label>
    <button type="submit">Change Password</button>
</form>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
