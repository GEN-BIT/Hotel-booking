<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('UPDATE service_orders SET status = "fulfilled" WHERE id = ? AND status = "requested"');
$stmt->execute([$id]);
if ($stmt->rowCount() > 0) {
    log_activity($pdo, 'service.fulfilled', "Service order #$id marked fulfilled");
}
header('Location: index.php');
exit;
