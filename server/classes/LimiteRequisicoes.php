<?php
class LimiteRequisicoes {
    private $arquivo;

    public function __construct($arquivo = __DIR__ . '/../limites.json') {
        $this->arquivo = $arquivo;
    }

    public function limitarPorCNPJ($cnpj, $cep, $limiteCeps, $tempo) {
        // Carrega os dados do arquivo JSON
        $data = file_exists($this->arquivo) ? json_decode(file_get_contents($this->arquivo), true) : [];

        // Limpa registros antigos
        foreach ($data as $cnpjSalvo => $info) {
            if (time() - $info['ultima_requisicao'] > $tempo) {
                unset($data[$cnpjSalvo]);
            }
        }

        // Inicializa o registro do CNPJ, caso não exista
        if (!isset($data[$cnpj])) {
            $data[$cnpj] = [
                'ceps' => [],
                'ultima_requisicao' => time()
            ];
        }

        // Verifica se o número de CEPs únicos ultrapassa o limite permitido
        if (count($data[$cnpj]['ceps']) >= $limiteCeps) {
            // Permite a requisição se o CEP já existir no registro
            if (in_array($cep, $data[$cnpj]['ceps'])) {
                return true;
            } else {
                return false;
            }
        }

        // Adiciona o CEP atual ao registro do CNPJ se ele ainda não estiver na lista
        if (!in_array($cep, $data[$cnpj]['ceps'])) {
            $data[$cnpj]['ceps'][] = $cep;
        }

        // Atualiza o tempo da última requisição
        $data[$cnpj]['ultima_requisicao'] = time();

        // Salva os dados atualizados no arquivo JSON
        file_put_contents($this->arquivo, json_encode($data, JSON_PRETTY_PRINT));

        return true;
    }
}
