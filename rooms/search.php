<?php require_once __DIR__ . '/../config/config.php';

$checkIn  = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests   = (int)($_GET['guests'] ?? 1);
$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$sort = $_GET['sort'] ?? 'recommended';
$bedType = $_GET['bed_type'] ?? '';
$building = $_GET['building'] ?? '';
$amenityFilter = $_GET['amenities'] ?? [];
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
    $sql = 'SELECT DISTINCT rt.*, r.id AS room_id, r.room_number, r.building
            FROM room_types rt
            JOIN rooms r ON r.room_type_id = rt.id
            LEFT JOIN room_amenities ra ON ra.room_type_id = rt.id
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
    if ($bedType) {
        $sql .= ' AND rt.bed_type = ?';
        $params[] = $bedType;
    }
    if ($building) {
        $sql .= ' AND r.building = ?';
        $params[] = $building;
    }
    if (!empty($amenityFilter)) {
        $placeholders = implode(',', array_fill(0, count($amenityFilter), '?'));
        $sql .= ' AND ra.amenity_id IN (' . $placeholders . ')';
        $params = array_merge($params, $amenityFilter);
    }

    $sql .= ' GROUP BY rt.id';

    if ($sort === 'price_asc') {
        $sql .= ' ORDER BY rt.base_price ASC';
    } elseif ($sort === 'price_desc') {
        $sql .= ' ORDER BY rt.base_price DESC';
    } elseif ($sort === 'rating') {
        $sql .= ' ORDER BY (SELECT AVG(rating) FROM reviews WHERE room_type_id = rt.id) DESC';
    } else {
        $sql .= ' ORDER BY rt.created_at DESC';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

$allRoomTypes = $pdo->query('SELECT DISTINCT bed_type FROM room_types WHERE bed_type IS NOT NULL AND bed_type != "" ORDER BY bed_type')->fetchAll(PDO::FETCH_COLUMN);
$allBuildings = $pdo->query('SELECT DISTINCT building FROM rooms WHERE building IS NOT NULL AND building != "" ORDER BY building')->fetchAll(PDO::FETCH_COLUMN);
$allAmenities = $pdo->query('SELECT * FROM amenities ORDER BY category, name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('available_rooms') ?></h1>
<p><?= htmlspecialchars($checkIn) ?> &rarr; <?= htmlspecialchars($checkOut) ?>, <?= $guests ?> guest(s) &mdash; <?= count($results) ?> propert<?= count($results) != 1 ? 'ies' : 'y' ?> found</p>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php else: ?>

<form method="get" class="filter-form" style="margin-bottom:1.5rem; background:var(--color-surface); padding:1rem; border:1px solid var(--color-border); border-radius:var(--radius);">
    <input type="hidden" name="check_in" value="<?= htmlspecialchars($checkIn) ?>">
    <input type="hidden" name="check_out" value="<?= htmlspecialchars($checkOut) ?>">
    <input type="hidden" name="guests" value="<?= (int)$guests ?>">
    
    <label style="min-width:150px;">Sort by
        <select name="sort" onchange="this.form.submit()">
            <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Recommended</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (low to high)</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (high to low)</option>
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating</option>
        </select>
    </label>
    
    <?php if ($allRoomTypes): ?>
    <label>Bed Type
        <select name="bed_type" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($allRoomTypes as $bt): ?>
            <option value="<?= htmlspecialchars($bt) ?>" <?= $bedType === $bt ? 'selected' : '' ?>><?= htmlspecialchars($bt) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php endif; ?>
    
    <?php if ($allBuildings): ?>
    <label>Building
        <select name="building" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($allBuildings as $bld): ?>
            <option value="<?= htmlspecialchars($bld) ?>" <?= $building === $bld ? 'selected' : '' ?>><?= htmlspecialchars($bld) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php endif; ?>
    
    <details style="flex:1 1 100%;">
        <summary style="cursor:pointer; padding:0.5rem 0; font-weight:600;">Filter by Amenities</summary>
        <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:0.5rem;">
            <?php foreach ($allAmenities as $a): ?>
            <label class="checkbox" style="font-size:0.9rem;">
                <input type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" <?= in_array($a['id'], $amenityFilter) ? 'checked' : '' ?> onchange="this.form.submit()">
                <?= htmlspecialchars($a['name']) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </details>
    
    <button type="submit" class="cta">Apply Filters</button>
    <a href="?check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= (int)$guests ?>" class="cta" style="background:var(--color-surface); color:var(--color-text); border:1px solid var(--color-border);">Reset</a>
</form>

<?php if (!$results): ?>
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
    $stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE room_type_id = ?');
    $stmt->execute([$r['id']]);
    $ratingSummary = $stmt->fetch();
    ?>
    <div class="room-card">
        <?php if ($photo): ?>
        <img src="<?= BASE_URL . htmlspecialchars($photo) ?>" class="room-image" alt="<?= htmlspecialchars($r['name']) ?>">
        <?php endif; ?>
        <div class="room-content">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
                <h2><?= htmlspecialchars($r['name']) ?></h2>
                <div style="text-align:right; flex-shrink:0;">
                    <p class="price" style="margin:0; font-size:1.1rem;"><?= format_currency($displayPrice) ?></p>
                    <p style="margin:0; font-size:0.8rem; color:var(--color-muted);">per night</p>
                </div>
            </div>
            <p style="color:var(--color-muted); font-size:0.85rem; margin:0 0 0.5rem;">📍 <?= htmlspecialchars($r['building'] ?? 'Main Building') ?> · Room <?= htmlspecialchars($r['room_number']) ?></p>
            <?php if ($r['bed_type']): ?>
                <p style="color:var(--color-muted); font-size:0.85rem; margin:0 0 0.5rem;">🛏 <?= htmlspecialchars($r['bed_type']) ?> · 📐 <?= htmlspecialchars($r['room_size'] ?? '') ?></p>
            <?php endif; ?>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin:0.5rem 0;">
                <?php if ($r['breakfast_included']): ?>
                    <span style="background:#e6f5e6; color:#007a3d; padding:0.2rem 0.5rem; border-radius:2px; font-size:0.75rem; font-weight:600;">Breakfast</span>
                <?php endif; ?>
                <?php if ($r['free_cancellation_days'] > 0): ?>
                    <span style="background:#e6f5e6; color:#007a3d; padding:0.2rem 0.5rem; border-radius:2px; font-size:0.75rem; font-weight:600;">Free Cancellation</span>
                <?php endif; ?>
                <?php if ($r['instant_confirmation']): ?>
                    <span style="background:#e6f0fa; color:#0071c2; padding:0.2rem 0.5rem; border-radius:2px; font-size:0.75rem; font-weight:600;">Instant Confirmation</span>
                <?php endif; ?>
            </div>
            <?php if ($ratingSummary['total'] > 0): ?>
                <p class="stars" style="font-size:0.85rem;">★ <?= number_format($ratingSummary['avg_rating'], 1) ?> (<?= (int)$ratingSummary['total'] ?>)</p>
            <?php endif; ?>
            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>booking/select-room.php?room_id=<?= (int)$r['room_id'] ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= $guests ?>" class="cta" style="flex:1; text-align:center;">
                    <?= trans('book_this_room') ?>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>account/wishlist.php?add=<?= (int)$r['id'] ?>&redirect=search.php?<?= http_build_query($_GET) ?>" class="cta" style="background:#fff; color:var(--color-primary); border:1px solid var(--color-border); padding:0.5rem 0.75rem; font-size:0.85rem;" title="Save to wishlist">♡</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/recently-viewed.php'; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>