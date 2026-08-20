<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$total = (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$occupied = (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn();
$rate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

$byType = $pdo->query(
    'SELECT rt.name, COUNT(r.id) AS total, SUM(r.status = "occupied") AS occupied
     FROM room_types rt JOIN rooms r ON r.room_type_id = rt.id
     GROUP BY rt.id'
)->fetchAll();
?>
<h1>Occupancy Report</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $rate ?>%</h3><p>Current Occupancy Rate</p></div>
    <div class="stat-card"><h3><?= $occupied ?> / <?= $total ?></h3><p>Rooms Occupied</p></div>
</div>
<h3>By Room Type</h3>
<table class="data-table">
    <tr><th>Type</th><th>Total Rooms</th><th>Occupied</th><th>Rate</th></tr>
    <?php foreach ($byType as $t): ?>
    <tr>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td><?= (int)$t['total'] ?></td>
        <td><?= (int)$t['occupied'] ?></td>
        <td><?= $t['total'] > 0 ? round(($t['occupied'] / $t['total']) * 100, 1) : 0 ?>%</td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
