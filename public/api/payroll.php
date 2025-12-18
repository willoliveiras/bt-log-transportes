<?php
// public/api/payroll.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/PayrollModel.php';
require_once __DIR__ . '/../../app/controllers/PayrollController.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new PayrollController();

    switch ($action) {
        case 'generate':
            $controller->generate();
            break;
            
        case 'get_details':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $controller->getDetails();
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID não informado'
                ]);
            }
            break;
            
        case 'mark_paid':
            $controller->markAsPaid();
            break;
            
        case 'reverse_payment':
            $controller->reversePayment();
            break;
            
        case 'delete_payroll':
            $controller->deletePayroll();
            break;
            
        case 'recalculate_commissions':
            $controller->recalculateCommissions();
            break;
            
        case 'export':
            $controller->export();
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [PAYROLL API] Erro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

exit;
?>