<?php require_once __DIR__ . '/../config/config.php';

$stmt = $pdo->query(
    'SELECT rt.*, AVG(rev.rating) AS avg_rating, COUNT(rev.id) AS review_count
     FROM room_types rt
     LEFT JOIN reviews rev ON rev.room_type_id = rt.id
     GROUP BY rt.id ORDER BY rt.base_price ASC'
);
$roomTypes = $stmt->fetchAll();
$hotelName = get_setting($pdo, 'hotel_name', 'Our Hotel');

require __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <h1><?= htmlspecialchars($hotelName) ?></h1>
    <p>Find your perfect room and book in minutes.</p>
</section>

<form method="get" action="search.php" class="search-form">
    <label>Check-in <input type="date" name="check_in" required></label>
    <label>Check-out <input type="date" name="check_out" required></label>
    <label>Guests <input type="number" name="guests" min="1" value="1" required></label>
    <button type="submit">Search Availability</button>
</form>

<div class="room-grid">
<?php foreach ($roomTypes as $rt): ?>
    <div class="room-card">
        <h2><?= htmlspecialchars($rt['name']) ?></h2>
        <?php if ($rt['review_count'] > 0): ?>
            <p class="stars">★ <?= number_format($rt['avg_rating'], 1) ?> (<?= (int)$rt['review_count'] ?>)</p>
        <?php endif; ?>
        <p><?= htmlspecialchars($rt['description']) ?></p>
        <p>Up to <?= (int)$rt['max_occupancy'] ?> guests</p>
        <p class="price">$<?= number_format($rt['base_price'], 2) ?> / night</p>
        <a href="details.php?id=<?= (int)$rt['id'] ?>">View Details</a>
    </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
