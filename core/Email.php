<?php
namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    protected $mailer;

    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        string $encryption = 'tls',
        string $fromEmail = null,
        string $fromName = null
    ) {
        $this->mailer = new PHPMailer(true);

        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host       = $host;
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $username;
        $this->mailer->Password   = $password;
        $this->mailer->SMTPSecure = $encryption;
        $this->mailer->Port       = $port;

        $this->mailer->setFrom($fromEmail ?? $username, $fromName ?? 'Mailer');
        $this->mailer->isHTML(true); // Default HTML emails
    }

    /**
     * Send email
     */
    public function send(string $toEmail, string $toName, string $subject, string $body, string $altBody = '')
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            return "Mailer Error: {$this->mailer->ErrorInfo}";
        }
    }
}
