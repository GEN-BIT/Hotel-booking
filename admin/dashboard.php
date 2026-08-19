<?php require __DIR__ . '/../includes/admin-header.php';

$stats = [
    'rooms'    => $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'occupied' => $pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn(),
    'bookings_today' => $pdo->query('SELECT COUNT(*) FROM bookings WHERE check_in = CURDATE()')->fetchColumn(),
    'pending'  => $pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
    'revenue'  => $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn(),
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
?>
<h1>Dashboard</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $stats['rooms'] ?></h3><p>Total Rooms</p></div>
    <div class="stat-card"><h3><?= $stats['occupied'] ?></h3><p>Occupied Now</p></div>
    <div class="stat-card"><h3><?= $stats['bookings_today'] ?></h3><p>Check-ins Today</p></div>
    <div class="stat-card"><h3><?= $stats['pending'] ?></h3><p>Pending Bookings</p></div>
    <div class="stat-card"><h3>$<?= number_format($stats['revenue'], 2) ?></h3><p>Total Revenue</p></div>
</div>

<div class="chart-card">
    <h3>Bookings — Last 14 Days</h3>
    <canvas id="bookingsChart" height="90"></canvas>
</div>
<div class="chart-card">
    <h3>Revenue — Last 14 Days</h3>
    <canvas id="revenueChart" height="90"></canvas>
</div>

<div class="chart-card">
    <h3>Recent Activity</h3>
    <?php if (!$recentActivity): ?>
        <p>No activity recorded yet.</p>
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
    <a href="activity-log.php">View Full Activity Log &rarr;</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    var styles = getComputedStyle(document.documentElement);
    var primary = styles.getPropertyValue('--color-primary').trim();
    var accent = styles.getPropertyValue('--color-accent').trim();

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
})();
</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
