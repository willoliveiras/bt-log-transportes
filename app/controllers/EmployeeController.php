<?php
// app/controllers/EmployeeController.php - VERSÃO CORRIGIDA
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class EmployeeController {
    private $employeeModel;
    private $companyModel;
    private $session;

    public function __construct() {
        $this->employeeModel = new EmployeeModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    public function index() {
        if (!$this->hasPermission('employees')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $employees = $this->employeeModel->getAll($companyFilter);
        $companies = $this->companyModel->getForDropdown();
        
        $pageTitle = 'Funcionários';
        $currentPage = 'employees';
        
        include '../app/views/layouts/header.php';
        include '../app/views/employees/list.php';
        include '../app/views/layouts/footer.php';
    }

    public function getEmployee($id) {
        if (!$this->hasPermission('employees')) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Sem permissão'];
        }

        $employee = $this->employeeModel->getById($id);
        if (!$employee) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Funcionário não encontrado'];
        }

        return $employee;
    }

    public function getAllEmployees($companyFilter = null) {
        if (!$this->hasPermission('employees')) {
            return [];
        }

        return $this->employeeModel->getAll($companyFilter);
    }

	public function save() {
		if (!$this->hasPermission('employees')) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Sem permissão']);
			return;
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['success' => false, 'message' => 'Método não permitido']);
			return;
		}

		try {
			// ✅ DEBUG: Log de todos os dados recebidos
			error_log("🎯 EmployeeController::save - Dados recebidos:");
			error_log("🎯 POST data: " . print_r($_POST, true));
			error_log("🎯 FILES data: " . print_r($_FILES, true));
			
			// ✅ CORREÇÃO: Obter employeeId de múltiplas formas
			$employeeId = $_POST['employeeId'] ?? null;
			if (!$employeeId) {
				$employeeId = $_POST['id'] ?? null;
			}
			
			error_log("🎯 EmployeeController::save - employeeId: " . $employeeId);
			
			// Coletar dados do formulário
			$data = $this->collectFormData();
			
			// ✅ CORREÇÃO: Processar upload da foto ANTES de validar/salvar
			$photoPath = null;
			if (isset($_FILES['employee_photo']) && $_FILES['employee_photo']['error'] === UPLOAD_ERR_OK) {
				error_log("📸 Processando upload de foto...");
				$photoPath = $this->handlePhotoUpload($_FILES['employee_photo']);
				if ($photoPath) {
					$data['photo'] = $photoPath;
					error_log("✅ Foto salva: " . $photoPath);
				} else {
					error_log("❌ Falha no upload da foto");
				}
			} else {
				error_log("📸 Nenhuma foto enviada ou erro no upload: " . ($_FILES['employee_photo']['error'] ?? 'N/A'));
				
				// ✅ CORREÇÃO: Manter foto existente se estiver editando
				if ($employeeId) {
					$existingEmployee = $this->employeeModel->getById($employeeId);
					if ($existingEmployee && $existingEmployee['photo']) {
						$data['photo'] = $existingEmployee['photo'];
						error_log("✅ Mantendo foto existente: " . $existingEmployee['photo']);
					}
				}
			}

			// ✅ DEBUG: Log dos dados coletados
			error_log("🎯 Dados coletados do formulário: " . print_r($data, true));

			// ✅ CORREÇÃO: Validar com employeeId
			$validation = $this->validateEmployeeData($data, $employeeId);
			if (!$validation['success']) {
				error_log("❌ Validação falhou: " . $validation['message']);
				http_response_code(400);
				echo json_encode($validation);
				return;
			}

			if ($employeeId) {
				// ✅ CORREÇÃO: Atualizar funcionário existente
				error_log("🔄 Atualizando funcionário ID: " . $employeeId);
				$success = $this->employeeModel->update($employeeId, $data);
				$message = 'Funcionário atualizado com sucesso!';
			} else {
				// Criar novo funcionário
				error_log("🆕 Criando novo funcionário");
				$employeeId = $this->employeeModel->create($data);
				$success = (bool)$employeeId;
				$message = 'Funcionário criado com sucesso!';
			}

			if ($success) {
				error_log("✅ Funcionário salvo com sucesso! ID: " . $employeeId);
				echo json_encode([
					'success' => true,
					'message' => $message,
					'employeeId' => $employeeId
				]);
			} else {
				error_log("❌ Falha ao salvar funcionário no banco de dados");
				throw new Exception('Erro ao salvar funcionário no banco de dados');
			}

		} catch (Exception $e) {
			error_log("❌ Erro ao salvar funcionário: " . $e->getMessage());
			http_response_code(500);
			echo json_encode([
				'success' => false, 
				'message' => 'Erro interno do servidor: ' . $e->getMessage()
			]);
		}
	}

	private function handlePhotoUpload($file) {
		try {
			// ✅ CORREÇÃO: Caminho absoluto correto
			$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/bt-log-transportes/public/assets/images/employees/';
			
			error_log("📸 Diretório de upload: " . $uploadDir);

			// Criar diretório se não existir
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
				error_log("📸 Diretório criado: " . $uploadDir);
			}

			// Validar tipo de arquivo
			$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
			$fileType = mime_content_type($file['tmp_name']);
			
			if (!in_array($fileType, $allowedTypes)) {
				error_log("❌ Tipo de arquivo não permitido: " . $fileType);
				return false;
			}

			// Validar tamanho (máximo 5MB)
			if ($file['size'] > 5 * 1024 * 1024) {
				error_log("❌ Arquivo muito grande: " . $file['size'] . " bytes");
				return false;
			}

			// Gerar nome único
			$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
			$fileName = 'employee_' . time() . '_' . uniqid() . '.' . $extension;
			$filePath = $uploadDir . $fileName;

			error_log("📸 Tentando salvar arquivo em: " . $filePath);

			// Mover arquivo
			if (move_uploaded_file($file['tmp_name'], $filePath)) {
				$relativePath = 'assets/images/employees/' . $fileName;
				error_log("✅ Foto movida com sucesso para: " . $relativePath);
				error_log("✅ Arquivo existe: " . (file_exists($filePath) ? 'Sim' : 'Não'));
				error_log("✅ Tamanho do arquivo: " . filesize($filePath) . " bytes");
				
				return $relativePath;
			} else {
				error_log("❌ Erro ao mover arquivo uploadado");
				error_log("❌ Erro de upload: " . $file['error']);
				error_log("❌ tmp_name: " . $file['tmp_name']);
				error_log("❌ Destino: " . $filePath);
				return false;
			}
			
		} catch (Exception $e) {
			error_log("❌ Erro no upload da foto: " . $e->getMessage());
			return false;
		}
	}



    private function collectFormData() {
        $data = [
            'company_id' => $_POST['company_id'] ?? null,
            'name' => trim($_POST['name'] ?? ''),
            'cpf' => $_POST['cpf'] ?? null,
            'rg' => $_POST['rg'] ?? null,
            'birth_date' => $_POST['birth_date'] ?? null,
            'ctps' => $_POST['ctps'] ?? null,
            'pis_pasep' => $_POST['pis_pasep'] ?? null,
            'titulo_eleitor' => $_POST['titulo_eleitor'] ?? null,
            'reservista' => $_POST['reservista'] ?? null,
            'nome_mae' => $_POST['nome_mae'] ?? null,
            'nome_pai' => $_POST['nome_pai'] ?? null,
            'naturalidade' => $_POST['naturalidade'] ?? null,
            'nacionalidade' => $_POST['nacionalidade'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'address' => $_POST['address'] ?? null,
            'estado_civil' => $_POST['estado_civil'] ?? null,
            'grau_instrucao' => $_POST['grau_instrucao'] ?? null,
            'tipo_sanguineo' => $_POST['tipo_sanguineo'] ?? null,
            'position' => $_POST['position'] ?? '',
            'salary' => $_POST['salary'] ?? 0,
            'inss' => $_POST['inss'] ?? 0,
            'irrf' => $_POST['irrf'] ?? 0,
            'fgts' => $_POST['fgts'] ?? 0,
            'vale_transporte' => $_POST['vale_transporte'] ?? 0,
            'vale_refeicao' => $_POST['vale_refeicao'] ?? 0,
            'plano_saude' => $_POST['plano_saude'] ?? 0,
            'outros_descontos' => $_POST['outros_descontos'] ?? 0,
            'commission_rate' => $_POST['commission_rate'] ?? 0,
            'is_driver' => isset($_POST['is_driver']) ? (bool)$_POST['is_driver'] : false,
            'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true
        ];

        return $data;
    }

    private function validateEmployeeData($data, $excludeId = null) {
        $errors = [];

        // Campos obrigatórios
        if (empty(trim($data['name']))) {
            $errors[] = 'O nome do funcionário é obrigatório';
        }

        if (empty(trim($data['position']))) {
            $errors[] = 'O cargo é obrigatório';
        }

        if (empty($data['company_id'])) {
            $errors[] = 'A empresa é obrigatória';
        }

        if (empty($data['salary']) || $data['salary'] <= 0) {
            $errors[] = 'O salário é obrigatório e deve ser maior que zero';
        }

        // ✅ CORREÇÃO: Validar CPF se informado
        if (!empty($data['cpf'])) {
            $cpf = preg_replace('/[^0-9]/', '', $data['cpf']);
            
            error_log("🔍 Validando CPF: {$cpf}, Excluindo ID: {$excludeId}");
            
            if (strlen($cpf) !== 11) {
                $errors[] = 'CPF deve conter 11 dígitos';
            } elseif ($this->employeeModel->cpfExists($cpf, $excludeId)) {
                $errors[] = 'CPF já cadastrado para outro funcionário';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    public function delete($id) {
        if (!$this->hasPermission('employees')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        $success = $this->employeeModel->delete($id);

        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Funcionário excluído com sucesso!'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir funcionário']);
        }
    }

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