<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$action = $_GET['action'] ?? '';
$userId = (int)($_GET['id'] ?? 0);

if ($action === 'approve' && $userId > 0) {
    $stmt = $pdo->prepare('UPDATE users SET approval_status = "approved" WHERE id = ? AND approval_status = "pending"');
    $stmt->execute([$userId]);
    header('Location: index.php');
    exit;
}

if ($action === 'reject' && $userId > 0) {
    $stmt = $pdo->prepare('UPDATE users SET approval_status = "rejected" WHERE id = ? AND approval_status = "pending"');
    $stmt->execute([$userId]);
    header('Location: index.php');
    exit;
}

$stmt = $pdo->query(
    'SELECT u.id, u.full_name, u.email, r.name AS role, s.position, s.is_active, u.approval_status
     FROM users u JOIN roles r ON u.role_id = r.id
     LEFT JOIN staff s ON s.user_id = u.id
     WHERE r.name IN ("staff","admin") ORDER BY u.approval_status DESC, u.full_name'
);
$staffList = $stmt->fetchAll();
?>
<h1>Staff</h1>
<a href="add.php" class="cta">+ Add Staff</a>
<table class="data-table">
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Position</th><th>Active</th><th>Status</th><th></th></tr>
    <?php foreach ($staffList as $s): ?>
    <tr>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td><?= htmlspecialchars($s['email']) ?></td>
        <td><?= htmlspecialchars($s['role']) ?></td>
        <td><?= htmlspecialchars($s['position'] ?? '—') ?></td>
        <td><?= $s['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
            <?php if ($s['approval_status'] === 'pending'): ?>
                <span class="status status-pending">Pending</span>
                <a href="?action=approve&id=<?= (int)$s['id'] ?>" class="cta" style="padding:0.35rem 0.7rem; font-size:0.85rem; margin-left:0.5rem;" onclick="return confirm('Approve this staff member?')">Approve</a>
                <a href="?action=reject&id=<?= (int)$s['id'] ?>" class="cta cta-danger" style="padding:0.35rem 0.7rem; font-size:0.85rem; margin-left:0.25rem;" onclick="return confirm('Reject this staff member?')">Reject</a>
            <?php elseif ($s['approval_status'] === 'approved'): ?>
                <span class="status status-confirmed">Approved</span>
            <?php else: ?>
                <span class="status status-cancelled">Rejected</span>
            <?php endif; ?>
        </td>
        <td><a href="edit.php?id=<?= (int)$s['id'] ?>">Edit</a> | <a href="permissions.php?id=<?= (int)$s['id'] ?>">Permissions</a> | <a href="delete.php?id=<?= (int)$s['id'] ?>" onclick="return confirm('Remove this staff member?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
