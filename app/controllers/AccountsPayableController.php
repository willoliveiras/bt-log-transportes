<?php
// app/controllers/AccountsPayableController.php

require_once __DIR__ . '/../models/AccountsPayableModel.php';
require_once __DIR__ . '/../core/Session.php';


class AccountsPayableController {
    private $payableModel;
    private $session;
    private $auth;

    public function __construct() {
        error_log("🎯 [CONTROLLER] Iniciando construtor AccountsPayableController...");
        
        try {
            $this->payableModel = new AccountsPayableModel();
            error_log("✅ [CONTROLLER] AccountsPayableModel instanciado com sucesso");
        } catch (Error $e) {
            error_log("❌ [CONTROLLER] Erro ao instanciar AccountsPayableModel: " . $e->getMessage());
            die("Erro ao inicializar o sistema. Verifique os logs.");
        }
        
        try {
            $this->session = new Session();
            error_log("✅ [CONTROLLER] Session instanciada com sucesso");
        } catch (Error $e) {
            error_log("❌ [CONTROLLER] Erro ao instanciar Session: " . $e->getMessage());
            die("Erro ao inicializar a sessão. Verifique os logs.");
        }
        
        try {
            $this->auth = new Auth();
            error_log("✅ [CONTROLLER] Auth instanciada com sucesso");
        } catch (Error $e) {
            error_log("❌ [CONTROLLER] Erro ao instanciar Auth: " . $e->getMessage());
        }
    }

    public function index() {
        error_log("🎯 [ACCOUNTS PAYABLE] Iniciando método index()...");
        
        // Verificar permissões
        if (!$this->hasPermission('financial')) {
            error_log("❌ [ACCOUNTS PAYABLE] Sem permissão para acessar contas a pagar");
            $this->redirectToUnauthorized();
            return;
        }

        // ✅ Obter company_id da sessão (com fallback)
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            error_log("⚠️ [ACCOUNTS PAYABLE] Company ID não encontrado na sessão, usando fallback");
            $company_id = 1; // Fallback para desenvolvimento
            $_SESSION['company_id'] = $company_id;
        }
        
        error_log("🔍 [ACCOUNTS PAYABLE] Company ID: " . $company_id);
        
