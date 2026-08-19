<?php require_once __DIR__ . '/../config/config.php';
require_login();

if (empty($_SESSION['pending_booking'])) {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}
$pb = $_SESSION['pending_booking'];

$stmt = $pdo->prepare('SELECT r.*, rt.name AS type_name, rt.base_price
                        FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id
                        WHERE r.id = ?');
$stmt->execute([$pb['room_id']]);
$room = $stmt->fetch();

// Re-verify availability server-side — never trust what got us here
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM bookings
     WHERE room_id = ? AND status IN ("pending","confirmed","checked_in")
       AND check_in < ? AND check_out > ?'
);
$stmt->execute([$pb['room_id'], $pb['check_out'], $pb['check_in']]);
$conflict = (int)$stmt->fetchColumn() > 0;

$nights = (strtotime($pb['check_out']) - strtotime($pb['check_in'])) / 86400;
$total  = $nights * $room['base_price'];
$_SESSION['pending_booking']['total_price'] = $total; // PHP-calculated, not browser-supplied

require __DIR__ . '/../includes/header.php';
?>
<h1>Review Your Booking</h1>
<?php if ($conflict): ?>
    <p class="error">Sorry, this room was just booked by someone else. Please search again.</p>
    <a href="<?= BASE_URL ?>rooms/index.php">Back to Rooms</a>
<?php else: ?>
    <p><strong><?= htmlspecialchars($room['type_name']) ?></strong> — Room <?= htmlspecialchars($room['room_number']) ?></p>
    <p><?= htmlspecialchars($pb['check_in']) ?> &rarr; <?= htmlspecialchars($pb['check_out']) ?> (<?= $nights ?> nights)</p>
    <p>Guests: <?= (int)$pb['guests'] ?></p>
    <?php if (!empty($pb['special_requests'])): ?>
        <p>Requests: <?= htmlspecialchars($pb['special_requests']) ?></p>
    <?php endif; ?>
    <p class="price">Total: $<?= number_format($total, 2) ?></p>
    <form method="post" action="confirm.php">
        <button type="submit">Confirm Booking</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
