<?php
// app/controllers/CompanyController.php

require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class CompanyController {
    private $companyModel;
    private $session;

    public function __construct() {
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    // Listar empresas (para a view)
    public function index() {
        if (!$this->hasPermission('companies')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companies = $this->companyModel->getAll();
        
        $pageTitle = 'Empresas';
        $currentPage = 'companies';
        
        include '../app/views/layouts/header.php';
        include '../app/views/companies/list.php';
        include '../app/views/layouts/footer.php';
    }

    // API: Buscar empresa por ID
    public function getCompany($id) {
        if (!$this->hasPermission('companies')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return null;
        }

        $company = $this->companyModel->getById($id);
        return $company;
    }

    // API: Listar todas as empresas
    public function getAllCompanies() {
        if (!$this->hasPermission('companies')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return [];
        }

        $includeInactive = $_GET['include_inactive'] ?? false;
        return $this->companyModel->getAll($includeInactive);
    }

    // API: Criar ou atualizar empresa
    public function save() {
        if (!$this->hasPermission('companies')) {
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
            $companyId = $_POST['id'] ?? null;
            
            // Validar dados
            $validation = $this->validateCompanyData($_POST, $companyId);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                return;
            }

            // Preparar dados da empresa
            $companyData = [
                'name' => trim($_POST['name']),
                'razao_social' => trim($_POST['razao_social']),
                'cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj']),
                'inscricao_estadual' => $_POST['isento_ie'] ? null : trim($_POST['inscricao_estadual'] ?? ''),
                'isento_ie' => (bool)($_POST['isento_ie'] ?? false),
                'atuacao' => $_POST['atuacao'],
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'phone2' => trim($_POST['phone2'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'color' => $_POST['color'] ?? '#FF6B00',
                'is_active' => (bool)($_POST['is_active'] ?? true)
            ];

            // Processar upload da logo se existir
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = $this->handleLogoUpload($_FILES['logo']);
                if ($logoPath) {
                    $companyData['logo'] = $logoPath;
                }
            }

            if ($companyId) {
                // Atualizar empresa existente
                $success = $this->companyModel->update($companyId, $companyData);
                $message = 'Empresa atualizada com sucesso!';
            } else {
                // Criar nova empresa
                $companyId = $this->companyModel->create($companyData);
                $success = (bool)$companyId;
                $message = 'Empresa criada com sucesso!';
            }

            if ($success) {
                error_log("✅ Empresa salva com ID: " . $companyId);
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'companyId' => $companyId
                ]);
            } else {
                error_log("❌ Falha ao salvar empresa");
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao salvar empresa'
                ]);
            }

        } catch (Exception $e) {
            error_log("Erro ao salvar empresa: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
    }

    // API: Atualizar empresa
    public function update($id) {
        if (!$this->hasPermission('companies')) {
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
            // Verificar se empresa existe
            $company = $this->companyModel->getById($id);
            if (!$company) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Empresa não encontrada']);
                return;
            }

            // Validar dados
            $validation = $this->validateCompanyData($_POST, $id);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                return;
            }

            // Preparar dados
            $companyData = [
                'name' => trim($_POST['name']),
                'razao_social' => trim($_POST['razao_social']),
                'cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj']),
                'inscricao_estadual' => $_POST['isento_ie'] ? null : trim($_POST['inscricao_estadual'] ?? ''),
                'isento_ie' => (bool)($_POST['isento_ie'] ?? false),
                'atuacao' => $_POST['atuacao'],
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'phone2' => trim($_POST['phone2'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'color' => $_POST['color'] ?? '#FF6B00',
                'is_active' => (bool)($_POST['is_active'] ?? true)
            ];

            // Processar upload da logo
            $logoPath = $company['logo'];
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $newLogoPath = $this->handleLogoUpload($_FILES['logo']);
                if ($newLogoPath) {
                    // Remover logo antiga se existir
                    if ($logoPath && file_exists(__DIR__ . '/../../' . $logoPath)) {
                        unlink(__DIR__ . '/../../' . $logoPath);
                    }
                    $logoPath = $newLogoPath;
                }
            }
            $companyData['logo'] = $logoPath;

            // Atualizar empresa
            $success = $this->companyModel->update($id, $companyData);

            if ($success) {
                error_log("Empresa atualizada: ID {$id}");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Empresa atualizada com sucesso!',
                    'companyId' => $id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao atualizar empresa'
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao atualizar empresa: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor'
            ]);
        }
    }

    // API: Excluir empresa
    public function delete($id) {
        if (!$this->hasPermission('companies')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        try {
            $success = $this->companyModel->delete($id);

            if ($success) {
                error_log("Empresa excluída: ID {$id}");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Empresa excluída com sucesso!'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao excluir empresa'
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao excluir empresa: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor'
            ]);
        }
    }

    // Validação de dados
    private function validateCompanyData($data, $excludeId = null) {
        $errors = [];

        // Nome obrigatório
        if (empty(trim($data['name']))) {
            $errors[] = 'O nome da empresa é obrigatório';
        }

        // Razão social obrigatória
        if (empty(trim($data['razao_social']))) {
            $errors[] = 'A razão social é obrigatória';
        }

        // CNPJ válido
        if (!empty($data['cnpj'])) {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $data['cnpj']);
            if (strlen($cnpjLimpo) !== 14) {
                $errors[] = 'CNPJ inválido. Deve conter 14 dígitos.';
            } elseif ($this->companyModel->cnpjExists($cnpjLimpo, $excludeId)) {
                $errors[] = 'CNPJ já cadastrado';
            }
        } else {
            $errors[] = 'CNPJ é obrigatório';
        }

        // Área de atuação obrigatória
        if (empty($data['atuacao'])) {
            $errors[] = 'A área de atuação é obrigatória';
        }

        // Email válido (se informado)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }

        // Cor válida
        if (!empty($data['color']) && !preg_match('/^#[0-9A-F]{6}$/i', $data['color'])) {
            $errors[] = 'Cor inválida';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Upload da logo
    private function handleLogoUpload($file) {
        $uploadDir = __DIR__ . '/../../storage/uploads/companies/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validar tipo do arquivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            return false;
        }

        // Validar tamanho (máximo 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }

        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return 'storage/uploads/companies/' . $fileName;
        }

        return false;
    }

    // Verificar permissão
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        return in_array($userRole, ['super_admin', 'admin']);
    }

    // Redirecionamentos
    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
}
?>