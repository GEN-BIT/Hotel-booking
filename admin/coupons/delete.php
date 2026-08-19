<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT code FROM coupons WHERE id = ?');
$stmt->execute([$id]);
$code = $stmt->fetchColumn();

$pdo->prepare('DELETE FROM coupons WHERE id = ?')->execute([$id]);
if ($code) log_activity($pdo, 'coupon.deleted', "Coupon $code deleted");
header('Location: index.php');
exit;
