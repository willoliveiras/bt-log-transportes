<?php
// public/api/financial.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/FinancialModel.php';
require_once __DIR__ . '/../../app/controllers/FinancialController.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new FinancialController();

    switch ($action) {
        case 'dashboard_data':
            $controller->getDashboardData();
            break;
            
        case 'alerts':
            $companyId = $_GET['company'] ?? null;
            $financialModel = new FinancialModel();
            $alerts = $financialModel->getFinancialAlerts($companyId);
            
            echo json_encode([
                'success' => true,
                'data' => $alerts
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [FINANCIAL API] Erro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

exit;
?>