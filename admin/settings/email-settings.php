<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$fields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email', 'from_name'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        set_setting($pdo, $f, trim($_POST[$f] ?? ''));
    }
    $success = 'Email settings saved.';
}

$values = [];
foreach ($fields as $f) $values[$f] = get_setting($pdo, $f);
?>
<h1>Email Settings</h1>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
    <label>SMTP Host <input type="text" name="smtp_host" value="<?= htmlspecialchars($values['smtp_host']) ?>"></label>
    <label>SMTP Port <input type="number" name="smtp_port" value="<?= htmlspecialchars($values['smtp_port']) ?>"></label>
    <label>SMTP Username <input type="text" name="smtp_username" value="<?= htmlspecialchars($values['smtp_username']) ?>"></label>
    <label>SMTP Password <input type="password" name="smtp_password" value="<?= htmlspecialchars($values['smtp_password']) ?>"></label>
    <label>From Email <input type="email" name="from_email" value="<?= htmlspecialchars($values['from_email']) ?>"></label>
    <label>From Name <input type="text" name="from_name" value="<?= htmlspecialchars($values['from_name']) ?>"></label>
    <button type="submit">Save</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
