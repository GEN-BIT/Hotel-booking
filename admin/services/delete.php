<?php
require __DIR__ . '/../../includes/admin-header.php';
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
}
header('Location: index.php');
exit;
