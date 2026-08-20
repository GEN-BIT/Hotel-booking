<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$stmt = $pdo->query(
    'SELECT rt.name, COUNT(b.id) AS bookings, COALESCE(SUM(b.total_price),0) AS revenue,
            COALESCE(AVG(DATEDIFF(b.check_out, b.check_in)),0) AS avg_stay
     FROM room_types rt
     LEFT JOIN rooms r ON r.room_type_id = rt.id
     LEFT JOIN bookings b ON b.room_id = r.id AND b.status != "cancelled"
     GROUP BY rt.id ORDER BY revenue DESC'
);
$rows = $stmt->fetchAll();
?>
<h1>Room Performance</h1>
<table class="data-table">
    <tr><th>Room Type</th><th>Bookings</th><th>Revenue</th><th>Avg Stay (nights)</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= (int)$r['bookings'] ?></td>
        <td>$<?= number_format($r['revenue'], 2) ?></td>
        <td><?= round($r['avg_stay'], 1) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
