<?php require __DIR__ . '/../../../includes/admin-header.php';

$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, name FROM room_types WHERE id = ?');
$stmt->execute([$roomTypeId]);
$roomType = $stmt->fetch();
if (!$roomType) die('Room type not found.');

$stmt = $pdo->prepare('SELECT * FROM room_type_pricing WHERE room_type_id = ? ORDER BY valid_from DESC');
$stmt->execute([$roomTypeId]);
$pricing = $stmt->fetchAll();
?>
<h1>Seasonal Pricing — <?= htmlspecialchars($roomType['name']) ?></h1>
<a href="add.php?room_type_id=<?= (int)$roomTypeId ?>" class="cta">+ Add Pricing Period</a>
<table class="data-table">
    <tr><th>Price</th><th>Valid From</th><th>Valid Until</th><th>Note</th><th></th></tr>
    <?php foreach ($pricing as $p): ?>
    <tr>
        <td><?= format_currency($p['price']) ?></td>
        <td><?= htmlspecialchars($p['valid_from']) ?></td>
        <td><?= htmlspecialchars($p['valid_until']) ?></td>
        <td><?= htmlspecialchars($p['note'] ?? '') ?></td>
        <td><a href="edit.php?id=<?= (int)$p['id'] ?>">Edit</a> | <a href="delete.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('Delete this pricing period?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<p><a href="../index.php">&larr; Back to Room Types</a></p>
<?php require __DIR__ . '/../../../includes/admin-footer.php'; ?>
