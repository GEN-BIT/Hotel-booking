<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_payments');

$stmt = $pdo->query(
    'SELECT p.*, b.booking_reference, u.full_name FROM payments p
     JOIN bookings b ON p.booking_id = b.id
     JOIN users u ON b.user_id = u.id
     ORDER BY p.created_at DESC'
);
$payments = $stmt->fetchAll();
?>
<h1>Payments</h1>
<table class="data-table">
    <tr><th>Booking</th><th>Guest</th><th>Amount</th><th>Method</th><th>Payment Status</th><th>Booking Status</th><th></th></tr>
    <?php foreach ($payments as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['booking_reference']) ?></td>
        <td><?= htmlspecialchars($p['full_name']) ?></td>
        <td><?= format_currency($p['amount']) ?></td>
        <td><?= htmlspecialchars($p['method']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
        <td><span class="status status-<?= htmlspecialchars($p['payment_status'] ?? 'pending') ?>"><?= htmlspecialchars($p['payment_status'] ?? 'pending') ?></span></td>
        <td><a href="view.php?id=<?= (int)$p['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
