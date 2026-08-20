<?php
require_once __DIR__ . '/../config/config.php';
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

$filename = 'revenue_export_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Transactions', 'Revenue']);

foreach ($dailyRevenue as $d) {
    fputcsv($output, [
        $d['day'],
        $d['count'],
        $d['total']
    ]);
}

fclose($output);
exit;
