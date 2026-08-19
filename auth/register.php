<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';

$error = ''; $devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare(
                'INSERT INTO users (role_id, full_name, email, phone, password_hash, is_verified, verification_token)
                 VALUES ((SELECT id FROM roles WHERE name = "guest"), ?, ?, ?, ?, 0, ?)'
            );
            $stmt->execute([$name, $email, $phone, $hash, $token]);

            $link = BASE_URL . 'auth/verify.php?token=' . $token;
            $sent = send_mail($pdo, $email, 'Verify your account',
                "Hi $name,<br><br>Please verify your account by clicking the link below:<br>
                 <a href=\"$link\">$link</a>");

            if (!$sent) {
                $devLink = $link;
            } else {
                header('Location: ' . BASE_URL . 'auth/login.php?registered=1');
                exit;
            }
        }
    }
}
require __DIR__ . '/../includes/header.php';
?>
<h1>Create an Account</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($devLink): ?>
    <p class="success">Account created! Please verify your email to log in.</p>
    <p class="notice">Email sending isn't configured yet, so here's your verification link directly (dev mode only):<br>
    <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a></p>
<?php endif; ?>
<form method="post">
    <label>Full Name <input type="text" name="full_name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Phone <input type="text" name="phone"></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a>.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
