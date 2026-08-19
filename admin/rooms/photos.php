<?php require __DIR__ . '/../../includes/admin-header.php';

$roomId = (int)($_GET['room_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) die('Room not found.');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $error = 'Only JPG, PNG, and WebP images are allowed.';
        } else {
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };
            $filename = 'room_' . $roomId . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../../uploads/rooms/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare('INSERT INTO room_photos (room_id, file_path, sort_order) VALUES (?, ?, ?)');
                $maxSort = $pdo->prepare('SELECT MAX(sort_order) FROM room_photos WHERE room_id = ?');
                $maxSort->execute([$roomId]);
                $sort = ((int)$maxSort->fetchColumn()) + 1;
                $stmt->execute([$roomId, 'uploads/rooms/' . $filename, $sort]);
                $success = 'Photo uploaded successfully.';
            } else {
                $error = 'Failed to upload photo.';
            }
        }
    } else {
        $error = 'Please select a photo to upload.';
    }
}

if (isset($_POST['delete_photo'])) {
    $photoId = (int)($_POST['photo_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT file_path FROM room_photos WHERE id = ? AND room_id = ?');
    $stmt->execute([$photoId, $roomId]);
    $photo = $stmt->fetch();
    if ($photo) {
        @unlink(__DIR__ . '/../../' . $photo['file_path']);
        $pdo->prepare('DELETE FROM room_photos WHERE id = ?')->execute([$photoId]);
        $success = 'Photo deleted.';
    }
}

$photos = $pdo->prepare('SELECT * FROM room_photos WHERE room_id = ? ORDER BY sort_order ASC, id ASC');
$photos->execute([$roomId]);
$photos = $photos->fetchAll();
?>
<h1>Photos — Room <?= htmlspecialchars($room['room_number']) ?></h1>
<p><a href="edit.php?id=<?= (int)$room['id'] ?>">&larr; Back to Edit Room</a></p>

<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" style="margin-bottom:2rem; max-width:400px;">
    <label>Upload Photo <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label>
    <button type="submit" name="upload_photo" class="cta">Upload</button>
</form>

<?php if (!$photos): ?>
    <p>No photos uploaded yet.</p>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:1rem;">
        <?php foreach ($photos as $p): ?>
        <div style="border:1px solid var(--color-border); border-radius:var(--radius); overflow:hidden; background:var(--color-surface); box-shadow:var(--shadow);">
            <img src="<?= BASE_URL . $p['file_path'] ?>" style="width:100%; height:140px; object-fit:cover; display:block;">
            <form method="post" style="padding:0.5rem; margin:0;" onsubmit="return confirm('Delete this photo?')">
                <input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
                <button type="submit" name="delete_photo" class="cta-danger" style="width:100%; padding:0.4rem; font-size:0.85rem;">Delete</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
