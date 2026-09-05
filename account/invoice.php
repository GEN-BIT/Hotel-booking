<?php require_once __DIR__ . '/../config/config.php';
require_login();

$bookingId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT b.*, u.full_name, u.email, r.room_number, rt.name AS type_name, rt.base_price,
            p.status AS payment_status, p.method AS payment_method, p.paid_at, p.transaction_ref
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.id = ? AND b.user_id = ?'
);
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    die('Booking not found.');
}

$nights = (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400;
$subtotal = $nights * $booking['base_price'];
$discount = $booking['discount_amount'] ?? 0;
$total = $booking['total_price'];

$stmt = $pdo->prepare('SELECT full_name FROM booking_guests WHERE booking_id = ?');
$stmt->execute([$bookingId]);
$extraGuests = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT bs.*, s.name AS service_name FROM booking_services bs JOIN services s ON bs.service_id = s.id WHERE bs.booking_id = ?');
$stmt->execute([$bookingId]);
$bookingServices = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<style>
.invoice-box {
  max-width: 800px;
  margin: 0 auto;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 2.5rem;
  box-shadow: var(--shadow);
}
.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid var(--color-border);
}
.invoice-title {
  font-size: 2rem;
  margin: 0;
  color: var(--color-primary);
}
.invoice-meta {
  text-align: right;
  color: var(--color-muted);
  font-size: 0.9rem;
}
.invoice-section {
  margin-bottom: 1.5rem;
}
.invoice-section h3 {
  margin: 0 0 0.75rem;
  font-size: 1.1rem;
  color: var(--color-primary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.invoice-table {
  width: 100%;
  border-collapse: collapse;
}
.invoice-table td {
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--color-border);
}
.invoice-table td:last-child {
  text-align: right;
  font-weight: 600;
}
.invoice-total {
  text-align: right;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-top: 1rem;
}
.invoice-footer {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 2px solid var(--color-border);
  text-align: center;
  color: var(--color-muted);
  font-size: 0.85rem;
}
.invoice-actions {
  text-align: center;
  margin-top: 1.5rem;
}
@media print {
  .site-header, .site-footer, .invoice-actions { display: none !important; }
  .invoice-box { border: none; box-shadow: none; max-width: 100%; }
  body { background: #fff; }
}
</style>

<div class="invoice-box">
  <div class="invoice-header">
    <div>
      <h1 class="invoice-title">INVOICE</h1>
      <p style="margin:0.5rem 0 0; color:var(--color-muted);"><?= htmlspecialchars(get_setting($pdo, 'hotel_name', 'Our Hotel')) ?></p>
    </div>
    <div class="invoice-meta">
      <p><strong>Reference:</strong> <?= htmlspecialchars($booking['booking_reference']) ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($booking['created_at']) ?></p>
      <p><strong>Status:</strong> <span class="status status-<?= htmlspecialchars($booking['status']) ?>"><?= htmlspecialchars($booking['status']) ?></span></p>
    </div>
  </div>

  <div class="invoice-section">
    <h3>Guest Information</h3>
    <table class="invoice-table">
        <tr><td>Name</td><td><?= htmlspecialchars($booking['full_name']) ?></td></tr>
        <tr><td>Email</td><td><?= htmlspecialchars($booking['email']) ?></td></tr>
    </table>
  </div>

  <div class="invoice-section">
    <h3>Booking Details</h3>
    <table class="invoice-table">
        <tr><td>Room</td><td><?= htmlspecialchars($booking['type_name']) ?> — Room <?= htmlspecialchars($booking['room_number']) ?></td></tr>
        <tr><td>Check-in</td><td><?= htmlspecialchars($booking['check_in']) ?></td></tr>
        <tr><td>Check-out</td><td><?= htmlspecialchars($booking['check_out']) ?></td></tr>
        <tr><td>Nights</td><td><?= (int)$nights ?></td></tr>
        <tr><td>Guests</td><td><?= (int)$booking['num_guests'] ?></td></tr>
        <?php if ($extraGuests): ?>
        <tr><td>Additional Guests</td><td><?= htmlspecialchars(implode(', ', $extraGuests)) ?></td></tr>
        <?php endif; ?>
    </table>
  </div>

  <div class="invoice-section">
    <h3>Payment Summary</h3>
    <table class="invoice-table">
        <tr><td>Room Rate</td><td>$<?= number_format($booking['base_price'], 2) ?> / night</td></tr>
        <tr><td>Subtotal (<?= (int)$nights ?> nights)</td><td>$<?= number_format($subtotal, 2) ?></td></tr>
        <?php if ($discount > 0): ?>
        <tr><td>Discount</td><td>-$<?= number_format($discount, 2) ?></td></tr>
        <?php endif; ?>
        <?php if ($bookingServices): ?>
        <tr><td colspan="2"><strong>Extra Services</strong></td></tr>
        <?php foreach ($bookingServices as $s): ?>
        <tr><td><?= htmlspecialchars($s['service_name']) ?> x<?= (int)$s['quantity'] ?></td><td>$<?= number_format($s['subtotal'], 2) ?></td></tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <tr><td><strong>Total</strong></td><td><strong>$<?= number_format($total, 2) ?></strong></td></tr>
        <tr><td>Payment Status</td><td><?= htmlspecialchars($booking['payment_status'] ?? 'unpaid') ?></td></tr>
        <?php if ($booking['payment_method']): ?>
        <tr><td>Payment Method</td><td><?= htmlspecialchars($booking['payment_method']) ?></td></tr>
        <?php endif; ?>
        <?php if ($booking['paid_at']): ?>
        <tr><td>Paid At</td><td><?= htmlspecialchars($booking['paid_at']) ?></td></tr>
        <?php endif; ?>
        <?php if ($booking['transaction_ref']): ?>
        <tr><td>Transaction Ref</td><td><?= htmlspecialchars($booking['transaction_ref']) ?></td></tr>
        <?php endif; ?>
    </table>
  </div>

  <div class="invoice-footer">
    <p>Thank you for choosing <?= htmlspecialchars(get_setting($pdo, 'hotel_name', 'Our Hotel')) ?>.</p>
    <p>For inquiries, contact us at <?= htmlspecialchars(get_setting($pdo, 'hotel_email', 'info@hotel.com')) ?></p>
  </div>

  <div class="invoice-actions">
    <button onclick="window.print()" class="cta">Print Invoice</button>
    <a href="reservations.php" class="cta" style="background:var(--color-border); color:var(--color-text); margin-left:0.5rem;">Back to Reservations</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
