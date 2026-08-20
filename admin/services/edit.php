<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_rooms');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$id]);
$service = $stmt->fetch();
if (!$service) die('Service not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!$name || $price < 0) {
        $error = 'Name and a valid price are required.';
    } else {
        $stmt = $pdo->prepare('UPDATE services SET name=?, description=?, price=?, is_active=? WHERE id=?');
        $stmt->execute([$name, $description, $price, $isActive, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Edit Service</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($service['name']) ?>" required></label>
    <label>Description <textarea name="description"><?= htmlspecialchars($service['description']) ?></textarea></label>
    <label>Price <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($service['price']) ?>" required></label>
    <label class="checkbox"><input type="checkbox" name="is_active" <?= $service['is_active'] ? 'checked' : '' ?>> Active</label>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
