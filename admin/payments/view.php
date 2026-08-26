<?php require __DIR__ . '/../../includes/admin-header.php';
require_once __DIR__ . '/../../includes/payment-gateway.php';
require_permission('manage_payments');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT p.*, b.booking_reference, u.full_name, b.total_price, b.amount_paid, b.deposit_amount, b.payment_status
     FROM payments p
     JOIN bookings b ON p.booking_id = b.id
     JOIN users u ON b.user_id = u.id
     WHERE p.id = ?'
);
$stmt->execute([$id]);
$payment = $stmt->fetch();
if (!$payment) die('Payment not found.');

$paymentManager = new PaymentManager($pdo);
$balance = $paymentManager->getPaymentBalance($payment['booking_id']);
$refunds = $pdo->prepare('SELECT * FROM refunds WHERE payment_id = ? ORDER BY created_at DESC');
$refunds->execute([$id]);
$refundHistory = $refunds->fetchAll();
?>
<h1>Payment for <?= htmlspecialchars($payment['booking_reference']) ?></h1>
<p>Guest: <?= htmlspecialchars($payment['full_name']) ?></p>
<p>Amount: <?= format_currency($payment['amount']) ?></p>
<p>Method: <?= htmlspecialchars($payment['method']) ?></p>
<p>Status: <span class="status status-<?= htmlspecialchars($payment['status']) ?>"><?= htmlspecialchars($payment['status']) ?></span></p>
<p>Transaction Ref: <?= htmlspecialchars($payment['transaction_ref'] ?? '—') ?></p>
<p>Paid At: <?= htmlspecialchars($payment['paid_at'] ?? '—') ?></p>

<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem; margin: 1.5rem 0;">
    <h3 style="margin: 0 0 1rem;">Payment Balance</h3>
    <table style="width: 100%;">
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);">Total Price</td>
            <td style="padding: 0.5rem 0; text-align: right;"><?= format_currency($balance['total_price']) ?></td>
        </tr>
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);">Total Paid</td>
            <td style="padding: 0.5rem 0; text-align: right;"><?= format_currency($balance['total_paid']) ?></td>
        </tr>
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);">Refunded</td>
            <td style="padding: 0.5rem 0; text-align: right; color: var(--color-error);">-<?= format_currency($balance['refunded']) ?></td>
        </tr>
        <tr style="border-top: 2px solid var(--color-border);">
            <td style="padding: 0.75rem 0; font-weight: 700;">Balance Due</td>
            <td style="padding: 0.75rem 0; text-align: right; font-weight: 700; color: <?= $balance['is_paid'] ? 'var(--color-success)' : 'var(--color-accent)' ?>;">
                <?= format_currency($balance['balance']) ?>
                <?php if ($balance['is_paid']): ?> ✓ Paid<?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<?php if ($refundHistory): ?>
<h3>Refund History</h3>
<table class="data-table">
    <tr><th>Amount</th><th>Reason</th><th>Status</th><th>Date</th></tr>
    <?php foreach ($refundHistory as $refund): ?>
    <tr>
        <td><?= format_currency($refund['amount']) ?></td>
        <td><?= htmlspecialchars($refund['reason'] ?? '—') ?></td>
        <td><span class="status status-<?= htmlspecialchars($refund['status']) ?>"><?= htmlspecialchars($refund['status']) ?></span></td>
        <td><?= htmlspecialchars($refund['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($payment['status'] === 'paid' && $balance['available_for_refund'] > 0): ?>
    <a href="refund.php?id=<?= $id ?>" class="cta cta-danger" onclick="return confirm('Issue a refund of <?= format_currency($payment['amount']) ?>?')">Issue Refund</a>
<?php endif; ?>
<a href="index.php">Back to Payments</a>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
