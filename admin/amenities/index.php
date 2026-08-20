<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_rooms');
$amenities = $pdo->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
?>
<h1><?= trans('amenities') ?></h1>
<a href="add.php" class="cta">+ <?= trans('add_amenity') ?></a>
<table class="data-table">
    <tr><th><?= trans('name') ?></th><th></th></tr>
    <?php foreach ($amenities as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['name']) ?></td>
        <td><a href="edit.php?id=<?= (int)$a['id'] ?>"><?= trans('edit') ?></a> | <a href="delete.php?id=<?= (int)$a['id'] ?>" onclick="return confirm('<?= trans('delete_this_amenity') ?>')"><?= trans('delete') ?></a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
