<?php require_once __DIR__ . '/../config/config.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT b.*, rt.name AS type_name, r.room_number, rev.id AS review_id
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     LEFT JOIN reviews rev ON rev.booking_id = b.id
     WHERE b.user_id = ?
     ORDER BY b.check_in DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('my_reservations') ?></h1>
<?php if (isset($_GET['reviewed'])): ?><p class="success"><?= trans('thank_you_for_review') ?></p><?php endif; ?>
<?php if (!$bookings): ?>
    <p><?= trans('no_reservations_yet', ['url' => BASE_URL . 'rooms/index.php']) ?></p>
<?php else: ?>
<table class="data-table">
    <tr><th><?= trans('reference') ?></th><th><?= trans('room') ?></th><th><?= trans('date') ?></th><th><?= trans('total') ?></th><th><?= trans('status') ?></th><th></th></tr>
    <?php foreach ($bookings as $b): ?>
    <tr>
        <td><?= htmlspecialchars($b['booking_reference']) ?></td>
        <td><?= htmlspecialchars($b['type_name']) ?> — <?= htmlspecialchars($b['room_number']) ?></td>
        <td><?= htmlspecialchars($b['check_in']) ?> &rarr; <?= htmlspecialchars($b['check_out']) ?></td>
        <td>$<?= number_format($b['total_price'], 2) ?></td>
        <td><span class="status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
        <td>
            <a href="reservation-details.php?id=<?= (int)$b['id'] ?>"><?= trans('view') ?></a>
            <?php if (in_array($b['status'], ['pending','confirmed'])): ?>
                &nbsp;|&nbsp;
                <form method="post" action="<?= BASE_URL ?>
          <?= csrf_field() ?>booking/cancel.php" style="display:inline"
                      onsubmit="return confirm('<?= trans('cancel_reservation_confirm') ?>');">
                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                    <button type="submit" class="link-btn"><?= trans('cancel') ?></button>
                </form>
            <?php endif; ?>
            <?php if ($b['status'] === 'checked_out'): ?>
                &nbsp;|&nbsp;
                <?php if ($b['review_id']): ?>
                    <span class="stars">&#9733; <?= trans('reviewed') ?></span>
                <?php else: ?>
                    <a href="review.php?booking_id=<?= (int)$b['id'] ?>"><?= trans('leave_review') ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
