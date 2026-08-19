<?php require_once __DIR__ . '/../../config/config.php';
require_role_any(['admin']);
$id = (int)($_GET['id'] ?? 0);

if ($id === (int)$_SESSION['user_id']) {
    die('You cannot delete your own account.');
}

$pdo->prepare('DELETE FROM staff WHERE user_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
header('Location: index.php');
exit;
