<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);
?>
<h1>Settings</h1>
<ul class="account-menu">
    <li><a href="hotel.php">Hotel Information</a></li>
    <li><a href="booking-policy.php">Booking Policy</a></li>
    <li><a href="payment-settings.php">Payment Settings</a></li>
    <li><a href="email-settings.php">Email Settings</a></li>
</ul>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
