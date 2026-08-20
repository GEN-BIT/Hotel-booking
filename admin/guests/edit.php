<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_guests');
require_role_any(['admin']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$guest = $stmt->fetch();
if (!$guest) die('Guest not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (!$name) {
        $error = 'Name is required.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name=?, phone=? WHERE id=?');
        $stmt->execute([$name, $phone, $id]);
        header('Location: view.php?id=' . $id);
        exit;
    }
}
?>
<h1>Edit Guest</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Full Name <input type="text" name="full_name" value="<?= htmlspecialchars($guest['full_name']) ?>" required></label>
    <label>Phone <input type="text" name="phone" value="<?= htmlspecialchars($guest['phone'] ?? '') ?>"></label>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
