<?php
// Importar a classe para realizar o teste.
require_once './server/classes/ConsultaPedidoSOAPClient.php';
require_once './server/classes/EnvLoader.php';
require_once './server/classes/Database.php';

// Função de teste para verificar a resposta do SOAP
function testeConsultaPedido($pedido_sap) {
    // Instanciar o cliente SOAP
    $client = new ConsultaPedidoSOAPClient();

    // Definir um número de pedido de teste
    

    // Enviar a requisição para o serviço SOAP
    $resposta = $client->enviarRequisicao($pedido_sap);
    var_dump($resposta['dados_Cliente']['email']);
}



function testeVarDumpEnv() {
    // Caminho do arquivo .env (certifique-se de que o arquivo existe e contém as variáveis para teste)
    $envFilePath = __DIR__ . '/server/.env';

    try {
        // Carregar as variáveis de ambiente do arquivo .env
        EnvLoader::load($envFilePath);

        // Exibir todas as variáveis de ambiente usando var_dump
        echo "Variáveis de ambiente carregadas:\n";
        var_dump($_ENV);

    } catch (Exception $e) {
        echo "Erro ao carregar o arquivo .env: " . $e->getMessage() . PHP_EOL;
    }
}

// Executar




// Executar o teste
testeVarDumpEnv();

$db = new Database();
$listaPedidos = $db->pegarPedidosPorCpfCnpj('29133549000136');
//var_dump($listaPedidos);

foreach ($listaPedidos as $pedido) {
    $pedidoSap = $pedido['NUMPED'];
    testeConsultaPedido($pedidoSap);
    
   
}
