<?php require __DIR__ . '/../includes/admin-header.php';

$stats = [
    'rooms'    => $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'occupied' => $pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn(),
    'bookings_today' => $pdo->query('SELECT COUNT(*) FROM bookings WHERE check_in = CURDATE()')->fetchColumn(),
    'pending'  => $pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
    'revenue'  => $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn(),
    'arrivals_today' => $pdo->query('SELECT COUNT(*) FROM bookings WHERE check_in = CURDATE() AND status IN ("pending","confirmed")')->fetchColumn(),
    'departures_today' => $pdo->query('SELECT COUNT(*) FROM bookings WHERE check_out = CURDATE() AND status = "checked_in"')->fetchColumn(),
];

$bookingsRaw = $pdo->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS count FROM bookings
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(created_at)"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$revenueRaw = $pdo->query(
    "SELECT DATE(paid_at) AS day, SUM(amount) AS total FROM payments
     WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(paid_at)"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$chartLabels = []; $bookingValues = []; $revenueValues = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($day));
    $bookingValues[] = (int)($bookingsRaw[$day] ?? 0);
    $revenueValues[] = (float)($revenueRaw[$day] ?? 0);
}

$recentActivity = $pdo->query('SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 15')->fetchAll();

$arrivals = $pdo->query(
    'SELECT b.booking_reference, u.full_name, r.room_number, rt.name AS type_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.check_in = CURDATE() AND b.status IN ("pending","confirmed")
     ORDER BY b.created_at ASC LIMIT 5'
)->fetchAll();

$departures = $pdo->query(
    'SELECT b.booking_reference, u.full_name, r.room_number, rt.name AS type_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     WHERE b.check_out = CURDATE() AND b.status = "checked_in"
     ORDER BY b.created_at ASC LIMIT 5'
)->fetchAll();
?>
<h1><?= trans('dashboard') ?></h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $stats['rooms'] ?></h3><p><?= trans('total_rooms') ?></p></div>
    <div class="stat-card"><h3><?= $stats['occupied'] ?></h3><p><?= trans('occupied_now') ?></p></div>
    <div class="stat-card"><h3><?= $stats['arrivals_today'] ?></h3><p><?= trans('arrivals_today') ?></p></div>
    <div class="stat-card"><h3><?= $stats['departures_today'] ?></h3><p><?= trans('departures_today') ?></p></div>
    <div class="stat-card"><h3><?= $stats['pending'] ?></h3><p><?= trans('pending_bookings') ?></p></div>
    <div class="stat-card"><h3><?= format_currency($stats['revenue']) ?></h3><p><?= trans('total_revenue') ?></p></div>
</div>

<div class="chart-card">
    <h3><?= trans('arrivals_today') ?></h3>
    <?php if (!$arrivals): ?>
        <p><?= trans('no_arrivals_today') ?></p>
    <?php else: ?>
    <ul class="activity-log">
        <?php foreach ($arrivals as $a): ?>
        <li>
            <strong><?= htmlspecialchars($a['full_name']) ?></strong> — <?= htmlspecialchars($a['type_name']) ?> <?= trans('room') ?> <?= htmlspecialchars($a['room_number']) ?>
            <div class="activity-time"><?= htmlspecialchars($a['booking_reference']) ?></div>
        </li>
        <?php endforeach; ?>
    </ul>
    <a href="reports/arrivals.php" style="display:inline-block; margin-top:0.75rem;"><?= trans('view_all_arrivals') ?> &rarr;</a>
    <?php endif; ?>
</div>

<div class="chart-card">
    <h3><?= trans('departures_today') ?></h3>
    <?php if (!$departures): ?>
        <p><?= trans('no_departures_today') ?></p>
    <?php else: ?>
    <ul class="activity-log">
        <?php foreach ($departures as $d): ?>
        <li>
            <strong><?= htmlspecialchars($d['full_name']) ?></strong> — <?= htmlspecialchars($d['type_name']) ?> <?= trans('room') ?> <?= htmlspecialchars($d['room_number']) ?>
            <div class="activity-time"><?= htmlspecialchars($d['booking_reference']) ?></div>
        </li>
        <?php endforeach; ?>
    </ul>
    <a href="reports/departures.php" style="display:inline-block; margin-top:0.75rem;"><?= trans('view_all_departures') ?> &rarr;</a>
    <?php endif; ?>
</div>

<div class="chart-card">
    <h3><?= trans('bookings_last_14') ?></h3>
    <canvas id="bookingsChart" height="90"></canvas>
</div>
<div class="chart-card">
    <h3><?= trans('revenue_last_14') ?></h3>
    <canvas id="revenueChart" height="90"></canvas>
</div>

<div class="chart-card">
    <h3><?= trans('recent_activity') ?></h3>
    <?php if (!$recentActivity): ?>
        <p><?= trans('no_activity_recorded') ?></p>
    <?php else: ?>
    <ul class="activity-log">
        <?php foreach ($recentActivity as $a): ?>
        <li>
            <strong><?= htmlspecialchars($a['actor_name']) ?></strong> — <?= htmlspecialchars($a['description']) ?>
            <div class="activity-time"><?= htmlspecialchars($a['created_at']) ?></div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <a href="activity-log.php"><?= trans('view_full_activity_log') ?> &rarr;</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    if (typeof Chart === 'undefined') return;
    var styles = getComputedStyle(document.documentElement);
    var primary = styles.getPropertyValue('--color-primary').trim();
    var accent = styles.getPropertyValue('--color-accent').trim();

    try {
        new Chart(document.getElementById('bookingsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{ label: 'Bookings', data: <?= json_encode($bookingValues) ?>, borderColor: primary, backgroundColor: 'transparent', tension: 0.3 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{ label: 'Revenue', data: <?= json_encode($revenueValues) ?>, backgroundColor: accent }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    } catch (e) {}
})();
</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
