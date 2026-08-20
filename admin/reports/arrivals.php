<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$today = date('Y-m-d');
$stmt = $pdo->prepare(
    'SELECT b.*, u.full_name, u.email, r.room_number, rt.name AS type_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.check_in = ? AND b.status IN ("pending","confirmed")
     ORDER BY b.created_at ASC'
);
$stmt->execute([$today]);
$arrivals = $stmt->fetchAll();

$count = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE check_in = CURDATE() AND status IN ("pending","confirmed")')->fetchColumn();
?>
<h1>Upcoming Arrivals</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $count ?></h3><p>Arriving Today</p></div>
</div>

<?php if (!$arrivals): ?>
    <p>No arrivals scheduled for today.</p>
<?php else: ?>
<table class="data-table">
    <tr><th>Reference</th><th>Guest</th><th>Room</th><th>Status</th><th></th></tr>
    <?php foreach ($arrivals as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['booking_reference']) ?></td>
        <td><?= htmlspecialchars($a['full_name']) ?><br><small><?= htmlspecialchars($a['email']) ?></small></td>
        <td><?= htmlspecialchars($a['type_name']) ?> — <?= htmlspecialchars($a['room_number']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
        <td><a href="view.php?id=<?= (int)$a['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
