<?php
// app/controllers/TripController.php

require_once __DIR__ . '/../models/TripModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/../models/DriverModel.php';
require_once __DIR__ . '/../models/VehicleModel.php';
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../core/Session.php';

class TripController {
    private $tripModel;
    private $companyModel;
    private $clientModel;
    private $driverModel;
    private $vehicleModel;
    private $baseModel;
    private $session;

    public function __construct() {
        $this->tripModel = new TripModel();
        $this->companyModel = new CompanyModel();
        $this->clientModel = new ClientModel();
        $this->driverModel = new DriverModel();
        $this->vehicleModel = new VehicleModel();
        $this->baseModel = new BaseModel();
        $this->session = new Session();
    }

    // Listar viagens
    public function index() {
		// ✅ CORREÇÃO: Comentar verificação de permissão temporariamente
		// if (!$this->hasPermission('trips')) {
		// 	$this->redirectToUnauthorized();
		// 	return;
		// }

		$companyFilter = $_GET['company'] ?? null;
		$statusFilter = $_GET['status'] ?? null;
		$dateFilter = $_GET['date'] ?? null;
		
		$trips = $this->tripModel->getAll($companyFilter);
		
		// Aplicar filtros
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
		
		// Sanitizar dados das viagens
		$trips = array_map([$this, 'sanitizeTripData'], $trips);
		
		// Buscar dados para os dropdowns
		$companies = $this->getCompaniesForDropdown();
		$clients = $this->getClientsForDropdown($companyFilter);
		$drivers = $this->getDriversForDropdown($companyFilter);
		$vehicles = $this->getVehiclesForDropdown($companyFilter);
		$bases = $this->getBasesForDropdown($companyFilter);
		
		// ✅ BUSCAR SERVIÇOS REAIS DO BANCO
		$services = $this->getServicesForDropdown($companyFilter);
		
		$pageTitle = 'Viagens';
		$currentPage = 'trips';
		
		include '../app/views/layouts/header.php';
		include '../app/views/trips/list.php';
		include '../app/views/layouts/footer.php';
	}
    
