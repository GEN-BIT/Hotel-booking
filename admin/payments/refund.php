<?php require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/payment-gateway.php';
require_permission('manage_payments');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ?');
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Payment not found.');
}

if ($payment['status'] !== 'paid') {
    die('Cannot refund unpaid or already refunded payment.');
}

$bookingId = $payment['booking_id'];
$paymentManager = new PaymentManager($pdo);
$balance = $paymentManager->getPaymentBalance($bookingId);

if (!$balance || $payment['amount'] > $balance['available_for_refund']) {
    die('Insufficient balance for refund. The booking has outstanding amounts or previous refunds.');
}

$reason = $_GET['reason'] ?? 'Administrator refund';
$result = $paymentManager->refund($id, $payment['amount'], $reason, $_SESSION['user_id']);

if ($result['success']) {
    $_SESSION['flash_success'] = 'Refund of $' . number_format($payment['amount'], 2) . ' processed successfully.';
} else {
    $_SESSION['flash_error'] = $result['message'];
}

header('Location: view.php?id=' . $bookingId);
exit;
