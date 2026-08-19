<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';

$info = ''; $devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Same message either way, so we don't reveal which emails are registered
    $info = 'If an account exists for that email, a reset link has been sent.';

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
        $stmt->execute([$token, $expires, $user['id']]);

        $link = BASE_URL . 'auth/reset-password.php?token=' . $token;
        $sent = send_mail($pdo, $email, 'Reset your password',
            "Hi {$user['full_name']},<br><br>Click below to reset your password (valid 1 hour):<br>
             <a href=\"$link\">$link</a>");

        if (!$sent) {
            $devLink = $link;
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Forgot Password</h1>
<?php if ($info): ?><p class="success"><?= htmlspecialchars($info) ?></p><?php endif; ?>
<?php if ($devLink): ?>
    <p class="notice">Email sending isn't configured yet, so here's your reset link directly (dev mode only):<br>
    <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a></p>
<?php endif; ?>
<form method="post">
    <label>Email <input type="email" name="email" required></label>
    <button type="submit">Send Reset Link</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
