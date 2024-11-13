<?php

class Database {
    private $pdoOracle;
    private $pdoMySQL;

    public function __construct() {
        EnvLoader::load(__DIR__ . '/../.env');
        
        // Configuração e conexão com o Oracle
        $dsnOracle = "oci:dbname=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=".getenv('DB_ORACLE_HOST').")(PORT=".getenv('DB_ORACLE_PORT').")))(CONNECT_DATA=(SERVICE_NAME=".getenv('DB_ORACLE_SERVICE_NAME').")))";
        try {
            $this->pdoOracle = new PDO($dsnOracle, getenv('DB_ORACLE_USERNAME'), getenv('DB_ORACLE_PASSWORD'));
            $this->pdoOracle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Erro na conexão com o Oracle DB: " . $e->getMessage());
        }

        // Configuração e conexão com o MySQL
        $dsnMySQL = "mysql:host=".getenv('DB_MYSQL_HOST').";port=".getenv('DB_MYSQL_PORT').";dbname=".getenv('DB_MYSQL_DATABASE').";charset=utf8";
        try {
            $this->pdoMySQL = new PDO($dsnMySQL, getenv('DB_MYSQL_USERNAME'), getenv('DB_MYSQL_PASSWORD'));
            $this->pdoMySQL->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Erro na conexão com o MySQL DB: " . $e->getMessage());
        }
    }

    public function verificaSeExistemPedidosOracle($cnpj, $cep) {
        $sql = "SELECT COUNT(*) FROM FRIOPECA.PCPEDCTEMP WHERE CGCCLI = :cnpj AND CEP_ENTREGA = :cep";
        $stmt = $this->pdoOracle->prepare($sql);
        $stmt->execute([':cnpj' => $cnpj, ':cep' => $cep]);
        return $stmt->fetchColumn();
    }

    public function pegarPedidosPorCpfCnpj($cnpj) {
        $sql = "SELECT NUMPED FROM FRIOPECA.PCPEDCTEMP WHERE CGCCLI = :cnpj";
        $stmt = $this->pdoOracle->prepare($sql);
        $stmt->execute([':cnpj' => $cnpj]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarSolicitacao($email, $cnpj, $cep) {
        $sql = "INSERT INTO solicitacoes (email, cnpj, data_solicitacao, cep) VALUES (:email, :cnpj, CURRENT_TIMESTAMP, :cep)";
        $stmt = $this->pdoMySQL->prepare($sql);
        return $stmt->execute([':email' => $email, ':cnpj' => $cnpj, ':cep' => $cep]);
    }

    // Método para salvar o código de 2FA no banco de dados MySQL
    public function salvarCodigo2FA($email, $cnpj, $code, $expiryTime) {
        $sql = "INSERT INTO two_factor_auth (email, cnpj, code, expiry_time) VALUES (:email, :cnpj, :code, :expiry_time)";
        $stmt = $this->pdoMySQL->prepare($sql);
        return $stmt->execute([':email' => $email, ':cnpj' => $cnpj, ':code' => $code, ':expiry_time' => $expiryTime]);
    }

    // Método para verificar o código de 2FA com base no email
    public function verificarCodigo2FA($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj); // Normaliza o CNPJ para remover caracteres não numéricos
    
        $sql = "SELECT code, expiry_time FROM two_factor_auth WHERE cnpj = :cnpj ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdoMySQL->prepare($sql);
        $stmt->execute([':cnpj' => $cnpj]);
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            error_log("Nenhum resultado encontrado para CNPJ: " . $cnpj);
        } else {
            error_log("Resultado encontrado: " . print_r($result, true));
        }
    
        return $result; // Retorna o resultado ou false
    }

    // Método para deletar o código de 2FA após verificação
    public function deletarCodigo2FA($email) {
        $sql = "DELETE FROM two_factor_auth WHERE email = :email";
        $stmt = $this->pdoMySQL->prepare($sql);
        return $stmt->execute([':email' => $email]);
    }
}
