<?php require __DIR__ . '/../../includes/admin-header.php';
require_permission('manage_guests');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$guest = $stmt->fetch();
if (!$guest) die('Guest not found.');

$stmt = $pdo->prepare(
    'SELECT b.*, r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id
     WHERE b.user_id = ? ORDER BY b.check_in DESC'
);
$stmt->execute([$id]);
$bookings = $stmt->fetchAll();
?>
<h1><?= htmlspecialchars($guest['full_name']) ?></h1>
<p>Email: <?= htmlspecialchars($guest['email']) ?></p>
<p>Phone: <?= htmlspecialchars($guest['phone'] ?? '—') ?></p>
<p>Joined: <?= htmlspecialchars($guest['created_at']) ?></p>

<div style="margin: 1rem 0;">
    <a href="documents.php?id=<?= (int)$guest['id'] ?>" class="cta"><?= trans('manage') ?> Documents</a>
</div>

<h3>Booking History</h3>
<table class="data-table">
    <tr><th>Reference</th><th>Room</th><th>Dates</th><th>Status</th></tr>
    <?php foreach ($bookings as $b): ?>
    <tr>
        <td><a href="<?= BASE_URL ?>admin/bookings/view.php?id=<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['booking_reference']) ?></a></td>
        <td><?= htmlspecialchars($b['room_number']) ?></td>
        <td><?= htmlspecialchars($b['check_in']) ?> &rarr; <?= htmlspecialchars($b['check_out']) ?></td>
        <td><span class="status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
    </tr>
    <?php endforeach; ?>
</table>
<a href="index.php">Back to Guests</a>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
