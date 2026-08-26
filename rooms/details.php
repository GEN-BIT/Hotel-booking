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

$stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total, AVG(cleanliness) AS avg_cleanliness, AVG(service) AS avg_service, AVG(location_rating) AS avg_location, AVG(value_rating) AS avg_value FROM reviews WHERE room_type_id = ?');
$stmt->execute([$id]);
$ratingSummary = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT rev.rating, rev.comment, rev.created_at, u.full_name, rev.cleanliness, rev.service, rev.location_rating, rev.value_rating
     FROM reviews rev JOIN users u ON rev.user_id = u.id
     WHERE rev.room_type_id = ? ORDER BY rev.created_at DESC LIMIT 20'
);
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT file_path, caption FROM room_type_photos WHERE room_type_id = ? ORDER BY sort_order ASC, id ASC LIMIT 8');
$stmt->execute([$id]);
$typePhotos = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT r.latitude, r.longitude, r.room_number FROM rooms r WHERE r.room_type_id = ? AND r.latitude IS NOT NULL LIMIT 1');
$stmt->execute([$id]);
$roomLocation = $stmt->fetch();

$similar = $pdo->prepare(
    'SELECT rt.*, ROUND(rt.base_price, 0) as display_price FROM room_types rt
     WHERE rt.id != ? AND rt.max_occupancy >= ?
     ORDER BY RAND() LIMIT 3'
);
$similar->execute([$id, $roomType['max_occupancy']]);
$similarRooms = $similar->fetchAll();

