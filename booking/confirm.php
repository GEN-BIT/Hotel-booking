<?php require_once __DIR__ . '/../config/config.php';
require_login();

if (empty($_SESSION['pending_booking']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}
$pb = $_SESSION['pending_booking'];

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

    $reference = 'HB-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    $stmt = $pdo->prepare(
        'INSERT INTO bookings (booking_reference, user_id, room_id, check_in, check_out, num_guests, total_price, status, special_requests)
         VALUES (?, ?, ?, ?, ?, ?, ?, "confirmed", ?)'
    );
    $stmt->execute([
        $reference, $_SESSION['user_id'], $pb['room_id'],
        $pb['check_in'], $pb['check_out'], $pb['guests'],
        $pb['total_price'], $pb['special_requests'] ?? null,
    ]);
    $bookingId = $pdo->lastInsertId();

    if (!empty($pb['extra_guests'])) {
        $gstmt = $pdo->prepare('INSERT INTO booking_guests (booking_id, full_name) VALUES (?, ?)');
        foreach ($pb['extra_guests'] as $name) {
            if (trim($name) !== '') $gstmt->execute([$bookingId, trim($name)]);
        }
    }

    $pdo->commit();
    unset($_SESSION['pending_booking']);
    $_SESSION['last_booking_reference'] = $reference;
    $_SESSION['last_booking_id'] = $bookingId;
    header('Location: ' . BASE_URL . 'booking/payment.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    require __DIR__ . '/../includes/header.php';
    echo '<h1>Booking Failed</h1><p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="' . BASE_URL . 'rooms/index.php">Back to Rooms</a>';
    require __DIR__ . '/../includes/footer.php';
}
