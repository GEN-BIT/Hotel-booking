<?php
/**
 * Payment Gateway Abstraction Layer
 * 
 * Provides a unified interface for payment processing.
 * Currently uses simulated gateway, but can be extended for real gateways (Stripe, PayPal, etc.)
 */

abstract class PaymentGateway {
    protected $config;
    protected $name;
    
    public function __construct($config = []) {
        $this->config = $config;
    }
    
    abstract public function createPayment($bookingId, $amount, $currency = 'USD', $metadata = []);
    abstract public function verifyPayment($transactionId);
    abstract public function refund($transactionId, $amount, $reason = '');
    abstract public function getStatus($transactionId);
    
    public function getName() {
        return $this->name;
    }
    
    protected function generateTransactionId() {
        return strtoupper(bin2hex(random_bytes(12)));
    }
}

/**
 * Simulated Payment Gateway
 * Used for development and testing. Mimics real gateway behavior.
 */
class SimulatedPaymentGateway extends PaymentGateway {
    protected $name = 'simulated';
    private $transactions = [];
    
    public function __construct($config = []) {
        parent::__construct($config);
        $this->transactions = [];
    }
    
    public function createPayment($bookingId, $amount, $currency = 'USD', $metadata = []) {
        $transactionId = $this->generateTransactionId();
        
        $this->transactions[$transactionId] = [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
            'requires_action' => false,
            'message' => 'Payment initiated successfully',
        ];
    }
    
    public function verifyPayment($transactionId) {
        if (!isset($this->transactions[$transactionId])) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Transaction not found',
            ];
        }
        
        $tx = $this->transactions[$transactionId];
        
        if ($tx['status'] === 'pending') {
            $this->transactions[$transactionId]['status'] = 'paid';
            $this->transactions[$transactionId]['paid_at'] = date('Y-m-d H:i:s');
        }
        
        return [
            'success' => true,
            'status' => $this->transactions[$transactionId]['status'],
            'amount' => $tx['amount'],
            'currency' => $tx['currency'],
            'paid_at' => $this->transactions[$transactionId]['paid_at'] ?? null,
            'message' => 'Payment verified',
        ];
    }
    
    public function refund($transactionId, $amount, $reason = '') {
        if (!isset($this->transactions[$transactionId])) {
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }
        
        $tx = $this->transactions[$transactionId];
        
        if ($tx['status'] !== 'paid') {
            return [
                'success' => false,
                'message' => 'Cannot refund unpaid transaction',
            ];
        }
        
        if ($amount > $tx['amount']) {
            return [
                'success' => false,
                'message' => 'Refund amount exceeds transaction amount',
            ];
        }
        
        $this->transactions[$transactionId]['status'] = 'refunded';
        $this->transactions[$transactionId]['refund_amount'] = $amount;
        $this->transactions[$transactionId]['refund_reason'] = $reason;
        $this->transactions[$transactionId]['refunded_at'] = date('Y-m-d H:i:s');
        
        return [
            'success' => true,
            'refund_id' => $this->generateTransactionId(),
            'amount' => $amount,
            'message' => 'Refund processed successfully',
        ];
    }
    
    public function getStatus($transactionId) {
        if (!isset($this->transactions[$transactionId])) {
            return [
                'success' => false,
                'status' => 'not_found',
            ];
        }
        
        return [
            'success' => true,
            'status' => $this->transactions[$transactionId]['status'],
            'amount' => $this->transactions[$transactionId]['amount'],
            'created_at' => $this->transactions[$transactionId]['created_at'],
            'paid_at' => $this->transactions[$transactionId]['paid_at'] ?? null,
        ];
    }
    
    /**
     * Simulate webhook callback from payment gateway
     */
    public function simulateWebhook($transactionId, $event, $data = []) {
        if (!isset($this->transactions[$transactionId])) {
            return false;
        }
        
        $validEvents = ['payment.completed', 'payment.failed', 'refund.processed'];
        if (!in_array($event, $validEvents)) {
            return false;
        }
        
        switch ($event) {
            case 'payment.completed':
                $this->transactions[$transactionId]['status'] = 'paid';
                $this->transactions[$transactionId]['paid_at'] = date('Y-m-d H:i:s');
                break;
            case 'payment.failed':
                $this->transactions[$transactionId]['status'] = 'failed';
                break;
            case 'refund.processed':
                $this->transactions[$transactionId]['status'] = 'refunded';
                break;
        }
        
        return true;
    }
}

/**
 * Payment Manager
 * Handles all payment operations and integrates with gateway
 */
class PaymentManager {
    private $pdo;
    private $gateway;
    
