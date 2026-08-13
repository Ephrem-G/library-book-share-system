<?php

require_once __DIR__ . '/../config/app.php';

class EmailService
{
    public static function sendVerificationEmail(string $email, string $name, string $token): void
    {
        $verifyUrl = base_url() . '/api/auth/verify-email?token=' . urlencode($token);
        $subject = 'Verify your Library Book Share account';
        $body = self::renderTemplate($name, $verifyUrl);
        $mailConfig = require __DIR__ . '/../config/mail.php';

        // For local demos, leave MAIL_ENABLED=false and the link is written to uploads/mail-log.html.
        if (!$mailConfig['enabled']) {
            self::writeLocalMailLog($email, $subject, $body);
            return;
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
        $mail->Port = $mailConfig['port'];

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = 'Verify your account: ' . $verifyUrl;
        $mail->send();
    }

    private static function renderTemplate(string $name, string $verifyUrl): string
    {
        ob_start();
        require __DIR__ . '/../templates/emails/verify-email.php';
        return ob_get_clean();
    }

    private static function writeLocalMailLog(string $email, string $subject, string $body): void
    {
        $path = __DIR__ . '/../uploads/mail-log.html';
        $entry = '<hr><p><strong>To:</strong> ' . htmlspecialchars($email) . '</p>';
        $entry .= '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>';
        $entry .= $body;
        file_put_contents($path, $entry, FILE_APPEND);
    }
}

