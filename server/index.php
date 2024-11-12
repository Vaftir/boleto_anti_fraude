<?php


date_default_timezone_set('America/Recife'); // Define o fuso horário
set_time_limit(300); // Aumenta o limite de execução para 300 segundos

// variaveis  globais para limite de requisições
$limiteCeps = 10; // 10 CEPs por CNPJ
$tempo = 300; // 5 minutos

// Função de autoload para carregar automaticamente as classes
spl_autoload_register(function ($class_name) {
    include __DIR__ . '/classes/' . $class_name . '.php';
});

// Carrega o arquivo .env
EnvLoader::load(__DIR__ . '/.env');


// Atribuição de parametros recebidos via POST
$cnpj = Validador::validarCPFouCNPJ($_POST['cnpj'] ?? '');
$exercicio = filter_var($_POST['exercicio'] ?? '2024', FILTER_VALIDATE_INT);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

// Chama o método verificar e passa o CEP recebido via POST
$cepResponse = ViaCep::verificar($_POST['cep'] ?? '');

$limiteRequisicoes = new LimiteRequisicoes();
if (!$limiteRequisicoes->limitarPorCNPJ($cnpj, $cepResponse ?? '',$limiteCeps, $tempo)) {
    http_response_code(429);
    echo json_encode(['error' => 'Você atingiu o limite máximo de CEPs diferentes para este CNPJ.']);
    exit;
}

if ($cepResponse['status'] !== 200) {
    // Se o status não for 200, trata como um erro e retorna a mensagem apropriada
    http_response_code($cepResponse['status']);
    echo json_encode(['error' => $cepResponse['error']]);
    exit;
}

// Caso o status seja 200, extrai o valor do CEP validado
$cep = $cepResponse['cep'];
// remove os caracteres não numéricos do CEP
$cep = preg_replace('/[^0-9]/', '', $cep);


$db = new Database();
// !! ATIVAR PARA PRODUÇÃO
if ($db->verificaSeExistemPedidosOracle($cnpj, $cep) <= 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Não Existem pedidos para este CNPJ e CEP.']);
    exit;
}


// Registra a solicitação no banco de dados MySQL
try {

    $db->registrarSolicitacao($email, $cnpj, $cep);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao registrar a solicitação: ' . $e->getMessage()]);
    exit;
}

$soapClient = new CustomSOAPClient();
$empresas = ['MAQL', 'FRIO'];
$response = [];
foreach ($empresas as $empresa) {
    $payload = $soapClient->construirPayload($cnpj, $empresa, $exercicio);
    $resultado = $soapClient->enviarRequisicao($payload);
    $response[$empresa] = $resultado;
    sleep(2);
    
}

echo json_encode($response);
