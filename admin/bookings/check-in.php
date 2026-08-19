<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT room_id FROM bookings WHERE id = ? AND status = "confirmed"');
$stmt->execute([$id]);
$roomId = $stmt->fetchColumn();

if ($roomId) {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE bookings SET status = "checked_in" WHERE id = ?')->execute([$id]);
    $pdo->prepare('UPDATE rooms SET status = "occupied" WHERE id = ?')->execute([$roomId]);
    $pdo->commit();
}
header('Location: view.php?id=' . $id);
exit;
