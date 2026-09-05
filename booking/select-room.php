<?php require_once __DIR__ . '/../config/config.php';
require_login();

$roomId   = (int)($_GET['room_id'] ?? 0);
$checkIn  = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests   = (int)($_GET['guests'] ?? 1);

$stmt = $pdo->prepare('SELECT r.*, rt.name AS type_name, rt.base_price, rt.max_occupancy
                        FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id
                        WHERE r.id = ?');
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room || !$checkIn || !$checkOut || $checkOut <= $checkIn) {
    die('Invalid booking request.');
}

$_SESSION['pending_booking'] = [
    'room_id'   => $roomId,
    'check_in'  => $checkIn,
    'check_out' => $checkOut,
    'guests'    => $guests,
];

header('Location: ' . BASE_URL . 'booking/guest-info.php');
exit;
