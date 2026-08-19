<?php require __DIR__ . '/../../../includes/admin-header.php';

$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ?');
$stmt->execute([$roomTypeId]);
$roomType = $stmt->fetch();
if (!$roomType) die('Room type not found.');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $files = $_FILES['photos'] ?? null;
    $category = trim($_POST['photo_category'] ?? '');
    $customCaptions = $_POST['captions'] ?? [];
    
    $allowedExtensions = ['jpg','jpeg','png','webp','gif','bmp','heic','heif','tiff','tif'];
    $allowedMimes = [
        'image/jpeg','image/png','image/webp','image/gif','image/bmp',
        'image/heic','image/heif','image/tiff','image/x-png','image/pjpeg',
        'image/avif','image/svg+xml'
    ];
    $uploaded = 0;
    $errors = [];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== 0) {
            $errors[] = 'Upload error code ' . $files['error'][$i] . ' for ' . $files['name'][$i];
            continue;
        }
        
        $originalName = $files['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = 'Invalid file type: ' . $originalName;
            continue;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $files['tmp_name'][$i]);
        finfo_close($finfo);
        
        if ($mime && !in_array($mime, $allowedMimes)) {
            $errors[] = 'Invalid MIME type: ' . $mime . ' for ' . $originalName;
            continue;
        }
        
            $finalExt = in_array($ext, ['jpg','jpeg','png','webp','gif','bmp']) ? $ext : 'jpg';
            $filename = 'roomtype_' . $roomTypeId . '_' . uniqid() . '.' . $finalExt;
            $dest = __DIR__ . '/../../../uploads/rooms/' . $filename;
            
            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $caption = '';
            if (!empty($customCaptions[$i])) {
                $caption = trim($customCaptions[$i]);
            } elseif ($category) {
                $caption = $category . ' - ' . $roomType['name'];
            }
            $stmt = $pdo->prepare('INSERT INTO room_type_photos (room_type_id, file_path, sort_order, caption) VALUES (?, ?, ?, ?)');
            $maxSort = $pdo->prepare('SELECT MAX(sort_order) FROM room_type_photos WHERE room_type_id = ?');
            $maxSort->execute([$roomTypeId]);
            $sort = ((int)$maxSort->fetchColumn()) + 1;
            $stmt->execute([$roomTypeId, 'uploads/rooms/' . $filename, $sort, $caption]);
            $uploaded++;
        } else {
            $errors[] = 'Failed to move uploaded file: ' . $originalName;
        }
    }
    
    if ($uploaded > 0) {
        $success = "$uploaded photo(s) uploaded successfully.";
        if (!empty($errors)) {
            $success .= ' Some files were skipped: ' . implode(', ', $errors);
        }
    } else {
        $error = 'No valid photos were uploaded. Details: ' . implode(', ', $errors);
    }
}

