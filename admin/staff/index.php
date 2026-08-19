<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$stmt = $pdo->query(
    'SELECT u.id, u.full_name, u.email, r.name AS role, s.position, s.is_active
     FROM users u JOIN roles r ON u.role_id = r.id
     LEFT JOIN staff s ON s.user_id = u.id
     WHERE r.name IN ("staff","admin") ORDER BY u.full_name'
);
$staffList = $stmt->fetchAll();
?>
<h1>Staff</h1>
<a href="add.php" class="cta">+ Add Staff</a>
<table class="data-table">
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Position</th><th>Active</th><th></th></tr>
    <?php foreach ($staffList as $s): ?>
    <tr>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td><?= htmlspecialchars($s['email']) ?></td>
        <td><?= htmlspecialchars($s['role']) ?></td>
        <td><?= htmlspecialchars($s['position'] ?? '—') ?></td>
        <td><?= $s['is_active'] ? 'Yes' : 'No' ?></td>
        <td><a href="edit.php?id=<?= (int)$s['id'] ?>">Edit</a> | <a href="permissions.php?id=<?= (int)$s['id'] ?>">Permissions</a> | <a href="delete.php?id=<?= (int)$s['id'] ?>" onclick="return confirm('Remove this staff member?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
