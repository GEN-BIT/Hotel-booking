<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_payments');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT p.*, b.booking_reference, u.full_name FROM payments p
     JOIN bookings b ON p.booking_id = b.id
     JOIN users u ON b.user_id = u.id
     WHERE p.id = ?'
);
$stmt->execute([$id]);
$payment = $stmt->fetch();
if (!$payment) die('Payment not found.');
?>
<h1>Payment for <?= htmlspecialchars($payment['booking_reference']) ?></h1>
<p>Guest: <?= htmlspecialchars($payment['full_name']) ?></p>
<p>Amount: $<?= number_format($payment['amount'], 2) ?></p>
<p>Method: <?= htmlspecialchars($payment['method']) ?></p>
<p>Status: <?= htmlspecialchars($payment['status']) ?></p>
<p>Transaction Ref: <?= htmlspecialchars($payment['transaction_ref'] ?? '—') ?></p>
<p>Paid At: <?= htmlspecialchars($payment['paid_at'] ?? '—') ?></p>

<?php if ($payment['status'] === 'paid'): ?>
    <a href="refund.php?id=<?= $id ?>" class="cta cta-danger" onclick="return confirm('Refund this payment?')">Issue Refund</a>
<?php endif; ?>
<a href="index.php">Back to Payments</a>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