if (isset($_POST['delete_photo'])) {
    $photoId = (int)($_POST['photo_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT file_path FROM room_type_photos WHERE id = ? AND room_type_id = ?');
    $stmt->execute([$photoId, $roomTypeId]);
    $photo = $stmt->fetch();
    if ($photo) {
        @unlink(__DIR__ . '/../../../' . $photo['file_path']);
        $pdo->prepare('DELETE FROM room_type_photos WHERE id = ?')->execute([$photoId]);
        $success = 'Photo deleted.';
    }
}

if (isset($_POST['update_caption'])) {
    $photoId = (int)($_POST['photo_id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    $pdo->prepare('UPDATE room_type_photos SET caption = ? WHERE id = ? AND room_type_id = ?')->execute([$caption, $photoId, $roomTypeId]);
    $success = 'Caption updated.';
}

if (isset($_POST['move_photo'])) {
    $photoId = (int)($_POST['photo_id'] ?? 0);
    $direction = $_POST['direction'] ?? '';
    $stmt = $pdo->prepare('SELECT id, sort_order FROM room_type_photos WHERE id = ? AND room_type_id = ?');
    $stmt->execute([$photoId, $roomTypeId]);
    $photo = $stmt->fetch();
    if ($photo && in_array($direction, ['up', 'down'])) {
        $currentSort = (int)$photo['sort_order'];
        $newSort = $direction === 'up' ? $currentSort - 1 : $currentSort + 1;
        if ($newSort >= 0) {
            $swapStmt = $pdo->prepare('SELECT id FROM room_type_photos WHERE room_type_id = ? AND sort_order = ? AND id != ?');
            $swapStmt->execute([$roomTypeId, $newSort, $photoId]);
            $swap = $swapStmt->fetchColumn();
            if ($swap) {
                $pdo->prepare('UPDATE room_type_photos SET sort_order = ? WHERE id = ?')->execute([$newSort, $photoId]);
                $pdo->prepare('UPDATE room_type_photos SET sort_order = ? WHERE id = ?')->execute([$currentSort, (int)$swap]);
            } else {
                $pdo->prepare('UPDATE room_type_photos SET sort_order = ? WHERE id = ?')->execute([$newSort, $photoId]);
            }
        }
    }
    header('Location: index.php?room_type_id=' . $roomTypeId);
    exit;
}

$photos = $pdo->prepare('SELECT * FROM room_type_photos WHERE room_type_id = ? ORDER BY sort_order ASC, id ASC');
$photos->execute([$roomTypeId]);
$photos = $photos->fetchAll();
?>
<h1>Photos — <?= htmlspecialchars($roomType['name']) ?></h1>
<p><a href="../edit.php?id=<?= (int)$roomType['id'] ?>">&larr; Back to Edit Room Type</a></p>

<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" style="margin-bottom:2rem; max-width:600px;">
    <label>Photo Category
        <select name="photo_category">
            <option value="">-- Select Category --</option>
            <option value="Main Room View">Main Room View</option>
            <option value="Queen Bed Area">Queen Bed Area</option>
            <option value="King Bed Area">King Bed Area</option>
            <option value="Twin Beds Area">Twin Beds Area</option>
            <option value="Living Area">Living Area</option>
            <option value="Modern Bathroom">Modern Bathroom</option>
            <option value="En-suite Bathroom">En-suite Bathroom</option>
            <option value="Window View">Window View</option>
            <option value="Balcony View">Balcony View</option>
            <option value="City View">City View</option>
            <option value="Panoramic View">Panoramic View</option>
            <option value="Room Entrance">Room Entrance</option>
            <option value="Entrance Hall">Entrance Hall</option>
            <option value="Private Entrance">Private Entrance</option>
            <option value="Other">Other</option>
        </select>
    </label>
    <label>Upload Photos (up to 5)
        <input type="file" name="photos[]" accept="image/*" multiple required>
    </label>
    <p style="color:var(--color-muted); font-size:0.85rem; margin:0.5rem 0 1rem;">Select a category above, then upload one or more images. You can edit individual captions after upload.</p>
    <button type="submit" name="upload_photo" class="cta">Upload Photos</button>
</form>

<?php if (!$photos): ?>
    <p>No photos uploaded yet. Upload at least 3-5 photos to showcase this room type.</p>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:1rem;">
        <?php foreach ($photos as $index => $p): ?>
        <div style="border:1px solid var(--color-border); border-radius:var(--radius); overflow:hidden; background:var(--color-surface); box-shadow:var(--shadow);">
            <img src="<?= BASE_URL . $p['file_path'] ?>" style="width:100%; height:160px; object-fit:cover; display:block;">
            <div style="padding:0.75rem;">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                    <?php if ($index > 0): ?>
                    <form method="post" style="flex:1;">
                        <input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" name="move_photo" class="cta" style="width:100%; padding:0.4rem; font-size:0.85rem;">↑ Up</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($index < count($photos) - 1): ?>
                    <form method="post" style="flex:1;">
                        <input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" name="move_photo" class="cta" style="width:100%; padding:0.4rem; font-size:0.85rem;">↓ Down</button>
                    </form>
                    <?php endif; ?>
                </div>
                <form method="post" style="margin-bottom:0.5rem;">
                    <input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
                    <input type="text" name="caption" value="<?= htmlspecialchars($p['caption'] ?? '') ?>" placeholder="Add caption..." style="width:100%; padding:0.4rem; border:1px solid var(--color-border); border-radius:var(--radius); background:var(--color-bg); color:var(--color-text); font-size:0.85rem;">
                    <button type="submit" name="update_caption" class="cta" style="width:100%; margin-top:0.5rem; padding:0.4rem; font-size:0.85rem;">Save Caption</button>
                </form>
                <form method="post" onsubmit="return confirm('Delete this photo?')">
                    <input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" name="delete_photo" class="cta-danger" style="width:100%; padding:0.4rem; font-size:0.85rem;">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../../includes/admin-footer.php'; ?>