        // ✅ Obter filtros da requisição
        $filters = [
            'status' => $_GET['status'] ?? null,
            'period' => $_GET['period'] ?? 'month',
            'supplier_id' => $_GET['supplier'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        error_log("🔍 [ACCOUNTS PAYABLE] Filtros: " . json_encode($filters));
        
        try {
            // ✅ 1. BUSCAR CONTAS A PAGAR
            $accounts = $this->payableModel->getByCompany($company_id, $filters);
            error_log("📊 [ACCOUNTS PAYABLE] " . count($accounts) . " contas encontradas");
            
            // ✅ 2. CALCULAR ESTATÍSTICAS
            $stats = $this->calculateStats($accounts);
            error_log("📈 [ACCOUNTS PAYABLE] Estatísticas calculadas");
            
            // ✅ 3. BUSCAR FORNECEDORES
            $suppliers = $this->payableModel->getSuppliersByCompany($company_id);
            error_log("🏭 [ACCOUNTS PAYABLE] " . count($suppliers) . " fornecedores encontrados");
            
            // ✅ 4. BUSCAR PLANO DE CONTAS
            $chartAccounts = $this->payableModel->getChartOfAccounts($company_id);
            error_log("📋 [ACCOUNTS PAYABLE] " . count($chartAccounts) . " contas contábeis encontradas");
            
            // ✅ 5. BUSCAR EMPRESAS
            $companies = $this->payableModel->getAllActiveCompanies();
            error_log("🏢 [ACCOUNTS PAYABLE] " . count($companies) . " empresas encontradas");
            
            // ✅ 6. BUSCAR FUNCIONÁRIOS PARA GERENTES (se necessário)
            $employees = [];
            if (method_exists($this->payableModel, 'getEmployeesByCompany')) {
                $employees = $this->payableModel->getEmployeesByCompany($company_id);
                error_log("👥 [ACCOUNTS PAYABLE] " . count($employees) . " funcionários encontrados");
            }
            
            // ✅ 7. BUSCAR VEÍCULOS (se necessário)
            $vehicles = [];
            if (method_exists($this->payableModel, 'getVehiclesByCompany')) {
                $vehicles = $this->payableModel->getVehiclesByCompany($company_id);
                error_log("🚛 [ACCOUNTS PAYABLE] " . count($vehicles) . " veículos encontrados");
            }
            
            // ✅ DEBUG: Verificar dados antes de passar para a view
            error_log("📋 [ACCOUNTS PAYABLE] Dados preparados:");
            error_log("📊 Stats keys: " . implode(', ', array_keys($stats)));
            error_log("📊 Total amount: " . $stats['total_amount']);
            error_log("📊 Total count: " . $stats['total_count']);
            
            // ✅ Configurar dados para a view
            $pageTitle = 'Contas a Pagar';
            $currentPage = 'financial';
            
            // ✅ Incluir a view com todos os dados necessários
            $viewPath = '../app/views/financial/accounts_payable_list.php';
            
            if (file_exists($viewPath)) {
                error_log("✅ [ACCOUNTS PAYABLE] View encontrada: " . $viewPath);
                include $viewPath;
            } else {
                error_log("❌ [ACCOUNTS PAYABLE] View não encontrada: " . $viewPath);
                echo "Erro: View não encontrada. Caminho: " . $viewPath;
            }
            
        } catch (Exception $e) {
            error_log("❌ [ACCOUNTS PAYABLE] Erro no método index(): " . $e->getMessage());
            error_log("❌ [ACCOUNTS PAYABLE] Trace: " . $e->getTraceAsString());
            
            // Exibir erro amigável
            echo "<div style='padding: 20px; background: #ffebee; color: #c62828; border-radius: 8px; margin: 20px;'>";
            echo "<h3>Erro ao carregar contas a pagar</h3>";
            echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
            echo "<p>Por favor, verifique os logs para mais detalhes.</p>";
            echo "</div>";
        }
    }

    /**
     * Calcular estatísticas das contas a pagar
     */
    private function calculateStats($accounts) {
        error_log("📈 [ACCOUNTS PAYABLE] Calculando estatísticas para " . count($accounts) . " contas...");
        
        $stats = [
            'total_amount' => 0,
            'total_count' => count($accounts),
            'pending_amount' => 0,
            'pending_count' => 0,
            'paid_amount' => 0,
            'paid_count' => 0,
            'overdue_amount' => 0,
            'overdue_count' => 0,
            'due_soon_amount' => 0,
            'due_soon_count' => 0,
            'average_amount' => 0,
            'recurring_count' => 0,
            'trend' => 0, // + ou - porcentagem
            'overdue_30_count' => 0,
            'overdue_60_count' => 0
        ];
        
        if (empty($accounts)) {
            error_log("📈 [ACCOUNTS PAYABLE] Nenhuma conta para calcular estatísticas");
            return $stats;
        }
        
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        
        foreach ($accounts as $account) {
            $amount = floatval($account['amount'] ?? 0);
            $status = $account['status'] ?? 'pendente';
            $dueDate = $account['due_date'] ?? $today;
            $isRecurring = isset($account['is_recurring']) && $account['is_recurring'] == 1;
            
            // Total geral
            $stats['total_amount'] += $amount;
            
            // Por status
            if ($status === 'pendente') {
                $stats['pending_amount'] += $amount;
                $stats['pending_count']++;
                
                // Verificar se está atrasado
                if ($dueDate < $today) {
                    $stats['overdue_amount'] += $amount;
                    $stats['overdue_count']++;
                    
                    // Dias de atraso
                    $daysOverdue = floor((strtotime($today) - strtotime($dueDate)) / (60 * 60 * 24));
                    if ($daysOverdue > 30) {
                        $stats['overdue_30_count']++;
                    }
                    if ($daysOverdue > 60) {
                        $stats['overdue_60_count']++;
                    }
                } 
                // Verificar se vence em breve (próximos 7 dias)
                elseif ($dueDate <= $nextWeek && $dueDate >= $today) {
                    $stats['due_soon_amount'] += $amount;
                    $stats['due_soon_count']++;
                }
            } elseif ($status === 'pago') {
                $stats['paid_amount'] += $amount;
                $stats['paid_count']++;
            }
            
            // Contar recorrentes
            if ($isRecurring) {
                $stats['recurring_count']++;
            }
        }
        
        // Calcular média
        $stats['average_amount'] = $stats['total_count'] > 0 
            ? $stats['total_amount'] / $stats['total_count'] 
            : 0;
        
        // Simular tendência (em produção, comparar com período anterior)
        $stats['trend'] = rand(-10, 10); // Simulação
        
        error_log("📈 [ACCOUNTS PAYABLE] Estatísticas calculadas:");
        error_log("📈 Total: R$ " . number_format($stats['total_amount'], 2, ',', '.'));
        error_log("📈 Pendentes: " . $stats['pending_count']);
        error_log("📈 Atrasadas: " . $stats['overdue_count']);
        
        return $stats;
    }

    public function save() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $validation = $this->validatePayableData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            // ✅ Obter company_id da sessão
            $company_id = $_SESSION['company_id'] ?? 1;
            
            // ✅ Determinar o tipo de fornecedor
            $supplierType = $_POST['supplier_type'] ?? 'custom';
            $supplierName = '';
            
            if ($supplierType === 'registered') {
                $supplierId = $_POST['supplier_selection'] ?? 0;
                // Buscar nome do fornecedor pelo ID
                $suppliers = $this->payableModel->getSuppliersByCompany($company_id);
                foreach ($suppliers as $supplier) {
                    if ($supplier['id'] == $supplierId) {
                        $supplierName = $supplier['name'];
                        break;
                    }
                }
            } else {
                $supplierName = trim($_POST['supplier_custom'] ?? '');
            }

            // ✅ Preparar dados
            $payableData = [
                'company_id' => $company_id,
                'chart_account_id' => $_POST['chart_account_id'],
                'description' => trim($_POST['description']),
                'amount' => $this->parseCurrency($_POST['amount']),
                'due_date' => $_POST['due_date'],
                'status' => $_POST['status'] ?? 'pendente',
                'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
                'recurrence_frequency' => $_POST['recurrence_frequency'] ?? null,
                'recurrence_end_date' => $_POST['recurrence_end_date'] ?? null,
                'recurrence_count' => $_POST['recurrence_count'] ?? null,
                'supplier' => $supplierName,
                'notes' => trim($_POST['notes'] ?? ''),
                'payment_date' => ($_POST['status'] == 'pago') ? ($_POST['payment_date'] ?? date('Y-m-d')) : null,
                'created_by' => $_SESSION['user_id'] ?? null
            ];

            error_log("💾 [ACCOUNTS PAYABLE] Salvando conta: " . json_encode($payableData));

            $success = $this->payableModel->create($payableData);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Conta a pagar salva com sucesso!'
                ]);
            } else {
                $error = $this->payableModel->getError();
                throw new Exception('Erro ao salvar conta: ' . $error);
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

    public function saveSupplier() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $validation = $this->validateSupplierData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            $company_id = $_SESSION['company_id'] ?? 1;

            $supplierData = [
                'company_id' => $company_id,
                'name' => trim($_POST['supplier_name']),
                'fantasy_name' => trim($_POST['fantasy_name'] ?? ''),
                'cpf_cnpj' => trim($_POST['cpf_cnpj'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'is_active' => 1,
                'created_by' => $_SESSION['user_id'] ?? null
            ];

            error_log("🏭 [ACCOUNTS PAYABLE] Salvando fornecedor: " . json_encode($supplierData));

            $success = $this->payableModel->createSupplier($supplierData);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Fornecedor salvo com sucesso!',
                    'data' => ['id' => $this->payableModel->getLastInsertId()]
                ]);
            } else {
                $error = $this->payableModel->getError();
                throw new Exception('Erro ao salvar fornecedor: ' . $error);
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

    public function getSuppliers() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $company_id = $_SESSION['company_id'] ?? 1;
            
            $suppliers = $this->payableModel->getSuppliersByCompany($company_id);
            
            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao buscar fornecedores: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function markPaid($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $data = [
                'status' => 'pago',
                'payment_date' => date('Y-m-d'),
                'updated_by' => $_SESSION['user_id'] ?? null
            ];
            
            $success = $this->payableModel->update($id, $data);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Conta marcada como paga!'
                ]);
            } else {
                $error = $this->payableModel->getError();
                throw new Exception('Erro ao marcar conta como paga: ' . $error);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao marcar conta: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function reopen($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $data = [
                'status' => 'pendente',
                'payment_date' => null,
                'updated_by' => $_SESSION['user_id'] ?? null
            ];
            
            $success = $this->payableModel->update($id, $data);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Conta reaberta com sucesso!'
                ]);
            } else {
                $error = $this->payableModel->getError();
                throw new Exception('Erro ao reabrir conta: ' . $error);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao reabrir conta: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $success = $this->payableModel->delete($id);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Conta excluída com sucesso!'
                ]);
            } else {
                $error = $this->payableModel->getError();
                throw new Exception('Erro ao excluir conta: ' . $error);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao excluir conta: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function get($id) {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $company_id = $_SESSION['company_id'] ?? 1;
            $account = $this->payableModel->getById($id, $company_id);

            if ($account) {
                echo json_encode([
                    'success' => true,
                    'data' => $account
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Conta não encontrada'
                ]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao buscar conta: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function filter() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $company_id = $_SESSION['company_id'] ?? 1;
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'period' => $_GET['period'] ?? null,
                'supplier_id' => $_GET['supplier'] ?? null,
                'search' => $_GET['search'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null
            ];
            
            error_log("🔍 [ACCOUNTS PAYABLE] Filtrando com: " . json_encode($filters));
            
            $accounts = $this->payableModel->getByCompany($company_id, $filters);
            $stats = $this->calculateStats($accounts);
            
            echo json_encode([
                'success' => true,
                'data' => $accounts,
                'stats' => $stats,
                'count' => count($accounts)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao filtrar contas: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function export() {
        if (!$this->hasPermission('financial')) {
            header('Location: /unauthorized');
            exit;
        }

        try {
            $company_id = $_SESSION['company_id'] ?? 1;
            $format = $_GET['format'] ?? 'excel';
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null
            ];
            
            $accounts = $this->payableModel->getByCompany($company_id, $filters);
            
            if ($format === 'pdf') {
                $this->exportPDF($accounts);
            } else {
                $this->exportExcel($accounts);
            }

        } catch (Exception $e) {
            error_log("❌ [ACCOUNTS PAYABLE] Erro ao exportar: " . $e->getMessage());
            header('Location: /financial/accounts_payable?error=export_failed');
            exit;
        }
    }

    private function exportPDF($accounts) {
        // Implementação básica de exportação PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="contas_a_pagar_' . date('Y-m-d') . '.pdf"');
        
        // Aqui você implementaria a geração de PDF
        // Por enquanto, apenas um exemplo simples
        echo "%PDF-1.4\n%....\n";
        echo "1 0 obj\n<</Type /Catalog /Pages 2 0 R>>\nendobj\n";
        echo "%%EOF";
        exit;
    }

    private function exportExcel($accounts) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="contas_a_pagar_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Descrição</th><th>Valor</th><th>Vencimento</th><th>Status</th><th>Fornecedor</th></tr>";
        
        foreach ($accounts as $account) {
            echo "<tr>";
            echo "<td>" . ($account['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($account['description'] ?? '') . "</td>";
            echo "<td>" . number_format($account['amount'] ?? 0, 2, ',', '.') . "</td>";
            echo "<td>" . ($account['due_date'] ?? '') . "</td>";
            echo "<td>" . ($account['status'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($account['supplier'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit;
    }

    // Métodos auxiliares privados
    private function validatePayableData($data) {
        $errors = [];

        if (empty(trim($data['description'] ?? ''))) {
            $errors[] = 'Descrição é obrigatória';
        }

        $amount = $this->parseCurrency($data['amount'] ?? 0);
        if (empty($data['amount']) || $amount <= 0) {
            $errors[] = 'Valor deve ser maior que zero';
        }

        if (empty($data['due_date'])) {
            $errors[] = 'Data de vencimento é obrigatória';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
            $errors[] = 'Data de vencimento inválida';
        }

        if (empty($data['chart_account_id'])) {
            $errors[] = 'Conta contábil é obrigatória';
        }

        // Validar status
        $validStatuses = ['pendente', 'pago', 'atrasado'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = 'Status inválido';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    private function validateSupplierData($data) {
        $errors = [];

        if (empty(trim($data['supplier_name'] ?? ''))) {
            $errors[] = 'Nome do fornecedor é obrigatório';
        }

        // Validar CPF/CNPJ se informado
        if (!empty($data['cpf_cnpj'])) {
            $cpf_cnpj = preg_replace('/[^0-9]/', '', $data['cpf_cnpj']);
            
            if (strlen($cpf_cnpj) == 11) {
                // Validar CPF
                if (!$this->validateCPF($cpf_cnpj)) {
                    $errors[] = 'CPF inválido';
                }
            } elseif (strlen($cpf_cnpj) == 14) {
                // Validar CNPJ
                if (!$this->validateCNPJ($cpf_cnpj)) {
                    $errors[] = 'CNPJ inválido';
                }
            } else {
                $errors[] = 'CPF/CNPJ inválido';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    private function validateCPF($cpf) {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se foi informado todos os digitos
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se foi informada uma sequência de digitos repetidos
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Calcula os dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }

    private function validateCNPJ($cnpj) {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica se foi informado todos os digitos
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verifica se foi informada uma sequência de digitos repetidos
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }
        
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[13] != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }
        
        return true;
    }

    private function parseCurrency($value) {
        // Remove "R$ " e formatação
        $value = str_replace('R$ ', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return floatval($value);
    }

    private function hasPermission($resource) {
        // Verificar se usuário está autenticado
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            error_log("❌ [PERMISSION] Usuário não autenticado");
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? 'guest';
        $allowedRoles = ['super_admin', 'admin', 'financeiro'];
        
        $hasPermission = in_array($userRole, $allowedRoles);
        
        error_log("👤 [PERMISSION] Usuário role: {$userRole}, Permissão para {$resource}: " . ($hasPermission ? 'SIM' : 'NÃO'));
        
        return $hasPermission;
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
    
    /**
     * Método auxiliar para debug
     */
    private function debugData($data, $label = 'DEBUG') {
        error_log("🔍 [{$label}] " . print_r($data, true));
    }
}