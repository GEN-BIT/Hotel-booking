<?php
require_once __DIR__ . '/../config/config.php';
require_permission('manage_rooms');

$format = $_GET['format'] ?? 'csv';

$rooms = $pdo->query(
    'SELECT r.*, rt.name AS type_name FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number'
)->fetchAll();

$filename = 'rooms_export_' . date('Y-m-d_H-i-s') . '.' . ($format === 'csv' ? 'csv' : 'json');

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($rooms, JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Room Number', 'Type', 'Floor', 'Building', 'Status', 'Notes']);

foreach ($rooms as $r) {
    fputcsv($output, [
        $r['room_number'],
        $r['type_name'],
        $r['floor'],
        $r['building'],
        $r['status'],
        $r['notes'] ?? ''
    ]);
}

fclose($output);
exit;
