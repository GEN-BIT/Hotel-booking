<?php require __DIR__ . '/../../includes/admin-header.php';
require_once __DIR__ . '/../../includes/bulk-actions.php';
require_permission('manage_guests');

$stmt = $pdo->prepare(
    'SELECT u.*, COUNT(b.id) AS booking_count FROM users u
     JOIN roles r ON u.role_id = r.id
     LEFT JOIN bookings b ON b.user_id = u.id
     WHERE r.name = "guest"
     GROUP BY u.id ORDER BY u.full_name'
);
$stmt->execute();
$guests = $stmt->fetchAll();

$bulkMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action']) && !empty($_POST['selected_ids'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $bulkMessage = 'Invalid CSRF token.';
    } else {
        $bulk = new BulkActions($pdo);
        $result = $bulk->process('guests', $_POST['bulk_action'], $_POST['selected_ids']);
        $bulkMessage = $result['message'];
        
        if ($result['success'] && isset($result['filepath'])) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            readfile($result['filepath']);
            unlink($result['filepath']);
            exit;
        }
    }
}
?>
<h1><?= trans('guests') ?></h1>

<?php if ($bulkMessage): ?>
    <p class="success"><?= htmlspecialchars($bulkMessage) ?></p>
<?php endif; ?>

<form method="post" id="bulkForm" style="margin: 1rem 0; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
    <?= csrf_field() ?>
    <select name="bulk_action" required style="padding: 0.5rem; border-radius: var(--radius); border: 1px solid var(--color-border);">
        <option value=""><?= trans('bulk_actions') ?></option>
        <option value="export"><?= trans('export_csv') ?></option>
    </select>
    <button type="submit" class="cta" style="padding: 0.5rem 1rem;" onclick="return confirm('Export selected guests?')"><?= trans('apply') ?></button>
</form>

<table class="data-table">
    <tr>
        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
        <th><?= trans('name') ?></th>
        <th><?= trans('email') ?></th>
        <th><?= trans('phone') ?></th>
        <th><?= trans('bookings') ?></th>
        <th></th>
    </tr>
    <?php foreach ($guests as $g): ?>
    <tr>
        <td><input type="checkbox" name="selected_ids[]" value="<?= (int)$g['id'] ?>" class="bulk-checkbox"></td>
        <td><?= htmlspecialchars($g['full_name']) ?></td>
        <td><?= htmlspecialchars($g['email']) ?></td>
        <td><?= htmlspecialchars($g['phone'] ?? '—') ?></td>
        <td><?= (int)$g['booking_count'] ?></td>
        <td><a href="view.php?id=<?= (int)$g['id'] ?>"><?= trans('view') ?></a></td>
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
