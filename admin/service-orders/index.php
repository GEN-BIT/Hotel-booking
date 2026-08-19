<?php require __DIR__ . '/../../includes/admin-header.php';

$statusFilter = $_GET['status'] ?? '';
$sql = 'SELECT so.*, s.name AS service_name, b.booking_reference, u.full_name
        FROM service_orders so
        JOIN services s ON so.service_id = s.id
        JOIN bookings b ON so.booking_id = b.id
        JOIN users u ON b.user_id = u.id';
$params = [];
if ($statusFilter) {
    $sql .= ' WHERE so.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY so.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<h1>Service Orders</h1>
<form method="get" class="filter-form">
    <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['requested','fulfilled','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<table class="data-table">
    <tr><th>Booking</th><th>Guest</th><th>Service</th><th>Qty</th><th>Total</th><th>Status</th><th></th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td><?= htmlspecialchars($o['booking_reference']) ?></td>
        <td><?= htmlspecialchars($o['full_name']) ?></td>
        <td><?= htmlspecialchars($o['service_name']) ?></td>
        <td><?= (int)$o['quantity'] ?></td>
        <td>$<?= number_format($o['unit_price'] * $o['quantity'], 2) ?></td>
        <td><?= htmlspecialchars($o['status']) ?></td>
        <td>
            <?php if ($o['status'] === 'requested'): ?>
                <a href="fulfill.php?id=<?= (int)$o['id'] ?>">Mark Fulfilled</a> |
                <a href="cancel.php?id=<?= (int)$o['id'] ?>" onclick="return confirm('Cancel this order?')">Cancel</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
