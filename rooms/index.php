<?php require_once __DIR__ . '/../config/config.php';

$roomTypes = $pdo->query('SELECT * FROM room_types ORDER BY name')->fetchAll();
$allAmenities = $pdo->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
$minPrice = $pdo->query('SELECT MIN(base_price) FROM room_types')->fetchColumn();
$maxPrice = $pdo->query('SELECT MAX(base_price) FROM room_types')->fetchColumn();

require __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <h1><?= htmlspecialchars(get_setting($pdo, 'hotel_name', trans('hotel_name'))) ?></h1>
    <p><?= trans('tagline') ?></p>
</section>

<form method="get" action="search.php" class="search-form">
    <div class="search-row">
        <label><?= trans('check_in') ?> <input type="date" name="check_in" required></label>
        <label><?= trans('check_out') ?> <input type="date" name="check_out" required></label>
        <label><?= trans('guests') ?> <input type="number" name="guests" min="1" value="1" required></label>
        <button type="submit"><?= trans('search_availability') ?></button>
    </div>
    <div class="search-row">
        <label><?= trans('room_type') ?>
            <select name="room_type_id">
                <option value=""><?= trans('all_types') ?></option>
                <?php foreach ($roomTypes as $rt): ?>
                <option value="<?= (int)$rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= trans('min_price') ?> <input type="number" name="min_price" min="0" step="1" placeholder="<?= (int)$minPrice ?>"></label>
        <label><?= trans('max_price') ?> <input type="number" name="max_price" min="0" step="1" placeholder="<?= (int)$maxPrice ?>"></label>
    </div>
</form>

<div class="room-grid">
<?php foreach ($roomTypes as $rt): ?>
    <?php
    $stmt = $pdo->prepare('SELECT file_path FROM room_type_photos WHERE room_type_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
    $stmt->execute([$rt['id']]);
    $photo = $stmt->fetchColumn();
    ?>
    <div class="room-card">
        <?php if ($photo): ?>
        <img src="<?= BASE_URL . htmlspecialchars($photo) ?>" style="width:100%; height:180px; object-fit:cover; border-radius:var(--radius) var(--radius) 0 0; margin:-1.5rem -1.5rem 1rem; border:none;">
        <?php endif; ?>
        <h2><?= htmlspecialchars($rt['name']) ?></h2>
        <?php if ($rt['bed_type']): ?>
            <p style="color:var(--color-muted); font-size:0.85rem; margin:0 0 0.5rem;">🛏 <?= htmlspecialchars($rt['bed_type']) ?> · 📐 <?= htmlspecialchars($rt['room_size'] ?? '') ?> · 📍 <?= htmlspecialchars($rt['building'] ?? '') ?></p>
        <?php endif; ?>
        <?php
        $stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE room_type_id = ?');
        $stmt->execute([$rt['id']]);
        $ratingSummary = $stmt->fetch();
        ?>
        <?php if ($ratingSummary['total'] > 0): ?>
            <p class="stars">★ <?= number_format($ratingSummary['avg_rating'], 1) ?> (<?= (int)$ratingSummary['total'] ?>)</p>
        <?php endif; ?>
        <p><?= htmlspecialchars($rt['description']) ?></p>
        <p><?= trans('up_to_guests', ['n' => (int)$rt['max_occupancy']]) ?></p>
        <p class="price">$<?= number_format($rt['base_price'], 2) ?> <?= trans('per_night') ?></p>
        <a href="details.php?id=<?= (int)$rt['id'] ?>"><?= trans('view_details') ?></a>
    </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>