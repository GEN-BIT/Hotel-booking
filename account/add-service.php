<?php require_once __DIR__ . '/../config/config.php';
require_login();

$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) die('Booking not found.');
if (!in_array($booking['status'], ['pending','confirmed','checked_in'])) {
    die('Services can only be added to an active reservation.');
}

$services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY name')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { if (!verify_csrf($_POST['csrf_token'] ?? '')) { $error = 'Invalid or expired CSRF token. Please try again.'; } else {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ? AND is_active = 1');
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();

    if (!$service) {
        $error = 'Please select a valid service.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO service_orders (booking_id, service_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$bookingId, $serviceId, $quantity, $service['price']]);
        log_activity($pdo, 'service.ordered', "{$service['name']} x$quantity added to booking {$booking['booking_reference']}");
        header('Location: reservation-details.php?id=' . $bookingId . '&service_added=1');
        exit;
    }
}
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Add a Service</h1>
<p>Booking <?= htmlspecialchars($booking['booking_reference']) ?></p>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if (!$services): ?>
    <p>No services are available right now.</p>
<?php else: ?>
<form method="post">
          <?= csrf_field() ?>
    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
    <label>Service
        <select name="service_id" required>
            <?php foreach ($services as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?> — $<?= number_format($s['price'], 2) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Quantity <input type="number" name="quantity" value="1" min="1" required></label>
    <button type="submit">Add to My Reservation</button>
</form>
<?php endif; ?>
<a href="reservation-details.php?id=<?= $bookingId ?>">Back to Reservation</a>
<?php require __DIR__ . '/../includes/footer.php'; ?>
