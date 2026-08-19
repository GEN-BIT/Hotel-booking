<?php require __DIR__ . '/../../includes/admin-header.php';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM amenities WHERE id = ?');
$stmt->execute([$id]);
$amenity = $stmt->fetch();
if (!$amenity) die('Amenity not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    if (!$name) {
        $error = 'Name is required.';
    } else {
        $stmt = $pdo->prepare('UPDATE amenities SET name=?, icon=? WHERE id=?');
        $stmt->execute([$name, $icon, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Edit Amenity</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($amenity['name']) ?>" required></label>
    <label>Icon <input type="text" name="icon" value="<?= htmlspecialchars($amenity['icon']) ?>"></label>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
