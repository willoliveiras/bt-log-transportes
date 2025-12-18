<?php
// public/api/vehicles.php

// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na resposta

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Log para debug
    error_log("🚗 [VEHICLES API] Iniciando API - Action: " . ($_GET['action'] ?? 'N/A'));
    
    // Definir caminho base - CORRIGIDO
    $rootPath = realpath(dirname(__FILE__) . '/../../app');
    
    if (!$rootPath) {
        throw new Exception("Não foi possível determinar o caminho base");
    }
    
    error_log("📁 [VEHICLES API] Root Path: " . $rootPath);

    // Incluir arquivos com verificação
    $filesToInclude = [
        '/config/config.php',
        '/config/database.php',
        '/core/Database.php',
        '/core/Session.php',
        '/models/VehicleModel.php',
        '/controllers/VehicleController.php'
    ];

    foreach ($filesToInclude as $file) {
        $fullPath = $rootPath . $file;
        if (!file_exists($fullPath)) {
            throw new Exception("Arquivo não encontrado: " . $fullPath);
        }
        require_once $fullPath;
        error_log("✅ [VEHICLES API] Arquivo incluído: " . $file);
    }

    // Obter ação
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada. Use: save, get ou delete');
    }

    error_log("🎯 [VEHICLES API] Executando ação: " . $action);

    $controller = new VehicleController();

    switch ($action) {
        case 'save':
            $controller->save();
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $controller->getVehicle($id);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID do veículo não informado'
                ]);
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $controller->delete($id);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID do veículo não informado'
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    // Log do erro
    error_log("❌ [VEHICLES API] Erro: " . $e->getMessage());
    error_log("❌ [VEHICLES API] Arquivo: " . $e->getFile() . " Linha: " . $e->getLine());
    error_log("❌ [VEHICLES API] Stack trace: " . $e->getTraceAsString());
    
    // Resposta de erro em JSON
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

exit;
?>