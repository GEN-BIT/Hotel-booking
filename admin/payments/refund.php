<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$pdo->prepare('UPDATE payments SET status = "refunded" WHERE id = ? AND status = "paid"')->execute([$id]);
header('Location: view.php?id=' . $id);
exit;
