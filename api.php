<?php
// api.php - Roteador Principal

// --- CONFIGURAÇÃO INICIAL E SEGURANÇA ---
date_default_timezone_set('America/Recife');
error_reporting(E_ALL);
ini_set('display_errors', 1); // Desativado em produção
ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Defina um caminho se necessário

session_start();

// --- INCLUSÃO DE TODOS OS FICHEIROS NECESSÁRIOS ---
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/core/helpers.php';
require_once __DIR__ . '/api/core/Database.php';
require_once __DIR__ . '/api/handlers/auth_handler.php';
require_once __DIR__ . '/api/handlers/data_handler.php';
require_once __DIR__ . '/api/handlers/opportunity_handler.php';
require_once __DIR__ . '/api/handlers/organization_handler.php';
require_once __DIR__ . '/api/handlers/contact_handler.php';
require_once __DIR__ . '/api/handlers/client_pf_handler.php'; // Inclui o handler onde está a nova função
require_once __DIR__ . '/api/handlers/proposal_handler.php';
require_once __DIR__ . '/api/handlers/user_handler.php';
require_once __DIR__ . '/api/handlers/external_api_handler.php';
require_once __DIR__ . '/api/handlers/agenda_handler.php';
require_once __DIR__ . '/api/handlers/lead_handler.php';
require_once __DIR__ . '/api/handlers/product_handler.php';
require_once __DIR__ . '/api/handlers/email_handler.php';
require_once __DIR__ . '/api/handlers/image_handler.php';


// --- MANIPULADORES DE ERRO GLOBAIS ---
set_error_handler('handle_php_error');
set_exception_handler(function($exception) {
    handle_php_error(
        $exception->getCode(),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
});

header("Content-Type: application/json");

// --- PROCESSAMENTO DA REQUISIÇÃO ---
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$data = [];

// Popula $data com base no método
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $input = file_get_contents('php://input');
    if ($input) {
        $decoded_data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded_data;
        } else {
             // Se não for JSON, pode ser FormData (para uploads)
             // Neste caso, os handlers específicos acessarão $_POST e $_FILES
             // Não é necessário preencher $data aqui para FormData
        }
    }
} elseif ($method === 'GET') {
    $data = $_GET; // Usa os parâmetros GET como dados para handlers GET
}


