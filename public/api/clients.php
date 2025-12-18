<?php
// public/api/clients.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/ClientModel.php';
require_once __DIR__ . '/../../app/controllers/ClientController.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new ClientController();

    switch ($action) {
        case 'save':
            $controller->save();
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $controller->getClient($id);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID não informado'
                ]);
            }
            break;
            
        case 'get_partner_companies':
            $controller->getPartnerCompanies();
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
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [CLIENTS API] Erro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

exit;
?>