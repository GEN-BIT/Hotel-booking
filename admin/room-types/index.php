<?php require __DIR__ . '/../../includes/admin-header.php';
$types = $pdo->query('SELECT * FROM room_types ORDER BY name')->fetchAll();
?>
<h1>Room Types</h1>
<a href="add.php" class="cta">+ Add Room Type</a>
<table class="data-table">
    <tr><th>Name</th><th>Base Price</th><th>Max Occupancy</th><th></th></tr>
    <?php foreach ($types as $t): ?>
    <tr>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td>$<?= number_format($t['base_price'], 2) ?></td>
        <td><?= (int)$t['max_occupancy'] ?></td>
        <td><a href="edit.php?id=<?= (int)$t['id'] ?>">Edit</a> | <a href="delete.php?id=<?= (int)$t['id'] ?>" onclick="return confirm('Delete this room type?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
