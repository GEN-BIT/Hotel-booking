<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('UPDATE payments SET status = "refunded" WHERE id = ? AND status = "paid"');
$stmt->execute([$id]);
if ($stmt->rowCount() > 0) {
    $info = $pdo->prepare(
        'SELECT p.amount, b.booking_reference FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE p.id = ?'
    );
    $info->execute([$id]);
    $row = $info->fetch();
    log_activity($pdo, 'payment.refunded', 'Refund of $' . number_format($row['amount'], 2) . " issued for booking {$row['booking_reference']}");
}
header('Location: view.php?id=' . $id);
exit;
