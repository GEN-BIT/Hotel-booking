<?php require_once __DIR__ . '/../config/config.php';
require_login();

if (empty($_SESSION['pending_booking'])) {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}
$pb = $_SESSION['pending_booking'];

$stmt = $pdo->prepare('SELECT r.*, rt.name AS type_name, rt.base_price, rt.id AS room_type_id
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

$seasonalPrice = get_seasonal_price($pdo, $room['room_type_id'], $pb['check_in'], $pb['check_out']);
$basePrice = $seasonalPrice !== null ? $seasonalPrice : $room['base_price'];
$subtotal = $nights * $basePrice;
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
            $couponError = trans('coupon_invalid_or_inactive');
        } elseif ($coupon['valid_from'] && $coupon['valid_from'] > date('Y-m-d')) {
            $couponError = trans('coupon_not_active_yet');
        } elseif ($coupon['valid_until'] && $coupon['valid_until'] < date('Y-m-d')) {
            $couponError = trans('coupon_expired');
        } elseif ($coupon['max_uses'] !== null && $coupon['times_used'] >= $coupon['max_uses']) {
            $couponError = trans('coupon_usage_limit');
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

$servicesTotal = 0;
$selectedServices = [];
if (!empty($pb['selected_services'])) {
    $serviceIds = array_keys($pb['selected_services']);
    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($serviceIds);
    $selectedServices = $stmt->fetchAll();
    foreach ($selectedServices as $s) {
        $servicesTotal += $s['price'];
    }
}

$taxRate = (float)get_setting($pdo, 'tax_rate', 0);
$taxName = get_setting($pdo, 'tax_name', 'Tax');
$serviceFee = (float)get_setting($pdo, 'service_fee', 0);
$subtotalAfterDiscount = max(0, $subtotal - $discount);
$taxAmount = $subtotalAfterDiscount * ($taxRate / 100);
$total = $subtotalAfterDiscount + $servicesTotal + $taxAmount + $serviceFee;
$pb['total_price'] = $total;
$pb['discount_amount'] = $discount;
$pb['coupon_id'] = $appliedCoupon['id'] ?? null;
$_SESSION['pending_booking'] = $pb;

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('review_your_booking') ?></h1>
<?php if ($conflict): ?>
    <p class="error"><?= trans('room_no_longer_available') ?></p>
    <a href="<?= BASE_URL ?>rooms/index.php"><?= trans('back_to_rooms') ?></a>
<?php else: ?>
    <p><strong><?= htmlspecialchars($room['type_name']) ?></strong> — <?= trans('room') ?> <?= htmlspecialchars($room['room_number']) ?></p>
    <p><?= htmlspecialchars($pb['check_in']) ?> &rarr; <?= htmlspecialchars($pb['check_out']) ?> (<?= $nights ?> <?= trans('nights') ?>)</p>
    <p><?= trans('guests_label') ?> <?= (int)$pb['guests'] ?></p>
    <?php if (!empty($pb['special_requests'])): ?>
        <p><?= trans('requests_label') ?> <?= htmlspecialchars($pb['special_requests']) ?></p>
    <?php endif; ?>

    <p><?= trans('room_subtotal') ?> <?= format_currency($subtotal) ?></p>
    <?php if ($seasonalPrice !== null): ?>
        <p class="notice"><?= trans('seasonal_rate_applies') ?></p>
    <?php endif; ?>

    <?php if ($selectedServices): ?>
        <h3><?= trans('extra_services') ?></h3>
        <ul>
            <?php foreach ($selectedServices as $s): ?>
                <li><?= htmlspecialchars($s['name']) ?> — <?= format_currency($s['price']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p><?= trans('services_total') ?> <?= format_currency($servicesTotal) ?></p>
    <?php endif; ?>

    <?php if ($couponError): ?><p class="error"><?= htmlspecialchars($couponError) ?></p><?php endif; ?>

    <?php if ($appliedCoupon): ?>
        <p class="success"><?= trans('coupon_applied_success', ['code' => htmlspecialchars($appliedCoupon['code']), 'discount' => format_currency($discount)]) ?>
        &nbsp;<a href="?remove_coupon=1"><?= trans('remove') ?></a></p>
    <?php else: ?>
        <form method="get" class="coupon-form">
            <label><?= trans('coupon_code_label') ?> <input type="text" name="coupon" placeholder="<?= trans('enter_code_placeholder') ?>"></label>
            <button type="submit"><?= trans('apply') ?></button>
        </form>
    <?php endif; ?>

    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1rem; margin:1rem 0;">
        <h3 style="margin:0 0 0.75rem; font-size:1rem;">Price Details</h3>
        <table style="width:100%; border-collapse:collapse;">
            <tr><td style="padding:0.3rem 0; color:var(--color-muted);">Room total (<?= $nights ?> nights)</td><td style="text-align:right;"><?= format_currency($subtotal) ?></td></tr>
            <?php if ($discount > 0): ?><tr><td style="padding:0.3rem 0; color:var(--color-success);">Discount</td><td style="text-align:right; color:var(--color-success);">-<?= format_currency($discount) ?></td></tr><?php endif; ?>
            <?php if ($servicesTotal > 0): ?><tr><td style="padding:0.3rem 0; color:var(--color-muted);">Services</td><td style="text-align:right;"><?= format_currency($servicesTotal) ?></td></tr><?php endif; ?>
            <?php if ($taxAmount > 0): ?><tr><td style="padding:0.3rem 0; color:var(--color-muted);"><?= htmlspecialchars($taxName) ?> (<?= number_format($taxRate, 2) ?>%)</td><td style="text-align:right;"><?= format_currency($taxAmount) ?></td></tr><?php endif; ?>
            <?php if ($serviceFee > 0): ?><tr><td style="padding:0.3rem 0; color:var(--color-muted);">Service Fee</td><td style="text-align:right;"><?= format_currency($serviceFee) ?></td></tr><?php endif; ?>
            <tr style="border-top:2px solid var(--color-border);"><td style="padding:0.5rem 0; font-weight:700;">Total</td><td style="text-align:right; font-weight:700; font-size:1.1rem; color:var(--color-primary);"><?= format_currency($total) ?></td></tr>
        </table>
    </div>

    <p class="price"><?= trans('total_price_label') ?> <?= format_currency($total) ?></p>
    <form method="post" action="confirm.php">
          <?= csrf_field() ?>
        <button type="submit"><?= trans('confirm_booking') ?></button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
