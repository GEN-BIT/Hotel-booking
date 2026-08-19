<?php require_once __DIR__ . '/../config/config.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = ''; $success = false;

if (!$token) die('Invalid reset link.');

$stmt = $pdo->prepare('SELECT id, reset_token_expires FROM users WHERE reset_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || $user['reset_token_expires'] < date('Y-m-d H:i:s')) {
    require __DIR__ . '/../includes/header.php';
    echo '<h1>Invalid Link</h1><p class="error">This reset link is invalid or has expired.</p>
          <a href="forgot-password.php">Request a new link</a>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        $success = true;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Reset Password</h1>
<?php if ($success): ?>
    <p class="success">Password updated. You can now log in.</p>
    <a href="login.php">Go to Login</a>
<?php else: ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label>New Password <input type="password" name="password" required></label>
        <label>Confirm Password <input type="password" name="confirm_password" required></label>
        <button type="submit">Reset Password</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
