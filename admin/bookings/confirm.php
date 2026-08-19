<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$pdo->prepare('UPDATE bookings SET status = "confirmed" WHERE id = ? AND status = "pending"')->execute([$id]);
header('Location: view.php?id=' . $id);
exit;
