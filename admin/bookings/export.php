<?php
require_once __DIR__ . '/../../config/config.php';
require_permission('manage_bookings');

$statusFilter = $_GET['status'] ?? '';
$sql = 'SELECT b.*, u.full_name, u.email, r.room_number, rt.name AS room_type FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id';
$params = [];
if ($statusFilter) {
    $sql .= ' WHERE b.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY b.check_in DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$filename = 'bookings_export_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Reference', 'Guest', 'Email', 'Room', 'Type', 'Check-in', 'Check-out', 'Guests', 'Total Price', 'Status', 'Created']);

foreach ($bookings as $b) {
    fputcsv($output, [
        $b['booking_reference'],
        $b['full_name'],
        $b['email'],
        $b['room_number'],
        $b['room_type'],
        $b['check_in'],
        $b['check_out'],
        $b['num_guests'],
        $b['total_price'],
        $b['status'],
        $b['created_at']
    ]);
}

fclose($output);
exit;
