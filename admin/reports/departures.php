<?php require __DIR__ . '/../../includes/admin-header.php';

$today = date('Y-m-d');
$count = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE check_out = CURDATE() AND status = "checked_in"')->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT b.*, u.full_name, u.email, r.room_number, rt.name AS type_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.check_out = ? AND b.status = "checked_in"
     ORDER BY b.created_at ASC'
);
$stmt->execute([$today]);
$departures = $stmt->fetchAll();
?>
<h1>Upcoming Departures</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $count ?></h3><p>Departing Today</p></div>
</div>

<?php if (!$departures): ?>
    <p>No departures scheduled for today.</p>
<?php else: ?>
<table class="data-table">
    <tr><th>Reference</th><th>Guest</th><th>Room</th><th>Checked In</th><th></th></tr>
    <?php foreach ($departures as $d): ?>
    <tr>
        <td><?= htmlspecialchars($d['booking_reference']) ?></td>
        <td><?= htmlspecialchars($d['full_name']) ?><br><small><?= htmlspecialchars($d['email']) ?></small></td>
        <td><?= htmlspecialchars($d['type_name']) ?> — <?= htmlspecialchars($d['room_number']) ?></td>
        <td><?= htmlspecialchars($d['check_in']) ?></td>
        <td><a href="view.php?id=<?= (int)$d['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
