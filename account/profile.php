<?php require_once __DIR__ . '/../config/config.php';
require_login();

$error = ''; $success = '';

$stmt = $pdo->prepare('SELECT full_name, email, phone FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$email) {
        $error = trans('please_fill_required_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = trans('invalid_email_address');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $name;
        $user['full_name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $success = trans('profile_updated');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('my_profile') ?></h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
    <label><?= trans('full_name') ?> <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required></label>
    <label><?= trans('email') ?> <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></label>
    <label><?= trans('phone') ?> <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></label>
    <button type="submit"><?= trans('save_changes') ?></button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
