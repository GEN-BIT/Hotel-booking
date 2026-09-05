<?php require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_payments');

$pendingPayments = $pdo->query(
    'SELECT p.*, b.booking_reference, u.full_name 
     FROM payments p
     JOIN bookings b ON p.booking_id = b.id
     JOIN users u ON b.user_id = u.id
     WHERE p.status = "pending"
     ORDER BY p.created_at DESC'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentId = (int)$_POST['payment_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($paymentId && $action === 'verify') {
        $paymentManager = new PaymentManager($pdo);
        $result = $paymentManager->verifyPayment($paymentId);
        
        if ($result['success']) {
            $_SESSION['flash_success'] = 'Payment verified successfully.';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
    } elseif ($paymentId && $action === 'fail') {
        $pdo->prepare('UPDATE payments SET status = "failed" WHERE id = ?')
            ->execute([$paymentId]);
        $_SESSION['flash_success'] = 'Payment marked as failed.';
    }
    
    header('Location: reconcile.php');
    exit;
}
?>
<h1>Payment Reconciliation</h1>
<p style="color: var(--color-muted); margin-bottom: 1.5rem;">Verify pending payments or mark them as failed. This is used to reconcile payments that need manual verification.</p>

<?php if (empty($pendingPayments)): ?>
    <div class="admin-empty">
        <h3>No Pending Payments</h3>
        <p>All payments have been processed. Check back later for new transactions.</p>
    </div>
<?php else: ?>
<table class="admin-table">
    <tr><th>Booking</th><th>Guest</th><th>Amount</th><th>Method</th><th>Transaction Ref</th><th>Created</th><th>Actions</th></tr>
    <?php foreach ($pendingPayments as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['booking_reference']) ?></td>
        <td><?= htmlspecialchars($p['full_name']) ?></td>
        <td><?= format_currency($p['amount']) ?></td>
        <td><?= htmlspecialchars($p['method']) ?></td>
        <td><?= htmlspecialchars($p['transaction_ref']) ?></td>
        <td><?= htmlspecialchars($p['created_at']) ?></td>
        <td class="actions">
            <form method="post" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="verify">
                <button type="submit" class="cta" style="font-size:0.85rem; padding:0.4rem 0.8rem;">Verify</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Mark this payment as failed?')">
                <?= csrf_field() ?>
                <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="fail">
                <button type="submit" class="cta-danger" style="font-size:0.85rem; padding:0.4rem 0.8rem;">Fail</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2 style="margin-top:2rem;">Recent Activity Log</h2>
<?php
$activities = $pdo->query(
    'SELECT * FROM activity_log WHERE action LIKE "payment.%"
     ORDER BY created_at DESC LIMIT 20'
)->fetchAll();
?>
<table class="admin-table">
    <tr><th>Time</th><th>Action</th><th>Description</th></tr>
    <?php foreach ($activities as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['created_at']) ?></td>
        <td><?= htmlspecialchars($a['action']) ?></td>
        <td><?= htmlspecialchars($a['description']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>