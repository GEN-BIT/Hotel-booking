<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$stmt = $pdo->query('SELECT id, name, description, base_price, max_occupancy FROM room_types ORDER BY base_price');
echo json_encode($stmt->fetchAll());
