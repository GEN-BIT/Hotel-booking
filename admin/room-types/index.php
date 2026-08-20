<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_rooms');
$types = $pdo->query('SELECT * FROM room_types ORDER BY name')->fetchAll();
?>
<h1><?= trans('room_types') ?></h1>
<a href="add.php" class="cta">+ <?= trans('add_room_type') ?></a>
<table class="data-table">
    <tr><th><?= trans('name') ?></th><th><?= trans('base_price') ?></th><th><?= trans('max_occupancy') ?></th><th><?= trans('bed_type') ?></th><th><?= trans('room_size') ?></th><th><?= trans('building') ?></th><th></th></tr>
    <?php foreach ($types as $t): ?>
    <tr>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td>$<?= number_format($t['base_price'], 2) ?></td>
        <td><?= (int)$t['max_occupancy'] ?></td>
        <td><?= htmlspecialchars($t['bed_type'] ?? '—') ?></td>
        <td><?= htmlspecialchars($t['room_size'] ?? '—') ?></td>
        <td><?= htmlspecialchars($t['building'] ?? '—') ?></td>
        <td>
            <a href="edit.php?id=<?= (int)$t['id'] ?>"><?= trans('edit') ?></a> |
            <a href="delete.php?id=<?= (int)$t['id'] ?>" onclick="return confirm('<?= trans('delete_this_room_type') ?>')"><?= trans('delete') ?></a> |
            <a href="photos/index.php?room_type_id=<?= (int)$t['id'] ?>"><?= trans('photos') ?></a> |
            <a href="pricing/index.php?room_type_id=<?= (int)$t['id'] ?>"><?= trans('pricing') ?></a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
