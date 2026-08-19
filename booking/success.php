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
<h1>Booking Confirmed!</h1>
<p>Your booking reference is: <strong><?= htmlspecialchars($reference) ?></strong></p>
<p class="success">Payment received — thank you!</p>
<p>You can view details anytime in your account.</p>
<a href="<?= BASE_URL ?>account/reservations.php">View My Reservations</a>
<?php require __DIR__ . '/../includes/footer.php'; ?>
