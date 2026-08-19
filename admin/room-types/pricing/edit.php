<?php require __DIR__ . '/../../../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM room_type_pricing WHERE id = ?');
$stmt->execute([$id]);
$pricing = $stmt->fetch();
if (!$pricing) die('Pricing period not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = (float)($_POST['price'] ?? 0);
    $validFrom = $_POST['valid_from'] ?? '';
    $validUntil = $_POST['valid_until'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (!$price || !$validFrom || !$validUntil) {
        $error = 'Price and date range are required.';
    } elseif ($validUntil <= $validFrom) {
        $error = 'Valid until must be after valid from.';
    } else {
        $stmt = $pdo->prepare('UPDATE room_type_pricing SET price=?, valid_from=?, valid_until=?, note=? WHERE id=?');
        $stmt->execute([$price, $validFrom, $validUntil, $note, $id]);
        header('Location: index.php?room_type_id=' . $pricing['room_type_id']);
        exit;
    }
}
$roomTypeId = $pricing['room_type_id'];
?>
<h1>Edit Seasonal Pricing</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Price <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($pricing['price']) ?>" required></label>
    <label>Valid From <input type="date" name="valid_from" value="<?= htmlspecialchars($pricing['valid_from']) ?>" required></label>
    <label>Valid Until <input type="date" name="valid_until" value="<?= htmlspecialchars($pricing['valid_until']) ?>" required></label>
    <label>Note <input type="text" name="note" value="<?= htmlspecialchars($pricing['note'] ?? '') ?>"></label>
    <button type="submit">Save Changes</button>
</form>
<p><a href="index.php?room_type_id=<?= (int)$roomTypeId ?>">Cancel</a></p>
<?php require __DIR__ . '/../../../includes/admin-footer.php'; ?>
