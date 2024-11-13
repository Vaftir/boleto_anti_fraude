<?php

class TwoFactorAuth {
    private $db;
    private $code;
    private $expiryTime;

    public function __construct($db, $expiryMinutes = 5) {
        $this->db = $db;
        $this->expiryTime = time() + ($expiryMinutes * 60); // Expira em X minutos
    }

    public function generateCode($email, $cnpj = null) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $this->code = substr(str_shuffle($characters), 0, 5);

        // Salva o código no banco de dados com o email e CNPJ
        $stmt = $this->db->salvarCodigo2FA($email, $cnpj, $this->code, $this->expiryTime);

        if (!$stmt) {
            throw new Exception("Erro ao salvar o código de 2FA no banco de dados.");
        }

        return $this->code; // Retorna o código gerado para envio
    }

    public function verifyCode($email, $inputCode) {
        // Remove espaços e converte o código em maiúsculas para garantir consistência
        $inputCode = strtoupper(trim($inputCode));

        // Obtém o código salvo no banco e sua expiração
        $validation = $this->db->verificarCodigo2FA($email);

        if (!$validation) {
            echo json_encode(['error' => 'Código de verificação inválido.']);
            return false; // Retorna falso se não houver código salvo
        }

        // Compara o código fornecido com o salvo no banco
        $expiryTime = (int) $validation['expiry_time'];

        if($this->expiryTime <=  $expiryTime){
            echo json_encode(['error' => 'Código de verificação 1232131212321.']);
        }


        

           

        if (strtoupper($validation['code']) === $inputCode) {
            // Código válido; exclui o código após validação
            $this->db->deletarCodigo2FA($email);
            return true;
        }

        return false; // Retorna falso se o código for inválido ou expirado
    }
}
