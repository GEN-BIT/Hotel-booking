<?php
if (isset($_GET['add']) && isset($_SESSION['user_id'])) {
    $roomTypeId = (int)$_GET['add'];
    $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, room_type_id) VALUES (?, ?)');
    $stmt->execute([$_SESSION['user_id'], $roomTypeId]);
    $_SESSION['flash_success'] = 'Added to wishlist!';
    $redirect = $_GET['redirect'] ?? 'wishlist.php';
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $pdo->prepare('DELETE FROM wishlists WHERE id = ? AND user_id = ?')->execute([$id, $_SESSION['user_id']]);
    $_SESSION['flash_success'] = 'Removed from wishlist!';
    header('Location: wishlist.php');
    exit;
}

require __DIR__ . '/../includes/header.php';
?>
<h1>My Wishlist</h1>

<?php if (empty($items)): ?>
    <p>You haven't saved any rooms to your wishlist yet.</p>
    <a href="<?= BASE_URL ?>rooms/index.php" class="cta">Browse Rooms</a>
<?php else: ?>
<div class="room-grid">
<?php foreach ($items as $item): ?>
    <div class="room-card">
        <?php if ($item['photo']): ?>
        <img src="<?= BASE_URL . htmlspecialchars($item['photo']) ?>" class="room-image" alt="<?= htmlspecialchars($item['name']) ?>">
        <?php endif; ?>
        <div class="room-content">
            <h2><?= htmlspecialchars($item['name']) ?></h2>
            <p style="color:var(--color-muted); font-size:0.85rem;">📍 <?= htmlspecialchars($item['building'] ?? '') ?> · Up to <?= (int)$item['max_occupancy'] ?> guests</p>
            <?php if ($item['avg_rating']): ?>
                <p class="stars">★ <?= number_format($item['avg_rating'], 1) ?> (<?= (int)$item['review_count'] ?>)</p>
            <?php endif; ?>
            <p class="price"><?= format_currency($item['base_price']) ?> / night</p>
            <div style="display:flex; gap:0.5rem;">
                <a href="details.php?id=<?= (int)$item['room_type_id'] ?>" class="cta">View</a>
                <a href="?remove=<?= (int)$item['id'] ?>" class="cta cta-danger" onclick="return confirm('Remove from wishlist?')">Remove</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>