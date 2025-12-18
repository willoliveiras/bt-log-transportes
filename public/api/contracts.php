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
            $id = $_POST['id'] ?? null;
            
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

// ✅ Funções independentes para buscar dados

function getClientsForDropdown($db, $companyId = null) {
    try {
        $sql = "SELECT id, name, fantasy_name, cpf_cnpj 
                FROM clients 
                WHERE is_active = 1";
        
        $params = [];
        if ($companyId && $companyId !== 'all') {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $sql .= " ORDER BY COALESCE(fantasy_name, name)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        error_log("✅ [API] Clientes encontrados: " . count($results) . " para empresa: " . $companyId);
        return $results;
        
    } catch (Exception $e) {
        error_log("❌ Erro ao buscar clientes: " . $e->getMessage());
        return [];
    }
}

function getSuppliersForDropdown($db, $companyId = null) {
    try {
        $sql = "SELECT id, name, fantasy_name, cpf_cnpj 
                FROM suppliers 
                WHERE is_active = 1";
        
        $params = [];
        if ($companyId && $companyId !== 'all') {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $sql .= " ORDER BY COALESCE(fantasy_name, name)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        error_log("✅ [API] Fornecedores encontrados: " . count($results) . " para empresa: " . $companyId);
        return $results;
        
    } catch (Exception $e) {
        error_log("❌ Erro ao buscar fornecedores: " . $e->getMessage());
        return [];
    }
}

// ✅ Função de validação
function validateContractData($data) {
    $errors = [];

    // Validar campos obrigatórios
    if (empty(trim($data['company_id'] ?? ''))) {
        $errors[] = 'A empresa é obrigatória';
    }

    if (empty(trim($data['contract_number'] ?? ''))) {
        $errors[] = 'O número do contrato é obrigatório';
    }

    if (empty(trim($data['title'] ?? ''))) {
        $errors[] = 'O título do contrato é obrigatório';
    }

    if (empty($data['contract_type'] ?? '')) {
        $errors[] = 'O tipo de contrato é obrigatório';
    }

    if (empty($data['start_date'] ?? '')) {
        $errors[] = 'A data de início é obrigatória';
    }

    if (empty($data['end_date'] ?? '')) {
        $errors[] = 'A data de término é obrigatória';
    }

    // Validar datas
    if (!empty($data['start_date']) && !empty($data['end_date'])) {
        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        
        if ($startDate > $endDate) {
            $errors[] = 'A data de início não pode ser posterior à data de término';
        }
    }

    // Validar tipo específico
    if ($data['contract_type'] === 'client' && empty($data['client_id'])) {
        $errors[] = 'Selecione um cliente para contrato com cliente';
    }

    if ($data['contract_type'] === 'supplier' && empty($data['supplier_id'])) {
        $errors[] = 'Selecione um fornecedor para contrato com fornecedor';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }

    return ['success' => true];
}

exit;
?>