<?php
// public/api/companies.php
require_once '../../app/config/config.php';
require_once '../../app/config/database.php';
require_once '../../app/core/Database.php';
require_once '../../app/core/Session.php';
require_once '../../app/controllers/CompanyController.php';

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$session = new Session();

// Verificar autenticação
if (!$session->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Determinar a ação
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

$controller = new CompanyController();

try {
    switch ($method) {
        case 'GET':
            // SUPORTE A DOIS FORMATOS:
            // 1. Formato novo: action=get&id=1
            // 2. Formato antigo: action=1 (para compatibilidade)
            
            if ($action === 'get' && $id) {
                // Formato novo: action=get&id=1
                $company = $controller->getCompany($id);
                if ($company) {
                    echo json_encode([
                        'success' => true, 
                        'data' => $company
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Empresa não encontrada'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } elseif (is_numeric($action)) {
                // Formato antigo: action=1 (para compatibilidade)
                $companyId = $action;
                $company = $controller->getCompany($companyId);
                if ($company) {
                    echo json_encode([
                        'success' => true, 
                        'data' => $company
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Empresa não encontrada'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Ação ou ID não especificado'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                // Criar nova empresa
                $controller->save();
            } elseif ($action === 'update') {
                // Atualizar empresa existente
                $companyId = $_POST['id'] ?? null;
                if ($companyId) {
                    $controller->update($companyId);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'ID da empresa não fornecido'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } elseif ($action === 'delete') {
                // Excluir empresa
                $companyId = $_POST['id'] ?? null;
                if ($companyId) {
                    $controller->delete($companyId);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'ID da empresa não fornecido'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Ação não especificada'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Método não permitido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    error_log("Erro na API companies: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor'
    ], JSON_UNESCAPED_UNICODE);
}
?>