<?php require __DIR__ . '/../../includes/admin-header.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
$stmt->execute([$id]);
$coupon = $stmt->fetch();
if (!$coupon) die('Coupon not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['discount_type'] ?? 'percentage';
    $value = (float)($_POST['discount_value'] ?? 0);
    $maxUses = trim($_POST['max_uses'] ?? '');
    $validFrom = trim($_POST['valid_from'] ?? '');
    $validUntil = trim($_POST['valid_until'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($value <= 0) {
        $error = 'A valid discount value is required.';
    } elseif ($type === 'percentage' && $value > 100) {
        $error = 'Percentage discount cannot exceed 100.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE coupons SET discount_type=?, discount_value=?, max_uses=?, valid_from=?, valid_until=?, is_active=? WHERE id=?'
        );
        $stmt->execute([
            $type, $value,
            $maxUses !== '' ? (int)$maxUses : null,
            $validFrom !== '' ? $validFrom : null,
            $validUntil !== '' ? $validUntil : null,
            $isActive, $id,
        ]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Edit Coupon — <?= htmlspecialchars($coupon['code']) ?></h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Discount Type
        <select name="discount_type">
            <option value="percentage" <?= $coupon['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
            <option value="fixed" <?= $coupon['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount ($)</option>
        </select>
    </label>
    <label>Discount Value <input type="number" step="0.01" name="discount_value" value="<?= htmlspecialchars($coupon['discount_value']) ?>" required></label>
    <label>Max Uses (optional) <input type="number" name="max_uses" value="<?= htmlspecialchars($coupon['max_uses'] ?? '') ?>" placeholder="Unlimited"></label>
    <label>Valid From (optional) <input type="date" name="valid_from" value="<?= htmlspecialchars($coupon['valid_from'] ?? '') ?>"></label>
    <label>Valid Until (optional) <input type="date" name="valid_until" value="<?= htmlspecialchars($coupon['valid_until'] ?? '') ?>"></label>
    <label class="checkbox"><input type="checkbox" name="is_active" <?= $coupon['is_active'] ? 'checked' : '' ?>> Active</label>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
