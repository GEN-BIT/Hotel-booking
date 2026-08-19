<?php require __DIR__ . '/../../includes/admin-header.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) die('Room not found.');

$roomTypes = $pdo->query('SELECT id, name FROM room_types ORDER BY name')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = trim($_POST['room_number'] ?? '');
    $typeId = (int)($_POST['room_type_id'] ?? 0);
    $floor  = trim($_POST['floor'] ?? '');
    $status = $_POST['status'] ?? 'available';

    if (!$number || !$typeId) {
        $error = 'Room number and type are required.';
    } else {
        $stmt = $pdo->prepare('UPDATE rooms SET room_type_id=?, room_number=?, floor=?, status=? WHERE id=?');
        $stmt->execute([$typeId, $number, $floor, $status, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Edit Room</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Room Number <input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required></label>
    <label>Room Type
        <select name="room_type_id" required>
            <?php foreach ($roomTypes as $rt): ?>
            <option value="<?= (int)$rt['id'] ?>" <?= $rt['id'] == $room['room_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($rt['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Floor <input type="text" name="floor" value="<?= htmlspecialchars($room['floor']) ?>"></label>
    <label>Status
        <select name="status">
            <?php foreach (['available','occupied','cleaning','maintenance'] as $s): ?>
            <option value="<?= $s ?>" <?= $s === $room['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