    public function __construct($pdo, $gateway = null) {
        $this->pdo = $pdo;
        $this->gateway = $gateway ?? new SimulatedPaymentGateway();
    }
    
    /**
     * Get active payment gateway
     */
    public static function getActiveGateway($pdo) {
        $stmt = $pdo->prepare('SELECT * FROM payment_gateways WHERE is_active = 1 LIMIT 1');
        $stmt->execute();
        $gateway = $stmt->fetch();
        
        if (!$gateway) {
            return new SimulatedPaymentGateway();
        }
        
        $config = json_decode($gateway['config'], true) ?: [];
        
        switch ($gateway['name']) {
            case 'stripe':
                return new StripePaymentGateway($config);
            case 'paypal':
                return new PayPalPaymentGateway($config);
            case 'mobile_money':
                return new MobileMoneyPaymentGateway($config);
            default:
                return new SimulatedPaymentGateway($config);
        }
    }
    
    /**
     * Create a payment record and initiate gateway payment
     */
    public function createPayment($bookingId, $amount, $method, $metadata = []) {
        $transactionId = $this->gateway->generateTransactionId();
        
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (booking_id, amount, method, status, transaction_ref, gateway, paid_at)
             VALUES (?, ?, ?, "pending", ?, ?, NOW())'
        );
        $stmt->execute([
            $bookingId,
            $amount,
            $method,
            $transactionId,
            $this->gateway->getName()
        ]);
        
        $paymentId = $this->pdo->lastInsertId();
        
        $gatewayResult = $this->gateway->createPayment($bookingId, $amount, 'USD', array_merge($metadata, [
            'payment_id' => $paymentId,
            'method' => $method,
        ]));
        
        if ($gatewayResult['success']) {
            $this->pdo->prepare('UPDATE payments SET gateway_transaction_id = ? WHERE id = ?')
                ->execute([$gatewayResult['transaction_id'], $paymentId]);
        }
        
        return [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'gateway_result' => $gatewayResult,
        ];
    }
    
    /**
     * Verify and complete a payment
     */
    public function verifyPayment($paymentId) {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE id = ?');
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }
        
        if ($payment['status'] === 'paid') {
            return ['success' => true, 'message' => 'Payment already verified', 'payment' => $payment];
        }
        
        $gatewayResult = $this->gateway->verifyPayment($payment['gateway_transaction_id'] ?? $payment['transaction_ref']);
        
        if ($gatewayResult['success'] && $gatewayResult['status'] === 'paid') {
            $this->pdo->prepare(
                'UPDATE payments SET status = "paid", verified_at = NOW(), verification_status = "verified" WHERE id = ?'
            )->execute([$paymentId]);
            
            $this->updateBookingPaymentStatus($payment['booking_id']);
            
            log_activity($this->pdo, 'payment.verified', 
                "Payment #$paymentId verified for booking {$payment['booking_id']}");
            
            return ['success' => true, 'message' => 'Payment verified', 'payment' => $payment];
        }
        
        return ['success' => false, 'message' => $gatewayResult['message'] ?? 'Payment verification failed'];
    }
    
    /**
     * Process a refund with balance check
     */
    public function refund($paymentId, $amount, $reason = '', $requestedBy) {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE id = ?');
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }
        
        if ($payment['status'] !== 'paid') {
            return ['success' => false, 'message' => 'Cannot refund unpaid payment'];
        }
        
        $bookingId = $payment['booking_id'];
        
        $stmt = $this->pdo->prepare('SELECT amount_paid, total_price, status FROM bookings WHERE id = ?');
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        $totalPaid = $booking['amount_paid'] ?? 0;
        $alreadyRefunded = $this->getTotalRefunded($bookingId);
        $availableForRefund = $totalPaid - $alreadyRefunded;
        
        if ($amount > $availableForRefund) {
            return [
                'success' => false, 
                'message' => "Refund amount ({$amount}) exceeds available balance ({$availableForRefund})"
            ];
        }
        
        $gatewayResult = $this->gateway->refund(
            $payment['gateway_transaction_id'] ?? $payment['transaction_ref'],
            $amount,
            $reason
        );
        
        if ($gatewayResult['success']) {
            $refundId = $this->pdo->lastInsertId();
            $this->pdo->prepare(
                'INSERT INTO refunds (booking_id, payment_id, amount, reason, status, requested_by, transaction_ref, created_at)
                 VALUES (?, ?, ?, ?, "completed", ?, ?, NOW())'
            )->execute([$bookingId, $paymentId, $amount, $reason, $requestedBy, $gatewayResult['refund_id']]);
            
            $this->pdo->prepare(
                'UPDATE payments SET status = "refunded" WHERE id = ?'
            )->execute([$paymentId]);
            
            $newBalance = $totalPaid - $amount;
            $newStatus = $newBalance <= 0 ? 'refunded' : 'partial';
            $this->pdo->prepare(
                'UPDATE bookings SET amount_paid = ?, payment_status = ? WHERE id = ?'
            )->execute([$newBalance, $newStatus, $bookingId]);
            
            log_activity($this->pdo, 'payment.refunded', 
                "Refund of {$amount} processed for booking {$booking['booking_reference']}. Reason: {$reason}");
            
            return ['success' => true, 'message' => 'Refund processed successfully', 'amount' => $amount];
        }
        
        return ['success' => false, 'message' => $gatewayResult['message'] ?? 'Refund failed'];
    }
    
    /**
     * Get total refunded amount for a booking
     */
    public function getTotalRefunded($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE booking_id = ? AND status = "completed"'
        );
        $stmt->execute([$bookingId]);
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Get payment balance for a booking
     */
    public function getPaymentBalance($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT total_price, amount_paid, deposit_amount, discount_amount, status FROM bookings WHERE id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return null;
        }
        
        $totalPaid = (float)$booking['amount_paid'];
        $refunded = $this->getTotalRefunded($bookingId);
        $netPaid = $totalPaid - $refunded;
        $balance = (float)$booking['total_price'] - $netPaid;
        $availableForRefund = $netPaid;
        
        return [
            'total_price' => (float)$booking['total_price'],
            'total_paid' => $totalPaid,
            'refunded' => $refunded,
            'net_paid' => $netPaid,
            'balance' => max(0, $balance),
            'available_for_refund' => $availableForRefund,
            'is_paid' => $balance <= 0,
            'status' => $booking['status'],
            'payment_status' => $booking['payment_status'] ?? 'pending',
        ];
    }
    
    /**
     * Update booking payment status based on payments
     */
    private function updateBookingPaymentStatus($bookingId) {
        $balance = $this->getPaymentBalance($bookingId);
        
        if (!$balance) return;
        
        if ($balance['is_paid']) {
            $status = 'paid';
        } elseif ($balance['net_paid'] > 0) {
            $status = 'partial';
        } else {
            $status = 'pending';
        }
        
        $this->pdo->prepare('UPDATE bookings SET payment_status = ? WHERE id = ?')
            ->execute([$status, $bookingId]);
    }
}

