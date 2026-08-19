<?php require __DIR__ . '/../../includes/admin-header.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    if (!$name) {
        $error = 'Name is required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO amenities (name, category, icon) VALUES (?, ?, ?)');
        $stmt->execute([$name, $category, $icon]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Add Amenity</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Name <input type="text" name="name" required></label>
    <label>Category <input type="text" name="category" placeholder="e.g. Room Features, Bathroom, Entertainment"></label>
    <label>Icon (optional class/name) <input type="text" name="icon"></label>
    <button type="submit">Add Amenity</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
