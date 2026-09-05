<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_bookings');

$stmt = $pdo->query(
    'SELECT rev.*, u.full_name, rt.name AS type_name FROM reviews rev
     JOIN users u ON rev.user_id = u.id
     JOIN room_types rt ON rev.room_type_id = rt.id
     ORDER BY rev.created_at DESC'
);
$reviews = $stmt->fetchAll();
?>
<h1>Reviews</h1>
<table class="data-table">
    <tr><th>Guest</th><th>Room Type</th><th>Rating</th><th>Comment</th><th>Date</th><th></th></tr>
    <?php foreach ($reviews as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['full_name']) ?></td>
        <td><?= htmlspecialchars($r['type_name']) ?></td>
        <td class="stars"><?= str_repeat('★', $r['rating']) ?></td>
        <td><?= htmlspecialchars($r['comment']) ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><a href="delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this review?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
