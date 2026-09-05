<?php require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_role_any(['admin', 'staff']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT room_id, booking_reference FROM bookings WHERE id = ? AND status = "confirmed"');
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row) {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE bookings SET status = "checked_in" WHERE id = ?')->execute([$id]);
    $pdo->prepare('UPDATE rooms SET status = "occupied" WHERE id = ?')->execute([$row['room_id']]);
    $pdo->commit();
    log_activity($pdo, 'booking.checked_in', "Booking {$row['booking_reference']} checked in");
    
    try {
        $notifier = new NotificationService($pdo);
        $notifier->sendCheckInNotification($id);
    } catch (Exception $e) {
        error_log('Check-in notification failed: ' . $e->getMessage());
    }
}
header('Location: view.php?id=' . $id);
exit;
