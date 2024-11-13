<?php

//session_start();

class TwoFactorAuth {
    private $code;
    private $expiryTime;

    public function __construct($expiryMinutes = 5) {
        $this->expiryTime = time() + ($expiryMinutes * 60);
    }

    public function generateCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $this->code = substr(str_shuffle($characters), 0, 5);
        
        // Store code and expiry in the session
        $_SESSION['2fa_code'] = $this->code;
        $_SESSION['2fa_expiry'] = $this->expiryTime;
        
        return $this->code;
    }

    public function verifyCode($inputCode) {

        echo '<script>console.log(' . json_encode($_SESSION) . ');</script>';
        if (isset($_SESSION['2fa_code']) && isset($_SESSION['2fa_expiry'])) {
            $isCodeValid = $_SESSION['2fa_code'] === strtoupper($inputCode);
            $isCodeExpired = time() > $_SESSION['2fa_expiry'];
            
            // Invalidate session data after check
            unset($_SESSION['2fa_code'], $_SESSION['2fa_expiry']);

            return $isCodeValid && !$isCodeExpired;
        }
        return false;
    }
}

