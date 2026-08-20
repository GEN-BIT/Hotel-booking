<?php
require_once __DIR__ . '/../config/config.php';
require_permission('manage_rooms');

$rooms = $pdo->query('SELECT r.*, rt.name AS type_name FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number')->fetchAll();

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (!$roomId || !$startDate || !$endDate) {
        $error = 'Room, start date, and end date are required.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO room_maintenance (room_id, start_date, end_date, notes, status)
             VALUES (?, ?, ?, ?, "scheduled")'
        );
        $stmt->execute([$roomId, $startDate, $endDate, $notes]);
        
        $pdo->prepare('UPDATE rooms SET status = "maintenance" WHERE id = ?')->execute([$roomId]);
        
        $success = 'Maintenance scheduled successfully.';
    }
}

$maintenance = $pdo->query(
    'SELECT m.*, r.room_number, rt.name AS type_name
     FROM room_maintenance m
     JOIN rooms r ON m.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     ORDER BY m.start_date DESC'
)->fetchAll();
?>
<h1><?= trans('maintenance_mode') ?></h1>

<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-top: 1.5rem;">
    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem;">
        <h3 style="margin: 0 0 1rem;">Schedule Maintenance</h3>
        <form method="post">
            <label>Room
                <select name="room_id" required>
                    <option value="">-- Select Room --</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?= (int)$room['id'] ?>"><?= htmlspecialchars($room['room_number']) ?> — <?= htmlspecialchars($room['type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Start Date <input type="date" name="start_date" required></label>
            <label>End Date <input type="date" name="end_date" required></label>
            <label>Notes <textarea name="notes" rows="3"></textarea></label>
            <button type="submit">Schedule Maintenance</button>
        </form>
    </div>

    <div>
        <h3>Maintenance Schedule</h3>
        <table class="data-table">
            <tr><th>Room</th><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Notes</th></tr>
            <?php foreach ($maintenance as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['room_number']) ?></td>
                <td><?= htmlspecialchars($m['type_name']) ?></td>
                <td><?= htmlspecialchars($m['start_date']) ?></td>
                <td><?= htmlspecialchars($m['end_date']) ?></td>
                <td><span class="status status-<?= htmlspecialchars($m['status']) ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                <td><?= htmlspecialchars($m['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>