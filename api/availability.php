<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = (int)($_GET['guests'] ?? 1);

if (!$checkIn || !$checkOut || $checkOut <= $checkIn) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing dates.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT rt.id, rt.name, rt.base_price, r.id AS room_id, r.room_number
     FROM room_types rt JOIN rooms r ON r.room_type_id = rt.id
     WHERE rt.max_occupancy >= ? AND r.status != "maintenance"
       AND r.id NOT IN (
           SELECT b.room_id FROM bookings b
           WHERE b.status IN ("pending","confirmed","checked_in")
             AND b.check_in < ? AND b.check_out > ?
       )'
);
$stmt->execute([$guests, $checkOut, $checkIn]);
echo json_encode($stmt->fetchAll());
