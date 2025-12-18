<?php
require_once __DIR__ . '/../models/MaintenanceModel.php';
require_once __DIR__ . '/../models/VehicleModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class MaintenanceController {
    private $maintenanceModel;
    private $vehicleModel;
    private $companyModel;
    private $session;
    private $db;

    public function __construct() {
        $this->maintenanceModel = new MaintenanceModel();
        $this->vehicleModel = new VehicleModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
        $this->db = Database::getInstance()->getConnection();
    }

    // Listar manutenções
    public function index() {
        if (!$this->hasPermission('maintenance')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $vehicleFilter = $_GET['vehicle'] ?? null;
        $typeFilter = $_GET['type'] ?? null;
        $statusFilter = $_GET['status'] ?? null;
        
        $maintenances = $this->maintenanceModel->getAll($vehicleFilter, $companyFilter);
        
        // Aplicar filtros
        if ($typeFilter) {
            $maintenances = array_filter($maintenances, function($maintenance) use ($typeFilter) {
                return $maintenance['type'] === $typeFilter;
            });
        }
        
        if ($statusFilter) {
            $maintenances = array_filter($maintenances, function($maintenance) use ($statusFilter) {
                return ($maintenance['maintenance_status'] ?? 'em_dia') === $statusFilter;
            });
        }
        
        // ✅ IMPLEMENTAÇÃO DIRETA: Buscar empresas para dropdown
        $companies = $this->getCompaniesForDropdown();
        
        // ✅ IMPLEMENTAÇÃO DIRETA: Buscar veículos para dropdown
        $vehicles = $this->getVehiclesForDropdown($companyFilter);
        
        // Buscar estatísticas
        $maintenanceStats = $this->maintenanceModel->getMaintenanceStats($companyFilter);
        $upcomingMaintenances = $this->maintenanceModel->getUpcomingMaintenances();
        
        $pageTitle = 'Manutenções';
        $currentPage = 'maintenance';
        
        include '../app/views/layouts/header.php';
        include '../app/views/maintenance/list.php';
        include '../app/views/layouts/footer.php';
    }
	
	// ✅ MÉTODO: Gerar conta a pagar após manutenção
	private function generatePayableFromMaintenance($maintenanceData) {
		try {
			// Buscar informações do veículo
			$vehicleStmt = $this->db->prepare("
				SELECT v.company_id, v.plate, v.brand, v.model 
				FROM vehicles v 
				WHERE v.id = ?
			");
			$vehicleStmt->execute([$maintenanceData['vehicle_id']]);
			$vehicle = $vehicleStmt->fetch();
			
			if (!$vehicle) return false;
			
			// Buscar conta contábil para manutenção
			$chartAccountStmt = $this->db->prepare("
				SELECT id FROM chart_of_accounts 
				WHERE company_id = ? 
				AND account_name LIKE '%manutenção%' 
				AND account_type = 'despesa' 
				LIMIT 1
			");
			$chartAccountStmt->execute([$vehicle['company_id']]);
			$chartAccount = $chartAccountStmt->fetch();
			
			$chartAccountId = $chartAccount['id'] ?? 6; // Fallback para conta genérica
			
			// Preparar descrição da conta
			$description = "Manutenção veículo " . 
						  $vehicle['plate'] . " - " . 
						  substr($maintenanceData['description'], 0, 100);
			
			// Data de vencimento padrão (30 dias após manutenção)
			$dueDate = date('Y-m-d', strtotime('+30 days', strtotime($maintenanceData['maintenance_date'])));
			
			// Inserir conta a pagar
			$payableStmt = $this->db->prepare("
				INSERT INTO accounts_payable 
				(company_id, chart_account_id, description, amount, due_date, 
				 supplier, notes, status, created_at) 
				VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
			");
			
			return $payableStmt->execute([
				$vehicle['company_id'],
				$chartAccountId,
				$description,
				$maintenanceData['cost'],
				$dueDate,
				$maintenanceData['service_provider'] ?? 'Fornecedor não informado',
				"Gerada automaticamente da manutenção #" . ($maintenanceData['id'] ?? 'N/A') . 
				"\nVeículo: " . $vehicle['plate'] . " - " . $vehicle['brand'] . " " . $vehicle['model'] .
				"\nTipo: " . $maintenanceData['type'] .
				"\nData: " . $maintenanceData['maintenance_date']
			]);
			
		} catch (Exception $e) {
			error_log("❌ Erro ao gerar conta a pagar da manutenção: " . $e->getMessage());
			return false;
		}
	}

    // ✅ IMPLEMENTAÇÃO DIRETA: Buscar empresas para dropdown
    private function getCompaniesForDropdown() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, color 
                FROM companies 
                WHERE is_active = 1 
                ORDER BY name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar empresas para dropdown: " . $e->getMessage());
            return [];
        }
    }

    // ✅ IMPLEMENTAÇÃO DIRETA: Buscar veículos para dropdown
    private function getVehiclesForDropdown($companyId = null) {
        try {
            $sql = "SELECT id, plate, brand, model, current_km, next_maintenance_km 
                    FROM vehicles 
                    WHERE is_active = 1";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY plate ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar veículos para dropdown: " . $e->getMessage());
            return [];
        }
    }

    // Salvar manutenção (create/update)
    public function save() {
		header('Content-Type: application/json');
		
		if (!$this->hasPermission('maintenance')) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Sem permissão']);
			exit;
		}

		try {
			$validation = $this->validateMaintenanceData($_POST);
			if (!$validation['success']) {
				http_response_code(400);
				echo json_encode($validation);
				exit;
			}

			// Preparar dados
			$maintenanceData = [
				'vehicle_id' => $_POST['vehicle_id'],
				'type' => $_POST['type'],
				'description' => $_POST['description'],
				'maintenance_date' => $_POST['maintenance_date'],
				'next_maintenance_date' => $_POST['next_maintenance_date'] ?? null,
				'cost' => (float)$_POST['cost'],
				'current_km' => $_POST['current_km'] ? (float)$_POST['current_km'] : null,
				'next_maintenance_km' => $_POST['next_maintenance_km'] ? (float)$_POST['next_maintenance_km'] : null,
				'service_provider' => $_POST['service_provider'] ?? null,
				'notes' => $_POST['notes'] ?? null
			];

			$maintenanceId = $_POST['id'] ?? null;
			
			if ($maintenanceId) {
				$success = $this->maintenanceModel->update($maintenanceId, $maintenanceData);
				$message = 'Manutenção atualizada com sucesso!';
			} else {
				$success = $this->maintenanceModel->create($maintenanceData);
				$message = 'Manutenção criada com sucesso!';
				
				// ✅ GERAR CONTA A PAGAR APÓS CRIAÇÃO
				if ($success) {
					// Pegar o ID da manutenção recém-criada
					$lastId = $this->db->lastInsertId();
					$maintenanceData['id'] = $lastId;
					
					// Gerar conta a pagar automaticamente
					$payableGenerated = $this->generatePayableFromMaintenance($maintenanceData);
					
					if ($payableGenerated) {
						$message .= ' Conta a pagar gerada automaticamente.';
					}
				}
			}

			if ($success) {
				echo json_encode([
					'success' => true, 
					'message' => $message,
					'maintenanceId' => $maintenanceId,
					'payableGenerated' => $payableGenerated ?? false
				]);
			} else {
				throw new Exception('Erro ao salvar manutenção no banco de dados');
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

    // Buscar manutenção por ID
    public function getMaintenance($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('maintenance')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        $maintenance = $this->maintenanceModel->getById($id);
        if (!$maintenance) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Manutenção não encontrada']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $maintenance
        ]);
        exit;
    }

    // Excluir manutenção
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('maintenance')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $success = $this->maintenanceModel->delete($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Manutenção excluída com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir manutenção');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir manutenção: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Buscar manutenções próximas
    public function getUpcoming() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('maintenance')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $days = $_GET['days'] ?? 7;
            $kmThreshold = $_GET['km_threshold'] ?? 500;
            
            $maintenances = $this->maintenanceModel->getUpcomingMaintenances($days, $kmThreshold);

            echo json_encode([
                'success' => true,
                'data' => $maintenances
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar manutenções: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Validar dados da manutenção
    private function validateMaintenanceData($data) {
        $errors = [];

        // Validar campos obrigatórios
        if (empty($data['vehicle_id'])) {
            $errors[] = 'O veículo é obrigatório';
        }

        if (empty($data['type'])) {
            $errors[] = 'O tipo de manutenção é obrigatório';
        }

        if (empty(trim($data['description'] ?? ''))) {
            $errors[] = 'A descrição é obrigatória';
        }

        if (empty($data['maintenance_date'])) {
            $errors[] = 'A data da manutenção é obrigatória';
        }

        if (empty($data['cost']) || $data['cost'] <= 0) {
            $errors[] = 'O custo deve ser maior que zero';
        }

        // Validar datas
        if (!empty($data['next_maintenance_date']) && !empty($data['maintenance_date'])) {
            if (strtotime($data['next_maintenance_date']) <= strtotime($data['maintenance_date'])) {
                $errors[] = 'A data da próxima manutenção deve ser depois da data atual';
            }
        }

        // Validar KM
        if (!empty($data['next_maintenance_km']) && !empty($data['current_km'])) {
            if ($data['next_maintenance_km'] <= $data['current_km']) {
                $errors[] = 'O KM da próxima manutenção deve ser maior que o KM atual';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Verificar permissão
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        $allowedRoles = ['super_admin', 'admin', 'operacional'];
        return in_array($userRole, $allowedRoles);
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }

    // ✅ MÉTODO ADICIONAL: Buscar tipos de manutenção
    public function getMaintenanceTypes() {
        return [
            'preventiva' => 'Preventiva',
            'corretiva' => 'Corretiva',
            'preditiva' => 'Preditiva',
            'inspecao' => 'Inspeção'
        ];
    }

    // ✅ MÉTODO ADICIONAL: Buscar serviços comuns
    public function getCommonServices() {
        return [
            'troca_oleo' => 'Troca de Óleo e Filtro',
            'filtro_ar' => 'Troca do Filtro de Ar',
            'filtro_combustivel' => 'Troca do Filtro de Combustível',
            'pastilhas_freio' => 'Troca de Pastilhas de Freio',
            'discos_freio' => 'Troca de Discos de Freio',
            'pneus' => 'Troca de Pneus',
            'alinhamento' => 'Alinhamento e Balanceamento',
            'suspensao' => 'Revisão da Suspensão',
            'transmissao' => 'Troca de Óleo da Transmissão',
            'diferencial' => 'Troca de Óleo do Diferencial',
            'bateria' => 'Troca da Bateria',
            'correia' => 'Troca da Correia Dentada',
            'velas' => 'Troca de Velas',
            'injetores' => 'Limpeza de Bicos Injetores',
            'ar_condicionado' => 'Manutenção do Ar Condicionado',
            'freios' => 'Revisão Completa do Sistema de Freios',
            'motor' => 'Revisão do Motor',
            'eletrica' => 'Revisão do Sistema Elétrico',
            'outros' => 'Outros Serviços'
        ];
    }

    // ✅ MÉTODO ADICIONAL: Buscar intervalos padrão
    public function getDefaultIntervals() {
        return [
            'troca_oleo' => 10000,
            'filtro_ar' => 15000,
            'filtro_combustivel' => 20000,
            'pastilhas_freio' => 25000,
            'pneus' => 50000,
            'alinhamento' => 10000,
            'transmissao' => 60000,
            'diferencial' => 60000,
            'correia' => 80000
        ];
    }
}
?>