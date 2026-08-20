<?php require __DIR__ . '/../../../includes/admin-header.php';

$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
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
        $stmt = $pdo->prepare('INSERT INTO room_type_pricing (room_type_id, price, valid_from, valid_until, note) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$roomTypeId, $price, $validFrom, $validUntil, $note]);
        header('Location: index.php?room_type_id=' . $roomTypeId);
        exit;
    }
}
?>
<h1>Add Seasonal Pricing</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Price <input type="number" step="0.01" name="price" required></label>
    <label>Valid From <input type="date" name="valid_from" required></label>
    <label>Valid Until <input type="date" name="valid_until" required></label>
    <label>Note <input type="text" name="note" placeholder="e.g. Holiday season"></label>
    <button type="submit">Add Pricing Period</button>
</form>
<p><a href="index.php?room_type_id=<?= (int)$roomTypeId ?>">Cancel</a></p>
<?php require __DIR__ . '/../../../includes/admin-footer.php'; ?>
