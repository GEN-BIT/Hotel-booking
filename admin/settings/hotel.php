<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$fields = ['hotel_name', 'hotel_address', 'hotel_phone', 'hotel_email', 'check_in_time', 'check_out_time'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        set_setting($pdo, $f, trim($_POST[$f] ?? ''));
    }
    $success = 'Settings saved.';
}

$values = [];
foreach ($fields as $f) $values[$f] = get_setting($pdo, $f);
?>
<h1>Hotel Information</h1>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
    <label>Hotel Name <input type="text" name="hotel_name" value="<?= htmlspecialchars($values['hotel_name']) ?>"></label>
    <label>Address <input type="text" name="hotel_address" value="<?= htmlspecialchars($values['hotel_address']) ?>"></label>
    <label>Phone <input type="text" name="hotel_phone" value="<?= htmlspecialchars($values['hotel_phone']) ?>"></label>
    <label>Email <input type="email" name="hotel_email" value="<?= htmlspecialchars($values['hotel_email']) ?>"></label>
    <label>Check-in Time <input type="time" name="check_in_time" value="<?= htmlspecialchars($values['check_in_time']) ?>"></label>
    <label>Check-out Time <input type="time" name="check_out_time" value="<?= htmlspecialchars($values['check_out_time']) ?>"></label>
    <button type="submit">Save</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