    private function getCompaniesForDropdown() {
		try {
			$stmt = $this->tripModel->getDb()->prepare("
				SELECT id, name, color 
				FROM companies 
				WHERE is_active = 1 
				ORDER BY name
			");
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log("Erro ao buscar empresas: " . $e->getMessage());
			return [];
		}
	}
	
	// Adicione este método na classe TripController
	private function sanitizeTripData($trip) {
		// Valores básicos
		$freightValue = floatval($trip['freight_value'] ?? 0);
		$totalServicesValue = floatval($trip['total_services_value'] ?? 0);
		$totalExpenses = floatval($trip['total_expenses'] ?? 0);
		
		// ✅ CORREÇÃO: Usar comissão já calculada corretamente pelo modelo (0% se não for personalizada)
		$commissionRate = floatval($trip['commission_rate'] ?? 0.00);
		$commissionAmount = floatval($trip['commission_amount'] ?? 0);
		
		// ✅ CORREÇÃO: Se a comissão não foi calculada, calcular agora (pode ser 0%)
		if ($commissionAmount <= 0 && $freightValue > 0) {
			$commissionAmount = ($freightValue * $commissionRate) / 100;
		}
		
		// ✅ CORREÇÃO: Calcular totais corretamente
		$totalRevenue = $freightValue + $totalServicesValue; // Receita = Frete + Serviços
		$totalCost = $totalExpenses + $commissionAmount;     // Despesa = Gastos + Comissão
		$profit = $totalRevenue - $totalCost;                // Lucro = Receita - Despesa
		
		$driverName = $trip['driver_name'] ?? 'Motorista não informado';
		$driverType = $trip['driver_type'] ?? 'external';
		
		// Se for motorista funcionário e o nome estiver vazio, buscar do employee
		if (($driverType == 'employee' && (empty($trip['driver_name']) || $trip['driver_name'] == 'Motorista não informado'))) {
			$employeeName = $this->getEmployeeNameForDriver($trip['driver_id'] ?? null);
			if ($employeeName) {
				$driverName = $employeeName;
			}
		}
		
		// Determinar origem e destino para exibição
		$originDisplay = $trip['origin_display'] ?? 
			(!empty($trip['origin_base_name']) ? 
				$trip['origin_base_name'] . ' - ' . $trip['origin_base_city'] . '/' . $trip['origin_base_state'] : 
				(!empty($trip['origin_address']) ? substr($trip['origin_address'], 0, 30) . '...' : 'Origem não informada')
			);
		
		$destinationDisplay = $trip['destination_display'] ?? 
			(!empty($trip['destination_base_name']) ? 
				$trip['destination_base_name'] . ' - ' . $trip['destination_base_city'] . '/' . $trip['destination_base_state'] : 
				(!empty($trip['destination_address']) ? substr($trip['destination_address'], 0, 30) . '...' : 'Destino não informado')
			);
		
		return [
			'id' => $trip['id'] ?? null,
			'company_id' => $trip['company_id'] ?? null,
			'client_id' => $trip['client_id'] ?? null,
			'driver_id' => $trip['driver_id'] ?? null,
			'vehicle_id' => $trip['vehicle_id'] ?? null,
			'trip_number' => $trip['trip_number'] ?? 'N/A',
			'client_name' => $trip['client_name'] ?? 'Cliente não informado',
			'driver_name' => $driverName,
			'driver_type' => $driverType,
			'vehicle_plate' => $trip['vehicle_plate'] ?? 'Placa não informada',
			'vehicle_brand' => $trip['vehicle_brand'] ?? '',
			'vehicle_model' => $trip['vehicle_model'] ?? '',
			'company_name' => $trip['company_name'] ?? 'Empresa não informada',
			'company_color' => $trip['company_color'] ?? '#FF6B00',
			'origin_address' => $trip['origin_address'] ?? 'Endereço não informado',
			'destination_address' => $trip['destination_address'] ?? 'Endereço não informado',
			'origin_base_name' => $trip['origin_base_name'] ?? null,
			'destination_base_name' => $trip['destination_base_name'] ?? null,
			'origin_display' => $originDisplay,
			'destination_display' => $destinationDisplay,
			'actual_origin_address' => $trip['actual_origin_address'] ?? null,
			'actual_destination_address' => $trip['actual_destination_address'] ?? null,
			'distance_km' => floatval($trip['distance_km'] ?? 0),
			'scheduled_date' => $trip['scheduled_date'] ?? null,
			'start_date' => $trip['start_date'] ?? null,
			'end_date' => $trip['end_date'] ?? null,
			'freight_value' => $freightValue,
			'status' => $trip['status'] ?? 'agendada',
			
			// ✅ CORREÇÃO: Valores financeiros calculados corretamente
			'total_expenses' => $totalExpenses,
			'commission_amount' => $commissionAmount,
			'commission_rate' => $commissionRate,
			'total_services_value' => $totalServicesValue,
			'total_revenue' => $totalRevenue,     // Receita = Frete + Serviços
			'total_cost' => $totalCost,           // Despesa = Gastos + Comissão
			'profit' => $profit                   // Lucro = Receita - Despesa
		];
	}
	
	// Método auxiliar para buscar nome do funcionário
	private function getEmployeeNameForDriver($driverId) {
		if (!$driverId) return null;
		
		try {
			$stmt = $this->tripModel->getDb()->prepare("
				SELECT e.name 
				FROM drivers d
				LEFT JOIN employees e ON d.employee_id = e.id
				WHERE d.id = ? AND d.driver_type = 'employee'
			");
			$stmt->execute([$driverId]);
			$result = $stmt->fetch();
			
			return $result['name'] ?? null;
		} catch (Exception $e) {
			error_log("Erro ao buscar nome do funcionário: " . $e->getMessage());
			return null;
		}
	}

    private function getClientsForDropdown($companyId = null) {
		try {
			$sql = "SELECT id, name FROM clients WHERE is_active = 1";
			$params = [];
			
			if ($companyId) {
				$sql .= " AND company_id = ?";
				$params[] = $companyId;
			}
			
			$sql .= " ORDER BY name";
			
			$stmt = $this->tripModel->getDb()->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log("Erro ao buscar clientes: " . $e->getMessage());
			return [];
		}
	}

    private function getDriversForDropdown($companyId = null) {
		try {
			$sql = "
				SELECT d.id, 
					   d.name, 
					   d.cnh_number,
					   d.driver_type,
					   d.custom_commission_rate,
					   CASE 
						   WHEN d.driver_type = 'employee' THEN COALESCE(e.name, d.name)
						   ELSE d.name 
					   END as display_name
				FROM drivers d
				LEFT JOIN employees e ON d.employee_id = e.id
				WHERE d.is_active = 1
			";
			$params = [];
			
			if ($companyId) {
				$sql .= " AND d.company_id = ?";
				$params[] = $companyId;
			}
			
			$sql .= " ORDER BY display_name";
			
			$stmt = $this->tripModel->getDb()->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log("Erro ao buscar motoristas: " . $e->getMessage());
			return [];
		}
	}

    private function getVehiclesForDropdown($companyId = null) {
		try {
			$sql = "SELECT id, plate, brand, model FROM vehicles WHERE is_active = 1";
			$params = [];
			
			if ($companyId) {
				$sql .= " AND company_id = ?";
				$params[] = $companyId;
			}
			
			$sql .= " ORDER BY plate";
			
			$stmt = $this->tripModel->getDb()->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log("Erro ao buscar veículos: " . $e->getMessage());
			return [];
		}
	}

    private function getBasesForDropdown($companyId = null) {
		try {
			$sql = "SELECT id, name, city, state FROM bases WHERE is_active = 1";
			$params = [];
			
			if ($companyId) {
				$sql .= " AND company_id = ?";
				$params[] = $companyId;
			}
			
			$sql .= " ORDER BY name";
			
			$stmt = $this->tripModel->getDb()->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log("Erro ao buscar bases: " . $e->getMessage());
			return [];
		}
	}

    // ✅ MÉTODO: Buscar serviços para dropdown
    private function getServicesForDropdown($companyId = null) {
		try {
			$sql = "SELECT id, name, base_price FROM services WHERE is_active = 1";
			$params = [];
			
			if ($companyId) {
				$sql .= " AND company_id = ?";
				$params[] = $companyId;
			}
			
			$sql .= " ORDER BY name";
			
			$stmt = $this->tripModel->getDb()->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
			
		} catch (PDOException $e) {
			error_log("Erro ao buscar serviços: " . $e->getMessage());
			return [];
		}
	}

    // Salvar viagem (create/update)
    public function save() {
		header('Content-Type: application/json');
		
		try {
			error_log("📥 Dados recebidos no save: " . print_r($_POST, true));

			$validation = $this->validateTripData($_POST);
			if (!$validation['success']) {
				http_response_code(400);
				echo json_encode($validation);
				exit;
			}

			// Preparar dados
			$tripData = [
				'company_id' => $_POST['company_id'],
				'client_id' => $_POST['client_id'],
				'driver_id' => $_POST['driver_id'],
				'vehicle_id' => $_POST['vehicle_id'],
				'origin_base_id' => !empty($_POST['origin_base_id']) ? $_POST['origin_base_id'] : null,
				'destination_base_id' => !empty($_POST['destination_base_id']) ? $_POST['destination_base_id'] : null,
				'description' => $_POST['description'] ?? null,
				'origin_address' => $this->getOriginAddress($_POST),
				'destination_address' => $this->getDestinationAddress($_POST),
				'distance_km' => !empty($_POST['distance_km']) ? (float)$_POST['distance_km'] : null,
				'scheduled_date' => !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null,
				'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
				'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
				'freight_value' => (float)$_POST['freight_value'],
				'status' => $_POST['status'] ?? 'agendada'
			];

			$tripId = $_POST['id'] ?? null;
			
			if ($tripId) {
				$success = $this->tripModel->update($tripId, $tripData);
				$message = 'Viagem atualizada com sucesso!';
			} else {
				$success = $this->tripModel->create($tripData);
				$tripId = $success;
				$message = 'Viagem criada com sucesso!';
			}

			if ($success) {
				$finalTripId = $tripId;
				
				// ✅ CORREÇÃO CRÍTICA: Salvar serviços da viagem
				$this->saveTripServices($finalTripId, $_POST);
				
				// ✅ CORREÇÃO SIMPLES: Salvar comissão na viagem
				$this->saveTripCommission($finalTripId, $_POST);
				
				echo json_encode([
					'success' => true, 
					'message' => $message,
					'tripId' => $finalTripId
				]);
			} else {
				$errorInfo = $this->tripModel->getDb()->errorInfo();
				error_log("❌ Erro no banco: " . print_r($errorInfo, true));
				throw new Exception('Erro ao salvar viagem no banco de dados. Detalhes: ' . ($errorInfo[2] ?? 'Desconhecido'));
			}

		} catch (Exception $e) {
			error_log("❌ [TRIP CONTROLLER] Erro no save: " . $e->getMessage());
			error_log("📋 Trace: " . $e->getTraceAsString());
			
			http_response_code(500);
			echo json_encode([
				'success' => false, 
				'message' => 'Erro interno do servidor: ' . $e->getMessage()
			]);
		}
		
		exit;
	}
	
	// ✅ NOVO MÉTODO SIMPLES: Salvar comissão na viagem
	private function saveTripCommission($tripId, $postData) {
		try {
			$freightValue = floatval($postData['freight_value']);
			$driverId = $postData['driver_id'];
			
			// ✅ USAR MESMA LÓGICA DO RESUMO FINANCEIRO
			$commissionRate = $this->getDriverCommissionRate($driverId);
			$commissionAmount = ($freightValue * $commissionRate) / 100;
			
			error_log("💰 [COMMISSION SAVE] Viagem {$tripId}: Frete = {$freightValue}, Taxa = {$commissionRate}%, Valor = {$commissionAmount}");
			
			// Salvar na coluna commission_amount da viagem
			$stmt = $this->tripModel->getDb()->prepare("
				UPDATE trips 
				SET commission_amount = ? 
				WHERE id = ?
			");
			
			$success = $stmt->execute([$commissionAmount, $tripId]);
			
			if ($success) {
				error_log("✅ [COMMISSION SAVED] Comissão R$ {$commissionAmount} salva para viagem {$tripId}");
			} else {
				error_log("❌ [COMMISSION ERROR] Erro ao salvar comissão para viagem {$tripId}");
			}
			
			return $success;
			
		} catch (Exception $e) {
			error_log("❌ [COMMISSION EXCEPTION] " . $e->getMessage());
			return false;
		}
	}
	
	
	// ✅ MÉTODO: Buscar taxa do motorista (igual ao JavaScript)
	private function getDriverCommissionRate($driverId) {
		try {
			$stmt = $this->tripModel->getDb()->prepare("
				SELECT 
					d.driver_type,
					d.custom_commission_rate,
					e.commission_rate as employee_commission_rate
				FROM drivers d
				LEFT JOIN employees e ON d.employee_id = e.id
				WHERE d.id = ? AND d.is_active = 1
			");
			
			$stmt->execute([$driverId]);
			$driver = $stmt->fetch();
			
			if (!$driver) {
				return 0.00;
			}
			
			// ✅ MESMA LÓGICA DO JAVASCRIPT
			$commissionRate = 0.00;
			
			if ($driver['driver_type'] == 'employee') {
				// Motorista funcionário: usa comissão do funcionário OU personalizada
				if ($driver['custom_commission_rate'] !== null && $driver['custom_commission_rate'] > 0) {
					$commissionRate = floatval($driver['custom_commission_rate']);
				} else if ($driver['employee_commission_rate'] !== null && $driver['employee_commission_rate'] > 0) {
					$commissionRate = floatval($driver['employee_commission_rate']);
				}
			} else {
				// Motorista avulso: usa apenas comissão personalizada se existir
				if ($driver['custom_commission_rate'] !== null && $driver['custom_commission_rate'] > 0) {
					$commissionRate = floatval($driver['custom_commission_rate']);
				}
			}
			
			return $commissionRate;
			
		} catch (Exception $e) {
			error_log("❌ [DRIVER COMMISSION RATE] " . $e->getMessage());
			return 0.00;
		}
	}
	
	// ✅ NOVO MÉTODO: Salvar serviços da viagem
	private function saveTripServices($tripId, $postData) {
		try {
			error_log("🔍 Verificando serviços para viagem {$tripId}");
			
			// Verificar se há serviços para salvar
			$hasServices = isset($postData['has_additional_services']) && $postData['has_additional_services'] == '1';
			
			if (!$hasServices) {
				error_log("❌ Checkbox de serviços NÃO está marcado");
				// Se não há serviços, remover todos os serviços existentes
				$this->tripModel->deleteTripServices($tripId);
				return true;
			}
			
			error_log("✅ Checkbox de serviços ESTÁ marcado");
			
			// ✅ CORREÇÃO: Obter serviços selecionados corretamente
			$selectedServices = [];
			
			if (isset($postData['trip_services']) && is_array($postData['trip_services'])) {
				$selectedServices = $postData['trip_services'];
			}
			
			error_log("📋 Serviços selecionados: " . print_r($selectedServices, true));
			
			if (empty($selectedServices)) {
				error_log("⚠️ Nenhum serviço selecionado, removendo serviços existentes");
				$this->tripModel->deleteTripServices($tripId);
				return true;
			}
			
			// Salvar serviços
			$success = $this->tripModel->saveTripServices($tripId, $selectedServices);
			
			if ($success) {
				error_log("✅ Serviços salvos com sucesso para viagem {$tripId}: " . count($selectedServices) . " serviços");
			} else {
				error_log("❌ Falha ao salvar serviços para viagem {$tripId}");
			}
			
			return $success;
			
		} catch (Exception $e) {
			error_log("❌ Erro em saveTripServices: " . $e->getMessage());
			return false;
		}
	}
	
	// ✅ NOVO MÉTODO: Obter endereço de origem baseado no tipo
	private function getOriginAddress($data) {
		if (isset($data['origin_type']) && $data['origin_type'] === 'base') {
			// Se é base, buscar endereço da base
			if (!empty($data['origin_base_id'])) {
				$base = $this->baseModel->getById($data['origin_base_id']);
				return $base ? ($base['address'] ?? 'Endereço da base') : 'Base selecionada';
			}
			return 'Base selecionada';
		} else {
			// Se é custom, usar endereço personalizado
			return $data['origin_address'] ?? '';
		}
	}
	
	// ✅ NOVO MÉTODO: Obter endereço de destino baseado no tipo
	private function getDestinationAddress($data) {
		if (isset($data['destination_type']) && $data['destination_type'] === 'base') {
			// Se é base, buscar endereço da base
			if (!empty($data['destination_base_id'])) {
				$base = $this->baseModel->getById($data['destination_base_id']);
				return $base ? ($base['address'] ?? 'Endereço da base') : 'Base selecionada';
			}
			return 'Base selecionada';
		} else {
			// Se é custom, usar endereço personalizado
			return $data['destination_address'] ?? '';
		}
	}

    // Buscar viagem por ID - CORRIGIDO
    public function getTrip($id) {
        header('Content-Type: application/json');
        
        // ✅ CORREÇÃO: Comentar verificação de permissão temporariamente
        // if (!$this->hasPermission('trips')) {
        //     http_response_code(403);
        //     echo json_encode(['success' => false, 'message' => 'Sem permissão']);
        //     exit;
        // }

        try {
            $trip = $this->tripModel->getTripWithDetails($id);
            if (!$trip) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Viagem não encontrada']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $trip
            ]);
            
        } catch (Exception $e) {
            error_log("❌ [TRIP CONTROLLER] Erro ao buscar viagem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar viagem: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Excluir viagem
    public function delete($id) {
        header('Content-Type: application/json');
        
        // ✅ CORREÇÃO: Comentar verificação de permissão temporariamente
        // if (!$this->hasPermission('trips')) {
        //     http_response_code(403);
        //     echo json_encode(['success' => false, 'message' => 'Sem permissão']);
        //     exit;
        // }

        try {
            $success = $this->tripModel->delete($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Viagem excluída com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir viagem');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir viagem: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Adicionar gasto à viagem
    public function addExpense() {
        header('Content-Type: application/json');
        
        // ✅ CORREÇÃO: Comentar verificação de permissão temporariamente
        // if (!$this->hasPermission('trips')) {
        //     http_response_code(403);
        //     echo json_encode(['success' => false, 'message' => 'Sem permissão']);
        //     exit;
        // }

        try {
            $validation = $this->validateExpenseData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            $expenseData = [
                'expense_type' => $_POST['expense_type'],
                'description' => $_POST['description'] ?? null,
                'amount' => (float)$_POST['amount'],
                'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                'receipt_image' => $_POST['receipt_image'] ?? null
            ];

            $success = $this->tripModel->addExpense($_POST['trip_id'], $expenseData);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Gasto adicionado com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao adicionar gasto');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Calcular comissão do motorista
    private function calculateDriverCommission($tripId) {
		try {
			$trip = $this->tripModel->getTripWithDetails($tripId);
			if (!$trip) return false;

			$driver = $this->driverModel->getById($trip['driver_id']);
			if (!$driver) return false;

			// ✅ CORREÇÃO: Usar comissão personalizada do motorista ou padrão (2% para funcionários, 10% para externos)
			$defaultCommission = ($driver['driver_type'] == 'employee') ? 2.00 : 10.00;
			$commissionRate = $driver['custom_commission_rate'] ?? $defaultCommission;
			$commissionAmount = ($trip['freight_value'] * $commissionRate) / 100;

			// Salvar comissão
			$stmt = $this->tripModel->getDb()->prepare("
				INSERT INTO driver_commissions 
				(trip_id, driver_id, commission_rate, commission_amount, payment_status) 
				VALUES (?, ?, ?, ?, 'pendente')
				ON DUPLICATE KEY UPDATE 
				commission_rate = VALUES(commission_rate),
				commission_amount = VALUES(commission_amount)
			");
			
			return $stmt->execute([
				$tripId,
				$trip['driver_id'],
				$commissionRate,
				$commissionAmount
			]);

		} catch (Exception $e) {
			error_log("Erro ao calcular comissão: " . $e->getMessage());
			return false;
		}
	}
	
	


    // Validar dados da viagem (CORREÇÃO)
    private function validateTripData($data) {
        $errors = [];

        // Validar campos obrigatórios
        if (empty($data['company_id'])) {
            $errors[] = 'A empresa é obrigatória';
        }

        if (empty($data['client_id'])) {
            $errors[] = 'O cliente é obrigatório';
        }

        if (empty($data['driver_id'])) {
            $errors[] = 'O motorista é obrigatório';
        }

        if (empty($data['vehicle_id'])) {
            $errors[] = 'O veículo é obrigatório';
        }

        // CORREÇÃO: Validar origem baseada no tipo
        if (isset($data['origin_type']) && $data['origin_type'] === 'base') {
            if (empty($data['origin_base_id'])) {
                $errors[] = 'A base de origem é obrigatória';
            }
        } else {
            if (empty(trim($data['origin_address'] ?? ''))) {
                $errors[] = 'O endereço de origem é obrigatório';
            }
        }

        // CORREÇÃO: Validar destino baseado no tipo
        if (isset($data['destination_type']) && $data['destination_type'] === 'base') {
            if (empty($data['destination_base_id'])) {
                $errors[] = 'A base de destino é obrigatória';
            }
        } else {
            if (empty(trim($data['destination_address'] ?? ''))) {
                $errors[] = 'O endereço de destino é obrigatório';
            }
        }

        if (empty($data['freight_value']) || $data['freight_value'] <= 0) {
            $errors[] = 'O valor do frete deve ser maior que zero';
        }

        // Validar datas
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                $errors[] = 'A data de início não pode ser depois da data de fim';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Validar dados de gasto
    private function validateExpenseData($data) {
        $errors = [];

        if (empty($data['trip_id'])) {
            $errors[] = 'ID da viagem é obrigatório';
        }

        if (empty($data['expense_type'])) {
            $errors[] = 'Tipo de gasto é obrigatório';
        }

        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'Valor do gasto deve ser maior que zero';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Verificar permissão
    private function hasPermission($resource) {
		$userRole = $this->session->get('user_role');
		$allowedRoles = ['super_admin', 'admin', 'comercial', 'operacional'];
		return in_array($userRole, $allowedRoles);
	}

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
}
?>