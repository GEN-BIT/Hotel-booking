<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-header.php';
require_once __DIR__ . '/../../includes/image-uploader.php';
require_permission('manage_guests');

$guestId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role_id = (SELECT id FROM roles WHERE name = "guest")');
$stmt->execute([$guestId]);
$guest = $stmt->fetch();

if (!$guest) {
    die('Guest not found.');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $uploader = new ImageUploader(__DIR__ . '/../../uploads/documents/');
    $result = $uploader->upload($_FILES['document'], 'doc_' . $guestId . '_');
    
    if ($result['success']) {
        $stmt = $pdo->prepare(
            'INSERT INTO guest_documents (guest_id, file_path, file_name, file_type, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $guestId,
            $result['filename'],
            $_FILES['document']['name'],
            $_FILES['document']['type'],
            $_FILES['document']['size'],
            $_SESSION['user_id']
        ]);
        
        log_activity($pdo, 'document.uploaded', "Document uploaded for guest {$guest['full_name']}");
        $success = 'Document uploaded successfully.';
    } else {
        $error = $result['error'];
    }
}

$documents = $pdo->prepare(
    'SELECT * FROM guest_documents WHERE guest_id = ? ORDER BY uploaded_at DESC'
);
$documents->execute([$guestId]);
$documents = $documents->fetchAll();
?>
<h1>Guest Documents — <?= htmlspecialchars($guest['full_name']) ?></h1>

<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin: 0 0 1rem;">Upload Document</h3>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Document
            <input type="file" name="document" accept="image/*,.pdf" required>
        </label>
        <p class="notice">Accepted formats: JPG, PNG, PDF. Maximum size: 5MB.</p>
        <button type="submit">Upload Document</button>
    </form>
</div>

<h3>Uploaded Documents</h3>
<?php if (empty($documents)): ?>
    <p>No documents uploaded yet.</p>
<?php else: ?>
<table class="data-table">
    <tr><th>File Name</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr>
    <?php foreach ($documents as $doc): ?>
    <tr>
        <td><?= htmlspecialchars($doc['file_name']) ?></td>
        <td><?= htmlspecialchars($doc['file_type']) ?></td>
        <td><?= number_format($doc['file_size'] / 1024, 1) ?> KB</td>
        <td><?= htmlspecialchars($doc['uploaded_at']) ?></td>
        <td>
            <a href="<?= BASE_URL ?>uploads/documents/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">View</a> |
            <a href="delete-document.php?id=<?= (int)$doc['id'] ?>" onclick="return confirm('Delete this document?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<a href="index.php">Back to Guests</a>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>