<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');
require_role_any(['admin', 'staff']);

$stmt = $pdo->query(
    'SELECT u.id, u.full_name, u.email, u.phone FROM users u
     JOIN roles r ON u.role_id = r.id WHERE r.name = "guest"'
);
echo json_encode($stmt->fetchAll());