$isInWishlist = false;
if (isset($_SESSION['user_id'])) {
    $wishlistCheck = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? AND room_type_id = ?');
    $wishlistCheck->execute([$_SESSION['user_id'], $id]);
    $isInWishlist = (bool)$wishlistCheck->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['add_wishlist'])) {
    if (!$isInWishlist) {
        $pdo->prepare('INSERT INTO wishlists (user_id, room_type_id) VALUES (?, ?)')->execute([$_SESSION['user_id'], $id]);
        $_SESSION['flash_success'] = 'Added to wishlist!';
    }
    header('Location: details.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['remove_wishlist'])) {
    $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND room_type_id = ?')->execute([$_SESSION['user_id'], $id]);
    $_SESSION['flash_success'] = 'Removed from wishlist!';
    header('Location: details.php?id=' . $id);
    exit;
}

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

<?php if ($roomType['description']): ?>
<p style="font-size:1.05rem; color:var(--color-muted); margin:0.5rem 0 1rem;"><?= htmlspecialchars($roomType['description']) ?></p>
<?php endif; ?>

<div style="display:flex; flex-wrap:wrap; gap:1.5rem; margin:1rem 0;">
    <div>
        <p style="margin:0;"><strong><?= trans('max_occupancy') ?>:</strong> <?= (int)$roomType['max_occupancy'] ?> guests</p>
        <?php if ($roomType['bed_type']): ?>
            <p style="margin:0.25rem 0;">🛏 <strong><?= trans('bed_type') ?>:</strong> <?= htmlspecialchars($roomType['bed_type']) ?></p>
        <?php endif; ?>
        <?php if ($roomType['room_size']): ?>
            <p style="margin:0.25rem 0;">📐 <strong><?= trans('room_size') ?>:</strong> <?= htmlspecialchars($roomType['room_size']) ?></p>
        <?php endif; ?>
        <?php if ($roomType['building']): ?>
            <p style="margin:0.25rem 0;">📍 <strong><?= trans('building') ?>:</strong> <?= htmlspecialchars($roomType['building']) ?></p>
        <?php endif; ?>
    </div>
    <div style="margin-left:auto; text-align:right;">
        <p class="price" style="font-size:1.5rem; margin:0;"><?= format_currency($roomType['base_price']) ?></p>
        <p style="margin:0; color:var(--color-muted);"><?= trans('per_night') ?></p>
    </div>
</div>

<div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin:1rem 0;">
    <?php if ($roomType['breakfast_included']): ?>
        <span style="background:#e6f5e6; color:#007a3d; padding:0.35rem 0.75rem; border-radius:2px; font-size:0.85rem; font-weight:600;">Breakfast Included</span>
    <?php endif; ?>
    <?php if ($roomType['free_cancellation_days'] > 0): ?>
        <span style="background:#e6f5e6; color:#007a3d; padding:0.35rem 0.75rem; border-radius:2px; font-size:0.85rem; font-weight:600;">Free Cancellation</span>
    <?php endif; ?>
    <?php if ($roomType['instant_confirmation']): ?>
        <span style="background:#e6f0fa; color:#0071c2; padding:0.35rem 0.75rem; border-radius:2px; font-size:0.85rem; font-weight:600;">Instant Confirmation</span>
    <?php endif; ?>
    <?php if ($roomType['no_prepayment']): ?>
        <span style="background:#fff8e6; color:#b07b12; padding:0.35rem 0.75rem; border-radius:2px; font-size:0.85rem; font-weight:600;">No Prepayment</span>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($isInWishlist): ?>
            <form method="post" style="display:inline;">
                <button type="submit" name="remove_wishlist" class="cta" style="padding:0.35rem 0.75rem; font-size:0.85rem; background:#b12704;">Remove from Wishlist</button>
            </form>
        <?php else: ?>
            <form method="post" style="display:inline;">
                <button type="submit" name="add_wishlist" class="cta" style="padding:0.35rem 0.75rem; font-size:0.85rem;">Save to Wishlist</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($roomType['cancellation_policy']): ?>
<div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1rem; margin:1rem 0;">
    <h3 style="margin:0 0 0.5rem;">Cancellation Policy</h3>
    <p style="margin:0; color:var(--color-muted);">
        <?php if ($roomType['cancellation_policy'] === 'free'): ?>
            Free cancellation until check-in. Cancel before <?= (int)$roomType['free_cancellation_days'] ?> day(s) before arrival for full refund.
        <?php elseif ($roomType['cancellation_policy'] === 'partial'): ?>
            Free cancellation until <?= (int)$roomType['free_cancellation_days'] ?> day(s) before arrival. Cancellations after this time incur a 1-night penalty.
        <?php else: ?>
            Non-refundable. No refund for cancellations or changes.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>

<?php if ($roomLocation && $roomLocation['latitude'] && $roomLocation['longitude']): ?>
<div style="margin:1.5rem 0;">
    <h3>Location</h3>
    <div id="map" style="height:300px; border-radius:var(--radius); border:1px solid var(--color-border);"></div>
    <p style="color:var(--color-muted); margin-top:0.5rem;">📍 Room <?= htmlspecialchars($roomLocation['room_number']) ?> — <?= htmlspecialchars($roomType['building']) ?></p>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script>
var map = L.map('map').setView([<?= $roomLocation['latitude'] ?>, <?= $roomLocation['longitude'] ?>], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
L.marker([<?= $roomLocation['latitude'] ?>, <?= $roomLocation['longitude'] ?>]).addTo(map).bindPopup('<?= htmlspecialchars($roomType['name']) ?>').openPopup();
</script>
<?php endif; ?>

<?php if ($typePhotos): ?>
<h3><?= trans('room_type_photos') ?></h3>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <?php foreach ($typePhotos as $photo): ?>
    <div style="border:1px solid var(--color-border); border-radius:var(--radius); overflow:hidden; background:var(--color-surface);">
        <img src="<?= BASE_URL . htmlspecialchars($photo['file_path']) ?>" style="width:100%; height:150px; object-fit:cover; display:block;" loading="lazy">
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
        <img src="<?= BASE_URL . htmlspecialchars($photo['file_path']) ?>" style="width:100%; height:150px; object-fit:cover; display:block;" loading="lazy">
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

<?php if ($ratingSummary['total'] > 0 && ($ratingSummary['avg_cleanliness'] || $ratingSummary['avg_service'] || $ratingSummary['avg_location'] || $ratingSummary['avg_value'])): ?>
<h3><?= trans('guest_reviews') ?> — <?= number_format($ratingSummary['avg_rating'], 1) ?> / 5</h3>
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin:1rem 0;">
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1rem; text-align:center;">
        <div style="font-size:1.5rem; font-weight:700; color:var(--color-primary);"><?= number_format($ratingSummary['avg_cleanliness'], 1) ?></div>
        <div style="font-size:0.85rem; color:var(--color-muted);">Cleanliness</div>
    </div>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1rem; text-align:center;">
        <div style="font-size:1.5rem; font-weight:700; color:var(--color-primary);"><?= number_format($ratingSummary['avg_service'], 1) ?></div>
        <div style="font-size:0.85rem; color:var(--color-muted);">Service</div>
    </div>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1rem; text-align:center;">
        <div style="font-size:1.5rem; font-weight:700; color:var(--color-primary);"><?= number_format($ratingSummary['avg_location'], 1) ?></div>
        <div style="font-size:0.85rem; color:var(--color-muted);">Location</div>
    </div>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1rem; text-align:center;">
        <div style="font-size:1.5rem; font-weight:700; color:var(--color-primary);"><?= number_format($ratingSummary['avg_value'], 1) ?></div>
        <div style="font-size:0.85rem; color:var(--color-muted);">Value</div>
    </div>
</div>
<?php endif; ?>

<?php if ($reviews): ?>
<ul class="review-list">
<?php foreach ($reviews as $r): ?>
    <li class="review-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
            <strong><?= htmlspecialchars($r['full_name']) ?></strong>
            <div class="stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
        </div>
        <?php if ($r['cleanliness'] || $r['service'] || $r['location_rating'] || $r['value_rating']): ?>
        <div style="display:flex; gap:1rem; font-size:0.8rem; color:var(--color-muted); margin-bottom:0.5rem;">
            <?php if ($r['cleanliness']): ?><span>Cleanliness: <?= $r['cleanliness'] ?>/5</span><?php endif; ?>
            <?php if ($r['service']): ?><span>Service: <?= $r['service'] ?>/5</span><?php endif; ?>
            <?php if ($r['location_rating']): ?><span>Location: <?= $r['location_rating'] ?>/5</span><?php endif; ?>
            <?php if ($r['value_rating']): ?><span>Value: <?= $r['value_rating'] ?>/5</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <p><?= htmlspecialchars($r['comment']) ?></p>
        <small><?= htmlspecialchars($r['created_at']) ?></small>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($similarRooms): ?>
<h3>Similar Properties</h3>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1.5rem; margin:1.5rem 0;">
    <?php foreach ($similarRooms as $sr): ?>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow);">
        <h4 style="margin:0 0 0.5rem; color:var(--color-primary);"><?= htmlspecialchars($sr['name']) ?></h4>
        <p style="margin:0 0 0.5rem; color:var(--color-muted);">Up to <?= (int)$sr['max_occupancy'] ?> guests</p>
        <p style="margin:0 0 1rem; font-weight:700; color:var(--color-primary);"><?= format_currency($sr['display_price']) ?> / night</p>
        <a href="details.php?id=<?= (int)$sr['id'] ?>" class="cta" style="width:100%; text-align:center; box-sizing:border-box;">View</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/recently-viewed.php'; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>