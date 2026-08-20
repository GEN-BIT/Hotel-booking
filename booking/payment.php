<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/payment-gateway.php';
require_once __DIR__ . '/../includes/notifications.php';
require_login();

$bookingId = $_SESSION['last_booking_id'] ?? 0;
if (!$bookingId) {
    header('Location: ' . BASE_URL . 'account/reservations.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();
if (!$booking) die('Booking not found.');

if ($booking['payment_status'] === 'paid') {
    header('Location: ' . BASE_URL . 'booking/success.php');
    exit;
}

$gateway = PaymentManager::getActiveGateway($pdo);
$enabledMethods = array_filter(explode(',', get_setting($pdo, 'enabled_payment_methods', 'card,cash,bank_transfer,mobile_money')));
$error = '';
$success = '';
$paymentResult = null;

$balance = PaymentManager::getPaymentBalance($bookingId);
$depositPercentage = (int)get_setting($pdo, 'deposit_percentage', 0);
$depositAmount = $depositPercentage > 0 ? ($booking['total_price'] * $depositPercentage / 100) : $booking['total_price'];
$amountDue = $depositAmount - $balance['net_paid'];
$amountDue = max(0, $amountDue);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired CSRF token. Please try again.';
    } else {
        $method = $_POST['method'] ?? '';
        if (!in_array($method, $enabledMethods, true)) {
            $error = 'Please select a valid payment method.';
        } else {
            $paymentManager = new PaymentManager($pdo, $gateway);
            $paymentResult = $paymentManager->createPayment($bookingId, $amountDue, $method, [
                'booking_reference' => $booking['booking_reference'],
                'customer_email' => $_SESSION['user_email'] ?? '',
                'customer_name' => $_SESSION['full_name'] ?? '',
            ]);
            
            if ($paymentResult['gateway_result']['success']) {
                $verifyResult = $paymentManager->verifyPayment($paymentResult['payment_id']);
                
                if ($verifyResult['success']) {
                    $success = 'Payment of $' . number_format($amountDue, 2) . ' received successfully!';
                    log_activity($pdo, 'payment.received', 
                        "Payment of $" . number_format($amountDue, 2) . " received for booking {$booking['booking_reference']} via $method");
                    
                    try {
                        $notifier = new NotificationService($pdo);
                        $notifier->sendPaymentReceiptToGuest($paymentResult['payment_id']);
                    } catch (Exception $e) {
                        error_log('Payment receipt notification failed: ' . $e->getMessage());
                    }
                    
                    if ($balance['balance'] <= $amountDue) {
                        header('Location: ' . BASE_URL . 'booking/success.php');
                        exit;
                    }
                } else {
                    $error = 'Payment verification failed. Please try again.';
                }
            } else {
                $error = $paymentResult['gateway_result']['message'] ?? 'Payment failed. Please try again.';
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('payment_title') ?></h1>
<p><?= trans('booking_label') ?> <?= htmlspecialchars($booking['booking_reference']) ?></p>

<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem; margin: 1.5rem 0;">
    <h3 style="margin: 0 0 1rem;">Payment Summary</h3>
    <table style="width: 100%;">
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);"><?= trans('total_price_label') ?></td>
            <td style="padding: 0.5rem 0; text-align: right; font-weight: 600;">$<?= number_format($booking['total_price'], 2) ?></td>
        </tr>
        <?php if ($booking['discount_amount'] > 0): ?>
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);"><?= trans('discount_applied') ?></td>
            <td style="padding: 0.5rem 0; text-align: right; color: var(--color-success);">-$<?= number_format($booking['discount_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($depositPercentage > 0): ?>
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);"><?= trans('deposit_required') ?> (<?= $depositPercentage ?>%)</td>
            <td style="padding: 0.5rem 0; text-align: right; font-weight: 600;">$<?= number_format($depositAmount, 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);"><?= trans('amount_paid') ?></td>
            <td style="padding: 0.5rem 0; text-align: right;">$<?= number_format($balance['net_paid'], 2) ?></td>
        </tr>
        <tr style="border-top: 2px solid var(--color-border);">
            <td style="padding: 0.75rem 0; font-weight: 700; font-size: 1.1rem;"><?= trans('balance_due') ?></td>
            <td style="padding: 0.75rem 0; text-align: right; font-weight: 700; font-size: 1.1rem; color: var(--color-accent);">$<?= number_format($amountDue, 2) ?></td>
        </tr>
    </table>
</div>

<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<?php if ($amountDue > 0): ?>
<form method="post">
    <?= csrf_field() ?>
    <fieldset>
        <legend><?= trans('select_payment_method') ?></legend>
        <?php foreach ($enabledMethods as $m): ?>
        <label class="checkbox"><input type="radio" name="method" value="<?= htmlspecialchars($m) ?>" required> <?= ucfirst(str_replace('_',' ',$m)) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <button type="submit"><?= trans('pay_now') ?> $<?= number_format($amountDue, 2) ?></button>
</form>
<?php else: ?>
<p class="success"><?= trans('payment_complete') ?></p>
<a href="<?= BASE_URL ?>booking/success.php" class="cta"><?= trans('continue') ?></a>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
