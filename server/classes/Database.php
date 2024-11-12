<?php

/**
 * Class Database
 * Conexão com o banco de dados Oracle e MySQL
 * @package server\classes
 */
require_once 'EnvLoader.php';

class Database {
    private $pdoOracle; // Conexão PDO com Oracle
    private $pdoMySQL; // Conexão PDO com MySQL

    public function __construct() {
        // Carrega as variáveis de ambiente para acessar as configurações dos bancos de dados
        EnvLoader::load(__DIR__ . '/../.env');
        
        // Configurações do banco Oracle
        $hostOracle = getenv('DB_ORACLE_HOST');
        $portOracle = getenv('DB_ORACLE_PORT');
        $serviceNameOracle = getenv('DB_ORACLE_SERVICE_NAME');
        $usernameOracle = getenv('DB_ORACLE_USERNAME');
        $passwordOracle = getenv('DB_ORACLE_PASSWORD');

        // Configurações do banco MySQL
        $hostMySQL = getenv('DB_MYSQL_HOST');
        $portMySQL = getenv('DB_MYSQL_PORT');
        $databaseMySQL = getenv('DB_MYSQL_DATABASE');
        $usernameMySQL = getenv('DB_MYSQL_USERNAME');
        $passwordMySQL = getenv('DB_MYSQL_PASSWORD');

        // String de conexão (DSN) para Oracle
        $dsnOracle = "oci:dbname=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$hostOracle)(PORT=$portOracle)))(CONNECT_DATA=(SERVICE_NAME=$serviceNameOracle)))";
        
        // Conexão Oracle
        try {
            $this->pdoOracle = new PDO($dsnOracle, $usernameOracle, $passwordOracle);
            $this->pdoOracle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Erro na conexão com o Oracle DB: " . $e->getMessage());
        }

        // String de conexão (DSN) para MySQL
        $dsnMySQL = "mysql:host=$hostMySQL;port=$portMySQL;dbname=$databaseMySQL;charset=utf8";
        
        // Conexão MySQL
        try {
            $this->pdoMySQL = new PDO($dsnMySQL, $usernameMySQL, $passwordMySQL);
            $this->pdoMySQL->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Erro na conexão com o MySQL DB: " . $e->getMessage());
        }
    }

    /**
     * Verifica se existem pedidos no banco de dados Oracle para o CNPJ e CEP informados
     * 
     * @param string $cnpj
     * @param string $cep
     * @return int|false Retorna a contagem de pedidos ou false em caso de erro
     */
    public function verificaSeExistemPedidosOracle($cnpj, $cep) {
        // Criação direta da query com valores embutidos (não seguro para produção)
        $sql = "SELECT COUNT(*) FROM FRIOPECA.PCPEDCTEMP WHERE CGCCLI = '$cnpj' AND CEP_ENTREGA = '$cep'";
    
        try {
            // Executa a query diretamente
            $stmt = $this->pdoOracle->query($sql);
    
            // Obtém o resultado e exibe para debug
            $result = $stmt->fetchColumn();
            return $result;

        } catch (PDOException $e) {
            throw new Exception("Erro ao executar a consulta no Oracle: " . $e->getMessage());
        }
    }

   /**
     * Registra uma nova solicitação no banco de dados MySQL
     * 
     * @param string $email Email do solicitante
     * @param string $cnpj CNPJ do solicitante
     * @param string $cep CEP da solicitação
     * @return bool Retorna true em caso de sucesso, ou false em caso de erro
     */
    public function registrarSolicitacao($email, $cnpj, $cep) {
        // Criação direta da query com valores embutidos (não seguro para produção)
        $sql = "INSERT INTO solicitacoes (email, cnpj, data_solicitacao, cep) 
                VALUES ('$email', '$cnpj', CURRENT_TIMESTAMP, '$cep')";
    
        try {
            // Executa a query diretamente
            $this->pdoMySQL->exec($sql);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao registrar a solicitação no MySQL: " . $e->getMessage());
        }
    }
}
