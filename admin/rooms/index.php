<?php require __DIR__ . '/../../includes/admin-header.php';

$rooms = $pdo->query(
    'SELECT r.*, rt.name AS type_name FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number'
)->fetchAll();
?>
<h1>Rooms</h1>
<a href="add.php" class="cta">+ Add Room</a>
<table class="data-table">
    <tr><th>Number</th><th>Type</th><th>Floor</th><th>Status</th><th>Photos</th><th></th></tr>
    <?php foreach ($rooms as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['room_number']) ?></td>
        <td><?= htmlspecialchars($r['type_name']) ?></td>
        <td><?= htmlspecialchars($r['floor']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
        <td>
            <?php
            $photoCount = $pdo->prepare('SELECT COUNT(*) FROM room_photos WHERE room_id = ?');
            $photoCount->execute([$r['id']]);
            $count = (int)$photoCount->fetchColumn();
            echo $count . ' photo' . ($count != 1 ? 's' : '');
            if ($count > 0) {
                echo ' <a href="photos.php?room_id=' . (int)$r['id'] . '" style="font-size:0.85rem;">[manage]</a>';
            }
            ?>
        </td>
        <td><a href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a> | <a href="delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this room?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
