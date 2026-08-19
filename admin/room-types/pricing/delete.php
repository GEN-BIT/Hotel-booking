<?php
require __DIR__ . '/../../../includes/admin-header.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT room_type_id FROM room_type_pricing WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
$roomTypeId = $row ? (int)$row['room_type_id'] : 0;

if ($id) {
    $pdo->prepare('DELETE FROM room_type_pricing WHERE id = ?')->execute([$id]);
}
header('Location: index.php?room_type_id=' . $roomTypeId);
exit;
