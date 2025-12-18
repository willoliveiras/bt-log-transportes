<?php
require_once __DIR__ . '/../models/VehicleModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class VehicleController {
    private $vehicleModel;
    private $companyModel;
    private $session;

    public function __construct() {
        $this->vehicleModel = new VehicleModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    // Listar veículos
    public function index() {
        if (!$this->hasPermission('vehicles')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $typeFilter = $_GET['type'] ?? null;
        $statusFilter = $_GET['status'] ?? null;
        
        $vehicles = $this->vehicleModel->getAll($companyFilter);
        
        // Aplicar filtros
        if ($typeFilter) {
            $vehicles = array_filter($vehicles, function($vehicle) use ($typeFilter) {
                return $vehicle['type'] === $typeFilter;
            });
        }
        
        if ($statusFilter) {
            $vehicles = array_filter($vehicles, function($vehicle) use ($statusFilter) {
                return $vehicle['status'] === $statusFilter;
            });
        }
        
        $companies = $this->companyModel->getForDropdown();
        $vehicleTypes = $this->vehicleModel->getVehicleTypes();
        $fuelTypes = $this->vehicleModel->getFuelTypes();
        $capacityUnits = $this->vehicleModel->getCapacityUnits();
        $statusTypes = $this->vehicleModel->getStatusTypes();
        
        $pageTitle = 'Veículos';
        $currentPage = 'vehicles';
        
        include '../app/views/layouts/header.php';
        include '../app/views/vehicles/list.php';
        include '../app/views/layouts/footer.php';
    }

    // Salvar veículo (create/update)
    public function save() {
        // Configurar headers primeiro
        header('Content-Type: application/json');
        
        // Log para debug
        error_log("📥 [VEHICLES CONTROLLER] Iniciando save...");
        error_log("📥 [VEHICLES CONTROLLER] POST data: " . print_r($_POST, true));

        try {
            // Verificar se é POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido. Use POST.');
            }

            if (!$this->hasPermission('vehicles')) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Sem permissão para acessar este recurso'
                ]);
                exit;
            }

            // Validar dados obrigatórios básicos
            $required = ['plate', 'brand', 'model', 'year', 'type', 'fuel_type', 'company_id'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => "Campo obrigatório não informado: {$field}"
                    ]);
                    exit;
                }
            }

            $validation = $this->validateVehicleData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            // Preparar dados
            $vehicleData = [
                'company_id' => $_POST['company_id'],
                'plate' => strtoupper(str_replace(['-', ' '], '', $_POST['plate'])),
                'brand' => $_POST['brand'],
                'model' => $_POST['model'],
                'year' => $_POST['year'],
                'color' => $_POST['color'] ?? '',
                'chassis_number' => $_POST['chassis_number'] ?? null,
                'type' => $_POST['type'],
                'vehicle_subtype' => $_POST['vehicle_subtype'] ?? null,
                'capacity' => !empty($_POST['capacity']) ? (float)$_POST['capacity'] : null,
                'capacity_unit' => $_POST['capacity_unit'] ?? 'kg',
                'fuel_type' => $_POST['fuel_type'],
                'fuel_capacity' => !empty($_POST['fuel_capacity']) ? (float)$_POST['fuel_capacity'] : null,
                'average_consumption' => !empty($_POST['average_consumption']) ? (float)$_POST['average_consumption'] : null,
                'insurance_company' => $_POST['insurance_company'] ?? null,
                'insurance_number' => $_POST['insurance_number'] ?? null,
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'registration_number' => $_POST['registration_number'] ?? null,
                'registration_expiry' => $_POST['registration_expiry'] ?? null,
                'current_km' => !empty($_POST['current_km']) ? (float)$_POST['current_km'] : 0,
                'status' => $_POST['status'] ?? 'disponivel',
                'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'notes' => $_POST['notes'] ?? null
            ];

            $vehicleId = $_POST['id'] ?? null;
            
            error_log("💾 [VEHICLES CONTROLLER] Salvando veículo ID: " . $vehicleId);
            error_log("💾 [VEHICLES CONTROLLER] Dados preparados: " . print_r($vehicleData, true));
            
            $success = false;
            $message = '';
            
            if ($vehicleId) {
                $success = $this->vehicleModel->update($vehicleId, $vehicleData);
                $message = 'Veículo atualizado com sucesso!';
            } else {
                $success = $this->vehicleModel->create($vehicleData);
                $message = 'Veículo criado com sucesso!';
                if ($success) {
                    $vehicleId = $this->vehicleModel->getLastInsertId();
                }
            }

            if ($success) {
                error_log("✅ [VEHICLES CONTROLLER] Veículo salvo com sucesso: " . $vehicleId);
                
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'vehicleId' => $vehicleId,
                    'data' => $vehicleData
                ]);
            } else {
                throw new Exception('Erro ao salvar veículo no banco de dados');
            }

        } catch (Exception $e) {
            error_log("❌ [VEHICLES CONTROLLER] Erro: " . $e->getMessage());
            error_log("❌ [VEHICLES CONTROLLER] Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }
	
	
	private function getVehicleIcon($vehicleType) {
		$icons = [
			'carro' => 'car',
			'motocicleta' => 'motorcycle',
			'caminhonete' => 'truck-pickup',
			'pickup' => 'truck-pickup',
			'van' => 'van',
			'minivan' => 'van',
			'onibus' => 'bus',
			'microonibus' => 'bus',
			'caminhao' => 'truck',
			'caminhao_toco' => 'truck',
			'caminhao_truck' => 'truck',
			'caminhao_carreta' => 'trailer',
			'caminhao_bitrem' => 'trailer',
			'caminhao_rodotrem' => 'trailer',
			'utilitario' => 'truck',
			'suv' => 'car',
			'hatch' => 'car',
			'sedan' => 'car',
			'hatchback' => 'car',
			'outros' => 'truck'
		];
		
		return $icons[$vehicleType] ?? 'truck';
	}

    // Buscar veículo por ID
    public function getVehicle($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('vehicles')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $vehicle = $this->vehicleModel->getById($id);
            if (!$vehicle) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Veículo não encontrado']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $vehicle
            ]);
        } catch (Exception $e) {
            error_log("❌ [VEHICLES CONTROLLER] Erro ao buscar veículo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Excluir veículo
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('vehicles')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            // Verificar se o veículo existe
            $vehicle = $this->vehicleModel->getById($id);
            if (!$vehicle) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Veículo não encontrado']);
                exit;
            }

            $success = $this->vehicleModel->delete($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Veículo excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir veículo do banco de dados');
            }
        } catch (Exception $e) {
            error_log("❌ [VEHICLES CONTROLLER] Erro ao excluir: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao excluir veículo: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Validar dados do veículo
    private function validateVehicleData($data) {
        $errors = [];

        // Validar campos obrigatórios
        if (empty(trim($data['plate'] ?? ''))) {
            $errors[] = 'A placa do veículo é obrigatória';
        }

        if (empty(trim($data['brand'] ?? ''))) {
            $errors[] = 'A marca do veículo é obrigatória';
        }

        if (empty(trim($data['model'] ?? ''))) {
            $errors[] = 'O modelo do veículo é obrigatório';
        }

        if (empty($data['year'] ?? '')) {
            $errors[] = 'O ano do veículo é obrigatório';
        }

        if (empty($data['type'] ?? '')) {
            $errors[] = 'O tipo do veículo é obrigatório';
        }

        if (empty($data['fuel_type'] ?? '')) {
            $errors[] = 'O tipo de combustível é obrigatório';
        }

        if (empty($data['company_id'] ?? '')) {
            $errors[] = 'A empresa é obrigatória';
        }

        // Validar placa única
        $vehicleId = $data['id'] ?? null;
        if ($this->vehicleModel->plateExists($data['plate'], $vehicleId)) {
            $errors[] = 'Já existe um veículo cadastrado com esta placa';
        }

        // Validar ano
        $currentYear = date('Y');
        $vehicleYear = (int)$data['year'];
        if ($vehicleYear < 1900 || $vehicleYear > ($currentYear + 1)) {
            $errors[] = 'Ano do veículo deve estar entre 1900 e ' . ($currentYear + 1);
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Verificar permissão
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        $allowedRoles = ['super_admin', 'admin', 'comercial'];
        return in_array($userRole, $allowedRoles);
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
}
?>