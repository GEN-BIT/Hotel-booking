<?php require_once __DIR__ . '/../config/config.php';

$checkIn  = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests   = (int)($_GET['guests'] ?? 1);
$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$results  = [];
$error    = '';

$today = date('Y-m-d');
if (!$checkIn || !$checkOut) {
    $error = trans('please_provide_dates');
} elseif ($checkIn < $today) {
    $error = trans('checkin_cannot_be_past');
} elseif ($checkOut <= $checkIn) {
    $error = trans('checkout_must_be_after_checkin');
} else {
    $sql = 'SELECT rt.*, r.id AS room_id, r.room_number
            FROM room_types rt
            JOIN rooms r ON r.room_type_id = rt.id
            WHERE rt.max_occupancy >= ?
              AND r.status != "maintenance"
              AND r.id NOT IN (
                  SELECT b.room_id FROM bookings b
                  WHERE b.status IN ("pending","confirmed","checked_in")
                    AND b.check_in < ? AND b.check_out > ?
              )';
    $params = [$guests, $checkOut, $checkIn];

    if ($roomTypeId > 0) {
        $sql .= ' AND rt.id = ?';
        $params[] = $roomTypeId;
    }
    if ($minPrice > 0) {
        $sql .= ' AND rt.base_price >= ?';
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $sql .= ' AND rt.base_price <= ?';
        $params[] = $maxPrice;
    }

    $sql .= ' GROUP BY rt.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('available_rooms') ?></h1>
<p><?= htmlspecialchars($checkIn) ?> &rarr; <?= htmlspecialchars($checkOut) ?>, <?= $guests ?> guest(s)</p>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php elseif (!$results): ?>
    <p><?= trans('no_rooms_available') ?></p>
<?php else: ?>
<div class="room-grid">
<?php foreach ($results as $r): ?>
    <?php
    $stmt = $pdo->prepare('SELECT file_path FROM room_type_photos WHERE room_type_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
    $stmt->execute([$r['id']]);
    $photo = $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT price FROM room_type_pricing WHERE room_type_id = ? AND valid_from <= ? AND valid_until >= ? LIMIT 1');
    $stmt->execute([$r['id'], $checkIn, $checkOut]);
    $seasonalPrice = $stmt->fetchColumn();
    $displayPrice = $seasonalPrice ?: $r['base_price'];
    ?>
    <div class="room-card">
        <?php if ($photo): ?>
        <img src="<?= BASE_URL . htmlspecialchars($photo) ?>" style="width:100%; height:180px; object-fit:cover; border-radius:var(--radius) var(--radius) 0 0; margin:-1.5rem -1.5rem 1rem; border:none;">
        <?php endif; ?>
        <h2><?= htmlspecialchars($r['name']) ?> — Room <?= htmlspecialchars($r['room_number']) ?></h2>
        <?php if ($r['bed_type']): ?>
            <p style="color:var(--color-muted); font-size:0.85rem; margin:0 0 0.5rem;">🛏 <?= htmlspecialchars($r['bed_type']) ?> · 📐 <?= htmlspecialchars($r['room_size'] ?? '') ?> · 📍 <?= htmlspecialchars($r['building'] ?? '') ?></p>
        <?php endif; ?>
        <?php
        $stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE room_type_id = ?');
        $stmt->execute([$r['id']]);
        $ratingSummary = $stmt->fetch();
        ?>
        <?php if ($ratingSummary['total'] > 0): ?>
            <p class="stars">★ <?= number_format($ratingSummary['avg_rating'], 1) ?> (<?= (int)$ratingSummary['total'] ?>)</p>
        <?php endif; ?>
        <p class="price">$<?= number_format($displayPrice, 2) ?> <?= trans('per_night') ?></p>
        <?php if ($seasonalPrice): ?>
            <p style="color:var(--color-accent); font-size:0.85rem;"><?= trans('seasonal_rate_applies') ?></p>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>booking/select-room.php?room_id=<?= (int)$r['room_id'] ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= $guests ?>">
            <?= trans('book_this_room') ?>
        </a>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
