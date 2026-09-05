<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_type_id = ?');
$stmt->execute([$id]);

if ((int)$stmt->fetchColumn() > 0) {
    die('Cannot delete: rooms still use this type. Reassign or delete them first.');
}

$pdo->prepare('DELETE FROM room_amenities WHERE room_type_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM room_types WHERE id = ?')->execute([$id]);
header('Location: index.php');
exit;
