<?php require __DIR__ . '/../../includes/admin-header.php';

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
    <tr><th>Booking</th><th>Guest</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr>
    <?php foreach ($payments as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['booking_reference']) ?></td>
        <td><?= htmlspecialchars($p['full_name']) ?></td>
        <td>$<?= number_format($p['amount'], 2) ?></td>
        <td><?= htmlspecialchars($p['method']) ?></td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td><a href="view.php?id=<?= (int)$p['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
