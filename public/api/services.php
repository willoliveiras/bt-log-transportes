<?php
// public/api/services.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/ServiceModel.php';
require_once __DIR__ . '/../../app/controllers/ServiceController.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new ServiceController();

    switch ($action) {
        case 'save':
            $controller->save();
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                // Buscar serviço individual
                $serviceModel = new ServiceModel();
                $service = $serviceModel->getById($id);
                
                if ($service) {
                    echo json_encode([
                        'success' => true,
                        'data' => $service
                    ]);
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Serviço não encontrado'
                    ]);
                }
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
                $serviceModel = new ServiceModel();
                $success = $serviceModel->delete($id);
                
                if ($success) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Serviço excluído com sucesso!'
                    ]);
                } else {
                    throw new Exception('Erro ao excluir serviço');
                }
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID não informado'
                ]);
            }
            break;
            
        case 'init_defaults':
            $controller->initializeDefaults();
            break;
            
        case 'get_by_company':
            $companyId = $_GET['company_id'] ?? null;
            if ($companyId) {
                $serviceModel = new ServiceModel();
                $services = $serviceModel->getByCompany($companyId);
                
                echo json_encode([
                    'success' => true,
                    'data' => $services
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Empresa não especificada'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [SERVICES API] Erro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

exit;
?>