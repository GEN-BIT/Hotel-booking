<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('view_reports');

$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$sql = 'SELECT * FROM email_logs';
$params = [];
if ($statusFilter) {
    $sql .= ' WHERE status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM email_logs' . ($statusFilter ? ' WHERE status = ?' : ''));
$totalStmt->execute($params ?: []);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
?>
<h1>Email Logs</h1>

<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1rem; margin-bottom: 1.5rem;">
    <form method="get" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <label style="display: flex; align-items: center; gap: 0.5rem;">
            Status:
            <select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </label>
    </form>
</div>

<table class="data-table">
    <tr><th>Time</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Error</th></tr>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= htmlspecialchars($log['created_at']) ?></td>
        <td>
            <?= htmlspecialchars($log['recipient_email']) ?>
            <?php if ($log['recipient_name']): ?>
                <br><small><?= htmlspecialchars($log['recipient_name']) ?></small>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($log['subject']) ?></td>
        <td>
            <span class="status status-<?= $log['status'] === 'sent' ? 'active' : ($log['status'] === 'failed' ? 'cancelled' : 'pending') ?>">
                <?= htmlspecialchars($log['status']) ?>
            </span>
            <?php if ($log['sent_at']): ?>
                <br><small><?= htmlspecialchars($log['sent_at']) ?></small>
            <?php endif; ?>
        </td>
        <td><?= $log['error_message'] ? htmlspecialchars($log['error_message']) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if ($totalPages > 1): ?>
<div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: center;">
    <?php if ($page > 1): ?>
        <a href="?status=<?= urlencode($statusFilter) ?>&page=<?= $page - 1 ?>" class="cta">Previous</a>
    <?php endif; ?>
    <span style="padding: 0.5rem 1rem;">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
        <a href="?status=<?= urlencode($statusFilter) ?>&page=<?= $page + 1 ?>" class="cta">Next</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>