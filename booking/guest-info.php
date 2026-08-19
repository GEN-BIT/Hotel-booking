<?php require_once __DIR__ . '/../config/config.php';
require_login();

if (empty($_SESSION['pending_booking'])) {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['pending_booking']['special_requests'] = trim($_POST['special_requests'] ?? '');
    $_SESSION['pending_booking']['extra_guests'] = $_POST['guest_name'] ?? [];
    header('Location: ' . BASE_URL . 'booking/review.php');
    exit;
}

$pb = $_SESSION['pending_booking'];
require __DIR__ . '/../includes/header.php';
?>
<h1>Guest Information</h1>
<form method="post">
    <label>Special Requests <input type="text" name="special_requests"></label>
    <?php for ($i = 1; $i < $pb['guests']; $i++): ?>
        <label>Guest <?= $i + 1 ?> Name <input type="text" name="guest_name[]"></label>
    <?php endfor; ?>
    <button type="submit">Continue to Review</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
