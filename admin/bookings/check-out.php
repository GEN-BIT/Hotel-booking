<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT room_id, booking_reference FROM bookings WHERE id = ? AND status = "checked_in"');
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row) {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE bookings SET status = "checked_out" WHERE id = ?')->execute([$id]);
    $pdo->prepare('UPDATE rooms SET status = "cleaning" WHERE id = ?')->execute([$row['room_id']]);
    $pdo->commit();
    log_activity($pdo, 'booking.checked_out', "Booking {$row['booking_reference']} checked out");
}
header('Location: view.php?id=' . $id);
exit;
