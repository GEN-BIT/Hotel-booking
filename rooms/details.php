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
    'SELECT a.name, a.category FROM amenities a
     JOIN room_amenities ra ON ra.amenity_id = a.id
     WHERE ra.room_type_id = ?'
);
$stmt->execute([$id]);
$amenities = $stmt->fetchAll();

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

$stmt = $pdo->prepare('SELECT file_path, caption FROM room_photos WHERE room_id IN (SELECT id FROM rooms WHERE room_type_id = ?) ORDER BY sort_order ASC, id ASC LIMIT 8');
$stmt->execute([$id]);
$photos = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT file_path, caption FROM room_type_photos WHERE room_type_id = ? ORDER BY sort_order ASC, id ASC LIMIT 8');
$stmt->execute([$id]);
$typePhotos = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= htmlspecialchars($roomType['name']) ?></h1>
<?php if ($ratingSummary['total'] > 0): ?>
<p class="stars">
    <?= str_repeat('★', (int)round($ratingSummary['avg_rating'])) . str_repeat('☆', 5 - (int)round($ratingSummary['avg_rating'])) ?>
    <?= number_format($ratingSummary['avg_rating'], 1) ?> (<?= (int)$ratingSummary['total'] ?> review<?= $ratingSummary['total'] != 1 ? 's' : '' ?>)
</p>
<?php else: ?>
<p class="stars"><?= trans('no_reviews_yet') ?></p>
<?php endif; ?>
<p><?= htmlspecialchars($roomType['description']) ?></p>
<p><?= trans('max_occupancy') ?>: <?= (int)$roomType['max_occupancy'] ?></p>
<?php if ($roomType['bed_type']): ?>
    <p>🛏 <?= trans('bed_type') ?>: <?= htmlspecialchars($roomType['bed_type']) ?></p>
<?php endif; ?>
<?php if ($roomType['room_size']): ?>
    <p>📐 <?= trans('room_size') ?>: <?= htmlspecialchars($roomType['room_size']) ?></p>
<?php endif; ?>
<?php if ($roomType['building']): ?>
    <p>📍 <?= trans('building') ?>: <?= htmlspecialchars($roomType['building']) ?></p>
<?php endif; ?>
<p class="price">$<?= number_format($roomType['base_price'], 2) ?> <?= trans('per_night') ?></p>

<?php if ($typePhotos): ?>
<h3><?= trans('room_type_photos') ?></h3>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <?php foreach ($typePhotos as $photo): ?>
    <div style="border:1px solid var(--color-border); border-radius:var(--radius); overflow:hidden; background:var(--color-surface);">
        <img src="<?= BASE_URL . htmlspecialchars($photo['file_path']) ?>" style="width:100%; height:150px; object-fit:cover; display:block;">
        <?php if ($photo['caption']): ?>
        <p style="padding:0.5rem; margin:0; font-size:0.85rem; color:var(--color-muted); font-style:italic;"><?= htmlspecialchars($photo['caption']) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($photos): ?>
<h3><?= trans('room_photos') ?></h3>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <?php foreach ($photos as $photo): ?>
    <div style="border:1px solid var(--color-border); border-radius:var(--radius); overflow:hidden; background:var(--color-surface);">
        <img src="<?= BASE_URL . htmlspecialchars($photo['file_path']) ?>" style="width:100%; height:150px; object-fit:cover; display:block;">
        <?php if ($photo['caption']): ?>
        <p style="padding:0.5rem; margin:0; font-size:0.85rem; color:var(--color-muted); font-style:italic;"><?= htmlspecialchars($photo['caption']) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($amenities): ?>
<h3><?= trans('amenities') ?></h3>
<?php
$byCategory = [];
foreach ($amenities as $a) {
    $cat = $a['category'] ?: 'Other';
    $byCategory[$cat][] = $a['name'];
}
foreach ($byCategory as $cat => $items): ?>
    <h4 style="margin:1rem 0 0.5rem; color:var(--color-muted); font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em;"><?= htmlspecialchars($cat) ?></h4>
    <ul style="margin:0 0 1rem; padding-left:1.25rem;">
        <?php foreach ($items as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endforeach; ?>
<?php endif; ?>

<a href="availability-check.php?room_type_id=<?= (int)$roomType['id'] ?>" class="cta"><?= trans('check_availability') ?></a>

<h3><?= trans('guest_reviews') ?></h3>
<?php if (!$reviews): ?>
<p><?= trans('no_reviews_yet') ?></p>
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
