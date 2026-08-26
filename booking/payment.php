<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$bookingId = $_SESSION['last_booking_id'] ?? 0;
if (!$bookingId) {
    header('Location: ' . BASE_URL . 'account/reservations.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();
if (!$booking) die('Booking not found.');

if ($booking['payment_status'] === 'paid') {
    header('Location: ' . BASE_URL . 'booking/success.php');
    exit;
}

// Calculate amount due
$balance = PaymentManager::getPaymentBalance($bookingId);
$depositPercentage = (int)get_setting($pdo, 'deposit_percentage', 0);
$depositAmount = $depositPercentage > 0 ? ($booking['total_price'] * $depositPercentage / 100) : $booking['total_price'];
$amountDue = max(0, $depositAmount - $balance['net_paid']);

$stripeClientSecret = null;
$stripePublicKey = null;
$error = '';

// Check if Stripe is the active gateway and enabled
$gateway = PaymentManager::getActiveGateway($pdo);
if ($gateway instanceof StripePaymentGateway && $gateway->isEnabled() && $amountDue > 0) {
    $stripePublicKey = get_setting($pdo, 'stripe_public_key');

    // Create a Payment Intent
    $paymentIntent = $gateway->createPaymentIntent(
        (int)($amountDue * 100), // Amount in cents
        strtolower(get_setting($pdo, 'site_currency', 'usd')),
        [
            'booking_id' => $bookingId,
            'booking_reference' => $booking['booking_reference'],
            'user_id' => $_SESSION['user_id']
        ]
    );

    if ($paymentIntent) {
        $stripeClientSecret = $paymentIntent->client_secret;
    } else {
        $error = 'Could not initialize payment process. Please contact support.';
    }
} else {
    // Fallback or error for when Stripe is not available
    $error = 'Online payment is currently unavailable. Please try again later.';
}

require __DIR__ . '/../includes/header.php';
?>

<h1><?= trans('payment_title') ?></h1>
<p><?= trans('booking_label') ?> <?= htmlspecialchars($booking['booking_reference']) ?></p>

<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1.5rem; margin: 1.5rem 0;">
    <h3 style="margin: 0 0 1rem;">Payment Summary</h3>
    <table style="width: 100%;">
        <tr>
            <td style="padding: 0.5rem 0; color: var(--color-muted);"><?= trans('total_price_label') ?></td>
            <td style="padding: 0.5rem 0; text-align: right; font-weight: 600;">$<?= number_format($booking['total_price'], 2) ?></td>
        </tr>
        <tr style="border-top: 2px solid var(--color-border);">
            <td style="padding: 0.75rem 0; font-weight: 700; font-size: 1.1rem;"><?= trans('balance_due') ?></td>
            <td style="padding: 0.75rem 0; text-align: right; font-weight: 700; font-size: 1.1rem; color: var(--color-accent);">$<?= number_format($amountDue, 2) ?></td>
        </tr>
    </table>
</div>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($stripeClientSecret && $stripePublicKey && $amountDue > 0): ?>
    <div id="payment-container">
        <form id="payment-form">
            <div id="payment-element">
                <!-- Stripe.js will create the payment element here -->
            </div>
            <button id="submit" class="cta" style="margin-top: 1.5rem; width: 100%;">
                <span id="button-text"><?= trans('pay_now') ?> $<?= number_format($amountDue, 2) ?></span>
                <span id="spinner" style="display: none;">Processing...</span>
            </button>
            <div id="payment-message" class="error" style="display: none; margin-top: 1rem;"></div>
        </form>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stripe = Stripe('<?= htmlspecialchars($stripePublicKey) ?>');
            const clientSecret = '<?= $stripeClientSecret ?>';
            
            const options = {
                clientSecret: clientSecret,
                appearance: {
                    theme: 'stripe',
                    variables: {
                        colorPrimary: '#0570de',
                        colorBackground: '#ffffff',
                        colorText: '#30313d',
                        colorDanger: '#df1b41',
                        fontFamily: 'Ideal Sans, system-ui, sans-serif',
                        spacingUnit: '2px',
                        borderRadius: '4px',
                    }
                }
            };

            // Set up Stripe.js and Elements
            const elements = stripe.elements(options);

            // Create and mount the Payment Element
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');

            const form = document.getElementById('payment-form');
            const submitButton = document.getElementById('submit');
            const paymentMessage = document.getElementById('payment-message');

            form.addEventListener('submit', async function(event) {
                event.preventDefault();

                // Disable the button to prevent multiple submissions
                submitButton.disabled = true;
                document.getElementById('spinner').style.display = 'inline';
                document.getElementById('button-text').style.display = 'none';

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: '<?= BASE_URL . 'booking/payment_status.php?booking_id=' . $bookingId ?>',
                    }
                });

                if (error) {
                    // This point will only be reached if there is an immediate error when
                    // confirming the payment. Otherwise, your customer will be redirected to
                    // your `return_url`.
                    paymentMessage.textContent = error.message;
                    paymentMessage.style.display = 'block';
                    
                    // Re-enable the button
                    submitButton.disabled = false;
                    document.getElementById('spinner').style.display = 'none';
                    document.getElementById('button-text').style.display = 'inline';
                }
            });
        });
    </script>
<?php elseif ($amountDue <= 0): ?>
    <p class="success"><?= trans('payment_complete') ?></p>
    <a href="<?= BASE_URL ?>booking/success.php" class="cta"><?= trans('continue') ?></a>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>