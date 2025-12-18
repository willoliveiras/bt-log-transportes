<?php
// public/api/bases.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Session.php';
require_once __DIR__ . '/../../app/models/BaseModel.php';
require_once __DIR__ . '/../../app/models/EmployeeModel.php';
require_once __DIR__ . '/../../app/models/VehicleModel.php';

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

    $baseModel = new BaseModel();
    $employeeModel = new EmployeeModel();
    $vehicleModel = new VehicleModel();

    switch ($action) {
        case 'getAll':
			$companyId = $_GET['company_id'] ?? null;
			$includeInactive = isset($_GET['include_inactive']) ? filter_var($_GET['include_inactive'], FILTER_VALIDATE_BOOLEAN) : false;
			
			$bases = $baseModel->getAll($companyId, $includeInactive);
			
			// ✅ DEBUG: Log para verificar dados retornados
			error_log("📊 [BASES API] Bases retornadas: " . count($bases));
			
			echo json_encode([
				'success' => true,
				'bases' => $bases
			]);
			break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $base = $baseModel->getById($id);
                if ($base) {
                    echo json_encode([
                        'success' => true,
                        'data' => $base
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Base não encontrada'
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
			$validation = validateBaseData($_POST);
			if (!$validation['success']) {
				http_response_code(400);
				echo json_encode($validation);
				break;
			}

			// Preparar dados
			$baseData = [
				'company_id' => $_POST['company_id'],
				'name' => trim($_POST['name']),
				'address' => $_POST['address'] ?? null,
				'city' => $_POST['city'] ?? null,
				'state' => $_POST['state'] ?? null,
				'phone' => $_POST['phone'] ?? null,
				'email' => $_POST['email'] ?? null,
				'manager_id' => $_POST['manager_id'] ?? null,
				'opening_date' => $_POST['opening_date'] ?? null,
				'capacity_vehicles' => $_POST['capacity_vehicles'] ? (int)$_POST['capacity_vehicles'] : 0,
				'capacity_drivers' => $_POST['capacity_drivers'] ? (int)$_POST['capacity_drivers'] : 0,
				'operating_hours' => $_POST['operating_hours'] ?? null,
				'latitude' => $_POST['latitude'] ?? null,
				'longitude' => $_POST['longitude'] ?? null,
				'notes' => $_POST['notes'] ?? null,
				'is_active' => isset($_POST['is_active']) ? filter_var($_POST['is_active'], FILTER_VALIDATE_BOOLEAN) : true
			];

			$baseId = $_POST['base_id'] ?? null;
			$success = false;
			
			if ($baseId) {
				// Atualizar base existente
				$success = $baseModel->update($baseId, $baseData);
				$message = 'Base atualizada com sucesso!';
			} else {
				// Criar nova base
				$success = $baseModel->create($baseData);
				$message = 'Base criada com sucesso!';
				$baseId = $baseModel->getLastInsertId();
			}

			// ✅ CORREÇÃO: Vincular funcionários e veículos selecionados
			if ($success && $baseId) {
				// Vincular funcionários
				if (!empty($_POST['selected_employees'])) {
					$employeeIds = json_decode($_POST['selected_employees'], true);
					if (is_array($employeeIds) && !empty($employeeIds)) {
						$baseModel->assignSelectedEmployees($baseId, $employeeIds);
					}
				}
				
				// Vincular veículos
				if (!empty($_POST['selected_vehicles'])) {
					$vehicleIds = json_decode($_POST['selected_vehicles'], true);
					if (is_array($vehicleIds) && !empty($vehicleIds)) {
						$baseModel->assignSelectedVehicles($baseId, $vehicleIds);
					}
				}
			}

			if ($success) {
				echo json_encode([
					'success' => true, 
					'message' => $message,
					'baseId' => $baseId
				]);
			} else {
				throw new Exception('Erro ao salvar base no banco de dados');
			}
			break;
            
        case 'delete':
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID da base não fornecido'
                ]);
                break;
            }

            // Verificar se a base existe
            $base = $baseModel->getById($id);
            if (!$base) {
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Base não encontrada'
                ]);
                break;
            }

            // Soft delete - desativar base
            $success = $baseModel->delete($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Base desativada com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao desativar base');
            }
            break;

        case 'activate':
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID da base não fornecido'
                ]);
                break;
            }

            $success = $baseModel->activate($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Base ativada com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao ativar base');
            }
            break;
            
        case 'available_managers':
            $companyId = $_GET['company_id'] ?? null;
            $managers = $baseModel->getAvailableManagers($companyId);

            echo json_encode([
                'success' => true,
                'data' => $managers
            ]);
            break;

        case 'get_employees':
            $baseId = $_GET['base_id'] ?? null;
            
            if (!$baseId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID da base não fornecido'
                ]);
                break;
            }

            $employees = $baseModel->getBaseEmployees($baseId);
            
            echo json_encode([
                'success' => true,
                'data' => $employees
            ]);
            break;

        case 'get_vehicles':
            $baseId = $_GET['base_id'] ?? null;
            
            if (!$baseId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID da base não fornecido'
                ]);
                break;
            }

            $vehicles = $baseModel->getBaseVehicles($baseId);
            
            echo json_encode([
                'success' => true,
                'data' => $vehicles
            ]);
            break;

        case 'assign_employee':
            $employeeId = $_POST['employee_id'] ?? null;
            $baseId = $_POST['base_id'] ?? null;
            
            if (!$employeeId || !$baseId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Dados incompletos'
                ]);
                break;
            }

            $success = $baseModel->assignEmployeeToBase($employeeId, $baseId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Funcionário vinculado à base com sucesso!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao vincular funcionário'
                ]);
            }
            break;

        case 'assign_vehicle':
            $vehicleId = $_POST['vehicle_id'] ?? null;
            $baseId = $_POST['base_id'] ?? null;
            
            if (!$vehicleId || !$baseId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Dados incompletos'
                ]);
                break;
            }

            $success = $baseModel->assignVehicleToBase($vehicleId, $baseId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Veículo vinculado à base com sucesso!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao vincular veículo'
                ]);
            }
            break;

        case 'remove_assignment':
            $entityType = $_POST['entity_type'] ?? null;
            $entityId = $_POST['entity_id'] ?? null;
            
            if (!$entityType || !$entityId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Dados incompletos'
                ]);
                break;
            }

            $success = false;
            
            if ($entityType === 'employee') {
                $success = $baseModel->removeEmployeeFromBase($entityId);
            } elseif ($entityType === 'vehicle') {
                $success = $baseModel->removeVehicleFromBase($entityId);
            }
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Vínculo removido com sucesso!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao remover vínculo'
                ]);
            }
            break;

        // ✅ NOVOS ENDPOINTS PARA FUNCIONÁRIOS E VEÍCULOS DISPONÍVEIS
        case 'get_available_employees':
            $companyId = $_GET['company_id'] ?? null;
            
            try {
                $employees = $employeeModel->getAvailableForBase($companyId);
                
                echo json_encode([
                    'success' => true,
                    'data' => $employees
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao buscar funcionários: ' . $e->getMessage()
                ]);
            }
            break;

        case 'get_available_vehicles':
            $companyId = $_GET['company_id'] ?? null;
            
            try {
                $vehicles = $vehicleModel->getAvailableForBase($companyId);
                
                echo json_encode([
                    'success' => true,
                    'data' => $vehicles
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao buscar veículos: ' . $e->getMessage()
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
    error_log("❌ [BASES API] Erro: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// ✅ Função de validação
function validateBaseData($data) {
    $errors = [];

    // Validar campos obrigatórios
    if (empty(trim($data['name'] ?? ''))) {
        $errors[] = 'O nome da base é obrigatório';
    }

    if (empty($data['company_id'] ?? '')) {
        $errors[] = 'A empresa é obrigatória';
    }

    // Validar capacidade
    if (isset($data['capacity_vehicles']) && $data['capacity_vehicles'] < 0) {
        $errors[] = 'A capacidade de veículos não pode ser negativa';
    }

    if (isset($data['capacity_drivers']) && $data['capacity_drivers'] < 0) {
        $errors[] = 'A capacidade de motoristas não pode ser negativa';
    }

    // Validar email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'O email informado é inválido';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }

    return ['success' => true];
}

exit;
?>