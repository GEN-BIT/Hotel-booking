<?php
require_once __DIR__ . '/../../config/config.php';
require_permission('manage_guests');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT file_path FROM guest_documents WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if ($doc) {
    $filepath = __DIR__ . '/../../uploads/documents/' . $doc['file_path'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    $pdo->prepare('DELETE FROM guest_documents WHERE id = ?')->execute([$id]);
    $_SESSION['flash_success'] = 'Document deleted successfully.';
}

header('Location: documents.php?id=' . ($_GET['guest_id'] ?? 0));
exit;
