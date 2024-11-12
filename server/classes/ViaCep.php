<?php


/**
 * Class ViaCep
 * Verifica a existência de um CEP na API ViaCEP
 * @package server\classes
 * @author Yago Faria
 */

require_once __DIR__ . '/Validador.php';

class ViaCep {
    public static function verificar($cep) {
        // Remove caracteres não numéricos e valida o CEP
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            return [
                'status' => 400,
                'error' => 'CEP inválido. Deve conter 8 dígitos numéricos.'
            ];
        }

        // URL da API ViaCEP
        $url = "viacep.com.br/ws/$cep/json/";

        // Inicializa o cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        // Executa a requisição e verifica a resposta
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Verifica o código de status HTTP
        if ($httpCode == 404 || !$response) {
            return [
                'status' => 404,
                'error' => 'CEP não encontrado ou inválido.'
            ];
        }

        // Decodifica o JSON da resposta
        $data = json_decode($response, true);

        // Verifica se a resposta contém o campo "erro" ou é nula
        if (is_null($data) || isset($data['erro'])) {
            return [
                'status' => 404,
                'error' => 'CEP não existe ou não foi encontrado.'
            ];
        }

        // Retorna o CEP formatado caso seja válido
        return [
            'status' => 200,
            'cep' => $data['cep']
        ];
    }
}
