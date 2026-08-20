<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/payment-gateway.php';
require_once __DIR__ . '/../includes/notifications.php';
require_login();

if (empty($_SESSION['pending_booking']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}
$pb = $_SESSION['pending_booking'];

$depositPercentage = (int)get_setting($pdo, 'deposit_percentage', 0);
$depositAmount = $depositPercentage > 0 ? ($pb['total_price'] * $depositPercentage / 100) : $pb['total_price'];

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM bookings
         WHERE room_id = ? AND status IN ("pending","confirmed","checked_in")
           AND check_in < ? AND check_out > ? FOR UPDATE'
    );
    $stmt->execute([$pb['room_id'], $pb['check_out'], $pb['check_in']]);
    if ((int)$stmt->fetchColumn() > 0) {
        throw new Exception('Room no longer available.');
    }

    $couponId = $pb['coupon_id'] ?? null;
    $discountAmount = $pb['discount_amount'] ?? 0;

    if ($couponId) {
        $cstmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ? AND is_active = 1 FOR UPDATE');
        $cstmt->execute([$couponId]);
        $coupon = $cstmt->fetch();

        if (!$coupon || ($coupon['max_uses'] !== null && $coupon['times_used'] >= $coupon['max_uses'])) {
            throw new Exception('Your coupon is no longer valid. Please review your booking again.');
        }
        $pdo->prepare('UPDATE coupons SET times_used = times_used + 1 WHERE id = ?')->execute([$couponId]);
    }

    $reference = 'HB-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    $initialStatus = $depositPercentage > 0 ? 'pending' : 'confirmed';

    $stmt = $pdo->prepare(
        'INSERT INTO bookings (booking_reference, user_id, room_id, check_in, check_out, num_guests, total_price, coupon_id, discount_amount, deposit_amount, amount_paid, payment_status, status, special_requests)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, "pending", ?, ?)'
    );
    $stmt->execute([
        $reference, $_SESSION['user_id'], $pb['room_id'],
        $pb['check_in'], $pb['check_out'], $pb['guests'],
        $pb['total_price'], $couponId, $discountAmount, $depositAmount,
        $initialStatus,
        $pb['special_requests'] ?? null,
    ]);
    $bookingId = $pdo->lastInsertId();

    if (!empty($pb['extra_guests'])) {
        $gstmt = $pdo->prepare('INSERT INTO booking_guests (booking_id, full_name) VALUES (?, ?)');
        foreach ($pb['extra_guests'] as $name) {
            if (trim($name) !== '') $gstmt->execute([$bookingId, trim($name)]);
        }
    }

    if (!empty($pb['selected_services'])) {
        $serviceIds = array_keys($pb['selected_services']);
        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        $stmt = $pdo->prepare("SELECT id, price FROM services WHERE id IN ($placeholders) AND is_active = 1");
        $stmt->execute($serviceIds);
        $activeServices = $stmt->fetchAll();
        $bsstmt = $pdo->prepare('INSERT INTO booking_services (booking_id, service_id, quantity, unit_price, subtotal) VALUES (?, ?, 1, ?, ?)');
        foreach ($activeServices as $s) {
            $bsstmt->execute([$bookingId, $s['id'], $s['price'], $s['price']]);
        }
    }

    $pdo->commit();
    $logMsg = "Booking $reference created, {$pb['check_in']} to {$pb['check_out']}";
    if ($couponId) $logMsg .= " (coupon {$pb['coupon_code']} applied, -$" . number_format($discountAmount, 2) . ")";
    if ($depositPercentage > 0) $logMsg .= " (deposit required: $depositPercentage%)";
    log_activity($pdo, 'booking.created', $logMsg);

    try {
        $notifier = new NotificationService($pdo);
        $notifier->sendBookingConfirmationToGuest($bookingId);
        $notifier->sendBookingNotificationToAdmin($bookingId);
    } catch (Exception $e) {
        error_log('Notification failed: ' . $e->getMessage());
    }

    unset($_SESSION['pending_booking']);
    $_SESSION['last_booking_reference'] = $reference;
    $_SESSION['last_booking_id'] = $bookingId;
    $_SESSION['pending_deposit'] = $depositAmount;
    header('Location: ' . BASE_URL . 'booking/payment.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    require __DIR__ . '/../includes/header.php';
    echo '<h1>Booking Failed</h1><p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="' . BASE_URL . 'rooms/index.php">Back to Rooms</a>';
    require __DIR__ . '/../includes/footer.php';
}
