<?php
// public/api/contracts.php - VERSÃO CORRIGIDA

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/ContractModel.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se usuário está logado
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Não autorizado'
        ]);
        exit;
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $contractModel = new ContractModel();
    $db = Database::getInstance()->getConnection();

    switch ($action) {
        case 'getAll':
            $companyId = $_GET['company_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $contracts = $contractModel->getAll($companyId, $status);
            
            echo json_encode([
                'success' => true,
                'contracts' => $contracts
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $contract = $contractModel->getById($id);
                if ($contract) {
                    echo json_encode([
                        'success' => true,
                        'data' => $contract
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Contrato não encontrado'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID não informado'
                ]);
            }
            break;
            
        case 'save':
            $validation = validateContractData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                break;
            }

            // Preparar dados
            $contractData = [
                'company_id' => $_POST['company_id'],
                'client_id' => $_POST['client_id'] ?? null,
                'supplier_id' => $_POST['supplier_id'] ?? null,
                'contract_type' => $_POST['contract_type'],
                'contract_number' => trim($_POST['contract_number']),
                'title' => trim($_POST['title']),
                'description' => $_POST['description'] ?? '',
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'value' => $_POST['value'] ?? 0,
                'currency' => $_POST['currency'] ?? 'BRL',
                'payment_terms' => $_POST['payment_terms'] ?? '',
                'renewal_terms' => $_POST['renewal_terms'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'notes' => $_POST['notes'] ?? ''
            ];

            $contractId = $_POST['contract_id'] ?? null;
            $file = isset($_FILES['contract_file']) ? $_FILES['contract_file'] : null;
            
            if ($contractId) {
                // Atualizar contrato existente
                $success = $contractModel->update($contractId, $contractData, $file);
                $message = 'Contrato atualizado com sucesso!';
            } else {
                // Criar novo contrato
                $contractId = $contractModel->create($contractData, $file);
                $success = (bool)$contractId;
                $message = 'Contrato criado com sucesso!';
            }

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'contractId' => $contractId
                ]);
            } else {
                throw new Exception('Erro ao salvar contrato no banco de dados');
            }
            break;
            
        case 'renew':
            $contractId = $_POST['contract_id'] ?? null;
            $newEndDate = $_POST['new_end_date'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            if (!$contractId || !$newEndDate) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Dados incompletos para renovação'
                ]);
                break;
            }

            $success = $contractModel->renew($contractId, $newEndDate, $notes);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Contrato renovado com sucesso!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao renovar contrato'
                ]);
            }
            break;
            
        case 'delete':
            // Aceitar id via POST, GET ou corpo raw (JSON) => torna o endpoint robusto
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                $raw = file_get_contents('php://input');
                if ($raw) {
                    $parsed = json_decode($raw, true);
                    if (isset($parsed['id'])) {
                        $id = $parsed['id'];
                    }
                }
            }
            
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID do contrato não fornecido'
                ]);
                break;
            }

            // Verificar se o contrato existe
            $contract = $contractModel->getById($id);
            if (!$contract) {
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Contrato não encontrado'
                ]);
                break;
            }

            // Soft delete - marcar como cancelado
            // Como ContractModel::update agora aceita dados parciais, podemos passar apenas 'status'
            $success = $contractModel->update($id, ['status' => 'cancelled'], null);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Contrato cancelado com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao cancelar contrato');
            }
            break;

        case 'check_number':
            $number = $_GET['number'] ?? '';
            $companyId = $_GET['company_id'] ?? '';
            $excludeId = $_GET['exclude_id'] ?? null;
            
            if (!$number || !$companyId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Dados incompletos'
                ]);
                break;
            }

            $exists = $contractModel->contractNumberExists($number, $companyId, $excludeId);

            echo json_encode([
                'success' => true,
                'exists' => $exists
            ]);
            break;

        case 'get_stats':
            $companyId = $_GET['company_id'] ?? null;
            $stats = $contractModel->getStats($companyId);

            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'get_clients':
            $companyId = $_GET['company_id'] ?? null;
            $clients = getClientsForDropdown($db, $companyId);
            echo json_encode([
                'success' => true,
                'data' => $clients
            ]);
            break;
            
        case 'get_suppliers':
            $companyId = $_GET['company_id'] ?? null;
            $suppliers = getSuppliersForDropdown($db, $companyId);
            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Exception $e) {
    error_log("❌ [CONTRACTS API] Erro: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// ... (rest of file unchanged)