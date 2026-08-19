<?php require __DIR__ . '/../../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT b.*, u.full_name, u.email, r.room_number, rt.name AS type_name
                        FROM bookings b
                        JOIN users u ON b.user_id = u.id
                        JOIN rooms r ON b.room_id = r.id
                        JOIN room_types rt ON r.room_type_id = rt.id
                        WHERE b.id = ?');
$stmt->execute([$id]);
$booking = $stmt->fetch();
if (!$booking) die('Booking not found.');

$stmt = $pdo->prepare('SELECT full_name FROM booking_guests WHERE booking_id = ?');
$stmt->execute([$id]);
$guests = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<h1>Booking <?= htmlspecialchars($booking['booking_reference']) ?></h1>
<p>Guest: <?= htmlspecialchars($booking['full_name']) ?> (<?= htmlspecialchars($booking['email']) ?>)</p>
<p>Room: <?= htmlspecialchars($booking['type_name']) ?> — <?= htmlspecialchars($booking['room_number']) ?></p>
<p>Dates: <?= htmlspecialchars($booking['check_in']) ?> &rarr; <?= htmlspecialchars($booking['check_out']) ?></p>
<p>Total: $<?= number_format($booking['total_price'], 2) ?></p>
<?php if ($guests): ?><p>Additional guests: <?= htmlspecialchars(implode(', ', $guests)) ?></p><?php endif; ?>
<?php if (!empty($booking['special_requests'])): ?><p>Requests: <?= htmlspecialchars($booking['special_requests']) ?></p><?php endif; ?>
<p>Status: <span class="status status-<?= htmlspecialchars($booking['status']) ?>"><?= htmlspecialchars($booking['status']) ?></span></p>

<div class="action-buttons">
<?php if ($booking['status'] === 'pending'): ?>
    <a href="confirm.php?id=<?= $id ?>" class="cta">Confirm Booking</a>
<?php endif; ?>
<?php if ($booking['status'] === 'confirmed'): ?>
    <a href="check-in.php?id=<?= $id ?>" class="cta">Check In</a>
<?php endif; ?>
<?php if ($booking['status'] === 'checked_in'): ?>
    <a href="check-out.php?id=<?= $id ?>" class="cta">Check Out</a>
<?php endif; ?>
<?php if (in_array($booking['status'], ['pending','confirmed'])): ?>
    <a href="cancel.php?id=<?= $id ?>" class="cta cta-danger" onclick="return confirm('Cancel this booking?')">Cancel</a>
<?php endif; ?>
</div>
<a href="index.php">Back to Bookings</a>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