/**
 * Stripe Payment Gateway (placeholder for future implementation)
 */
class StripePaymentGateway extends PaymentGateway {
    protected $name = 'stripe';
    
    public function createPayment($bookingId, $amount, $currency = 'USD', $metadata = []) {
        // TODO: Implement Stripe API integration
        return [
            'success' => false,
            'message' => 'Stripe integration not yet implemented',
        ];
    }
    
    public function verifyPayment($transactionId) {
        return ['success' => false, 'message' => 'Stripe integration not yet implemented'];
    }
    
    public function refund($transactionId, $amount, $reason = '') {
        return ['success' => false, 'message' => 'Stripe integration not yet implemented'];
    }
    
    public function getStatus($transactionId) {
        return ['success' => false, 'status' => 'not_found'];
    }
}

/**
 * PayPal Payment Gateway (placeholder for future implementation)
 */
class PayPalPaymentGateway extends PaymentGateway {
    protected $name = 'paypal';
    
    public function createPayment($bookingId, $amount, $currency = 'USD', $metadata = []) {
        return [
            'success' => false,
            'message' => 'PayPal integration not yet implemented',
        ];
    }
    
    public function verifyPayment($transactionId) {
        return ['success' => false, 'message' => 'PayPal integration not yet implemented'];
    }
    
    public function refund($transactionId, $amount, $reason = '') {
        return ['success' => false, 'message' => 'PayPal integration not yet implemented'];
    }
    
    public function getStatus($transactionId) {
        return ['success' => false, 'status' => 'not_found'];
    }
}

/**
 * Mobile Money Payment Gateway (placeholder for future implementation)
 */
class MobileMoneyPaymentGateway extends PaymentGateway {
    protected $name = 'mobile_money';
    
    public function createPayment($bookingId, $amount, $currency = 'USD', $metadata = []) {
        return [
            'success' => false,
            'message' => 'Mobile Money integration not yet implemented',
        ];
    }
    
    public function verifyPayment($transactionId) {
        return ['success' => false, 'message' => 'Mobile Money integration not yet implemented'];
    }
    
    public function refund($transactionId, $amount, $reason = '') {
        return ['success' => false, 'message' => 'Mobile Money integration not yet implemented'];
    }
    
    public function getStatus($transactionId) {
        return ['success' => false, 'status' => 'not_found'];
    }
}
