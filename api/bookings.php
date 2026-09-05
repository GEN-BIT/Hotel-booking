<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.*, r.room_number, rt.name AS type_name FROM bookings b
     JOIN rooms r ON b.room_id = r.id JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.user_id = ? ORDER BY b.check_in DESC'
);
$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetchAll());
