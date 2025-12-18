<?php
// public/api/maintenance.php - API COMPLETA DE MANUTENÇÕES

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/MaintenanceModel.php';
require_once __DIR__ . '/../../app/controllers/MaintenanceController.php';

try {
    // ✅ CORREÇÃO: Headers PRIMEIRO
    header('Content-Type: application/json');

    // ✅ CORREÇÃO: Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new MaintenanceController();

    switch ($action) {
        case 'save':
            $controller->save();
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $controller->getMaintenance($id);
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
            
        case 'upcoming':
            $controller->getUpcoming();
            break;
            
        case 'stats':
            $companyId = $_GET['company_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $maintenanceModel = new MaintenanceModel();
            $stats = $maintenanceModel->getMaintenanceStats($companyId, $startDate, $endDate);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'types':
            $maintenanceModel = new MaintenanceModel();
            $types = $maintenanceModel->getMaintenanceTypes();
            $services = $maintenanceModel->getCommonServices();
            $intervals = $maintenanceModel->getDefaultIntervals();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'types' => $types,
                    'services' => $services,
                    'intervals' => $intervals
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [MAINTENANCE API] Erro: " . $e->getMessage());
    
    // ✅ CORREÇÃO: Sempre retornar JSON, mesmo em erro
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// ✅ CORREÇÃO: Garantir que não há output extra
exit;
?>