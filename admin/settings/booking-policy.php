<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$fields = ['min_stay_nights', 'max_stay_nights', 'cancellation_window_hours', 'advance_booking_days'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        set_setting($pdo, $f, trim($_POST[$f] ?? ''));
    }
    $success = 'Policy saved.';
}

$values = [];
foreach ($fields as $f) $values[$f] = get_setting($pdo, $f);
?>
<h1>Booking Policy</h1>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Minimum Stay (nights) <input type="number" name="min_stay_nights" value="<?= htmlspecialchars($values['min_stay_nights']) ?>"></label>
    <label>Maximum Stay (nights) <input type="number" name="max_stay_nights" value="<?= htmlspecialchars($values['max_stay_nights']) ?>"></label>
    <label>Free Cancellation Window (hours before check-in) <input type="number" name="cancellation_window_hours" value="<?= htmlspecialchars($values['cancellation_window_hours']) ?>"></label>
    <label>Max Advance Booking (days) <input type="number" name="advance_booking_days" value="<?= htmlspecialchars($values['advance_booking_days']) ?>"></label>
    <button type="submit">Save</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
