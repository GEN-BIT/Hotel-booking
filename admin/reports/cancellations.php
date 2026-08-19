<?php require __DIR__ . '/../../includes/admin-header.php';

$stmt = $pdo->query(
    'SELECT b.booking_reference, u.full_name, b.check_in, b.check_out, b.total_price, b.updated_at
     FROM bookings b JOIN users u ON b.user_id = u.id
     WHERE b.status = "cancelled" ORDER BY b.updated_at DESC LIMIT 100'
);
$cancellations = $stmt->fetchAll();
$totalCount = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "cancelled"')->fetchColumn();
$lostRevenue = (float)$pdo->query('SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status = "cancelled"')->fetchColumn();
?>
<h1>Cancellations Report</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $totalCount ?></h3><p>Total Cancellations</p></div>
    <div class="stat-card"><h3>$<?= number_format($lostRevenue, 2) ?></h3><p>Lost Revenue</p></div>
</div>
<table class="data-table">
    <tr><th>Reference</th><th>Guest</th><th>Dates</th><th>Value</th><th>Cancelled At</th></tr>
    <?php foreach ($cancellations as $c): ?>
    <tr>
        <td><?= htmlspecialchars($c['booking_reference']) ?></td>
        <td><?= htmlspecialchars($c['full_name']) ?></td>
        <td><?= htmlspecialchars($c['check_in']) ?> &rarr; <?= htmlspecialchars($c['check_out']) ?></td>
        <td>$<?= number_format($c['total_price'], 2) ?></td>
        <td><?= htmlspecialchars($c['updated_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
