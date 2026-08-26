<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$params = [$startDate, $endDate . ' 23:59:59'];
$whereClause = 'WHERE status = "paid" AND paid_at >= ? AND paid_at <= ?';

$stmt = $pdo->prepare(
    "SELECT DATE(paid_at) AS day, SUM(amount) AS total, COUNT(*) AS count
     FROM payments $whereClause
     GROUP BY DATE(paid_at) ORDER BY day DESC"
);
$stmt->execute($params);
$dailyRevenue = $stmt->fetchAll();

$revStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments $whereClause");
$revStmt->execute($params);
$totalRevenue = (float)$revStmt->fetchColumn();
$pendingRevenue = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "pending"')->fetchColumn();

$byMethod = $pdo->prepare(
    "SELECT method, SUM(amount) AS total, COUNT(*) AS count
     FROM payments $whereClause GROUP BY method"
);
$byMethod->execute($params);
$byMethod = $byMethod->fetchAll();
?>
<h1><?= trans('revenue') ?></h1>

<form method="get" class="filter-form" style="margin-bottom: 2rem;">
    <label style="display: flex; flex-direction: column; gap: 0.3rem;">
        <?= trans('from') ?>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
    </label>
    <label style="display: flex; flex-direction: column; gap: 0.3rem;">
        <?= trans('to') ?>
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
    </label>
    <button type="submit" class="cta"><?= trans('apply_filter') ?></button>
    <a href="?start_date=<?= date('Y-m-d', strtotime('-30 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="cta" style="background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border);"><?= trans('reset_filter') ?></a>
    <a href="export.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="cta" style="background: linear-gradient(135deg, #27ae60, #2ecc71);"><?= trans('export_csv') ?></a>
</form>

<div class="stat-grid">
    <div class="stat-card"><h3><?= format_currency($totalRevenue) ?></h3><p><?= trans('total_revenue') ?></p></div>
    <div class="stat-card"><h3><?= format_currency($pendingRevenue) ?></h3><p><?= trans('pending_bookings') ?></p></div>
</div>

<h3><?= trans('revenue') ?> — <?= date('M j, Y', strtotime($startDate)) ?> to <?= date('M j, Y', strtotime($endDate)) ?></h3>
<table class="data-table">
    <tr><th><?= trans('method') ?></th><th><?= trans('bookings') ?></th><th><?= trans('total') ?></th></tr>
    <?php foreach ($byMethod as $m): ?>
    <tr>
        <td><?= htmlspecialchars(ucfirst($m['method'])) ?></td>
        <td><?= (int)$m['count'] ?></td>
        <td><?= format_currency($m['total']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3><?= trans('revenue') ?> — <?= trans('date_range') ?></h3>
<table class="data-table">
    <tr><th><?= trans('date') ?></th><th><?= trans('bookings') ?></th><th><?= trans('revenue') ?></th></tr>
    <?php foreach ($dailyRevenue as $d): ?>
    <tr>
        <td><?= htmlspecialchars($d['day']) ?></td>
        <td><?= (int)$d['count'] ?></td>
        <td><?= format_currency($d['total']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
