<?php require __DIR__ . '/../../includes/admin-header.php';
$error = '';
$allAmenities = $pdo->query('SELECT * FROM amenities ORDER BY name')->fetchAll();

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
        $stmt = $pdo->prepare('INSERT INTO room_types (name, description, base_price, max_occupancy, bed_type, room_size, building) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $desc, $price, $occ, $bedType, $roomSize, $building]);
        $typeId = $pdo->lastInsertId();
        $astmt = $pdo->prepare('INSERT INTO room_amenities (room_type_id, amenity_id) VALUES (?, ?)');
        foreach ($amenityIds as $aid) $astmt->execute([$typeId, (int)$aid]);
        $pdo->commit();
        header('Location: index.php');
        exit;
    }
}
?>
<h1>Add Room Type</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Name <input type="text" name="name" required></label>
    <label>Description <textarea name="description"></textarea></label>
    <label>Base Price <input type="number" step="0.01" name="base_price" required></label>
    <label>Max Occupancy <input type="number" name="max_occupancy" value="2" required></label>
    <label>Bed Type <input type="text" name="bed_type" placeholder="e.g. King, Queen, Twin"></label>
    <label>Room Size <input type="text" name="room_size" placeholder="e.g. 35 m²"></label>
    <label>Building <input type="text" name="building" placeholder="e.g. Main Building, Tower A"></label>
    <fieldset>
        <legend>Amenities</legend>
        <?php foreach ($allAmenities as $a): ?>
        <label class="checkbox"><input type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>"> <?= htmlspecialchars($a['name']) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <button type="submit">Add Room Type</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
