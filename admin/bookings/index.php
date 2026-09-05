<?php require __DIR__ . '/../../includes/admin-header.php';
require_once __DIR__ . '/../../includes/bulk-actions.php';
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

$bulkMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action']) && !empty($_POST['selected_ids'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $bulkMessage = 'Invalid CSRF token.';
    } else {
        $bulk = new BulkActions($pdo);
        $result = $bulk->process('bookings', $_POST['bulk_action'], $_POST['selected_ids']);
        $bulkMessage = $result['message'];
    }
}
?>
<h1><?= trans('bookings') ?></h1>

<?php if ($bulkMessage): ?>
    <p class="<?= strpos($bulkMessage, 'successfully') !== false || strpos($bulkMessage, 'confirmed') !== false || strpos($bulkMessage, 'cancelled') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($bulkMessage) ?></p>
<?php endif; ?>

<form method="get" class="filter-form">
    <select name="status" onchange="this.form.submit()">
        <option value=""><?= trans('all_statuses') ?></option>
        <?php foreach (['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<form method="post" id="bulkForm" style="margin: 1rem 0; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <?= csrf_field() ?>
    <select name="bulk_action" required style="padding: 0.5rem; border-radius: var(--radius); border: 1px solid var(--color-border);">
        <option value=""><?= trans('bulk_actions') ?></option>
        <option value="confirm"><?= trans('confirm') ?></option>
        <option value="cancel"><?= trans('cancel_booking') ?></option>
        <option value="delete"><?= trans('delete_selected') ?></option>
    </select>
    <button type="submit" class="cta" style="padding: 0.5rem 1rem;" onclick="return confirm('<?= trans('are_you_sure_delete_selected') ?>')"><?= trans('apply') ?></button>
    <a href="export.php" class="cta" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #27ae60, #2ecc71);"><?= trans('export_csv') ?></a>
</form>

<table class="data-table">
    <tr>
        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
        <th><?= trans('booking_reference') ?></th>
        <th><?= trans('guest') ?></th>
        <th><?= trans('room') ?></th>
        <th><?= trans('dates') ?></th>
        <th><?= trans('status') ?></th>
        <th></th>
    </tr>
    <?php foreach ($bookings as $b): ?>
    <tr>
        <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$b['id'] ?>" class="bulk-checkbox"></td>
        <td><?= htmlspecialchars($b['booking_reference']) ?></td>
        <td><?= htmlspecialchars($b['full_name']) ?></td>
        <td><?= htmlspecialchars($b['room_number']) ?></td>
        <td><?= htmlspecialchars($b['check_in']) ?> &rarr; <?= htmlspecialchars($b['check_out']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
        <td><a href="view.php?id=<?= (int)$b['id'] ?>"><?= trans('manage') ?></a></td>
    </tr>
    <?php endforeach; ?>
</table>

<script>
function toggleAll(source) {
    document.querySelectorAll('.bulk-checkbox').forEach(function(cb) {
        cb.checked = source.checked;
    });
}

document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.bulk-checkbox:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Please select at least one item.');
    }
});
</script>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
