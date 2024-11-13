<?php
require_once 'EnvLoader.php';  // Carregar variáveis de ambiente.

/**
 * Class ConsultaPedidoSOAPClient
 * Envia requisições SOAP para o serviço externo de consulta de pedidos.
 * @package server\classes
 */

class ConsultaPedidoSOAPClient {
    private $soapUrl;
    private $soapAuth;
    private $namespace;
    private $timeout;

    public function __construct() {
        // Carregar configurações do .env
        $this->soapUrl = getenv('SOAP_URL_PEDIDOS');
        $this->soapAuth = getenv('SOAP_AUTH_PEDIDOS');
        $this->namespace = 'http://friopecas.com.br/CAR/ConsultaPedido';
        $this->timeout = getenv('TIMEOUT') ?: 120; // Timeout padrão de 120 segundos
    }

    public function construirPayload($pedido_sap) {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:con=\"{$this->namespace}\">
            <soapenv:Header/>
            <soapenv:Body>
                <con:MT_CAR_Consulta_Pedido_OB>
                    <params>
                        <pedido_sap>$pedido_sap</pedido_sap>
                    </params>
                </con:MT_CAR_Consulta_Pedido_OB>
            </soapenv:Body>
        </soapenv:Envelope>";
    }

    public function enviarRequisicao($pedido_sap) {
        $payload = $this->construirPayload($pedido_sap);

        $headers = [
            'Authorization: ' . $this->soapAuth,
            'Accept: application/xhtml+xml',
            'Accept: text/xml',
            'Accept: application/xml',
            'Content-Type: application/xml'
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $ch = curl_init($this->soapUrl);
            if ($ch === false) return ['error' => 'Falha ao inicializar cURL'];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_SSL_VERIFYHOST => false, // Em produção, mude para 2
                CURLOPT_SSL_VERIFYPEER => false, // Em produção, mude para true
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_errno($ch);

            curl_close($ch);

            if (!$error && $http_code === 200 && !empty($response)) {
                return $this->processarResposta($response);
            }

            if ($attempt < 5) {
                sleep(2); // Espera 2 segundos antes de uma nova tentativa
            }
        }

        return [
            'error' => 'Erro na requisição. Tente novamente.',
            'http_code' => $http_code ?? null,
            'response' => $response ?? null
        ];
    }

    private function processarResposta($response) {
        $xml = simplexml_load_string($response, null, LIBXML_NOCDATA);
        if ($xml === false) return null;
    
        // Registrar o namespace para facilitar a navegação
        $xml->registerXPathNamespace('ns0', $this->namespace);
    
        // Buscar o nó <result> dentro da resposta SOAP
        $resultNode = $xml->xpath('//ns0:MT_CAR_Consulta_Pedido_IB/result');
    
        if (!$resultNode || empty($resultNode[0])) {
            return null;
        }
    
        // Converte o conteúdo do nó <result> para um array associativo
        $resultArray = json_decode(json_encode($resultNode[0]), true);
    
        // Verificar se o array possui a estrutura esperada
        if (isset($resultArray['param']) && isset($resultArray['dados_Cliente']) && isset($resultArray['cabecalho'])) {
            // Retornar o array processado completo
            return $resultArray;
        } else {
            // Retornar um erro se a estrutura não for a esperada
            return ['error' => 'Estrutura inesperada na resposta'];
        }
    }
    
    
}
