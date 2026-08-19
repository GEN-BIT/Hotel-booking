<?php require __DIR__ . '/../../includes/admin-header.php';

$stmt = $pdo->prepare(
    'SELECT u.*, COUNT(b.id) AS booking_count FROM users u
     JOIN roles r ON u.role_id = r.id
     LEFT JOIN bookings b ON b.user_id = u.id
     WHERE r.name = "guest"
     GROUP BY u.id ORDER BY u.full_name'
);
$stmt->execute();
$guests = $stmt->fetchAll();
?>
<h1>Guests</h1>
<table class="data-table">
    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th></th></tr>
    <?php foreach ($guests as $g): ?>
    <tr>
        <td><?= htmlspecialchars($g['full_name']) ?></td>
        <td><?= htmlspecialchars($g['email']) ?></td>
        <td><?= htmlspecialchars($g['phone'] ?? '—') ?></td>
        <td><?= (int)$g['booking_count'] ?></td>
        <td><a href="view.php?id=<?= (int)$g['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
