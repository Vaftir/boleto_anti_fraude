<?php

date_default_timezone_set('America/Recife');
set_time_limit(300);

$limiteCeps = 10;
$tempo = 300;

// Certifique-se de que o caminho de sessão está configurado corretamente
$sessionPath = __DIR__ . '/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true); // Cria o diretório, caso não exista
}
ini_set('session.save_path', $sessionPath);
session_start();

// Função de autoload para carregar automaticamente as classes
spl_autoload_register(function ($class_name) {
    include __DIR__ . '/classes/' . $class_name . '.php';
});

EnvLoader::load(__DIR__ . '/.env');

function getPostData() {
    $cnpj = Validador::validarCPFouCNPJ($_POST['cnpj'] ?? '');
    $exercicio = filter_var($_POST['exercicio'] ?? '2024', FILTER_VALIDATE_INT);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');

    return [$cnpj, $exercicio, $email, $cep];
}

function verificarLimiteRequisicoes($cnpj, $cepResponse, $limiteCeps, $tempo) {
    $limiteRequisicoes = new LimiteRequisicoes();
    if (!$limiteRequisicoes->limitarPorCNPJ($cnpj, $cepResponse, $limiteCeps, $tempo)) {
        http_response_code(429);
        echo json_encode(['error' => 'Você atingiu o limite máximo de CEPs diferentes para este CNPJ.']);
        exit;
    }
}

function verificarPedidosOracle($db, $cnpj, $cep) {
    if ($db->verificaSeExistemPedidosOracle($cnpj, $cep) <= 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Nenhum boleto foi encontrado.']);
        exit;
    }
}

function registrarSolicitacao($db, $email, $cnpj, $cep) {
    try {
        $db->registrarSolicitacao($email, $cnpj, $cep);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao registrar a solicitação: ' . $e->getMessage()]);
        exit;
    }
}



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

function main() {
    list($cnpj, $exercicio, $email, $cep) = getPostData();

    

    $cepResponse = $cep;
    verificarLimiteRequisicoes($cnpj, $cepResponse ?? '', $GLOBALS['limiteCeps'], $GLOBALS['tempo']);

    $db = new Database();
    
    //$listaPedidos = $db->pegarPedidosPorCpfCnpj($cnpj);
    //verificarPedidosOracle($db, $cnpj, $cep);

    registrarSolicitacao($db, $email, $cnpj, $cep);

    enviarCodigo2FA($db, $email, $cnpj);

    echo json_encode(['message' => 'Código de verificação enviado para o e-mail fornecido.']);
}

function enviarCodigo2FA($db, $email, $cnpj) {
    $twoFactorAuth = new TwoFactorAuth($db);
    $code = $twoFactorAuth->generateCode( $email, $cnpj);

    

    // Envia o código de verificação por e-mail
    $emailSender = new EmailSender(getenv('EMAIL_HOST'), getenv('EMAIL_USERNAME'), getenv('EMAIL_PASSWORD'));
    if (!$emailSender->send2FACode($email, 'User', $code)) {
        http_response_code(500);
        echo json_encode(['error' => 'Falha ao enviar o código de verificação.']);
        exit;
    }
}

function verificarCodigo2FA($db) {
    $email = $_POST['email'] ?? '';
    $inputCode = $_POST['codigo_2fa'] ?? '';
    $cnpj = Validador::validarCPFouCNPJ($_POST['cnpj'] ?? '');

    

    $twoFactorAuth = new TwoFactorAuth($db);
    $isValid = $twoFactorAuth->verifyCode($cnpj,  $inputCode);

    if (!$isValid) {
        http_response_code(401);
        echo json_encode(['error' => 'Código de verificação inválido.'.$cnpj]);
        exit;
    }


    $soapClient = new CustomSOAPClient();
    list($cnpj, $exercicio, , ) = getPostData();
    $response = consultarEmpresas($soapClient, $cnpj, $exercicio);

    echo json_encode($response);
}


if (isset($_POST['codigo_2fa'])) {
    $db = new Database();
    verificarCodigo2FA($db);
} else {
    main();
}
