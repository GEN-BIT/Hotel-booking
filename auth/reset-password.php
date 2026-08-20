<?php require_once __DIR__ . '/../config/config.php';

$extraCSS = [BASE_URL . 'assets/css/auth.css'];
$extraJS = [BASE_URL . 'assets/js/auth.js'];

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = ''; $success = false;

if (!$token) die('Invalid reset link.');

$stmt = $pdo->prepare('SELECT id, reset_token_expires FROM users WHERE reset_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || $user['reset_token_expires'] < date('Y-m-d H:i:s')) {
    require __DIR__ . '/../includes/header.php';
    ?>
    <div class="auth-page">
      <div class="auth-card">
        <div class="auth-panel active" style="width:100%">
          <h2>Invalid Link</h2>
          <p class="auth-subtitle">This reset link is invalid or has expired.</p>
          <p class="error">This reset link is invalid or has expired.</p>
          <a href="login.php?mode=forgot" class="cta" style="text-align:center; margin-top:1rem;">Request a new link</a>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired CSRF token. Please try again.';
    } else {
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
}

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-panel active" style="width:100%">
      <h2>Reset Password</h2>
      <p class="auth-subtitle">Create a new password for your account</p>
      <?php if ($success): ?>
        <p class="success">Password updated. You can now log in.</p>
        <a href="login.php" class="cta" style="text-align:center; margin-top:1rem;">Go to Login</a>
      <?php else: ?>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <label>New Password <input type="password" name="password" required></label>
            <label>Confirm Password <input type="password" name="confirm_password" required></label>
            <button type="submit">Reset Password</button>
        </form>
      <div class="auth-switch">
        <p><a href="login.php">Back to login</a></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
