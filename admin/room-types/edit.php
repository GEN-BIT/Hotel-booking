<?php require __DIR__ . '/../../includes/admin-header.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ?');
$stmt->execute([$id]);
$type = $stmt->fetch();
if (!$type) die('Room type not found.');

$allAmenities = $pdo->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
$stmt = $pdo->prepare('SELECT amenity_id FROM room_amenities WHERE room_type_id = ?');
$stmt->execute([$id]);
$currentAmenities = $stmt->fetchAll(PDO::FETCH_COLUMN);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['base_price'] ?? 0);
    $occ  = (int)($_POST['max_occupancy'] ?? 1);
    $bedType = trim($_POST['bed_type'] ?? '');
    $roomSize = trim($_POST['room_size'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $amenityIds = $_POST['amenities'] ?? [];

    if (!$name || $price <= 0) {
        $error = 'Name and a valid price are required.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE room_types SET name=?, description=?, base_price=?, max_occupancy=?, bed_type=?, room_size=?, building=? WHERE id=?');
        $stmt->execute([$name, $desc, $price, $occ, $bedType, $roomSize, $building, $id]);
        $pdo->prepare('DELETE FROM room_amenities WHERE room_type_id = ?')->execute([$id]);
        $astmt = $pdo->prepare('INSERT INTO room_amenities (room_type_id, amenity_id) VALUES (?, ?)');
        foreach ($amenityIds as $aid) $astmt->execute([$id, (int)$aid]);
        $pdo->commit();
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Edit Room Type</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($type['name']) ?>" required></label>
    <label>Description <textarea name="description"><?= htmlspecialchars($type['description']) ?></textarea></label>
    <label>Base Price <input type="number" step="0.01" name="base_price" value="<?= htmlspecialchars($type['base_price']) ?>" required></label>
    <label>Max Occupancy <input type="number" name="max_occupancy" value="<?= (int)$type['max_occupancy'] ?>" required></label>
    <label>Bed Type <input type="text" name="bed_type" value="<?= htmlspecialchars($type['bed_type'] ?? '') ?>" placeholder="e.g. King, Queen, Twin"></label>
    <label>Room Size <input type="text" name="room_size" value="<?= htmlspecialchars($type['room_size'] ?? '') ?>" placeholder="e.g. 35 m²"></label>
    <label>Building <input type="text" name="building" value="<?= htmlspecialchars($type['building'] ?? '') ?>" placeholder="e.g. Main Building, Tower A"></label>
    <fieldset>
        <legend>Amenities</legend>
        <?php foreach ($allAmenities as $a): ?>
        <label class="checkbox"><input type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" <?= in_array($a['id'], $currentAmenities) ? 'checked' : '' ?>> <?= htmlspecialchars($a['name']) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <p><a href="photos/index.php?room_type_id=<?= (int)$id ?>" class="cta">Manage Photos</a></p>
    <button type="submit">Save Changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
