<?php require __DIR__ . '/../../includes/admin-header.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? 'percentage';
    $value = (float)($_POST['discount_value'] ?? 0);
    $maxUses = trim($_POST['max_uses'] ?? '');
    $validFrom = trim($_POST['valid_from'] ?? '');
    $validUntil = trim($_POST['valid_until'] ?? '');

    if (!$code || $value <= 0) {
        $error = 'Code and a valid discount value are required.';
    } elseif ($type === 'percentage' && $value > 100) {
        $error = 'Percentage discount cannot exceed 100.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM coupons WHERE code = ?');
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            $error = 'A coupon with that code already exists.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO coupons (code, discount_type, discount_value, max_uses, valid_from, valid_until, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $code, $type, $value,
                $maxUses !== '' ? (int)$maxUses : null,
                $validFrom !== '' ? $validFrom : null,
                $validUntil !== '' ? $validUntil : null,
            ]);
            log_activity($pdo, 'coupon.added', "Coupon $code created ($type, $value)");
            header('Location: index.php');
            exit;
        }
    }
}
?>
<h1>Add Coupon</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Code <input type="text" name="code" required placeholder="e.g. SUMMER25"></label>
    <label>Discount Type
        <select name="discount_type">
            <option value="percentage">Percentage (%)</option>
            <option value="fixed">Fixed Amount ($)</option>
        </select>
    </label>
    <label>Discount Value <input type="number" step="0.01" name="discount_value" required></label>
    <label>Max Uses (optional) <input type="number" name="max_uses" placeholder="Unlimited"></label>
    <label>Valid From (optional) <input type="date" name="valid_from"></label>
    <label>Valid Until (optional) <input type="date" name="valid_until"></label>
    <button type="submit">Add Coupon</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
