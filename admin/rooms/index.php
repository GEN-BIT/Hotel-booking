<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_rooms');

$rooms = $pdo->query(
    'SELECT r.*, rt.name AS type_name FROM rooms r
     JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number'
)->fetchAll();
?>
<h1><?= trans('rooms') ?></h1>
<a href="add.php" class="cta">+ <?= trans('add_room') ?></a>
<table class="data-table">
    <tr><th><?= trans('room_number') ?></th><th><?= trans('type') ?></th><th><?= trans('floor') ?></th><th><?= trans('status') ?></th><th><?= trans('photos') ?></th><th></th></tr>
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
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
