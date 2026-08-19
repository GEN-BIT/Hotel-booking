<?php require_once __DIR__ . '/../config/config.php';
require_login();

$bookingId = $_SESSION['last_booking_id'] ?? 0;
if (!$bookingId) {
    header('Location: ' . BASE_URL . 'account/reservations.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();
if (!$booking) die('Booking not found.');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = "paid"');
$stmt->execute([$bookingId]);
if ((int)$stmt->fetchColumn() > 0) {
    header('Location: ' . BASE_URL . 'booking/success.php');
    exit;
}

$enabledMethods = array_filter(explode(',', get_setting($pdo, 'enabled_payment_methods', 'card,cash,bank_transfer,mobile_money')));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';
    if (!in_array($method, $enabledMethods, true)) {
        $error = 'Please select a valid payment method.';
    } else {
        $ref = strtoupper(bin2hex(random_bytes(5)));
        $stmt = $pdo->prepare(
            'INSERT INTO payments (booking_id, amount, method, status, transaction_ref, paid_at)
             VALUES (?, ?, ?, "paid", ?, NOW())'
        );
        $stmt->execute([$bookingId, $booking['total_price'], $method, $ref]);
        header('Location: ' . BASE_URL . 'booking/success.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Payment</h1>
<p>Booking <?= htmlspecialchars($booking['booking_reference']) ?> — Total due: $<?= number_format($booking['total_price'], 2) ?></p>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<p class="notice">This is a simulated payment for demo purposes — no real card processing occurs.</p>
<form method="post">
    <fieldset>
        <legend>Select Payment Method</legend>
        <?php foreach ($enabledMethods as $m): ?>
        <label class="checkbox"><input type="radio" name="method" value="<?= htmlspecialchars($m) ?>" required> <?= ucfirst(str_replace('_',' ',$m)) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <button type="submit">Pay Now</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
