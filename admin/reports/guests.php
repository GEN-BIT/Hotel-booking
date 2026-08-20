<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$totalGuests = (int)$pdo->query('SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name="guest"')->fetchColumn();
$repeatGuests = (int)$pdo->query(
    'SELECT COUNT(*) FROM (SELECT user_id FROM bookings GROUP BY user_id HAVING COUNT(*) > 1) t'
)->fetchColumn();

$topGuests = $pdo->query(
    'SELECT u.full_name, u.email, COUNT(b.id) AS bookings, COALESCE(SUM(b.total_price),0) AS spent
     FROM users u JOIN bookings b ON b.user_id = u.id
     GROUP BY u.id ORDER BY spent DESC LIMIT 10'
)->fetchAll();
?>
<h1>Guest Statistics</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $totalGuests ?></h3><p>Total Guests</p></div>
    <div class="stat-card"><h3><?= $repeatGuests ?></h3><p>Repeat Guests</p></div>
</div>
<h3>Top Guests by Spend</h3>
<table class="data-table">
    <tr><th>Name</th><th>Email</th><th>Bookings</th><th>Total Spent</th></tr>
    <?php foreach ($topGuests as $g): ?>
    <tr>
        <td><?= htmlspecialchars($g['full_name']) ?></td>
        <td><?= htmlspecialchars($g['email']) ?></td>
        <td><?= (int)$g['bookings'] ?></td>
        <td>$<?= number_format($g['spent'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
