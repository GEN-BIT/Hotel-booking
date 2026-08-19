<?php require_once __DIR__ . '/../config/config.php';
require_login();

$bookingId = (int)($_POST['booking_id'] ?? $_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking not found.');
}

if (in_array($booking['status'], ['confirmed', 'pending'])) {
    $stmt = $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ?');
    $stmt->execute([$bookingId]);
}

header('Location: ' . BASE_URL . 'account/reservations.php');
exit;
