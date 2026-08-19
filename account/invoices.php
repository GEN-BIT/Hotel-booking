<?php require_once __DIR__ . '/../config/config.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT b.id, b.booking_reference, b.total_price, b.check_in, b.check_out, p.status AS payment_status, p.paid_at
     FROM bookings b
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$invoices = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Invoices</h1>
<?php if (!$invoices): ?>
    <p>No invoices yet.</p>
<?php else: ?>
<table class="data-table">
    <tr><th>Reference</th><th>Dates</th><th>Total</th><th>Payment Status</th><th></th></tr>
    <?php foreach ($invoices as $i): ?>
    <tr>
        <td><?= htmlspecialchars($i['booking_reference']) ?></td>
        <td><?= htmlspecialchars($i['check_in']) ?> &rarr; <?= htmlspecialchars($i['check_out']) ?></td>
        <td>$<?= number_format($i['total_price'], 2) ?></td>
        <td><?= htmlspecialchars($i['payment_status'] ?? 'unpaid') ?></td>
        <td><a href="reservation-details.php?id=<?= (int)$i['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
