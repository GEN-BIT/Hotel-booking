<?php
/**
 * Notification & Email Service
 * 
 * Handles both email notifications (via PHPMailer) and in-app notifications.
 * Provides templates for booking confirmations, payment receipts, and cancellations.
 */

class NotificationService {
    private $pdo;
    private $mailerAvailable;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->mailerAvailable = $this->checkMailerAvailability();
    }
    
    /**
     * Check if PHPMailer is available
     */
    private function checkMailerAvailability() {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        return file_exists($autoload);
    }
    
    /**
     * Check if SMTP is configured
     */
    public function isEmailEnabled() {
        $host = get_setting($this->pdo, 'smtp_host');
        return !empty($host);
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendEmail($to, $toName, $subject, $htmlBody, $altBody = '') {
        $logEntry = [
            'recipient_email' => $to,
            'recipient_name' => $toName,
            'subject' => $subject,
            'body' => $htmlBody,
            'status' => 'pending',
            'gateway' => 'smtp',
        ];
        
        if (!$this->mailerAvailable) {
            $logEntry['status'] = 'failed';
            $logEntry['error_message'] = 'PHPMailer not available';
            $this->logEmail($logEntry);
            
            return [
                'success' => false,
                'message' => 'PHPMailer not available',
                'skipped' => true,
            ];
        }
        
        if (!$this->isEmailEnabled()) {
            $logEntry['status'] = 'failed';
            $logEntry['error_message'] = 'SMTP not configured';
            $this->logEmail($logEntry);
            
            return [
                'success' => false,
                'message' => 'SMTP not configured',
                'skipped' => true,
            ];
        }
        
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $host = get_setting($this->pdo, 'smtp_host');
            $port = (int)get_setting($this->pdo, 'smtp_port', 587);
            $username = get_setting($this->pdo, 'smtp_username');
            $password = get_setting($this->pdo, 'smtp_password');
            $fromEmail = get_setting($this->pdo, 'from_email') ?: 'no-reply@hotel-booking.local';
            $fromName = get_setting($this->pdo, 'from_name') ?: 'Hotel Booking System';
            
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = !empty($username);
            if (!empty($username)) {
                $mail->Username = $username;
                $mail->Password = $password;
            }
            $mail->SMTPSecure = 'tls';
            $mail->Port = $port;
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);
            
            $mail->send();
            
            $logEntry['status'] = 'sent';
            $logEntry['sent_at'] = date('Y-m-d H:i:s');
            $this->logEmail($logEntry);
            
            return [
                'success' => true,
                'message' => 'Email sent successfully',
            ];
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $logEntry['status'] = 'failed';
            $logEntry['error_message'] = $mail->ErrorInfo;
            $this->logEmail($logEntry);
            
            error_log('Mail send failed: ' . $mail->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Mail send failed: ' . $mail->ErrorInfo,
                'error' => $mail->ErrorInfo,
            ];
        }
    }
    
    /**
     * Log email to database
     */
    private function logEmail($entry) {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO email_logs (recipient_email, recipient_name, subject, body, status, error_message, gateway, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $entry['recipient_email'],
                $entry['recipient_name'] ?? null,
                $entry['subject'],
                $entry['body'],
                $entry['status'],
                $entry['error_message'] ?? null,
                $entry['gateway'] ?? 'smtp',
                $entry['sent_at'] ?? null,
            ]);
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }
    
    /**
     * Create in-app notification
     */
    private function createInAppNotification($userId, $title, $message, $type = 'info') {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, is_read, created_at)
             VALUES (?, ?, ?, 0, NOW())'
        );
        $stmt->execute([$userId, $title, $message]);
        
        return [
            'success' => true,
            'notification_id' => $this->pdo->lastInsertId(),
        ];
    }
    
    /**
     * Send notification via all available channels
     */
    public function notify($userId, $title, $message, $email = null, $emailSubject = null, $emailTemplate = null) {
        $results = [
            'in_app' => false,
            'email' => false,
            'email_error' => null,
        ];
        
        if ($userId) {
            $results['in_app'] = $this->createInAppNotification($userId, $title, $message);
        }
        
        if ($email && $this->isEmailEnabled()) {
            $subject = $emailSubject ?: $title;
            $html = $emailTemplate ?: $this->defaultEmailTemplate($title, $message);
            
            $emailResult = $this->sendEmail($email, '', $subject, $html);
            $results['email'] = $emailResult['success'];
            $results['email_error'] = $emailResult['message'] ?? null;
        }
        
        return $results;
    }
    
    /**
     * Default email template
     */
    private function defaultEmailTemplate($title, $message) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($title) . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>' . htmlspecialchars($hotelName) . '</h2>
            </div>
            <div class="content">
                <h3>' . htmlspecialchars($title) . '</h3>
                <p>' . nl2br(htmlspecialchars($message)) . '</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Send booking confirmation to guest
     */
    public function sendBookingConfirmationToGuest($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.email, u.full_name, r.room_number, rt.name as room_type_name, rt.base_price
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        $title = 'Booking Confirmation - ' . $booking['booking_reference'];
        $message = "Dear {$booking['full_name']},\n\n"
            . "Your booking has been confirmed!\n\n"
            . "Booking Reference: {$booking['booking_reference']}\n"
            . "Room: {$booking['room_type_name']} (Room {$booking['room_number']})\n"
            . "Check-in: {$booking['check_in']}\n"
            . "Check-out: {$booking['check_out']}\n"
            . "Guests: {$booking['num_guests']}\n"
            . "Total Price: $" . number_format($booking['total_price'], 2) . "\n"
            . "Status: " . ucfirst($booking['status']) . "\n\n"
            . "Thank you for choosing us!\n";
        
        $htmlBody = $this->generateBookingConfirmationHTML($booking);
        
        return $this->notify(
            $booking['user_id'],
            $title,
            $message,
            $booking['email'],
            $title,
            $htmlBody
        );
    }
    
    /**
     * Send booking notification to admin
     */
    public function sendBookingNotificationToAdmin($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.full_name, u.email, u.phone, r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        $adminEmail = get_setting($this->pdo, 'hotel_email', 'admin@hotel-booking.local');
        
        $title = 'New Booking Received - ' . $booking['booking_reference'];
        $message = "A new booking has been created.\n\n"
            . "Booking Reference: {$booking['booking_reference']}\n"
            . "Guest: {$booking['full_name']}\n"
            . "Email: {$booking['email']}\n"
            . "Phone: {$booking['phone']}\n"
            . "Room: {$booking['room_type_name']} (Room {$booking['room_number']})\n"
            . "Check-in: {$booking['check_in']}\n"
            . "Check-out: {$booking['check_out']}\n"
            . "Guests: {$booking['num_guests']}\n"
            . "Total Price: $" . number_format($booking['total_price'], 2) . "\n"
            . "Status: " . ucfirst($booking['status']) . "\n";
        
        $htmlBody = $this->generateAdminBookingNotificationHTML($booking);
        
        return $this->sendEmail(
            $adminEmail,
            'Admin',
            $title,
            $htmlBody,
            $message
        );
    }
    
    /**
     * Send payment receipt to guest
     */
    public function sendPaymentReceiptToGuest($paymentId) {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, b.booking_reference, b.check_in, b.check_out, b.num_guests,
             u.email, u.full_name, r.room_number, rt.name as room_type_name
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE p.id = ?'
        );
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }
        
        $title = 'Payment Receipt - ' . $payment['booking_reference'];
        $message = "Dear {$payment['full_name']},\n\n"
            . "We have received your payment.\n\n"
            . "Booking Reference: {$payment['booking_reference']}\n"
            . "Room: {$payment['room_type_name']} (Room {$payment['room_number']})\n"
            . "Dates: {$payment['check_in']} to {$payment['check_out']}\n"
            . "Amount Paid: $" . number_format($payment['amount'], 2) . "\n"
            . "Payment Method: " . ucfirst(str_replace('_', ' ', $payment['method'])) . "\n"
            . "Transaction Ref: {$payment['transaction_ref']}\n"
            . "Paid At: " . ($payment['paid_at'] ?? 'N/A') . "\n\n"
            . "Thank you for your payment!\n";
        
        $htmlBody = $this->generatePaymentReceiptHTML($payment);
        
        return $this->notify(
            $payment['user_id'] ?? null,
            $title,
            $message,
            $payment['email'],
            $title,
            $htmlBody
        );
    }
    
    /**
     * Send cancellation notification to guest
     */
    public function sendCancellationToGuest($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.email, u.full_name, r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        $title = 'Booking Cancellation - ' . $booking['booking_reference'];
        $message = "Dear {$booking['full_name']},\n\n"
            . "Your booking has been cancelled.\n\n"
            . "Booking Reference: {$booking['booking_reference']}\n"
            . "Room: {$booking['room_type_name']} (Room {$booking['room_number']})\n"
            . "Check-in: {$booking['check_in']}\n"
            . "Check-out: {$booking['check_out']}\n\n"
            . "If you have any questions, please contact us.\n";
        
        $htmlBody = $this->generateCancellationHTML($booking);
        
        return $this->notify(
            $booking['user_id'],
            $title,
            $message,
            $booking['email'],
            $title,
            $htmlBody
        );
    }
    
    /**
     * Send cancellation notification to admin
     */
    public function sendCancellationToAdmin($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.full_name, u.email, r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        $adminEmail = get_setting($this->pdo, 'hotel_email', 'admin@hotel-booking.local');
        
        $title = 'Booking Cancelled - ' . $booking['booking_reference'];
        $message = "A booking has been cancelled.\n\n"
            . "Booking Reference: {$booking['booking_reference']}\n"
            . "Guest: {$booking['full_name']}\n"
            . "Email: {$booking['email']}\n"
            . "Room: {$booking['room_type_name']} (Room {$booking['room_number']})\n"
            . "Check-in: {$booking['check_in']}\n"
            . "Check-out: {$booking['check_out']}\n";
        
        $htmlBody = $this->generateAdminCancellationHTML($booking);
        
        return $this->sendEmail($adminEmail, 'Admin', $title, $htmlBody, $message);
    }
    
    /**
     * Generate booking confirmation HTML email
     */
    private function generateBookingConfirmationHTML($booking) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        $checkInTime = get_setting($this->pdo, 'check_in_time', '14:00');
        $checkOutTime = get_setting($this->pdo, 'check_out_time', '11:00');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Booking Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                .success { color: #27ae60; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>' . htmlspecialchars($hotelName) . '</h2>
            </div>
            <div class="content">
                <h3 class="success">Booking Confirmed!</h3>
                <p>Dear ' . htmlspecialchars($booking['full_name']) . ',</p>
                <p>Your booking has been confirmed. Here are your booking details:</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($booking['booking_reference']) . '</strong></td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($booking['room_type_name']) . ' (Room ' . htmlspecialchars($booking['room_number']) . ')</td></tr>
                        <tr><td>Check-in</td><td>' . htmlspecialchars($booking['check_in']) . ' from ' . htmlspecialchars($checkInTime) . '</td></tr>
                        <tr><td>Check-out</td><td>' . htmlspecialchars($booking['check_out']) . ' until ' . htmlspecialchars($checkOutTime) . '</td></tr>
                        <tr><td>Guests</td><td>' . (int)$booking['num_guests'] . '</td></tr>
                        <tr><td>Total Price</td><td>$' . number_format($booking['total_price'], 2) . '</td></tr>
                    </table>
                </div>
                
                <p>Please present this confirmation upon check-in. If you have any questions, contact us.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Generate admin booking notification HTML
     */
    private function generateAdminBookingNotificationHTML($booking) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>New Booking Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>' . htmlspecialchars($hotelName) . ' - Admin</h2>
            </div>
            <div class="content">
                <h3>New Booking Received</h3>
                <p>A new booking has been created and requires your attention.</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($booking['booking_reference']) . '</strong></td></tr>
                        <tr><td>Guest</td><td>' . htmlspecialchars($booking['full_name']) . '</td></tr>
                        <tr><td>Email</td><td>' . htmlspecialchars($booking['email']) . '</td></tr>
                        <tr><td>Phone</td><td>' . htmlspecialchars($booking['phone'] ?? 'N/A') . '</td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($booking['room_type_name']) . ' (Room ' . htmlspecialchars($booking['room_number']) . ')</td></tr>
                        <tr><td>Check-in</td><td>' . htmlspecialchars($booking['check_in']) . '</td></tr>
                        <tr><td>Check-out</td><td>' . htmlspecialchars($booking['check_out']) . '</td></tr>
                        <tr><td>Guests</td><td>' . (int)$booking['num_guests'] . '</td></tr>
                        <tr><td>Total Price</td><td>$' . number_format($booking['total_price'], 2) . '</td></tr>
                        <tr><td>Status</td><td>' . ucfirst($booking['status']) . '</td></tr>
                    </table>
                </div>
                
                <p>Please review this booking in the admin panel.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Generate payment receipt HTML email
     */
    private function generatePaymentReceiptHTML($payment) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Payment Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #27ae60; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                .success { color: #27ae60; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Payment Receipt</h2>
            </div>
            <div class="content">
                <h3 class="success">Payment Received!</h3>
                <p>Dear ' . htmlspecialchars($payment['full_name']) . ',</p>
                <p>Thank you for your payment. Here is your receipt:</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($payment['booking_reference']) . '</strong></td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($payment['room_type_name']) . ' (Room ' . htmlspecialchars($payment['room_number']) . ')</td></tr>
                        <tr><td>Dates</td><td>' . htmlspecialchars($payment['check_in']) . ' to ' . htmlspecialchars($payment['check_out']) . '</td></tr>
                        <tr><td>Amount Paid</td><td><strong>$' . number_format($payment['amount'], 2) . '</strong></td></tr>
                        <tr><td>Payment Method</td><td>' . ucfirst(str_replace('_', ' ', $payment['method'])) . '</td></tr>
                        <tr><td>Transaction Ref</td><td>' . htmlspecialchars($payment['transaction_ref']) . '</td></tr>
                        <tr><td>Paid At</td><td>' . htmlspecialchars($payment['paid_at'] ?? 'N/A') . '</td></tr>
                    </table>
                </div>
                
                <p>Please keep this receipt for your records.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Generate cancellation HTML email
     */
    private function generateCancellationHTML($booking) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Booking Cancellation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Booking Cancellation</h2>
            </div>
            <div class="content">
                <h3>Your Booking Has Been Cancelled</h3>
                <p>Dear ' . htmlspecialchars($booking['full_name']) . ',</p>
                <p>Your booking has been cancelled as requested. Here are the details:</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($booking['booking_reference']) . '</strong></td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($booking['room_type_name']) . ' (Room ' . htmlspecialchars($booking['room_number']) . ')</td></tr>
                        <tr><td>Check-in</td><td>' . htmlspecialchars($booking['check_in']) . '</td></tr>
                        <tr><td>Check-out</td><td>' . htmlspecialchars($booking['check_out']) . '</td></tr>
                    </table>
                </div>
                
                <p>If you did not request this cancellation or have any questions, please contact us immediately.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Generate admin cancellation HTML email
     */
    private function generateAdminCancellationHTML($booking) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Booking Cancelled</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Booking Cancelled</h2>
            </div>
            <div class="content">
                <h3>A Booking Has Been Cancelled</h3>
                <p>The following booking has been cancelled:</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($booking['booking_reference']) . '</strong></td></tr>
                        <tr><td>Guest</td><td>' . htmlspecialchars($booking['full_name']) . '</td></tr>
                        <tr><td>Email</td><td>' . htmlspecialchars($booking['email']) . '</td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($booking['room_type_name']) . ' (Room ' . htmlspecialchars($booking['room_number']) . ')</td></tr>
                        <tr><td>Check-in</td><td>' . htmlspecialchars($booking['check_in']) . '</td></tr>
                        <tr><td>Check-out</td><td>' . htmlspecialchars($booking['check_out']) . '</td></tr>
                    </table>
                </div>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Send check-in notification to guest
     */
    public function sendCheckInNotification($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.email, u.full_name, r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        $title = 'Checked In - ' . $booking['booking_reference'];
        $message = "Dear {$booking['full_name']},\n\n"
            . "You have been checked in successfully.\n\n"
            . "Booking Reference: {$booking['booking_reference']}\n"
            . "Room: {$booking['room_type_name']} (Room {$booking['room_number']})\n"
            . "Check-out: {$booking['check_out']}\n\n"
            . "Enjoy your stay!\n";
        
        $htmlBody = $this->generateCheckInHTML($booking);
        
        return $this->notify(
            $booking['user_id'],
            $title,
            $message,
            $booking['email'],
            $title,
            $htmlBody
        );
    }
    
    /**
     * Send check-out notification to guest
     */
    public function sendCheckOutNotification($bookingId) {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.email, u.full_name, r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        $title = 'Checked Out - ' . $booking['booking_reference'];
        $message = "Dear {$booking['full_name']},\n\n"
            . "You have been checked out successfully.\n\n"
            . "Booking Reference: {$booking['booking_reference']}\n"
            . "Room: {$booking['room_type_name']} (Room {$booking['room_number']})\n\n"
            . "Thank you for staying with us. We hope to see you again soon!\n";
        
        $htmlBody = $this->generateCheckOutHTML($booking);
        
        return $this->notify(
            $booking['user_id'],
            $title,
            $message,
            $booking['email'],
            $title,
            $htmlBody
        );
    }
    
    /**
     * Generate check-in HTML email
     */
    private function generateCheckInHTML($booking) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        $checkOutTime = get_setting($this->pdo, 'check_out_time', '11:00');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Checked In</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                .success { color: #3498db; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Welcome!</h2>
            </div>
            <div class="content">
                <h3 class="success">You are now checked in</h3>
                <p>Dear ' . htmlspecialchars($booking['full_name']) . ',</p>
                <p>Welcome to ' . htmlspecialchars($hotelName) . '! You have been checked in successfully.</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($booking['booking_reference']) . '</strong></td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($booking['room_type_name']) . ' (Room ' . htmlspecialchars($booking['room_number']) . ')</td></tr>
                        <tr><td>Check-out</td><td>' . htmlspecialchars($booking['check_out']) . ' until ' . htmlspecialchars($checkOutTime) . '</td></tr>
                    </table>
                </div>
                
                <p>If you need anything, please contact the front desk.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
    
    /**
     * Generate check-out HTML email
     */
    private function generateCheckOutHTML($booking) {
        $hotelName = get_setting($this->pdo, 'hotel_name', 'Hotel Booking System');
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Checked Out</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #95a5a6; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .details table { width: 100%; border-collapse: collapse; }
                .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .details td:first-child { font-weight: bold; width: 40%; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Thank You for Staying</h2>
            </div>
            <div class="content">
                <h3>You have been checked out</h3>
                <p>Dear ' . htmlspecialchars($booking['full_name']) . ',</p>
                <p>Thank you for staying at ' . htmlspecialchars($hotelName) . '. We hope you enjoyed your stay.</p>
                
                <div class="details">
                    <table>
                        <tr><td>Booking Reference</td><td><strong>' . htmlspecialchars($booking['booking_reference']) . '</strong></td></tr>
                        <tr><td>Room</td><td>' . htmlspecialchars($booking['room_type_name']) . ' (Room ' . htmlspecialchars($booking['room_number']) . ')</td></tr>
                    </table>
                </div>
                
                <p>We hope to see you again soon!</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($hotelName) . '. All rights reserved.</p>
            </div>
        </body>
        </html>
        ';
    }
}
