<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin']);
$id = (int)($_GET['id'] ?? 0);

if ($id === (int)$_SESSION['user_id']) {
    die('You cannot delete your own account.');
}

$nameStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
$nameStmt->execute([$id]);
$removedName = $nameStmt->fetchColumn();

$pdo->prepare('DELETE FROM staff WHERE user_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
log_activity($pdo, 'staff.removed', "Staff account removed: $removedName");
header('Location: index.php');
exit;
