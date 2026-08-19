<?php require_once __DIR__ . '/../config/config.php';

$roomTypes = $pdo->query('SELECT * FROM room_types ORDER BY name')->fetchAll();
$allAmenities = $pdo->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
$minPrice = $pdo->query('SELECT MIN(base_price) FROM room_types')->fetchColumn();
$maxPrice = $pdo->query('SELECT MAX(base_price) FROM room_types')->fetchColumn();

require __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <h1><?= htmlspecialchars(get_setting($pdo, 'hotel_name', 'Our Hotel')) ?></h1>
    <p>Find your perfect room and book in minutes.</p>
</section>

<form method="get" action="search.php" class="search-form">
    <div class="search-row">
        <label>Check-in <input type="date" name="check_in" required></label>
        <label>Check-out <input type="date" name="check_out" required></label>
        <label>Guests <input type="number" name="guests" min="1" value="1" required></label>
        <button type="submit">Search Availability</button>
    </div>
    <div class="search-row">
        <label>Room Type
            <select name="room_type_id">
                <option value="">All Types</option>
                <?php foreach ($roomTypes as $rt): ?>
                <option value="<?= (int)$rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Min Price <input type="number" name="min_price" min="0" step="1" placeholder="<?= (int)$minPrice ?>"></label>
        <label>Max Price <input type="number" name="max_price" min="0" step="1" placeholder="<?= (int)$maxPrice ?>"></label>
    </div>
</form>

<div class="room-grid">
<?php foreach ($roomTypes as $rt): ?>
    <div class="room-card">
        <h2><?= htmlspecialchars($rt['name']) ?></h2>
        <?php
        $stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE room_type_id = ?');
        $stmt->execute([$rt['id']]);
        $ratingSummary = $stmt->fetch();
        ?>
        <?php if ($ratingSummary['total'] > 0): ?>
            <p class="stars">★ <?= number_format($ratingSummary['avg_rating'], 1) ?> (<?= (int)$ratingSummary['total'] ?>)</p>
        <?php endif; ?>
        <p><?= htmlspecialchars($rt['description']) ?></p>
        <p>Up to <?= (int)$rt['max_occupancy'] ?> guests</p>
        <p class="price">$<?= number_format($rt['base_price'], 2) ?> / night</p>
        <a href="details.php?id=<?= (int)$rt['id'] ?>">View Details</a>
    </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
