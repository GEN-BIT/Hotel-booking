<?php require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT b.*, rt.name AS type_name, r.room_number, rt.base_price
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
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
<p class="price">Total: $<?= number_format($booking['total_price'], 2) ?></p>
<p>Status: <span class="status status-<?= htmlspecialchars($booking['status']) ?>"><?= htmlspecialchars($booking['status']) ?></span></p>

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
