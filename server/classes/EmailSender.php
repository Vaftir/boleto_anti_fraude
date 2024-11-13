<?php

require_once 'src/PHPMailer.php';
require_once 'src/SMTP.php';
require_once 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;
    private $host;
    private $username;
    private $password;
    private $port;

    public function __construct($host, $username, $password, $port = 587) {
        $this->mail = new PHPMailer(true);
        $this->host = $host ?? 'smtp.gmail.com';
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->setupSMTP();
    }

    private function setupSMTP() {
        $this->mail->isSMTP();
        $this->mail->Host = $this->host;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->username;
        $this->mail->Password = $this->password;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $this->port;
        $this->mail->setFrom($this->username, 'Your Name');
    }

    public function send2FACode($toEmail, $toName, $code) {
        try {
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Your 2FA Verification Code';
            $this->mail->Body = "Your 2FA code is: <strong>{$code}</strong>";
            $this->mail->AltBody = "Your 2FA code is: {$code}";
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
