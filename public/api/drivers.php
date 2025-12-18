<?php
// public/api/drivers.php - VERSÃO SIMPLIFICADA E FUNCIONAL

// ✅ CORREÇÃO: Headers PRIMEIRO
header('Content-Type: application/json');

// ✅ CORREÇÃO: Incluir arquivos necessários
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/DriverModel.php';
require_once __DIR__ . '/../../app/controllers/DriverController.php';

try {
    // ✅ CORREÇÃO: Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new DriverController();

    switch ($action) {
        case 'save':
            $controller->save();
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $driver = $controller->getDriver($id);
                echo json_encode([
                    'success' => true, 
                    'data' => $driver
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID não informado'
                ]);
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $controller->delete($id);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID não informado'
                ]);
            }
            break;
            
        case 'available_employees':
            // ✅ CORREÇÃO: Chamar método do controller
            $controller->getAvailableEmployees();
            break;
            
        case 'get_employee_data':
            // ✅ NOVA AÇÃO: Buscar dados do funcionário
            $controller->getEmployeeData();
            break;
            
        case 'debug':
            // ✅ AÇÃO DEBUG
            $controller->debug();
            break;
            
        case 'fix_company_ids':
            // ✅ AÇÃO CORREÇÃO
            $controller->fixCompanyIds();
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [DRIVERS API] Erro: " . $e->getMessage());
    
    // ✅ CORREÇÃO: Sempre retornar JSON, mesmo em erro
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// ✅ CORREÇÃO: Garantir que não há output extra
exit;
?>