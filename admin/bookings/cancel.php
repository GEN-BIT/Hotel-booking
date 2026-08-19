<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND status IN ("pending","confirmed")');
$stmt->execute([$id]);
if ($stmt->rowCount() > 0) {
    $ref = $pdo->prepare('SELECT booking_reference FROM bookings WHERE id = ?');
    $ref->execute([$id]);
    log_activity($pdo, 'booking.cancelled_by_admin', 'Booking ' . $ref->fetchColumn() . ' cancelled by staff');
}
header('Location: view.php?id=' . $id);
exit;
