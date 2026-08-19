<?php require_once __DIR__ . '/../config/config.php';

$token = $_GET['token'] ?? '';
if (!$token) die('Invalid verification link.');

$stmt = $pdo->prepare('SELECT id FROM users WHERE verification_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

require __DIR__ . '/../includes/header.php';

if (!$user) {
    echo '<h1>Verification Failed</h1><p class="error">Invalid or expired verification link.</p>';
} else {
    $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);
    echo '<h1>Email Verified</h1><p class="success">Your account is now verified. You can log in.</p>
          <a href="login.php">Go to Login</a>';
}
require __DIR__ . '/../includes/footer.php';
