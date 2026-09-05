<?php require_once __DIR__ . '/../config/config.php';

$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$checkIn  = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$available = null;

if ($checkIn && $checkOut && $checkOut > $checkIn) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM rooms r
         WHERE r.room_type_id = ? AND r.status != "maintenance"
           AND r.id NOT IN (
               SELECT b.room_id FROM bookings b
               WHERE b.status IN ("pending","confirmed","checked_in")
                 AND b.check_in < ? AND b.check_out > ?
           )'
    );
    $stmt->execute([$roomTypeId, $checkOut, $checkIn]);
    $available = (int)$stmt->fetchColumn() > 0;
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Check Availability</h1>
<form method="get">
    <input type="hidden" name="room_type_id" value="<?= $roomTypeId ?>">
    <label>Check-in <input type="date" name="check_in" value="<?= htmlspecialchars($checkIn) ?>" required></label>
    <label>Check-out <input type="date" name="check_out" value="<?= htmlspecialchars($checkOut) ?>" required></label>
    <button type="submit">Check</button>
</form>

<?php if ($available === true): ?>
    <p class="success">Available for those dates.</p>
    <a href="<?= BASE_URL ?>rooms/search.php?check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=1">See bookable rooms</a>
<?php elseif ($available === false): ?>
    <p class="error">No rooms of this type are available for those dates.</p>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
