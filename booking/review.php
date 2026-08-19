<?php require_once __DIR__ . '/../config/config.php';
require_login();

if (empty($_SESSION['pending_booking'])) {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}
$pb = $_SESSION['pending_booking'];

$stmt = $pdo->prepare('SELECT r.*, rt.name AS type_name, rt.base_price
                        FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id
                        WHERE r.id = ?');
$stmt->execute([$pb['room_id']]);
$room = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM bookings
     WHERE room_id = ? AND status IN ("pending","confirmed","checked_in")
       AND check_in < ? AND check_out > ?'
);
$stmt->execute([$pb['room_id'], $pb['check_out'], $pb['check_in']]);
$conflict = (int)$stmt->fetchColumn() > 0;

$nights = (strtotime($pb['check_out']) - strtotime($pb['check_in'])) / 86400;
$subtotal = $nights * $room['base_price'];
$couponError = '';

if (isset($_GET['coupon'])) {
    $code = strtoupper(trim($_GET['coupon']));
    if ($code === '') {
        unset($pb['coupon_code']);
    } else {
        $cstmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ?');
        $cstmt->execute([$code]);
        $coupon = $cstmt->fetch();

        if (!$coupon || !$coupon['is_active']) {
            $couponError = 'Invalid or inactive coupon code.';
        } elseif ($coupon['valid_from'] && $coupon['valid_from'] > date('Y-m-d')) {
            $couponError = 'This coupon is not active yet.';
        } elseif ($coupon['valid_until'] && $coupon['valid_until'] < date('Y-m-d')) {
            $couponError = 'This coupon has expired.';
        } elseif ($coupon['max_uses'] !== null && $coupon['times_used'] >= $coupon['max_uses']) {
            $couponError = 'This coupon has reached its usage limit.';
        } else {
            $pb['coupon_code'] = $code;
        }
    }
}

if (isset($_GET['remove_coupon'])) {
    unset($pb['coupon_code']);
}

$discount = 0;
$appliedCoupon = null;
if (!empty($pb['coupon_code'])) {
    $cstmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1');
    $cstmt->execute([$pb['coupon_code']]);
    $appliedCoupon = $cstmt->fetch();

    if ($appliedCoupon) {
        $discount = $appliedCoupon['discount_type'] === 'percentage'
            ? $subtotal * ($appliedCoupon['discount_value'] / 100)
            : min($appliedCoupon['discount_value'], $subtotal);
    } else {
        unset($pb['coupon_code']);
    }
}

$total = max(0, $subtotal - $discount);
$pb['total_price'] = $total;
$pb['discount_amount'] = $discount;
$pb['coupon_id'] = $appliedCoupon['id'] ?? null;
$_SESSION['pending_booking'] = $pb;

require __DIR__ . '/../includes/header.php';
?>
<h1>Review Your Booking</h1>
<?php if ($conflict): ?>
    <p class="error">Sorry, this room was just booked by someone else. Please search again.</p>
    <a href="<?= BASE_URL ?>rooms/index.php">Back to Rooms</a>
<?php else: ?>
    <p><strong><?= htmlspecialchars($room['type_name']) ?></strong> — Room <?= htmlspecialchars($room['room_number']) ?></p>
    <p><?= htmlspecialchars($pb['check_in']) ?> &rarr; <?= htmlspecialchars($pb['check_out']) ?> (<?= $nights ?> nights)</p>
    <p>Guests: <?= (int)$pb['guests'] ?></p>
    <?php if (!empty($pb['special_requests'])): ?>
        <p>Requests: <?= htmlspecialchars($pb['special_requests']) ?></p>
    <?php endif; ?>

    <p>Subtotal: $<?= number_format($subtotal, 2) ?></p>

    <?php if ($couponError): ?><p class="error"><?= htmlspecialchars($couponError) ?></p><?php endif; ?>

    <?php if ($appliedCoupon): ?>
        <p class="success">Coupon "<?= htmlspecialchars($appliedCoupon['code']) ?>" applied: -$<?= number_format($discount, 2) ?>
        &nbsp;<a href="?remove_coupon=1">Remove</a></p>
    <?php else: ?>
        <form method="get" class="coupon-form">
            <label>Coupon Code <input type="text" name="coupon" placeholder="Enter code"></label>
            <button type="submit">Apply</button>
        </form>
    <?php endif; ?>

    <p class="price">Total: $<?= number_format($total, 2) ?></p>
    <form method="post" action="confirm.php">
        <button type="submit">Confirm Booking</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
