<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class StripePaymentGateway {
    private $stripe;
    private $secret_key;

    public function __construct() {
        $this->loadSettings();
        if ($this->secret_key) {
            Stripe::setApiKey($this->secret_key);
            Stripe::setApiVersion("2022-11-15"); // Optional: for API consistency
            $this->stripe = new \Stripe\StripeClient($this->secret_key);
        }
    }

    /**
     * Load Stripe API keys from the database settings.
     */
    private function loadSettings() {
        // This assumes you have a function or method to get settings from the DB
        // Let's use a placeholder function for now.
        // In a real app, you would replace this with your actual settings provider.
        $this->secret_key = get_setting('stripe_secret_key');
    }

    /**
     * Check if the gateway is properly configured and enabled.
     * @return bool
     */
    public function isEnabled() {
        return !empty($this->secret_key);
    }

    /**
     * Create a new Payment Intent with Stripe.
     *
     * @param int $amount The amount to charge, in the smallest currency unit (e.g., cents).
     * @param string $currency The currency code (e.g., 'usd').
     * @param array $metadata Optional metadata to attach to the payment.
     * @return PaymentIntent|null
     */
    public function createPaymentIntent($amount, $currency = 'usd', $metadata = []) {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $metadata,
            ]);
            return $paymentIntent;
        } catch (ApiErrorException $e) {
            // Log the error
            error_log("Stripe API Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve a Payment Intent from Stripe.
     *
     * @param string $paymentIntentId
     * @return PaymentIntent|null
     */
    public function retrievePaymentIntent($paymentIntentId) {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            error_log("Stripe API Error: " . $e->getMessage());
            return null;
        }
    }
}