<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');
require_role_any(['admin', 'staff']);

echo json_encode([
    'rooms' => (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'occupied' => (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn(),
    'bookings_today' => (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE check_in = CURDATE()')->fetchColumn(),
    'pending' => (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
    'revenue' => (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn(),
]);
