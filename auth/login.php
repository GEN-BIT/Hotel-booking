<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';

$extraCSS = [BASE_URL . 'assets/css/auth.css'];
$extraJS = [BASE_URL . 'assets/js/auth.js'];

$mode = $_GET['mode'] ?? 'login';
$error = '';
$success = '';
$devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'login';

    if ($formType === 'register') {
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
    } elseif ($formType === 'forgot') {
        $email = trim($_POST['email'] ?? '');
        $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $success = 'If an account exists for that email, a reset link has been sent.';

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
    } else {
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
}

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-slider" id="authSlider">

      <div class="auth-panel <?= $mode === 'login' ? 'active' : '' ?>" id="loginPanel">
        <h2>Welcome Back</h2>
        <p class="auth-subtitle">Sign in to continue your stay</p>
        <?php if (isset($_GET['registered'])): ?>
          <p class="success">Account created — please log in.</p>
        <?php endif; ?>
        <?php if ($error && $mode === 'login'): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="form_type" value="login">
          <label>Email <input type="email" name="email" required autofocus></label>
          <label>Password <input type="password" name="password" required></label>
          <button type="submit">Sign In</button>
        </form>
        <div class="auth-switch">
          <p><a onclick="showForgot()">Forgot password?</a></p>
          <p>No account? <a onclick="showPanel('registerPanel')">Create account</a></p>
        </div>
      </div>

      <div class="auth-panel <?= $mode === 'register' ? 'active' : '' ?>" id="registerPanel">
        <h2>Create Account</h2>
        <p class="auth-subtitle">Join us for a luxurious experience</p>
        <?php if ($error && $mode === 'register'): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success && $mode === 'register'): ?>
          <p class="success"><?= htmlspecialchars($success) ?></p>
          <?php if ($devLink): ?>
            <p class="notice">Email sending isn't configured yet, so here's your verification link directly (dev mode only):<br>
            <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a></p>
          <?php endif; ?>
        <?php else: ?>
        <form method="post">
          <input type="hidden" name="form_type" value="register">
          <label>Full Name <input type="text" name="full_name" required></label>
          <label>Email <input type="email" name="email" required></label>
          <label>Phone <input type="text" name="phone"></label>
          <label>Password <input type="password" name="password" required></label>
          <button type="submit">Create Account</button>
        </form>
        <div class="auth-switch">
          <p>Already have an account? <a onclick="showPanel('loginPanel')">Login here</a></p>
        </div>
        <?php endif; ?>
      </div>

    </div>

    <div class="auth-forgot <?= $mode === 'forgot' ? 'active' : '' ?>" id="forgotOverlay">
      <h2>Reset Password</h2>
      <p class="auth-subtitle">We'll send you a reset link</p>
      <?php if ($success && $mode === 'forgot'): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php if ($devLink): ?>
          <p class="notice">Email sending isn't configured yet, so here's your reset link directly (dev mode only):<br>
          <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a></p>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($error && $mode === 'forgot'): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="form_type" value="forgot">
          <label>Email <input type="email" name="email" required autofocus></label>
          <button type="submit">Send Reset Link</button>
        </form>
      <?php endif; ?>
      <div class="auth-switch">
        <p><a onclick="hideForgot()">Back to login</a></p>
      </div>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
