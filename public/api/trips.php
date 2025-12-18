<?php
// public/api/trips.php

// ✅ CORREÇÃO: Headers no início para garantir resposta JSON
header('Content-Type: application/json');

// ✅ CORREÇÃO: Tratamento de erros global
try {
    // ✅ CORREÇÃO: Incluir arquivos necessários com caminhos absolutos
    $baseDir = __DIR__ . '/../../app/';
    
    require_once $baseDir . 'config/config.php';
    require_once $baseDir . 'config/database.php';
    require_once $baseDir . 'core/Database.php';
    require_once $baseDir . 'core/Session.php';
    require_once $baseDir . 'models/TripModel.php';
    require_once $baseDir . 'models/CompanyModel.php';
    require_once $baseDir . 'models/ClientModel.php';
    require_once $baseDir . 'models/DriverModel.php';
    require_once $baseDir . 'models/VehicleModel.php';
    require_once $baseDir . 'models/BaseModel.php';
    require_once $baseDir . 'controllers/TripController.php';

    // ✅ CORREÇÃO: Iniciar sessão se não estiver iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ✅ CORREÇÃO: Verificar se usuário está logado
    $session = new Session();
    if (!$session->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autorizado. Faça login.']);
        exit;
    }

    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    $controller = new TripController();

    switch ($action) {
        case 'save':
            $controller->save();
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $controller->getTrip($id);
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
            
        case 'add_expense':
            $controller->addExpense();
            break;
            
        case 'stats':
            $companyId = $_GET['company_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $tripModel = new TripModel();
            $stats = $tripModel->getTripStats($companyId, $startDate, $endDate);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'get_services':
            $companyId = $_GET['company_id'] ?? null;
            
            try {
                // ✅ CORREÇÃO: Buscar serviços do banco com tratamento de erro
                $tripModel = new TripModel();
                $services = $tripModel->getServicesForDropdown($companyId);
                
                echo json_encode([
                    'success' => true,
                    'data' => $services
                ]);
                
            } catch (Exception $e) {
                error_log("❌ [TRIPS API] Erro ao buscar serviços: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao buscar serviços: ' . $e->getMessage(),
                    'data' => []
                ]);
            }
            break;
            
        case 'get_driver_commission':
			$driverId = $_GET['driver_id'] ?? null;
			
			if (!$driverId) {
				echo json_encode([
					'success' => false,
					'message' => 'ID do motorista não informado'
				]);
				exit;
			}
			
			try {
				// ✅ CORREÇÃO: Buscar dados completos do motorista
				$tripModel = new TripModel();
				$sql = "
					SELECT 
						d.*,
						e.commission_rate as employee_commission_rate,
						d.custom_commission_rate as driver_custom_commission,
						d.driver_type
					FROM drivers d
					LEFT JOIN employees e ON d.employee_id = e.id
					WHERE d.id = ? AND d.is_active = 1
				";
				
				$stmt = $tripModel->getDb()->prepare($sql);
				$stmt->execute([$driverId]);
				$driver = $stmt->fetch();
				
				if (!$driver) {
					echo json_encode([
						'success' => false,
						'message' => 'Motorista não encontrado',
						'data' => ['commission_rate' => 0.00]
					]);
					exit;
				}
				
				// ✅ CORREÇÃO: Lógica correta de comissão
				$commissionRate = 0.00;
				
				if ($driver['driver_type'] == 'employee') {
					// Motorista funcionário: usa comissão do funcionário OU personalizada
					if ($driver['driver_custom_commission'] !== null && $driver['driver_custom_commission'] > 0) {
						$commissionRate = floatval($driver['driver_custom_commission']);
					} else if ($driver['employee_commission_rate'] !== null && $driver['employee_commission_rate'] > 0) {
						$commissionRate = floatval($driver['employee_commission_rate']);
					}
					// Se não tem comissão definida, fica 0%
				} else {
					// Motorista avulso: usa apenas comissão personalizada se existir
					if ($driver['driver_custom_commission'] !== null && $driver['driver_custom_commission'] > 0) {
						$commissionRate = floatval($driver['driver_custom_commission']);
					}
					// Se não tem comissão personalizada, fica 0%
				}
				
				echo json_encode([
					'success' => true,
					'data' => [
						'commission_rate' => $commissionRate,
						'driver_type' => $driver['driver_type'],
						'driver_name' => $driver['name'],
						'has_custom_commission' => ($driver['driver_custom_commission'] > 0)
					]
				]);
				
			} catch (Exception $e) {
				error_log("❌ [TRIPS API] Erro ao buscar comissão: " . $e->getMessage());
				echo json_encode([
					'success' => false,
					'message' => 'Erro ao buscar comissão: ' . $e->getMessage(),
					'data' => ['commission_rate' => 0.00]
				]);
			}
			break;
			
			
		case 'get_expenses':
			$tripId = $_GET['trip_id'] ?? null;
			
			if (!$tripId) {
				echo json_encode([
					'success' => false,
					'message' => 'ID da viagem não informado'
				]);
				exit;
			}
			
			try {
				$tripModel = new TripModel();
				
				// Buscar despesas do banco
				$stmt = $tripModel->getDb()->prepare("
					SELECT * FROM trip_expenses 
					WHERE trip_id = ? 
					ORDER BY expense_date DESC, created_at DESC
				");
				$stmt->execute([$tripId]);
				$expenses = $stmt->fetchAll();
				
				echo json_encode([
					'success' => true,
					'data' => $expenses
				]);
				
			} catch (Exception $e) {
				error_log("❌ [TRIPS API] Erro ao buscar despesas: " . $e->getMessage());
				echo json_encode([
					'success' => false,
					'message' => 'Erro ao buscar despesas: ' . $e->getMessage(),
					'data' => []
				]);
			}
			break;
			
		case 'delete_expense':
			$expenseId = $_POST['expense_id'] ?? null;
			$tripId = $_POST['trip_id'] ?? null;
			
			if (!$expenseId || !$tripId) {
				echo json_encode([
					'success' => false,
					'message' => 'ID da despesa ou viagem não informado'
				]);
				exit;
			}
			
			try {
				$tripModel = new TripModel();
				
				// Verificar se a despesa pertence à viagem
				$checkStmt = $tripModel->getDb()->prepare("
					SELECT id FROM trip_expenses 
					WHERE id = ? AND trip_id = ?
				");
				$checkStmt->execute([$expenseId, $tripId]);
				$expenseExists = $checkStmt->fetch();
				
				if (!$expenseExists) {
					throw new Exception('Despesa não encontrada para esta viagem');
				}
				
				// Deletar despesa
				$deleteStmt = $tripModel->getDb()->prepare("
					DELETE FROM trip_expenses WHERE id = ?
				");
				$success = $deleteStmt->execute([$expenseId]);
				
				if ($success) {
					echo json_encode([
						'success' => true,
						'message' => 'Despesa excluída com sucesso'
					]);
				} else {
					throw new Exception('Erro ao excluir despesa do banco de dados');
				}
				
			} catch (Exception $e) {
				error_log("❌ [TRIPS API] Erro ao excluir despesa: " . $e->getMessage());
				echo json_encode([
					'success' => false,
					'message' => 'Erro ao excluir despesa: ' . $e->getMessage()
				]);
			}
			break;
					
        case 'list':
            // ✅ NOVA AÇÃO: Listar viagens com filtros
            $companyFilter = $_GET['company'] ?? null;
            $statusFilter = $_GET['status'] ?? null;
            $dateFilter = $_GET['date'] ?? null;
            
            $tripModel = new TripModel();
            $trips = $tripModel->getAll($companyFilter);
            
            // Aplicar filtros adicionais
            if ($statusFilter) {
                $trips = array_filter($trips, function($trip) use ($statusFilter) {
                    return ($trip['status'] ?? 'agendada') === $statusFilter;
                });
            }
            
            if ($dateFilter) {
                $trips = array_filter($trips, function($trip) use ($dateFilter) {
                    $tripDate = !empty($trip['scheduled_date']) ? date('Y-m-d', strtotime($trip['scheduled_date'])) : '';
                    return $tripDate === $dateFilter;
                });
            }
            
            echo json_encode([
                'success' => true,
                'data' => array_values($trips)
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }

} catch (Throwable $e) {
    // ✅ CORREÇÃO: Capturar qualquer tipo de erro (Exception ou Error)
    error_log("❌ [TRIPS API] Erro global: " . $e->getMessage());
    error_log("📋 Stack trace: " . $e->getTraceAsString());
    
    // ✅ CORREÇÃO: Sempre retornar JSON mesmo em caso de erro
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ]
    ]);
}

exit;
?>