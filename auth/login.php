<?php require_once __DIR__ . '/../config/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT u.id, u.full_name, u.password_hash, u.is_verified, r.name AS role
         FROM users u JOIN roles r ON u.role_id = r.id
         WHERE u.email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (!$user['is_verified']) {
            $error = 'Please verify your email before logging in. Check your inbox for the verification link.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin' || $user['role'] === 'staff') {
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'account/index.php');
            }
            exit;
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
require __DIR__ . '/../includes/header.php';
?>
<h1>Login</h1>
<?php if (isset($_GET['registered'])): ?><p class="success">Account created — please log in.</p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit">Login</button>
</form>
<p><a href="forgot-password.php">Forgot your password?</a></p>
<p>No account? <a href="register.php">Register here</a>.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
