<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_rooms');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if (!$name || $price < 0) {
        $error = 'Name and a valid price are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO services (name, description, price, is_active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$name, $description, $price]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Add Service</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Name <input type="text" name="name" required placeholder="e.g. Airport Shuttle"></label>
    <label>Description <textarea name="description"></textarea></label>
    <label>Price <input type="number" step="0.01" name="price" required></label>
    <button type="submit">Add Service</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
