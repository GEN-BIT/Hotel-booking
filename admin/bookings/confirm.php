<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('UPDATE bookings SET status = "confirmed" WHERE id = ? AND status = "pending"');
$stmt->execute([$id]);
if ($stmt->rowCount() > 0) {
    $ref = $pdo->prepare('SELECT booking_reference FROM bookings WHERE id = ?');
    $ref->execute([$id]);
    log_activity($pdo, 'booking.confirmed', 'Booking ' . $ref->fetchColumn() . ' confirmed by staff');
}
header('Location: view.php?id=' . $id);
exit;
