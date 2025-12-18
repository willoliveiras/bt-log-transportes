<?php
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class ClientController {
    private $clientModel;
    private $companyModel;
    private $session;

    public function __construct() {
        $this->clientModel = new ClientModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    // Listar clientes
    public function index() {
        if (!$this->hasPermission('clients')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $categoryFilter = $_GET['category'] ?? null;
        $segmentFilter = $_GET['segment'] ?? null;
        $statusFilter = $_GET['status'] ?? null;
        
        $clients = $this->clientModel->getAll($companyFilter);
        
        // Aplicar filtros
        if ($categoryFilter) {
            $clients = array_filter($clients, function($client) use ($categoryFilter) {
                return $client['client_category'] === $categoryFilter;
            });
        }
        
        if ($segmentFilter) {
            $clients = array_filter($clients, function($client) use ($segmentFilter) {
                return $client['client_segment'] === $segmentFilter;
            });
        }
        
        if ($statusFilter === 'active') {
            $clients = array_filter($clients, function($client) {
                return $client['is_active'];
            });
        } elseif ($statusFilter === 'inactive') {
            $clients = array_filter($clients, function($client) {
                return !$client['is_active'];
            });
        }
        
        $companies = $this->companyModel->getForDropdown();
        $partnerCompanies = $this->clientModel->getPartnerCompanies($companyFilter);
        $clientStats = $this->clientModel->getClientStats($companyFilter);
        
        $pageTitle = 'Clientes';
        $currentPage = 'clients';
        
        include '../app/views/layouts/header.php';
        include '../app/views/clients/list.php';
        include '../app/views/layouts/footer.php';
    }

    // Salvar cliente (create/update) - CORRIGIDO
    public function save() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            error_log("📥 Dados recebidos: " . print_r($_POST, true));

            $validation = $this->validateClientData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            // Preparar dados CORRIGIDOS
            $clientData = [
                'company_id' => $_POST['company_id'],
                'name' => trim($_POST['name']),
                'fantasy_name' => trim($_POST['fantasy_name'] ?? ''),
                'type' => $_POST['type'],
                'client_category' => $_POST['client_category'],
                'cpf_cnpj' => !empty($_POST['cpf_cnpj']) ? preg_replace('/[^0-9]/', '', $_POST['cpf_cnpj']) : null,
                'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'phone' => !empty($_POST['phone']) ? trim($_POST['phone']) : null,
                'address' => !empty($_POST['address']) ? trim($_POST['address']) : null,
                'contact_name' => !empty($_POST['contact_name']) ? trim($_POST['contact_name']) : null,
                'contact_phone' => !empty($_POST['contact_phone']) ? trim($_POST['contact_phone']) : null,
                'contact_email' => !empty($_POST['contact_email']) ? trim($_POST['contact_email']) : null,
                'client_segment' => $_POST['client_segment'] ?? 'outros',
                'client_size' => $_POST['client_size'] ?? 'medio',
                'payment_terms' => !empty($_POST['payment_terms']) ? trim($_POST['payment_terms']) : null,
                'credit_limit' => !empty($_POST['credit_limit']) ? (float)$_POST['credit_limit'] : 0.00,
                'registration_date' => !empty($_POST['registration_date']) ? $_POST['registration_date'] : null,
                'partner_company_id' => !empty($_POST['partner_company_id']) ? $_POST['partner_company_id'] : null,
                'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
                'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true
            ];

            error_log("📋 Dados processados: " . print_r($clientData, true));

            $clientId = $_POST['id'] ?? null;
            
            if ($clientId) {
                $success = $this->clientModel->update($clientId, $clientData);
                $message = 'Cliente atualizado com sucesso!';
            } else {
                $success = $this->clientModel->create($clientData);
                $message = 'Cliente criado com sucesso!';
            }

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'clientId' => $clientId
                ]);
            } else {
                throw new Exception('Erro ao salvar cliente no banco de dados');
            }

        } catch (Exception $e) {
            error_log("❌ Erro no ClientController: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Buscar cliente por ID
    public function getClient($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        $client = $this->clientModel->getById($id);
        if (!$client) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $client
        ]);
        exit;
    }

    // Buscar empresas parceiras
    public function getPartnerCompanies() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        $companyId = $_GET['company_id'] ?? null;
        $partnerCompanies = $this->clientModel->getPartnerCompanies($companyId);

        echo json_encode([
            'success' => true,
            'data' => $partnerCompanies
        ]);
        exit;
    }

    // Excluir cliente
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $success = $this->clientModel->delete($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cliente excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir cliente');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir cliente: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Validar dados do cliente - CORRIGIDO
    private function validateClientData($data) {
        $errors = [];

        // Validar campos obrigatórios
        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = 'A razão social é obrigatória';
        }

        if (empty($data['type'] ?? '')) {
            $errors[] = 'O tipo de cliente é obrigatório';
        }

        if (empty($data['client_category'] ?? '')) {
            $errors[] = 'A categoria do cliente é obrigatória';
        }

        if (empty($data['company_id'] ?? '')) {
            $errors[] = 'A empresa é obrigatória';
        }

        // Validar regras de negócio
        $type = $data['type'] ?? '';
        $category = $data['client_category'] ?? '';

        // Pessoa física só pode ser cliente comum ou cliente de empresa parceira
        if ($type === 'pessoa_fisica' && $category === 'empresa_parceira') {
            $errors[] = 'Pessoa física não pode ser cadastrada como Empresa Parceira';
        }

        // Cliente de empresa parceira precisa ter empresa parceira selecionada
        if ($category === 'cliente_empresa_parceira' && empty($data['partner_company_id'])) {
            $errors[] = 'Para cliente de empresa parceira, é necessário selecionar a empresa parceira';
        }

        // Validar CPF/CNPJ único
        if (!empty($data['cpf_cnpj'])) {
            $clientId = $data['id'] ?? null;
            if ($this->clientModel->documentExists($data['cpf_cnpj'], $clientId)) {
                $errors[] = 'Já existe um cliente cadastrado com este CPF/CNPJ';
            }
        }

        // Validar email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'O email informado é inválido';
        }

        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'O email do contato é inválido';
        }

        // Validar limite de crédito - CORREÇÃO
        if (isset($data['credit_limit'])) {
            $creditLimit = floatval($data['credit_limit']);
            if ($creditLimit < 0) {
                // Corrigir automaticamente em vez de dar erro
                $data['credit_limit'] = 0;
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
        $allowedRoles = ['super_admin', 'admin', 'comercial', 'financeiro'];
        return in_array($userRole, $allowedRoles);
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }

    // MÉTODOS ADICIONAIS PARA COMPLETAR O CONTROLLER

    // Buscar clientes por empresa (para selects)
    public function getByCompany($companyId) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $clients = $this->clientModel->getAll($companyId);
            
            // Formatar para select
            $formattedClients = array_map(function($client) {
                return [
                    'id' => $client['id'],
                    'text' => $client['fantasy_name'] ?: $client['name'],
                    'name' => $client['fantasy_name'] ?: $client['name'],
                    'cpf_cnpj' => $client['cpf_cnpj'],
                    'type' => $client['type']
                ];
            }, $clients);

            echo json_encode([
                'success' => true,
                'data' => $formattedClients
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar clientes: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Ativar/desativar cliente
    public function toggleStatus($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $client = $this->clientModel->getById($id);
            if (!$client) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
                exit;
            }

            $newStatus = !$client['is_active'];
            $success = $this->clientModel->update($id, ['is_active' => $newStatus]);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => $newStatus ? 'Cliente ativado com sucesso!' : 'Cliente desativado com sucesso!',
                    'newStatus' => $newStatus
                ]);
            } else {
                throw new Exception('Erro ao alterar status do cliente');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Buscar estatísticas detalhadas
    public function getDetailedStats() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $companyId = $_GET['company_id'] ?? null;
            $period = $_GET['period'] ?? 'month'; // month, quarter, year
            
            $stats = $this->clientModel->getClientStats($companyId);
            
            // Estatísticas adicionais
            $additionalStats = [
                'revenue_by_segment' => $this->getRevenueBySegment($companyId, $period),
                'clients_by_size' => $this->getClientsBySize($companyId),
                'active_vs_inactive' => [
                    'active' => $stats['active_clients'],
                    'inactive' => $stats['total_clients'] - $stats['active_clients']
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => array_merge($stats, $additionalStats)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar estatísticas: ' . $e->getMessage()]);
        }
        
        exit;
    }

    // Métodos auxiliares privados
    private function getRevenueBySegment($companyId = null, $period = 'month') {
        // Implementar lógica para calcular receita por segmento
        // Por enquanto, retornar dados de exemplo
        return [
            ['segment' => 'industria', 'revenue' => 150000.00],
            ['segment' => 'varejo', 'revenue' => 80000.00],
            ['segment' => 'servicos', 'revenue' => 60000.00],
            ['segment' => 'outros', 'revenue' => 30000.00]
        ];
    }

    private function getClientsBySize($companyId = null) {
        // Implementar lógica para contar clientes por porte
        // Por enquanto, retornar dados de exemplo
        return [
            ['size' => 'pequeno', 'count' => 15],
            ['size' => 'medio', 'count' => 25],
            ['size' => 'grande', 'count' => 8],
            ['size' => 'corporativo', 'count' => 2]
        ];
    }

    // Exportar clientes
    public function export() {
        if (!$this->hasPermission('clients')) {
            $this->redirectToUnauthorized();
            return;
        }

        try {
            $companyFilter = $_GET['company'] ?? null;
            $format = $_GET['format'] ?? 'excel'; // excel, pdf, csv
            
            $clients = $this->clientModel->getAll($companyFilter);
            
            switch ($format) {
                case 'excel':
                    $this->exportExcel($clients);
                    break;
                case 'pdf':
                    $this->exportPDF($clients);
                    break;
                case 'csv':
                    $this->exportCSV($clients);
                    break;
                default:
                    throw new Exception('Formato de exportação não suportado');
            }
        } catch (Exception $e) {
            error_log("❌ Erro ao exportar clientes: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro ao exportar clientes: ' . $e->getMessage();
            header('Location: /bt-log-transportes/public/index.php?page=clients');
            exit;
        }
    }

    private function exportExcel($clients) {
        // Implementar exportação Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="clientes_' . date('Y-m-d') . '.xls"');
        
        echo "Nome\tTipo\tCategoria\tCPF/CNPJ\tEmail\tTelefone\tStatus\n";
        foreach ($clients as $client) {
            echo $client['fantasy_name'] ?: $client['name'] . "\t";
            echo $client['type'] . "\t";
            echo $client['client_category'] . "\t";
            echo $client['cpf_cnpj'] . "\t";
            echo $client['email'] . "\t";
            echo $client['phone'] . "\t";
            echo ($client['is_active'] ? 'Ativo' : 'Inativo') . "\n";
        }
        exit;
    }

    private function exportPDF($clients) {
        // Implementar exportação PDF (simplificada)
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="clientes_' . date('Y-m-d') . '.pdf"');
        
        // Em uma implementação real, usar biblioteca como TCPDF ou Dompdf
        echo "%PDF-1.4\n";
        echo "1 0 obj\n";
        echo "<< /Type /Catalog /Pages 2 0 R >>\n";
        echo "endobj\n";
        echo "2 0 obj\n";
        echo "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        echo "endobj\n";
        echo "3 0 obj\n";
        echo "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\n";
        echo "endobj\n";
        echo "4 0 obj\n";
        echo "<< /Length 44 >>\n";
        echo "stream\n";
        echo "BT /F1 12 Tf 50 750 Td (Relatório de Clientes) Tj ET\n";
        echo "endstream\n";
        echo "endobj\n";
        echo "xref\n";
        echo "0 5\n";
        echo "0000000000 65535 f \n";
        echo "0000000009 00000 n \n";
        echo "0000000058 00000 n \n";
        echo "0000000115 00000 n \n";
        echo "0000000222 00000 n \n";
        echo "trailer\n";
        echo "<< /Size 5 /Root 1 0 R >>\n";
        echo "startxref\n";
        echo "309\n";
        echo "%%EOF";
        exit;
    }

    private function exportCSV($clients) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="clientes_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nome', 'Tipo', 'Categoria', 'CPF/CNPJ', 'Email', 'Telefone', 'Status']);
        
        foreach ($clients as $client) {
            fputcsv($output, [
                $client['fantasy_name'] ?: $client['name'],
                $client['type'],
                $client['client_category'],
                $client['cpf_cnpj'],
                $client['email'],
                $client['phone'],
                $client['is_active'] ? 'Ativo' : 'Inativo'
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Buscar sugestões para autocomplete
    public function search() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('clients')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $query = $_GET['q'] ?? '';
            $companyId = $_GET['company_id'] ?? null;
            
            if (empty($query)) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }

            $clients = $this->clientModel->getAll($companyId);
            
            // Filtrar por query
            $filteredClients = array_filter($clients, function($client) use ($query) {
                $searchableText = strtolower($client['fantasy_name'] ?: $client['name'] . ' ' . $client['cpf_cnpj'] . ' ' . $client['email']);
                return stripos($searchableText, strtolower($query)) !== false;
            });

            // Formatar para autocomplete
            $suggestions = array_map(function($client) {
                return [
                    'id' => $client['id'],
                    'text' => ($client['fantasy_name'] ?: $client['name']) . ' - ' . $client['cpf_cnpj'],
                    'name' => $client['fantasy_name'] ?: $client['name'],
                    'cpf_cnpj' => $client['cpf_cnpj'],
                    'email' => $client['email']
                ];
            }, array_slice($filteredClients, 0, 10)); // Limitar a 10 resultados

            echo json_encode([
                'success' => true,
                'data' => $suggestions
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro na busca: ' . $e->getMessage()]);
        }
        
        exit;
    }
}
?>