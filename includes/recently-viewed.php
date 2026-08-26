<?php require_once __DIR__ . '/../config/config.php';

$recent = $_SESSION['recently_viewed'] ?? [];
$roomTypeId = (int)($_GET['id'] ?? 0);

if ($roomTypeId > 0) {
    $recent = array_filter($recent, function($id) use ($roomTypeId) { return $id != $roomTypeId; });
    array_unshift($recent, $roomTypeId);
    $recent = array_slice($recent, 0, 8);
    $_SESSION['recently_viewed'] = $recent;
}

if (!empty($recent)) {
    $placeholders = implode(',', array_fill(0, count($recent), '?'));
    $stmt = $pdo->prepare(
        "SELECT rt.*, (SELECT file_path FROM room_type_photos WHERE room_type_id = rt.id ORDER BY sort_order ASC, id ASC LIMIT 1) as photo,
         (SELECT AVG(rating) FROM reviews WHERE room_type_id = rt.id) as avg_rating,
         (SELECT COUNT(*) FROM reviews WHERE room_type_id = rt.id) as review_count
         FROM room_types rt WHERE rt.id IN ($placeholders) ORDER BY FIELD(rt.id, " . implode(',', $recent) . ")"
    );
    $stmt->execute($recent);
    $recentRooms = $stmt->fetchAll();
}
?>
<?php if (!empty($recentRooms)): ?>
<div style="margin:2rem 0; padding:1.5rem; background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius);">
    <h3 style="margin:0 0 1rem; font-size:1.1rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:0.05em;"><?= trans('recently_viewed') ?></h3>
    <div style="display:flex; gap:1rem; overflow-x:auto; padding-bottom:0.5rem;">
        <?php foreach ($recentRooms as $rr): ?>
        <div style="min-width:180px; max-width:220px; flex:1; background:var(--color-bg); border:1px solid var(--color-border); border-radius:var(--radius); overflow:hidden; text-decoration:none; color:inherit;">
            <?php if ($rr['photo']): ?>
            <img src="<?= BASE_URL . htmlspecialchars($rr['photo']) ?>" style="width:100%; height:120px; object-fit:cover; display:block;" loading="lazy">
            <?php endif; ?>
            <div style="padding:0.75rem;">
                <div style="font-weight:600; font-size:0.9rem; margin-bottom:0.25rem; color:var(--color-primary);"><?= htmlspecialchars($rr['name']) ?></div>
                <?php if ($rr['avg_rating']): ?>
                    <div class="stars" style="font-size:0.8rem;">★ <?= number_format($rr['avg_rating'], 1) ?> (<?= (int)$rr['review_count'] ?>)</div>
                <?php endif; ?>
                <div style="font-weight:700; color:var(--color-primary); margin-top:0.25rem;"><?= format_currency($rr['base_price']) ?> / night</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>