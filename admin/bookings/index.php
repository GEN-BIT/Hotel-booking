<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_bookings');

$statusFilter = $_GET['status'] ?? '';
$sql = 'SELECT b.*, u.full_name, r.room_number FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id';
$params = [];
if ($statusFilter) {
    $sql .= ' WHERE b.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY b.check_in DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<h1><?= trans('bookings') ?></h1>
<form method="get" class="filter-form">
    <select name="status" onchange="this.form.submit()">
        <option value=""><?= trans('all_statuses') ?></option>
        <?php foreach (['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<table class="data-table">
    <tr><th><?= trans('booking_reference') ?></th><th><?= trans('guest') ?></th><th><?= trans('room') ?></th><th><?= trans('dates') ?></th><th><?= trans('status') ?></th><th></th></tr>
    <?php foreach ($bookings as $b): ?>
    <tr>
        <td><?= htmlspecialchars($b['booking_reference']) ?></td>
        <td><?= htmlspecialchars($b['full_name']) ?></td>
        <td><?= htmlspecialchars($b['room_number']) ?></td>
        <td><?= htmlspecialchars($b['check_in']) ?> &rarr; <?= htmlspecialchars($b['check_out']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
        <td><a href="view.php?id=<?= (int)$b['id'] ?>"><?= trans('manage') ?></a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
