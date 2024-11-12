<?php
require_once 'EnvLoader.php';  // Para carregar as variáveis de ambiente.
require_once 'Validador.php';   // Para validar CPF/CNPJ.

/**
 * Class CustomSOAPClient
 * Envia requisições SOAP para um serviço externo
 * @package server\classes
 * @author Yago Faria
 */

class CustomSOAPClient  {
    private $soapUrl;
    private $soapAuth;
    private $namespace;
    private $timeout;

    public function __construct() {
        // Carrega configurações do .env
        $this->soapUrl = getenv('SOAP_URL');
        $this->soapAuth = getenv('SOAP_AUTH');
        $this->namespace = 'http://friopecas.com.br/ConsultaBoletosAberto';
        $this->timeout = 120; // 2 minutos de timeout
    }

    public function construirPayload($cnpj, $empresa, $exercicio) {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:con=\"{$this->namespace}\">
            <soapenv:Header/>
            <soapenv:Body>
                <con:MT_ConsultaBoletosAberto_OB>
                    <cnpj>$cnpj</cnpj>
                    <empresa>$empresa</empresa>
                    <exercicio>$exercicio</exercicio>
                </con:MT_ConsultaBoletosAberto_OB>
            </soapenv:Body>
        </soapenv:Envelope>";
    }

    public function enviarRequisicao($payload) {
        $headers = [
            'Accept: application/xml',
            'SOAPAction: ' . $this->soapUrl,
            'Authorization: ' . $this->soapAuth,
            'Content-Type: application/xml'
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) { // Duas tentativas 
            $ch = curl_init($this->soapUrl);
            if ($ch === false) return ['error' => 'Falha ao inicializar cURL'];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_SSL_VERIFYHOST => false, // Em produção, mude para 2
                CURLOPT_SSL_VERIFYPEER => false, // Em produção, mude para true
                CURLOPT_TIMEOUT => $this->timeout, // 2 minutos de timeout
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_errno($ch);

            curl_close($ch);

            // Verifica se houve erro ou se a resposta está vazia
            if (!$error && $http_code === 200 && !empty($response)) {
                return $this->processarResposta($response);
            }

            // Aguarda um momento antes de tentar novamente
            if ($attempt <= 5) {
                sleep(2); // Espera 2 segundos antes da segunda tentativa
            }
        }

        // Caso as duas tentativas falhem
        return [
            'error' => 'Erro na requisição. Tente novamente.',
            'http_code' => $http_code ?? null,
            'response' => $response ?? null
        ];
    }

    private function processarResposta($response) {
        $xml = simplexml_load_string($response, null, LIBXML_NOCDATA);
        if ($xml === false) return null;

        $xml->registerXPathNamespace('con', $this->namespace);
        $base64 = $xml->xpath('//con:MT_ConsultaBoletosAberto_IB/base64');
        return $base64 ? (string) $base64[0] : null;
    }
}
