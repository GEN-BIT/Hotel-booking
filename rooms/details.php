<?php require_once __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ?');
$stmt->execute([$id]);
$roomType = $stmt->fetch();

if (!$roomType) {
    http_response_code(404);
    die('Room type not found.');
}

$stmt = $pdo->prepare(
    'SELECT a.name FROM amenities a
     JOIN room_amenities ra ON ra.amenity_id = a.id
     WHERE ra.room_type_id = ?'
);
$stmt->execute([$id]);
$amenities = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE room_type_id = ?');
$stmt->execute([$id]);
$ratingSummary = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT rev.rating, rev.comment, rev.created_at, u.full_name
     FROM reviews rev JOIN users u ON rev.user_id = u.id
     WHERE rev.room_type_id = ? ORDER BY rev.created_at DESC LIMIT 20'
);
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= htmlspecialchars($roomType['name']) ?></h1>
<?php if ($ratingSummary['total'] > 0): ?>
<p class="stars">
    <?= str_repeat('★', (int)round($ratingSummary['avg_rating'])) . str_repeat('☆', 5 - (int)round($ratingSummary['avg_rating'])) ?>
    <?= number_format($ratingSummary['avg_rating'], 1) ?> (<?= (int)$ratingSummary['total'] ?> review<?= $ratingSummary['total'] != 1 ? 's' : '' ?>)
</p>
<?php else: ?>
<p class="stars">No reviews yet</p>
<?php endif; ?>
<p><?= htmlspecialchars($roomType['description']) ?></p>
<p>Max occupancy: <?= (int)$roomType['max_occupancy'] ?></p>
<p class="price">$<?= number_format($roomType['base_price'], 2) ?> / night</p>

<?php if ($amenities): ?>
<h3>Amenities</h3>
<ul>
<?php foreach ($amenities as $a): ?>
    <li><?= htmlspecialchars($a) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<a href="availability-check.php?room_type_id=<?= (int)$roomType['id'] ?>" class="cta">Check Availability</a>

<h3>Guest Reviews</h3>
<?php if (!$reviews): ?>
<p>No reviews yet — be the first to stay and leave one!</p>
<?php else: ?>
<ul class="review-list">
<?php foreach ($reviews as $r): ?>
    <li class="review-card">
        <div class="stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
        <strong><?= htmlspecialchars($r['full_name']) ?></strong>
        <p><?= htmlspecialchars($r['comment']) ?></p>
        <small><?= htmlspecialchars($r['created_at']) ?></small>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
