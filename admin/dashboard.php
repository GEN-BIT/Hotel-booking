<?php require __DIR__ . '/../includes/admin-header.php';

$stats = [
    'rooms'    => $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'occupied' => $pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn(),
    'bookings_today' => $pdo->query('SELECT COUNT(*) FROM bookings WHERE check_in = CURDATE()')->fetchColumn(),
    'pending'  => $pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
    'revenue'  => $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn(),
];
?>
<h1>Dashboard</h1>
<div class="stat-grid">
    <div class="stat-card"><h3><?= $stats['rooms'] ?></h3><p>Total Rooms</p></div>
    <div class="stat-card"><h3><?= $stats['occupied'] ?></h3><p>Occupied Now</p></div>
    <div class="stat-card"><h3><?= $stats['bookings_today'] ?></h3><p>Check-ins Today</p></div>
    <div class="stat-card"><h3><?= $stats['pending'] ?></h3><p>Pending Bookings</p></div>
    <div class="stat-card"><h3>$<?= number_format($stats['revenue'], 2) ?></h3><p>Total Revenue</p></div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
