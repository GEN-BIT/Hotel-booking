<?php require_once __DIR__ . '/../config/config.php';

$checkIn  = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests   = (int)($_GET['guests'] ?? 1);
$results  = [];
$error    = '';

$today = date('Y-m-d');
if (!$checkIn || !$checkOut) {
    $error = 'Please provide both check-in and check-out dates.';
} elseif ($checkIn < $today) {
    $error = 'Check-in date cannot be in the past.';
} elseif ($checkOut <= $checkIn) {
    $error = 'Check-out date must be after check-in date.';
} else {
    // Room types that can hold the party size, with at least one physical
    // room not booked for any overlapping date range.
    $stmt = $pdo->prepare(
        'SELECT rt.*, r.id AS room_id, r.room_number
         FROM room_types rt
         JOIN rooms r ON r.room_type_id = rt.id
         WHERE rt.max_occupancy >= ?
           AND r.status != "maintenance"
           AND r.id NOT IN (
               SELECT b.room_id FROM bookings b
               WHERE b.status IN ("pending","confirmed","checked_in")
                 AND b.check_in < ? AND b.check_out > ?
           )
         GROUP BY rt.id'
    );
    $stmt->execute([$guests, $checkOut, $checkIn]);
    $results = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Available Rooms</h1>
<p><?= htmlspecialchars($checkIn) ?> &rarr; <?= htmlspecialchars($checkOut) ?>, <?= $guests ?> guest(s)</p>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php elseif (!$results): ?>
    <p>No rooms available for those dates.</p>
<?php else: ?>
<div class="room-grid">
<?php foreach ($results as $r): ?>
    <div class="room-card">
        <h2><?= htmlspecialchars($r['name']) ?> — Room <?= htmlspecialchars($r['room_number']) ?></h2>
        <p class="price">$<?= number_format($r['base_price'], 2) ?> / night</p>
        <a href="<?= BASE_URL ?>booking/select-room.php?room_id=<?= (int)$r['room_id'] ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= $guests ?>">
            Book This Room
        </a>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
