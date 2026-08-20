<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_rooms');

$error = '';
$roomTypes = $pdo->query('SELECT id, name FROM room_types ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = trim($_POST['room_number'] ?? '');
    $typeId = (int)($_POST['room_type_id'] ?? 0);
    $floor  = trim($_POST['floor'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$number || !$typeId) {
        $error = 'Room number and type are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO rooms (room_type_id, room_number, floor, building, status, notes) VALUES (?, ?, ?, ?, "available", ?)');
        $stmt->execute([$typeId, $number, $floor, $building, $notes]);
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Add Room</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
          <?= csrf_field() ?>
    <label>Room Number <input type="text" name="room_number" required></label>
    <label>Room Type
        <select name="room_type_id" required>
            <option value="">-- Select --</option>
            <?php foreach ($roomTypes as $rt): ?>
            <option value="<?= (int)$rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Floor <input type="text" name="floor"></label>
    <label>Building <input type="text" name="building"></label>
    <label>Notes <textarea name="notes"></textarea></label>
    <button type="submit">Add Room</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
