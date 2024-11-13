<?php

date_default_timezone_set('America/Recife'); // Define o fuso horário
set_time_limit(300); // Aumenta o limite de execução para 300 segundos

// Variáveis globais para limite de requisições
$limiteCeps = 10; // 10 CEPs por CNPJ
$tempo = 300; // 5 minutos

// Inicializa a sessão para armazenar o código de autenticação, se não existir
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função de autoload para carregar automaticamente as classes
spl_autoload_register(function ($class_name) {
    include __DIR__ . '/classes/' . $class_name . '.php';
});

// Carrega o arquivo .env
EnvLoader::load(__DIR__ . '/.env');

// Função para validar e limpar dados recebidos via POST
function getPostData() {
    $cnpj = Validador::validarCPFouCNPJ($_POST['cnpj'] ?? '');
    $exercicio = filter_var($_POST['exercicio'] ?? '2024', FILTER_VALIDATE_INT);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');

    return [$cnpj, $exercicio, $email, $cep];
}

// Função para verificar limite de requisições por CNPJ
function verificarLimiteRequisicoes($cnpj, $cepResponse, $limiteCeps, $tempo) {
    $limiteRequisicoes = new LimiteRequisicoes();
    if (!$limiteRequisicoes->limitarPorCNPJ($cnpj, $cepResponse, $limiteCeps, $tempo)) {
        http_response_code(429);
        echo json_encode(['error' => 'Você atingiu o limite máximo de CEPs diferentes para este CNPJ.']);
        exit;
    }
}

// Função para verificar existência de pedidos no Oracle e tratar erros
function verificarPedidosOracle($db, $cnpj, $cep) {
    if ($db->verificaSeExistemPedidosOracle($cnpj, $cep) <= 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Nenhum boleto foi encontrado. Para mais informações, entre em contato com o time de pós-vendas no (31) 4007-2565']);
        exit;
    }
}

// Função para registrar solicitação no banco de dados e tratar erros
function registrarSolicitacao($db, $email, $cnpj, $cep) {
    try {
        $db->registrarSolicitacao($email, $cnpj, $cep);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao registrar a solicitação: ' . $e->getMessage()]);
        exit;
    }
}

// Função para consultar e obter resposta de diferentes empresas via SOAP
function consultarEmpresas($soapClient, $cnpj, $exercicio, $empresas = ['MAQL', 'FRIO']) {
    $response = [];
    foreach ($empresas as $empresa) {
        $payload = $soapClient->construirPayload($cnpj, $empresa, $exercicio);
        $resultado = $soapClient->enviarRequisicao($payload);
        $response[$empresa] = $resultado;
        sleep(2);
    }
    return $response;
}

// Função principal para coordenar a execução do código
function main() {
    list($cnpj, $exercicio, $email, $cep) = getPostData();

    $cepResponse = $cep;
    verificarLimiteRequisicoes($cnpj, $cepResponse ?? '', $GLOBALS['limiteCeps'], $GLOBALS['tempo']);

    $db = new Database();
    verificarPedidosOracle($db, $cnpj, $cep);
    registrarSolicitacao($db, $email, $cnpj, $cep);

    $soapClient = new CustomSOAPClient();
    $response = consultarEmpresas($soapClient, $cnpj, $exercicio);

    echo json_encode($response);
}

// Executa o fluxo principal
main();

