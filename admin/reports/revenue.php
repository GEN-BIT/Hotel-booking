<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$stmt = $pdo->query(
    'SELECT DATE(paid_at) AS day, SUM(amount) AS total, COUNT(*) AS count
     FROM payments WHERE status = "paid" AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(paid_at) ORDER BY day DESC'
);
$dailyRevenue = $stmt->fetchAll();

$totalRevenue = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn();
$monthRevenue = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid" AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();
$pendingRevenue = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "pending"')->fetchColumn();

$byMethod = $pdo->query(
    'SELECT method, SUM(amount) AS total, COUNT(*) AS count
     FROM payments WHERE status = "paid" GROUP BY method'
)->fetchAll();
?>
<h1>Revenue Report</h1>
<div class="stat-grid">
    <div class="stat-card"><h3>$<?= number_format($totalRevenue, 2) ?></h3><p>Total Revenue</p></div>
    <div class="stat-card"><h3>$<?= number_format($monthRevenue, 2) ?></h3><p>Last 30 Days</p></div>
    <div class="stat-card"><h3>$<?= number_format($pendingRevenue, 2) ?></h3><p>Pending Payments</p></div>
</div>

<h3>Revenue by Payment Method</h3>
<table class="data-table">
    <tr><th>Method</th><th>Transactions</th><th>Total</th></tr>
    <?php foreach ($byMethod as $m): ?>
    <tr>
        <td><?= htmlspecialchars(ucfirst($m['method'])) ?></td>
        <td><?= (int)$m['count'] ?></td>
        <td>$<?= number_format($m['total'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>Daily Revenue — Last 30 Days</h3>
<table class="data-table">
    <tr><th>Date</th><th>Transactions</th><th>Revenue</th></tr>
    <?php foreach ($dailyRevenue as $d): ?>
    <tr>
        <td><?= htmlspecialchars($d['day']) ?></td>
        <td><?= (int)$d['count'] ?></td>
        <td>$<?= number_format($d['total'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
