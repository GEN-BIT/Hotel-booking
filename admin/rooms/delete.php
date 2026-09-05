<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ("pending","confirmed","checked_in")');
$stmt->execute([$id]);

if ((int)$stmt->fetchColumn() > 0) {
    die('Cannot delete: room has active bookings.');
}

$stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
$stmt->execute([$id]);
header('Location: index.php');
exit;
