<?php require_once __DIR__ . '/../config/config.php';
require_login();

$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT b.*, r.room_type_id, rt.name AS type_name FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.id = ? AND b.user_id = ?'
);
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) die('Booking not found.');
if ($booking['status'] !== 'checked_out') die('You can only review completed stays.');

$stmt = $pdo->prepare('SELECT id FROM reviews WHERE booking_id = ?');
$stmt->execute([$bookingId]);
if ($stmt->fetch()) die('You have already reviewed this booking.');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating from 1 to 5.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO reviews (booking_id, user_id, room_type_id, rating, comment) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$bookingId, $_SESSION['user_id'], $booking['room_type_id'], $rating, $comment]);
        header('Location: reservations.php?reviewed=1');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Review Your Stay</h1>
<p><?= htmlspecialchars($booking['type_name']) ?> — <?= htmlspecialchars($booking['check_in']) ?> &rarr; <?= htmlspecialchars($booking['check_out']) ?></p>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
    <fieldset>
        <legend>Rating</legend>
        <?php for ($i = 5; $i >= 1; $i--): ?>
        <label class="checkbox"><input type="radio" name="rating" value="<?= $i ?>" required> <?= $i ?> star<?= $i > 1 ? 's' : '' ?></label>
        <?php endfor; ?>
    </fieldset>
    <label>Comment <textarea name="comment" rows="4"></textarea></label>
    <button type="submit">Submit Review</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
