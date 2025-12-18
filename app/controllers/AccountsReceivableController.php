<?php
require_once '../app/core/Database.php';
require_once '../app/core/Auth.php';
require_once '../app/middleware/AuthMiddleware.php';
require_once '../app/models/FinancialModel.php';

class AccountsPayableController {
    private $db;
    private $financialModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->financialModel = new FinancialModel();
    }

    public function index() {
        // Verificar permissões
        AuthMiddleware::checkPermission(['super_admin', 'admin', 'financeiro']);
        
        $company_id = $_SESSION['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month';
        
        // Buscar contas a pagar
        $accounts = $this->financialModel->getAccountsPayable($company_id, $period);
        
        // Buscar fornecedores
        $suppliers = $this->financialModel->getSuppliers($company_id);
        
        // Buscar plano de contas
        $chartAccounts = $this->financialModel->getChartOfAccounts($company_id);
        
        include '../app/views/financial/accounts_payable.php';
    }

    public function save() {
        AuthMiddleware::checkPermission(['super_admin', 'admin', 'financeiro']);
        
        if ($_POST) {
            try {
                $data = [
                    'company_id' => $_SESSION['company_id'],
                    'chart_account_id' => $_POST['chart_account_id'],
                    'description' => trim($_POST['description']),
                    'amount' => floatval(str_replace(['.', ','], ['', '.'], $_POST['amount'])),
                    'due_date' => $_POST['due_date'],
                    'status' => 'pendente',
                    'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
                    'recurrence_frequency' => $_POST['recurrence_frequency'] ?? null,
                    'supplier' => trim($_POST['supplier'] ?? ''),
                    'notes' => trim($_POST['notes'] ?? '')
                ];
                
                // Validações
                if (empty($data['description'])) {
                    throw new Exception('Descrição é obrigatória.');
                }
                
                if ($data['amount'] <= 0) {
                    throw new Exception('Valor deve ser maior que zero.');
                }
                
                if (empty($data['due_date'])) {
                    throw new Exception('Data de vencimento é obrigatória.');
                }
                
                $result = $this->financialModel->saveAccountPayable($data);
                
                if ($result) {
                    $_SESSION['alert'] = [
                        'type' => 'success', 
                        'message' => 'Conta a pagar salva com sucesso!'
                    ];
                } else {
                    throw new Exception('Erro ao salvar conta a pagar no banco de dados.');
                }
                
            } catch (Exception $e) {
                $_SESSION['alert'] = [
                    'type' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
            
            header('Location: index.php?page=accounts_payable');
            exit;
        }
    }

    public function markPaid() {
        AuthMiddleware::checkPermission(['super_admin', 'admin', 'financeiro']);
        
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $result = $this->financialModel->markAccountPaid($id, $_SESSION['company_id']);
                
                if ($result) {
                    $_SESSION['alert'] = [
                        'type' => 'success', 
                        'message' => 'Conta marcada como paga!'
                    ];
                } else {
                    throw new Exception('Erro ao marcar conta como paga.');
                }
            } catch (Exception $e) {
                $_SESSION['alert'] = [
                    'type' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
        }
        
        header('Location: index.php?page=accounts_payable');
        exit;
    }

    public function delete() {
        AuthMiddleware::checkPermission(['super_admin', 'admin']);
        
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $result = $this->financialModel->deleteAccountPayable($id, $_SESSION['company_id']);
                
                if ($result) {
                    $_SESSION['alert'] = [
                        'type' => 'success', 
                        'message' => 'Conta excluída com sucesso!'
                    ];
                } else {
                    throw new Exception('Erro ao excluir conta.');
                }
            } catch (Exception $e) {
                $_SESSION['alert'] = [
                    'type' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
        }
        
        header('Location: index.php?page=accounts_payable');
        exit;
    }

    public function saveSupplier() {
        AuthMiddleware::checkPermission(['super_admin', 'admin', 'financeiro']);
        
        if ($_POST) {
            try {
                $data = [
                    'company_id' => $_SESSION['company_id'],
                    'name' => trim($_POST['supplier_name']),
                    'contact' => trim($_POST['supplier_contact'] ?? ''),
                    'email' => trim($_POST['supplier_email'] ?? ''),
                    'phone' => trim($_POST['supplier_phone'] ?? ''),
                    'address' => trim($_POST['supplier_address'] ?? '')
                ];
                
                if (empty($data['name'])) {
                    throw new Exception('Nome do fornecedor é obrigatório.');
                }
                
                $result = $this->financialModel->saveSupplier($data);
                
                if ($result) {
                    $_SESSION['alert'] = [
                        'type' => 'success', 
                        'message' => 'Fornecedor salvo com sucesso!'
                    ];
                } else {
                    throw new Exception('Erro ao salvar fornecedor.');
                }
                
            } catch (Exception $e) {
                $_SESSION['alert'] = [
                    'type' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
            
            header('Location: index.php?page=accounts_payable');
            exit;
        }
    }

    // Gerar conta a pagar automaticamente quando viagem é concluída
    public function generateFromTrip($tripId) {
        try {
            $trip = $this->financialModel->getTripById($tripId);
            
            if ($trip && $trip['status'] === 'concluida') {
                $tripExpenses = $this->financialModel->getTripExpenses($tripId);
                $totalExpenses = 0;
                
                foreach ($tripExpenses as $expense) {
                    $totalExpenses += floatval($expense['amount']);
                }
                
                if ($totalExpenses > 0) {
                    $data = [
                        'company_id' => $trip['company_id'],
                        'chart_account_id' => $this->financialModel->getExpenseAccountId(),
                        'description' => "Despesas da viagem #{$trip['trip_number']} - {$trip['description']}",
                        'amount' => $totalExpenses,
                        'due_date' => date('Y-m-d', strtotime('+15 days')),
                        'status' => 'pendente',
                        'is_recurring' => 0,
                        'supplier' => 'Vários Fornecedores',
                        'notes' => "Gerado automaticamente da viagem #{$trip['trip_number']}. Despesas: " . 
                                  implode(', ', array_column($tripExpenses, 'expense_type'))
                    ];
                    
                    $this->financialModel->saveAccountPayable($data);
                    
                    // Log da geração automática
                    error_log("Conta a pagar gerada automaticamente para viagem #{$tripId} - Valor: R$ {$totalExpenses}");
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao gerar conta a pagar da viagem #{$tripId}: " . $e->getMessage());
        }
    }

    // API para dashboard
    public function getDashboardData() {
        AuthMiddleware::checkPermission(['super_admin', 'admin', 'financeiro']);
        
        $company_id = $_SESSION['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month';
        
        try {
            $data = [
                'total_payable' => $this->financialModel->getTotalAccountsPayable($company_id, $period),
                'pending_count' => $this->financialModel->getPendingAccountsCount($company_id),
                'overdue_count' => $this->financialModel->getOverdueAccountsCount($company_id),
                'next_due_accounts' => $this->financialModel->getNextDueAccounts($company_id, 5)
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>