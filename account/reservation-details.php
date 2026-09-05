<?php require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT b.*, rt.name AS type_name, r.room_number, rt.base_price, c.code AS coupon_code
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     LEFT JOIN coupons c ON b.coupon_id = c.id
     WHERE b.id = ? AND b.user_id = ?'
);
$stmt->execute([$id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    die('Reservation not found.');
}

$stmt = $pdo->prepare('SELECT full_name FROM booking_guests WHERE booking_id = ?');
$stmt->execute([$id]);
$guests = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC');
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT so.*, s.name FROM service_orders so JOIN services s ON so.service_id = s.id
     WHERE so.booking_id = ? ORDER BY so.created_at DESC'
);
$stmt->execute([$id]);
$serviceOrders = $stmt->fetchAll();
$servicesTotal = 0;
foreach ($serviceOrders as $so) { $servicesTotal += $so['unit_price'] * $so['quantity']; }

require __DIR__ . '/../includes/header.php';
?>
<h1>Reservation <?= htmlspecialchars($booking['booking_reference']) ?></h1>
<p><strong><?= htmlspecialchars($booking['type_name']) ?></strong> — Room <?= htmlspecialchars($booking['room_number']) ?></p>
<p><?= htmlspecialchars($booking['check_in']) ?> &rarr; <?= htmlspecialchars($booking['check_out']) ?></p>
<p>Guests: <?= (int)$booking['num_guests'] ?></p>
<?php if ($guests): ?>
    <p>Additional guests: <?= htmlspecialchars(implode(', ', $guests)) ?></p>
<?php endif; ?>
<?php if (!empty($booking['special_requests'])): ?>
    <p>Requests: <?= htmlspecialchars($booking['special_requests']) ?></p>
<?php endif; ?>
<?php if ($booking['discount_amount'] > 0): ?>
    <p>Coupon<?= $booking['coupon_code'] ? ' (' . htmlspecialchars($booking['coupon_code']) . ')' : '' ?> discount: -$<?= number_format($booking['discount_amount'], 2) ?></p>
<?php endif; ?>
<p class="price">Room Total: $<?= number_format($booking['total_price'], 2) ?></p>
<p>Status: <span class="status status-<?= htmlspecialchars($booking['status']) ?>"><?= htmlspecialchars($booking['status']) ?></span></p>

<h3>Additional Services</h3>
<?php if (isset($_GET['service_added'])): ?><p class="success">Service added!</p><?php endif; ?>
<?php if (!$serviceOrders): ?>
    <p>No services added yet.</p>
<?php else: ?>
<table class="data-table">
    <tr><th>Service</th><th>Qty</th><th>Cost</th><th>Status</th></tr>
    <?php foreach ($serviceOrders as $so): ?>
    <tr>
        <td><?= htmlspecialchars($so['name']) ?></td>
        <td><?= (int)$so['quantity'] ?></td>
        <td>$<?= number_format($so['unit_price'] * $so['quantity'], 2) ?></td>
        <td><?= htmlspecialchars($so['status']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<p>Services subtotal: $<?= number_format($servicesTotal, 2) ?></p>
<?php endif; ?>
<?php if (in_array($booking['status'], ['pending','confirmed','checked_in'])): ?>
<a href="add-service.php?booking_id=<?= $id ?>" class="cta">+ Add a Service</a>
<?php endif; ?>

<h3>Payments</h3>
<?php if (!$payments): ?>
    <p>No payments recorded yet.</p>
<?php else: ?>
<table class="data-table">
    <tr><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
    <?php foreach ($payments as $p): ?>
    <tr>
        <td>$<?= number_format($p['amount'], 2) ?></td>
        <td><?= htmlspecialchars($p['method']) ?></td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td><?= htmlspecialchars($p['paid_at'] ?? '—') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<a href="reservations.php">Back to Reservations</a>
<?php require __DIR__ . '/../includes/footer.php'; ?>
