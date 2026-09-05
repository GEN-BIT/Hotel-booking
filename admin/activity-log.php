<?php require __DIR__ . '/../includes/admin-header.php';

$actionFilter = $_GET['action'] ?? '';
$sql = 'SELECT * FROM activity_log';
$params = [];
if ($actionFilter) {
    $sql .= ' WHERE action = ?';
    $params[] = $actionFilter;
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actionTypes = $pdo->query('SELECT DISTINCT action FROM activity_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
?>
<h1>Activity Log</h1>
<form method="get" class="filter-form">
    <select name="action" onchange="this.form.submit()">
        <option value="">All Actions</option>
        <?php foreach ($actionTypes as $a): ?>
        <option value="<?= htmlspecialchars($a) ?>" <?= $a === $actionFilter ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<table class="data-table">
    <tr><th>When</th><th>Who</th><th>Action</th><th>Details</th></tr>
    <?php foreach ($logs as $l): ?>
    <tr>
        <td><?= htmlspecialchars($l['created_at']) ?></td>
        <td><?= htmlspecialchars($l['actor_name']) ?></td>
        <td><?= htmlspecialchars($l['action']) ?></td>
        <td><?= htmlspecialchars($l['description']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
