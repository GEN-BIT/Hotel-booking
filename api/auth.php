<?php require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

echo json_encode([
    'logged_in' => is_logged_in(),
    'role' => current_role(),
    'user_id' => $_SESSION['user_id'] ?? null,
]);
