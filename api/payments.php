<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required.']);
    exit;
}

$bookingId = (int)($_GET['booking_id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT p.* FROM payments p JOIN bookings b ON p.booking_id = b.id
     WHERE p.booking_id = ? AND b.user_id = ?'
);
$stmt->execute([$bookingId, $_SESSION['user_id']]);
echo json_encode($stmt->fetchAll());
