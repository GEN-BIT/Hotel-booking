<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_mail($pdo, $to, $subject, $htmlBody) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        error_log('PHPMailer not installed — run: composer install');
        return false;
    }
    require_once $autoload;

    $host = get_setting($pdo, 'smtp_host');
    if (!$host) {
        return false; // SMTP not configured yet
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
        return true;
    } catch (Exception $e) {
        error_log('Mail send failed: ' . $mail->ErrorInfo);
        return false;
    }
}
