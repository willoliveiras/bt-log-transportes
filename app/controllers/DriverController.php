<?php
// app/controllers/DriverController.php - VERSÃO COMPLETAMENTE CORRIGIDA
require_once __DIR__ . '/../models/DriverModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';

class DriverController {
    private $driverModel;
    private $employeeModel;
    private $companyModel;
    private $session;

    public function __construct() {
        $this->driverModel = new DriverModel();
        $this->employeeModel = new EmployeeModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    // Listar motoristas - VERSÃO CORRIGIDA
    public function index() {
        if (!$this->hasPermission('drivers')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        
        // ✅ CORREÇÃO: Usar o driverModel corretamente
        $drivers = $this->driverModel->getAll($companyFilter);
        $companies = $this->companyModel->getForDropdown();
        
        $pageTitle = 'Motoristas';
        $currentPage = 'drivers';
        
        include '../app/views/layouts/header.php';
        include '../app/views/drivers/list.php';
        include '../app/views/layouts/footer.php';
    }

    // ✅ CORREÇÃO: Método save() COMPLETAMENTE REVISADO
    public function save() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('drivers')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            error_log("📥 [DRIVERS CONTROLLER] Dados recebidos: " . print_r($_POST, true));
            
            // ✅ CORREÇÃO CRÍTICA: Determinar o tipo de motorista corretamente
            $driverType = 'external'; // Padrão
            if (isset($_POST['is_employee_driver']) && $_POST['is_employee_driver'] == '1') {
                $driverType = 'employee';
            }
            
            // ✅ CORREÇÃO: Forçar o driver_type correto
            $_POST['driver_type'] = $driverType;

            // ✅ CORREÇÃO: Obter company_id
            $companyId = $_POST['company_id'] ?? $this->getDefaultCompanyId();
            if (!$companyId) {
                throw new Exception('Nenhuma empresa disponível para vincular o motorista');
            }

            error_log("🎯 [DRIVERS CONTROLLER] Tipo definido: " . $driverType . ", Empresa: " . $companyId);

            $validation = $this->validateDriverData($_POST, $driverType);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            // ✅ CORREÇÃO: Preparar dados com company_id
            if ($driverType === 'employee') {
                $driverData = [
                    'company_id' => $companyId,
                    'driver_type' => 'employee',
                    'employee_id' => $_POST['employee_id'] ?? null,
                    'cnh_number' => $_POST['cnh_number'] ?? null,
                    'cnh_category' => $_POST['cnh_category'] ?? null,
                    'cnh_expiration' => $_POST['cnh_expiration'] ?? null,
                    'custom_commission_rate' => $_POST['custom_commission_rate'] ?? null,
                    'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true
                ];
            } else {
                $driverData = [
                    'company_id' => $companyId,
                    'driver_type' => 'external',
                    'name' => trim($_POST['name'] ?? ''),
                    'cpf' => $_POST['cpf'] ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : null,
                    'rg' => $_POST['rg'] ?? null,
                    'birth_date' => $_POST['birth_date'] ?? null,
                    'phone' => $_POST['phone'] ?? null,
                    'address' => $_POST['address'] ?? null,
                    'email' => $_POST['email'] ?? null,
                    'cnh_number' => $_POST['cnh_number'] ?? null,
                    'cnh_category' => $_POST['cnh_category'] ?? null,
                    'cnh_expiration' => $_POST['cnh_expiration'] ?? null,
                    'custom_commission_rate' => $_POST['custom_commission_rate'] ?? null,
                    'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true
                ];
            }

            $driverId = $_POST['id'] ?? null;
            
            if ($driverId) {
                $success = $this->driverModel->update($driverId, $driverData);
                $message = 'Motorista atualizado com sucesso!';
            } else {
                $driverId = $this->driverModel->create($driverData);
                $success = (bool)$driverId;
                $message = 'Motorista criado com sucesso!';
            }

            if ($success) {
                error_log("✅ [DRIVERS] Motorista salvo: ID " . ($driverId ?: 'novo') . " - Tipo: " . $driverType . " - Empresa: " . $companyId);
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'driverId' => $driverId
                ]);
            } else {
                throw new Exception('Erro ao salvar motorista no banco de dados');
            }

        } catch (Exception $e) {
            error_log("💥 [DRIVERS] Erro ao salvar motorista: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // ✅ CORREÇÃO: Validação simplificada e funcional
    private function validateDriverData($data, $driverType) {
        $errors = [];

        if ($driverType === 'employee') {
            // Validação para motorista funcionário
            if (empty($data['employee_id'])) {
                $errors[] = 'O funcionário é obrigatório para motoristas internos';
            }
        } else {
            // Validação para motorista externo
            if (empty(trim($data['name'] ?? ''))) {
                $errors[] = 'O nome do motorista é obrigatório';
            }

            if (empty(trim($data['cpf'] ?? ''))) {
                $errors[] = 'O CPF do motorista é obrigatório';
            }
        }

        // Validações comuns a ambos os tipos
        if (empty(trim($data['cnh_number'] ?? ''))) {
            $errors[] = 'O número da CNH é obrigatório';
        }

        if (empty($data['cnh_category'] ?? '')) {
            $errors[] = 'A categoria da CNH é obrigatória';
        }

        if (empty($data['cnh_expiration'] ?? '')) {
            $errors[] = 'A data de validade da CNH é obrigatória';
        }

        // ✅ CORREÇÃO: Validação de CNH como aviso apenas
        if (!empty($data['cnh_expiration'])) {
            $expiration = new DateTime($data['cnh_expiration']);
            $today = new DateTime();
            
            if ($expiration <= $today) {
                error_log("⚠️ [DRIVERS] ATENÇÃO: CNH expirada informada");
                // Não bloqueia, apenas registra o aviso
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Buscar motorista por ID - VERSÃO CORRIGIDA
    public function getDriver($id) {
        if (!$this->hasPermission('drivers')) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Sem permissão'];
        }

        $driver = $this->driverModel->getById($id);
        if (!$driver) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Motorista não encontrado'];
        }

        return $driver;
    }

    // Excluir motorista - VERSÃO CORRIGIDA
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('drivers')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $success = $this->driverModel->delete($id);

            if ($success) {
                error_log("🗑️ [DRIVERS] Motorista excluído: ID {$id} por usuário " . ($this->session->get('user_id') ?? 'desconhecido'));
                echo json_encode([
                    'success' => true, 
                    'message' => 'Motorista excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir motorista');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao excluir motorista: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // ✅ CORREÇÃO: Buscar funcionários disponíveis para motorista - VERSÃO MELHORADA
    public function getAvailableEmployees() {
		header('Content-Type: application/json');
		
		if (!$this->hasPermission('drivers')) {
			http_response_code(403);
			echo json_encode([
				'success' => false, 
				'message' => 'Sem permissão'
			]);
			exit;
		}

		try {
			$companyId = $_GET['company_id'] ?? null;
			error_log("🔍 [DRIVERS CONTROLLER] Buscando funcionários disponíveis - Empresa: " . ($companyId ?: 'Todas'));
			
			// ✅ BUSCAR DIRETAMENTE DO BANCO
			$db = Database::getInstance()->getConnection();
			
			$sql = "
				SELECT 
					e.id, 
					e.name, 
					e.position,
					e.cpf,
					e.phone,
					c.name as company_name
				FROM employees e
				LEFT JOIN companies c ON e.company_id = c.id
				WHERE e.is_active = 1 
				AND e.is_driver = 1
				AND e.id NOT IN (
					SELECT employee_id 
					FROM drivers 
					WHERE employee_id IS NOT NULL
					AND is_active = 1
				)
			";
			
			$params = [];
			
			if ($companyId) {
				$sql .= " AND e.company_id = ?";
				$params[] = $companyId;
			}
			
			$sql .= " ORDER BY e.name";
			
			error_log("📋 [DRIVERS CONTROLLER] SQL: " . $sql);
			
			$stmt = $db->prepare($sql);
			$stmt->execute($params);
			$employees = $stmt->fetchAll();
			
			error_log("✅ [DRIVERS CONTROLLER] Funcionários disponíveis encontrados: " . count($employees));
			
			echo json_encode([
				'success' => true,
				'data' => $employees,
				'count' => count($employees),
				'message' => count($employees) > 0 
					? 'Funcionários carregados com sucesso' 
					: 'Nenhum funcionário disponível como motorista'
			]);
			
		} catch (Exception $e) {
			error_log("💥 [DRIVERS CONTROLLER] Erro ao buscar funcionários disponíveis: " . $e->getMessage());
			
			http_response_code(500);
			echo json_encode([
				'success' => false, 
				'message' => 'Erro ao buscar funcionários disponíveis: ' . $e->getMessage(),
				'data' => [],
				'count' => 0
			]);
		}
		
		exit;
	}

    // ✅ NOVO MÉTODO: Buscar dados completos do funcionário
    public function getEmployeeData() {
		header('Content-Type: application/json');
		
		if (!$this->hasPermission('drivers')) {
			http_response_code(403);
			echo json_encode([
				'success' => false, 
				'message' => 'Sem permissão'
			]);
			exit;
		}

		try {
			$employeeId = $_GET['id'] ?? null;
			
			if (!$employeeId) {
				throw new Exception('ID do funcionário não informado');
			}

			error_log("🔍 [DRIVERS CONTROLLER] Buscando dados do funcionário ID: " . $employeeId);
			
			// ✅ BUSCAR DIRETAMENTE DO BANCO
			$db = Database::getInstance()->getConnection();
			$stmt = $db->prepare("
				SELECT 
					id, name, cpf, rg, birth_date, phone, email, address, 
					position, is_active
				FROM employees 
				WHERE id = ? AND is_active = 1
			");
			$stmt->execute([$employeeId]);
			$employee = $stmt->fetch();
			
			if (!$employee) {
				throw new Exception('Funcionário não encontrado ou inativo');
			}

			// ✅ GARANTIR TODOS OS CAMPOS
			$employeeData = [
				'id' => $employee['id'],
				'name' => $employee['name'] ?? '',
				'cpf' => $employee['cpf'] ?? '',
				'rg' => $employee['rg'] ?? '',
				'birth_date' => $employee['birth_date'] ?? '',
				'phone' => $employee['phone'] ?? '',
				'email' => $employee['email'] ?? '',
				'address' => $employee['address'] ?? '', // ✅ ENDEREÇO INCLUÍDO
				'position' => $employee['position'] ?? '',
				'is_active' => $employee['is_active'] ?? true
			];

			error_log("✅ [DRIVERS CONTROLLER] Dados do funcionário carregados: " . $employeeData['name']);
			
			echo json_encode([
				'success' => true,
				'data' => $employeeData,
				'message' => 'Dados do funcionário carregados com sucesso'
			]);
			
		} catch (Exception $e) {
			error_log("💥 [DRIVERS CONTROLLER] Erro ao buscar dados do funcionário: " . $e->getMessage());
			
			http_response_code(500);
			echo json_encode([
				'success' => false, 
				'message' => 'Erro ao buscar dados do funcionário: ' . $e->getMessage(),
				'data' => null
			]);
		}
		
		exit;
	}

    // ✅ NOVO MÉTODO: Obter company_id padrão
    private function getDefaultCompanyId() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id FROM companies WHERE is_active = 1 ORDER BY id LIMIT 1");
            $company = $stmt->fetch();
            
            if ($company) {
                $companyId = $company['id'];
                error_log("🏢 [DRIVERS] Usando empresa padrão ID: " . $companyId);
                return $companyId;
            } else {
                error_log("❌ [DRIVERS] Nenhuma empresa ativa encontrada!");
                return null;
            }
        } catch (Exception $e) {
            error_log("❌ [DRIVERS] Erro ao obter company_id padrão: " . $e->getMessage());
            return null;
        }
    }

    // ✅ NOVO MÉTODO: Debug para desenvolvimento
    public function debug() {
        header('Content-Type: application/json');
        
        try {
            error_log("🐛 [DRIVERS DEBUG] Iniciando debug...");
            
            $db = Database::getInstance()->getConnection();
            
            // Contar motoristas
            $stmt = $db->query("SELECT COUNT(*) as total FROM drivers");
            $totalDrivers = $stmt->fetch()['total'];
            
            // Contar motoristas com company_id
            $stmt = $db->query("SELECT COUNT(*) as with_company FROM drivers WHERE company_id IS NOT NULL");
            $withCompany = $stmt->fetch()['with_company'];
            
            // Listar motoristas
            $stmt = $db->query("SELECT id, name, driver_type, company_id, is_active FROM drivers");
            $drivers = $stmt->fetchAll();
            
            // Listar empresas
            $stmt = $db->query("SELECT id, name, is_active FROM companies");
            $companies = $stmt->fetchAll();
            
            $debugInfo = [
                'total_drivers' => $totalDrivers,
                'drivers_with_company' => $withCompany,
                'drivers_list' => $drivers,
                'companies_list' => $companies,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log("📊 [DRIVERS DEBUG] Info: " . print_r($debugInfo, true));
            
            echo json_encode([
                'success' => true,
                'debug_info' => $debugInfo,
                'message' => 'Debug executado com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("💥 [DRIVERS DEBUG] Erro: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Erro no debug: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // ✅ NOVO MÉTODO: Forçar correção de company_id
    public function fixCompanyIds() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('drivers')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $companyId = $this->getDefaultCompanyId();
            if (!$companyId) {
                throw new Exception('Nenhuma empresa disponível para correção');
            }
            
            $db = Database::getInstance()->getConnection();
            
            // Atualizar motoristas sem company_id
            $stmt = $db->prepare("UPDATE drivers SET company_id = ? WHERE company_id IS NULL");
            $stmt->execute([$companyId]);
            $affected = $stmt->rowCount();
            
            error_log("🔧 [DRIVERS] Correção aplicada: {$affected} motoristas atualizados com company_id = {$companyId}");
            
            echo json_encode([
                'success' => true,
                'message' => "Correção aplicada! {$affected} motoristas atualizados com company_id.",
                'affected_rows' => $affected,
                'company_id_used' => $companyId
            ]);
            
        } catch (Exception $e) {
            error_log("💥 [DRIVERS] Erro na correção: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro na correção: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Verificar permissão
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        return in_array($userRole, ['super_admin', 'admin']);
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
}
?>