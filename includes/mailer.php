<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @deprecated Use NotificationService instead. This function is kept for backward compatibility.
 */
function send_mail($pdo, $to, $subject, $htmlBody) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        error_log('PHPMailer not installed — run: composer install');
        return false;
    }
    
    require_once $autoload;

    $host = get_setting($pdo, 'smtp_host');
    if (!$host) {
        error_log('SMTP not configured — cannot send email to: ' . $to);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = get_setting($pdo, 'smtp_username');
        $mail->Password = get_setting($pdo, 'smtp_password');
        $mail->SMTPSecure = 'tls';
        $mail->Port = (int)get_setting($pdo, 'smtp_port', 587);

        $fromEmail = get_setting($pdo, 'from_email') ?: 'no-reply@hotel-booking.local';
        $fromName  = get_setting($pdo, 'from_name') ?: 'Hotel Booking System';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        error_log("Email sent successfully to: $to, subject: $subject");
        return true;
    } catch (Exception $e) {
        error_log("Mail send failed to: $to, subject: $subject, error: " . $mail->ErrorInfo);
        return false;
    }
}
