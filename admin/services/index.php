<?php require __DIR__ . '/../../includes/admin-header.php';
$services = $pdo->query('SELECT * FROM services ORDER BY name')->fetchAll();
?>
<h1><?= trans('services') ?></h1>
<a href="add.php" class="cta">+ <?= trans('add_service') ?></a>
<table class="data-table">
    <tr><th><?= trans('name') ?></th><th><?= trans('price') ?></th><th><?= trans('active') ?></th><th></th></tr>
    <?php foreach ($services as $s): ?>
    <tr>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td>$<?= number_format($s['price'], 2) ?></td>
        <td><?= $s['is_active'] ? trans('yes') : trans('no') ?></td>
        <td><a href="edit.php?id=<?= (int)$s['id'] ?>"><?= trans('edit') ?></a> | <a href="delete.php?id=<?= (int)$s['id'] ?>" onclick="return confirm('<?= trans('delete_this_service') ?>')"><?= trans('delete') ?></a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
