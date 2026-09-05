<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$success = '';
$methods = ['card', 'cash', 'bank_transfer', 'mobile_money'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = $_POST['enabled_methods'] ?? [];
    set_setting($pdo, 'enabled_payment_methods', implode(',', $enabled));
    set_setting($pdo, 'deposit_percentage', trim($_POST['deposit_percentage'] ?? ''));
    $success = 'Payment settings saved.';
}

$enabledMethods = explode(',', get_setting($pdo, 'enabled_payment_methods', implode(',', $methods)));
$deposit = get_setting($pdo, 'deposit_percentage', '0');
?>
<h1>Payment Settings</h1>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <fieldset>
        <legend>Enabled Payment Methods</legend>
        <?php foreach ($methods as $m): ?>
        <label class="checkbox"><input type="checkbox" name="enabled_methods[]" value="<?= $m ?>" <?= in_array($m, $enabledMethods) ? 'checked' : '' ?>> <?= str_replace('_',' ',ucfirst($m)) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <label>Deposit Required (%) <input type="number" name="deposit_percentage" value="<?= htmlspecialchars($deposit) ?>"></label>
    <button type="submit">Save</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
