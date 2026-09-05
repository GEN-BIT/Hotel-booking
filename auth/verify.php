<?php require_once __DIR__ . '/../config/config.php';

$extraCSS = ['/hotel-booking/assets/css/auth.css'];

$token = $_GET['token'] ?? '';
if (!$token) die('Invalid verification link.');

$stmt = $pdo->prepare('SELECT id FROM users WHERE verification_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-panel active" style="width:100%">
      <?php if (!$user): ?>
        <h2>Verification Failed</h2>
        <p class="auth-subtitle">Invalid or expired verification link.</p>
        <p class="error">Invalid or expired verification link.</p>
        <a href="login.php" class="cta" style="text-align:center; margin-top:1rem;">Go to Login</a>
      <?php else: ?>
        <h2>Email Verified</h2>
        <p class="auth-subtitle">Your account is now verified</p>
        <?php
        $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?');
        $stmt->execute([$user['id']]);
        ?>
        <p class="success">Your account is now verified. You can log in.</p>
        <a href="login.php" class="cta" style="text-align:center; margin-top:1rem;">Go to Login</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
