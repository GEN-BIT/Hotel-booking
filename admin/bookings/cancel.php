<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND status IN ("pending","confirmed")')->execute([$id]);
header('Location: view.php?id=' . $id);
exit;