try {
    $database = new Database();
    $pdo = $database->getConnection();

    // --- Rotas Públicas ---
    if ($action === 'login' && $method === 'POST') {
        handle_login($pdo, $data);
        exit;
    }
    if ($action === 'fetch_cnpj' && $method === 'GET') {
        handle_fetch_cnpj($data['cnpj'] ?? '');
        exit;
    }
    if ($action === 'fetch_cep' && $method === 'GET') {
        handle_fetch_cep($data['cep'] ?? '');
        exit;
    }
     // Rota de upload de imagem para o editor (POST)
    if ($action === 'upload_email_image' && $method === 'POST') {
        // Verifica autenticação ANTES de processar o upload
        if (!isset($_SESSION['user_id'])) {
            json_response(['error' => 'Acesso não autorizado para upload.'], 401);
        }
        handle_upload_email_image(); // Não precisa passar $pdo ou $data, pois usa $_FILES
        exit;
    }


    // --- Rotas Protegidas (Requerem Login) ---
    if (!isset($_SESSION['user_id'])) {
        json_response(['error' => 'Acesso não autorizado.'], 401);
    }

    // Mapeamento de Rotas [action][method] => handler
    $routes = [
        'logout' => ['POST' => 'handle_logout'],
        'get_data' => ['GET' => 'handle_get_data'],
        'get_stats' => ['GET' => 'handle_get_stats'],

        // Vendas Fornecedores
        'create_venda_fornecedor' => ['POST' => 'handle_create_venda_fornecedor'],
        'update_venda_fornecedor' => ['POST' => 'handle_update_venda_fornecedor'], // Poderia ser PUT
        'delete_venda_fornecedor' => ['POST' => 'handle_delete_venda_fornecedor'], // Poderia ser DELETE

        // Oportunidades
        'create_opportunity' => ['POST' => 'handle_create_opportunity'],
        'update_opportunity' => ['POST' => 'handle_update_opportunity'], // Poderia ser PUT
        'delete_opportunity' => ['POST' => 'handle_delete_opportunity'], // Poderia ser DELETE
        'move_opportunity' => ['POST' => 'handle_move_opportunity'],
        'get_opportunity_details' => ['GET' => 'handle_get_opportunity_details'],
        'transfer_opportunity' => ['POST' => 'handle_transfer_opportunity'],

        // Organizações
        'create_organization' => ['POST' => 'handle_create_organization'],
        'update_organization' => ['POST' => 'handle_update_organization'], // Poderia ser PUT
        'get_organization_details' => ['GET' => 'handle_get_organization_details'],

        // Contatos
        'create_contact' => ['POST' => 'handle_create_contact'],
        'update_contact' => ['POST' => 'handle_update_contact'], // Poderia ser PUT
        'get_contact_details' => ['GET' => 'handle_get_contact_details'],

        // Clientes PF
        'create_cliente_pf' => ['POST' => 'handle_create_cliente_pf'],
        'update_cliente_pf' => ['POST' => 'handle_update_cliente_pf'], // Poderia ser PUT
        'get_cliente_pf_details' => ['GET' => 'handle_get_cliente_pf_details'],
        'import_clients' => ['POST' => 'handle_import_clients'], // <-- NOVA ROTA DE IMPORTAÇÃO

        // Propostas
        'create_proposal' => ['POST' => 'handle_create_proposal'],
        'update_proposal' => ['POST' => 'handle_update_proposal'], // Poderia ser PUT
        'delete_proposal' => ['POST' => 'handle_delete_proposal'], // Adicionado DELETE
        'update_proposal_status' => ['POST' => 'handle_update_proposal_status'], // Poderia ser PUT ou PATCH
        'get_proposal_details' => ['GET' => 'handle_get_proposal_details'],
        'upload_image' => ['POST' => 'handle_upload_image'], // Upload para itens da proposta

        // Catálogo de Produtos
        'upload_product_image' => ['POST' => 'handle_upload_product_image'],
        'create_product' => ['POST' => 'handle_create_product'],
        'update_product' => ['POST' => 'handle_update_product'], // Poderia ser PUT
        'delete_product' => ['POST' => 'handle_delete_product'], // Poderia ser DELETE

        // Usuários
        'create_user' => ['POST' => 'handle_create_user'],
        'update_user' => ['POST' => 'handle_update_user'], // Poderia ser PUT
        'delete_user' => ['POST' => 'handle_delete_user'], // Poderia ser DELETE

        // Agenda
        'get_agendamentos' => ['GET' => 'handle_get_agendamentos'],
        'create_agendamento' => ['POST' => 'handle_create_agendamento'],
        'update_agendamento' => ['POST' => 'handle_update_agendamento'], // Poderia ser PUT
        'delete_agendamento' => ['POST' => 'handle_delete_agendamento'], // Adicionado DELETE

        // Leads
        'update_lead_status' => ['POST' => 'handle_update_lead_status'], // Poderia ser PUT ou PATCH
        'update_lead_field' => ['POST' => 'handle_update_lead_field'], // Poderia ser PUT ou PATCH
        'update_lead_fields' => ['POST' => 'handle_update_lead_fields'], // Poderia ser PUT ou PATCH
        'import_leads' => ['POST' => 'handle_import_leads'],

        // Email Marketing
        'send_bulk_email_leads' => ['POST' => 'handle_send_bulk_email_leads'],
        // 'upload_email_image' já definida
    ];

    // Executa o handler correspondente à ação e ao método
    if (isset($routes[$action]) && isset($routes[$action][$method])) {
        $handler_function = $routes[$action][$method];
        if (function_exists($handler_function)) {
             // Passa $pdo e $data para a maioria dos handlers
             // Ajusta if/else se algum handler tiver assinatura diferente
             if (in_array($handler_function, ['handle_logout'])) {
                  $handler_function();
             } elseif (in_array($handler_function, ['handle_get_data', 'handle_get_stats', 'handle_get_agendamentos'])) {
                  $handler_function($pdo);
             } else {
                 $handler_function($pdo, $data);
             }
        } else {
            json_response(['error' => "Handler não implementado: {$handler_function}"], 501);
        }
    } else {
         json_response(['error' => "Ação inválida ({$action}) ou método não permitido ({$method})."], 400);
    }


} catch (PDOException $e) {
    // Log detalhado do erro PDO pode ser útil aqui
    error_log('Erro PDO: ' . $e->getMessage() . "\n" . $e->getTraceAsString()); // Log detalhado
    json_response(['error' => 'Erro de Base de Dados.'], 500); // Mensagem genérica para o cliente
} catch (Exception $e) {
     // Log detalhado do erro geral pode ser útil aqui
     error_log('Erro Geral: ' . $e->getMessage() . "\n" . $e->getTraceAsString()); // Log detalhado
    json_response(['error' => 'Erro Interno do Servidor.'], 500); // Mensagem genérica para o cliente
}

?>
