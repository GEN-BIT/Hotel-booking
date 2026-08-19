<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$pdo->prepare('DELETE FROM room_amenities WHERE amenity_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM amenities WHERE id = ?')->execute([$id]);
header('Location: index.php');
exit;
