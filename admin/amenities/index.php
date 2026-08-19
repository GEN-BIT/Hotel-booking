<?php require __DIR__ . '/../../includes/admin-header.php';
$amenities = $pdo->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
?>
<h1>Amenities</h1>
<a href="add.php" class="cta">+ Add Amenity</a>
<table class="data-table">
    <tr><th>Name</th><th></th></tr>
    <?php foreach ($amenities as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['name']) ?></td>
        <td><a href="edit.php?id=<?= (int)$a['id'] ?>">Edit</a> | <a href="delete.php?id=<?= (int)$a['id'] ?>" onclick="return confirm('Delete this amenity?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
