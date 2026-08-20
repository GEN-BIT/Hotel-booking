<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/notifications.php';

$extraCSS = [BASE_URL . 'assets/css/auth.css'];
$extraJS = [BASE_URL . 'assets/js/auth.js'];

$mode = $_GET['mode'] ?? 'login';
$error = '';
$success = '';
$devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired CSRF token. Please try again.';
    } else {
        $formType = $_POST['form_type'] ?? 'login';

        if ($formType === 'register') {
            if (!check_rate_limit('register_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 3, 600)) {
                $error = 'Too many registration attempts. Please try again later.';
            } else {
                $name = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'guest';

                if (!$name || !$email || !$password) {
                    $error = trans('please_fill_required_fields');
                } elseif (!in_array($role, ['guest', 'staff'])) {
                    $error = trans('invalid_role_selected');
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = trans('invalid_email_address');
                } elseif (strlen($password) < 8) {
                    $error = trans('password_at_least_8');
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = trans('account_email_exists');
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $token = bin2hex(random_bytes(32));
                        $roleId = $pdo->prepare('SELECT id FROM roles WHERE name = ?')->execute([$role]) ? $pdo->fetchColumn() : $pdo->prepare('SELECT id FROM roles WHERE name = "guest"')->fetchColumn();
                        $approvalStatus = ($role === 'staff' || $role === 'admin') ? 'pending' : 'approved';

                        $stmt = $pdo->prepare(
                            'INSERT INTO users (role_id, full_name, email, phone, password_hash, is_verified, verification_token, approval_status)
                             VALUES (?, ?, ?, ?, ?, 0, ?, ?)'
                        );
                        $stmt->execute([$roleId, $name, $email, $phone, $hash, $token, $approvalStatus]);

                        if ($role === 'guest') {
                            $link = BASE_URL . 'auth/verify.php?token=' . $token;
                            $notifier = new NotificationService($pdo);
                            $result = $notifier->notify(
                                $roleId,
                                trans('verify_your_account'),
                                "Hi $name, please verify your account by clicking: $link",
                                $email,
                                trans('verify_your_account'),
                                "<p>Hi $name,</p><p>Please verify your account by clicking the link below:</p><p><a href=\"$link\">$link</a></p>"
                            );
                            
                            if (!$result['email'] && !$result['email_error']) {
                                $devLink = $link;
                            } else {
                                header('Location: ' . BASE_URL . 'auth/login.php?registered=1');
                                exit;
                            }
                        } else {
                            header('Location: ' . BASE_URL . 'auth/login.php?registered=1&pending=1');
                            exit;
                        }
                    }
                }
            }
        } elseif ($formType === 'forgot') {
            if (!check_rate_limit('forgot_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 600)) {
                $success = trans('forgot_success_msg');
            } else {
                $email = trim($_POST['email'] ?? '');
                $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                $success = trans('forgot_success_msg');

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
                    $stmt->execute([$token, $expires, $user['id']]);

                    $link = BASE_URL . 'auth/reset-password.php?token=' . $token;
                    $notifier = new NotificationService($pdo);
                    $notifier->notify(
                        $user['id'],
                        'Reset your password',
                        "Hi {$user['full_name']}, click below to reset your password (valid 1 hour): $link",
                        $email,
                        'Reset your password',
                        "<p>Hi {$user['full_name']},</p><p>Click below to reset your password (valid 1 hour):</p><p><a href=\"$link\">$link</a></p>"
                    );
                    
                    $success = trans('forgot_success_msg');
                }
            }
        } else {
            if (!check_rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 300)) {
                $error = 'Too many login attempts. Please try again later.';
            } else {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                $stmt = $pdo->prepare(
                    'SELECT u.id, u.full_name, u.password_hash, u.is_verified, u.approval_status, r.name AS role
                     FROM users u JOIN roles r ON u.role_id = r.id
                     WHERE u.email = ?'
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    if (!$user['is_verified']) {
                        $error = trans('please_verify_email');
                    } elseif ($user['approval_status'] === 'pending') {
                        $error = trans('account_pending_approval');
                    } elseif ($user['approval_status'] === 'rejected') {
                        $error = trans('account_rejected');
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in_at'] = time();

                        if ($user['role'] === 'admin' || $user['role'] === 'staff') {
                            header('Location: ' . BASE_URL . 'admin/dashboard.php');
                        } else {
                            header('Location: ' . BASE_URL . 'account/index.php');
                        }
                        exit;
                    }
                } else {
                    $error = trans('invalid_email_or_password');
                }
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-slider" id="authSlider">

      <div class="auth-panel <?= $mode === 'login' ? 'active' : '' ?>" id="loginPanel">
        <h2><?= trans('welcome_back') ?></h2>
        <p class="auth-subtitle"><?= trans('sign_in_to_continue') ?></p>
        <?php if (isset($_GET['registered'])): ?>
          <p class="success"><?= trans('account_created_please_login') ?></p>
        <?php endif; ?>
        <?php if ($error && $mode === 'login'): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="login">
          <label><?= trans('email') ?> <input type="email" name="email" required autofocus></label>
          <label><?= trans('password') ?> <input type="password" name="password" required></label>
          <button type="submit"><?= trans('sign_in_button') ?></button>
        </form>
        <div class="auth-switch">
          <p><a onclick="showForgot()"><?= trans('forgot_password') ?></a></p>
          <p><?= trans('no_account') ?> <a onclick="showPanel('registerPanel')"><?= trans('create_account') ?></a></p>
        </div>
      </div>

      <div class="auth-panel <?= $mode === 'register' ? 'active' : '' ?>" id="registerPanel">
        <h2><?= trans('create_account') ?></h2>
        <p class="auth-subtitle"><?= trans('join_us_for_luxury') ?></p>
        <?php if ($error && $mode === 'register'): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success && $mode === 'register'): ?>
          <p class="success"><?= htmlspecialchars($success) ?></p>
          <?php if ($devLink): ?>
            <p class="notice"><?= trans('email_sending_not_configured') ?><br>
            <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a></p>
          <?php endif; ?>
        <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="register">
          <label><?= trans('full_name') ?> <input type="text" name="full_name" required></label>
          <label><?= trans('email') ?> <input type="email" name="email" required></label>
          <label><?= trans('phone') ?> <input type="text" name="phone"></label>
          <label><?= trans('password') ?> <input type="password" name="password" required></label>
          <label><?= trans('register_as') ?>
            <select name="role" required>
              <option value="guest"><?= trans('guest') ?></option>
              <option value="staff"><?= trans('staff') ?></option>
            </select>
          </label>
          <button type="submit"><?= trans('create_account_button') ?></button>
        </form>
        <div class="auth-switch">
          <p><?= trans('already_have_account') ?> <a onclick="showPanel('loginPanel')"><?= trans('login_here') ?></a></p>
        </div>
        <?php endif; ?>
      </div>

    </div>

    <div class="auth-forgot <?= $mode === 'forgot' ? 'active' : '' ?>" id="forgotOverlay">
      <h2><?= trans('reset_password') ?></h2>
      <p class="auth-subtitle"><?= trans('we_will_send_reset_link') ?></p>
      <?php if ($success && $mode === 'forgot'): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php if ($devLink): ?>
          <p class="notice"><?= trans('email_sending_not_configured') ?><br>
          <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a></p>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($error && $mode === 'forgot'): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="forgot">
          <label><?= trans('email') ?> <input type="email" name="email" required autofocus></label>
          <button type="submit"><?= trans('send_reset_link') ?></button>
        </form>
      <?php endif; ?>
      <div class="auth-switch">
        <p><a onclick="hideForgot()"><?= trans('back_to_login') ?></a></p>
      </div>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>