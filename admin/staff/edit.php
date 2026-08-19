<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT u.*, s.position, s.is_active FROM users u LEFT JOIN staff s ON s.user_id = u.id WHERE u.id = ?');
$stmt->execute([$id]);
$staffMember = $stmt->fetch();
if (!$staffMember) die('Staff member not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!$name) {
        $error = 'Name is required.';
    } else {
        $pdo->prepare('UPDATE users SET full_name=? WHERE id=?')->execute([$name, $id]);
        $pdo->prepare('UPDATE staff SET position=?, is_active=? WHERE user_id=?')->execute([$position, $isActive, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Edit Staff</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Full Name <input type="text" name="full_name" value="<?= htmlspecialchars($staffMember['full_name']) ?>" required></label>
    <label>Position <input type="text" name="position" value="<?= htmlspecialchars($staffMember['position'] ?? '') ?>"></label>
    <label class="checkbox"><input type="checkbox" name="is_active" <?= $staffMember['is_active'] ? 'checked' : '' ?>> Active</label>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
