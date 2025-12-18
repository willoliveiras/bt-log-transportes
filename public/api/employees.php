<?php
// public/api/employees.php - VERSÃO CORRIGIDA
require_once '../../app/config/config.php';
require_once '../../app/config/database.php';
require_once '../../app/core/Database.php';
require_once '../../app/core/Session.php';
require_once '../../app/models/EmployeeModel.php';
require_once '../../app/models/CompanyModel.php';
require_once '../../app/controllers/EmployeeController.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$session = new Session();
if (!$session->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$controller = new EmployeeController();

try {
    switch ($method) {
        case 'GET':
            if ($action === 'companies') {
                try {
                    $companyModel = new CompanyModel();
                    $companies = $companyModel->getForDropdown();
                    
                    if (empty($companies)) {
                        echo json_encode([
                            'success' => true, 
                            'data' => [],
                            'message' => 'Nenhuma empresa encontrada'
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        echo json_encode([
                            'success' => true, 
                            'data' => $companies
                        ], JSON_UNESCAPED_UNICODE);
                    }
                    
                } catch (Exception $e) {
                    error_log("Erro ao buscar empresas: " . $e->getMessage());
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Erro ao carregar empresas: ' . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE);
                }
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $employee = $controller->getEmployee($_GET['id']);
                if ($employee) {
                    echo json_encode(['success' => true, 'data' => $employee], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ação não especificada'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            if ($action === 'create' || $action === 'update') {
                $controller->save();
            } elseif ($action === 'delete') {
                $employeeId = $_POST['id'] ?? null;
                if ($employeeId) {
                    $controller->delete($employeeId);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID do funcionário não fornecido'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ação não especificada'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    error_log("Erro em employees.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>