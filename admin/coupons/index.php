<?php require __DIR__ . '/../../includes/admin-header.php';

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
?>
<h1>Coupons</h1>
<a href="add.php" class="cta">+ Add Coupon</a>
<table class="data-table">
    <tr><th>Code</th><th>Discount</th><th>Uses</th><th>Valid</th><th>Active</th><th></th></tr>
    <?php foreach ($coupons as $c): ?>
    <tr>
        <td><?= htmlspecialchars($c['code']) ?></td>
        <td><?= $c['discount_type'] === 'percentage' ? (int)$c['discount_value'] . '%' : '$' . number_format($c['discount_value'], 2) ?></td>
        <td><?= (int)$c['times_used'] ?><?= $c['max_uses'] !== null ? ' / ' . (int)$c['max_uses'] : '' ?></td>
        <td><?= $c['valid_from'] ? htmlspecialchars($c['valid_from']) : '—' ?> &rarr; <?= $c['valid_until'] ? htmlspecialchars($c['valid_until']) : '—' ?></td>
        <td><?= $c['is_active'] ? 'Yes' : 'No' ?></td>
        <td><a href="edit.php?id=<?= (int)$c['id'] ?>">Edit</a> | <a href="delete.php?id=<?= (int)$c['id'] ?>" onclick="return confirm('Delete this coupon?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
