<?php require_once __DIR__ . '/../config/config.php';
require_login();

$reference = $_SESSION['last_booking_reference'] ?? null;
if (!$reference) {
    header('Location: ' . BASE_URL . 'account/index.php');
    exit;
}
unset($_SESSION['last_booking_reference']);
unset($_SESSION['last_booking_id']);

require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('booking_confirmed') ?></h1>
<p><?= trans('your_booking_reference') ?> <strong><?= htmlspecialchars($reference) ?></strong></p>
<p class="success"><?= trans('payment_received_thanks') ?></p>
<p><?= trans('view_details_anytime') ?></p>
<a href="<?= BASE_URL ?>account/reservations.php"><?= trans('view_my_reservations') ?></a>
<?php require __DIR__ . '/../includes/footer.php'; ?>
