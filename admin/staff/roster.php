<?php
require_once __DIR__ . '/../../config/config.php';
require_permission('view_reports');

$tab = $_GET['tab'] ?? 'leaves';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tab === 'leaves') {
        $staffId = (int)$_POST['staff_id'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $reason = trim($_POST['reason'] ?? '');
        
        $stmt = $pdo->prepare(
            'INSERT INTO staff_leaves (staff_user_id, start_date, end_date, reason, status)
             VALUES (?, ?, ?, ?, "pending")'
        );
        $stmt->execute([$staffId, $startDate, $endDate, $reason]);
        
        $success = 'Leave request submitted.';
    } elseif ($tab === 'shifts') {
        $staffId = (int)$_POST['staff_id'];
        $shiftDate = $_POST['shift_date'];
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $role = $_POST['role'];
        
        $stmt = $pdo->prepare(
            'INSERT INTO staff_shifts (staff_user_id, shift_date, start_time, end_time, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$staffId, $shiftDate, $startTime, $endTime, $role]);
        
        $success = 'Shift added successfully.';
    }
}

$staff = $pdo->query(
    'SELECT u.id, u.full_name, s.position FROM users u JOIN staff s ON s.user_id = u.id WHERE s.is_active = 1 ORDER BY u.full_name'
)->fetchAll();

if ($tab === 'leaves') {
    $leaves = $pdo->query(
        'SELECT l.*, u.full_name FROM staff_leaves l JOIN users u ON u.id = l.staff_user_id ORDER BY l.start_date DESC'
    )->fetchAll();
} else {
    $shifts = $pdo->query(
        'SELECT s.*, u.full_name FROM staff_shifts s JOIN users u ON u.id = s.staff_user_id ORDER BY s.shift_date DESC, s.start_time ASC'
    )->fetchAll();
}
?>
<h1>Staff Roster</h1>

<div style="border-bottom: 1px solid var(--color-border); margin-bottom: 1.5rem;">
    <a href="?tab=leaves" class="cta" style="<?= $tab === 'leaves' ? 'background: var(--color-primary);' : 'background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border);' ?>">Leave Requests</a>
    <a href="?tab=shifts" class="cta" style="<?= $tab === 'shifts' ? 'background: var(--color-primary);' : 'background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border);' ?>">Shift Schedule</a>
</div>

<?php if (isset($success)): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<?php if ($tab === 'leaves'): ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem;">
        <h3 style="margin: 0 0 1rem;">Request Leave</h3>
        <form method="post">
            <label>Staff
                <select name="staff_id" required>
                    <option value="">-- Select Staff --</option>
                    <?php foreach ($staff as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['position'] ?? 'Staff') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Start Date <input type="date" name="start_date" required></label>
            <label>End Date <input type="date" name="end_date" required></label>
            <label>Reason <textarea name="reason" rows="3"></textarea></label>
            <button type="submit">Submit Request</button>
        </form>
    </div>

    <div>
        <h3>Leave Requests</h3>
        <table class="data-table">
            <tr><th>Staff</th><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Reason</th></tr>
            <?php foreach ($leaves as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['full_name']) ?></td>
                <td><?= htmlspecialchars($l['leave_type']) ?></td>
                <td><?= htmlspecialchars($l['start_date']) ?></td>
                <td><?= htmlspecialchars($l['end_date']) ?></td>
                <td><span class="status status-<?= htmlspecialchars($l['status']) ?>"><?= htmlspecialchars($l['status']) ?></span></td>
                <td><?= htmlspecialchars($l['reason'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php else: ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem;">
        <h3 style="margin: 0 0 1rem;">Add Shift</h3>
        <form method="post">
            <label>Staff
                <select name="staff_id" required>
                    <option value="">-- Select Staff --</option>
                    <?php foreach ($staff as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date <input type="date" name="shift_date" required></label>
            <label>Start Time <input type="time" name="start_time" required></label>
            <label>End Time <input type="time" name="end_time" required></label>
            <label>Role <input type="text" name="role" required></label>
            <button type="submit">Add Shift</button>
        </form>
    </div>

    <div>
        <h3>Shift Schedule</h3>
        <table class="data-table">
            <tr><th>Staff</th><th>Date</th><th>Time</th><th>Role</th></tr>
            <?php foreach ($shifts as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['shift_date']) ?></td>
                <td><?= htmlspecialchars($s['start_time']) ?> - <?= htmlspecialchars($s['end_time']) ?></td>
                <td><?= htmlspecialchars($s['role']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>