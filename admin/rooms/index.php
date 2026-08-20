<?php require __DIR__ . '/../../includes/admin-header.php';
require_once __DIR__ . '/../../includes/bulk-actions.php';
require_permission('manage_rooms');

$rooms = $pdo->query(
    'SELECT r.*, rt.name AS type_name FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number'
)->fetchAll();

$bulkMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action']) && !empty($_POST['selected_ids'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $bulkMessage = 'Invalid CSRF token.';
    } else {
        $bulk = new BulkActions($pdo);
        $result = $bulk->process('rooms', $_POST['bulk_action'], $_POST['selected_ids']);
        $bulkMessage = $result['message'];
    }
}
?>
<h1><?= trans('rooms') ?></h1>

<?php if ($bulkMessage): ?>
    <p class="<?= strpos($bulkMessage, 'successfully') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($bulkMessage) ?></p>
<?php endif; ?>

<a href="add.php" class="cta">+ <?= trans('add_room') ?></a>

<form method="post" id="bulkForm" style="margin: 1rem 0; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <?= csrf_field() ?>
    <select name="bulk_action" required style="padding: 0.5rem; border-radius: var(--radius); border: 1px solid var(--color-border);">
        <option value=""><?= trans('bulk_actions') ?></option>
        <option value="delete"><?= trans('delete_selected') ?></option>
        <option value="set_available"><?= trans('set_available') ?></option>
        <option value="set_maintenance"><?= trans('set_maintenance') ?></option>
    </select>
    <button type="submit" class="cta" style="padding: 0.5rem 1rem;" onclick="return confirm('<?= trans('are_you_sure_delete_selected') ?>')"><?= trans('apply') ?></button>
    <a href="export.php" class="cta" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #27ae60, #2ecc71);"><?= trans('export_csv') ?></a>
</form>

<table class="data-table">
    <tr>
        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
        <th><?= trans('room_number') ?></th>
        <th><?= trans('type') ?></th>
        <th><?= trans('floor') ?></th>
        <th><?= trans('status') ?></th>
        <th><?= trans('photos') ?></th>
        <th></th>
    </tr>
    <?php foreach ($rooms as $r): ?>
    <tr>
        <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$r['id'] ?>" class="bulk-checkbox"></td>
        <td><?= htmlspecialchars($r['room_number']) ?></td>
        <td><?= htmlspecialchars($r['type_name']) ?></td>
        <td><?= htmlspecialchars($r['floor']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
        <td>
            <?php
            $photoCount = $pdo->prepare('SELECT COUNT(*) FROM room_photos WHERE room_id = ?');
            $photoCount->execute([$r['id']]);
            $count = (int)$photoCount->fetchColumn();
            echo $count . ' ' . trans('photo' . ($count != 1 ? 's' : ''));
            if ($count > 0) {
                echo ' <a href="photos.php?room_id=' . (int)$r['id'] . '" style="font-size:0.85rem;">[' . trans('manage') . ']</a>';
            }
            ?>
        </td>
        <td><a href="edit.php?id=<?= (int)$r['id'] ?>"><?= trans('edit') ?></a> | <a href="delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('<?= trans('delete_this_room') ?>')"><?= trans('delete') ?></a></td>
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
