<?php
// app/controllers/PayrollController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/PayrollModel.php';

class PayrollController {
    private $payrollModel;

    public function __construct() {
        $this->payrollModel = new PayrollModel();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Listar folha de pagamento
    public function index() {
        if (!$this->hasPermission('payroll')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $monthFilter = $_GET['month'] ?? date('Y-m');
        $statusFilter = $_GET['status'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        
        // Buscar dados
        $payrollData = $this->payrollModel->getAll($companyFilter, $monthFilter, $statusFilter, $page);
        $payrolls = $payrollData['data'];
        
        $companies = $this->payrollModel->getCompaniesForDropdown();
        $months = $this->payrollModel->generateMonthOptions();
        $payrollStats = $this->payrollModel->getPayrollStats($companyFilter, $monthFilter);
        
        $pageTitle = 'Folha de Pagamento - ' . APP_NAME;
        
        include __DIR__ . '/../views/layouts/header.php';
        include __DIR__ . '/../views/payroll/list.php';
        include __DIR__ . '/../views/layouts/footer.php';
    }

    // Gerar folha de pagamento
    public function generate() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('payroll')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
            exit;
        }

        try {
            $companyId = $_POST['company_id'] ?? null;
            $referenceMonth = $_POST['reference_month'] ?? date('Y-m');
            
            if (!$companyId) {
                throw new Exception('Empresa não especificada');
            }

            // Verificar se folha já existe
            if ($this->payrollModel->payrollExists($companyId, $referenceMonth)) {
                $monthInfo = $this->payrollModel->getPayrollMonthInfo($companyId, $referenceMonth);
                $createdAt = date('d/m/Y H:i', strtotime($monthInfo['created_at']));
                
                throw new Exception(
                    "Folha de {$referenceMonth} já foi gerada! " .
                    "{$monthInfo['total_records']} registros criados em {$createdAt}. " .
                    "Exclua a folha existente para gerar novamente."
                );
            }

            $generatedCount = $this->payrollModel->generatePayroll($companyId, $referenceMonth);

            if ($generatedCount > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "✅ Folha gerada com sucesso! {$generatedCount} funcionários processados.",
                    'count' => $generatedCount
                ]);
            } else {
                throw new Exception('Nenhum funcionário ativo encontrado para processar');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Excluir folha do mês
    public function deletePayroll() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('payroll')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
            exit;
        }

        try {
            $companyId = $_POST['company_id'] ?? null;
            $referenceMonth = $_POST['reference_month'] ?? null;
            
            if (!$companyId || !$referenceMonth) {
                throw new Exception('Empresa e mês de referência são obrigatórios');
            }

            if (!$this->payrollModel->payrollExists($companyId, $referenceMonth)) {
                throw new Exception("Folha de {$referenceMonth} não encontrada para exclusão");
            }

            $monthInfo = $this->payrollModel->getPayrollMonthInfo($companyId, $referenceMonth);
            $success = $this->payrollModel->deletePayrollByMonth($companyId, $referenceMonth);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => "✅ Folha de {$referenceMonth} excluída com sucesso! " .
                               "{$monthInfo['total_records']} registros removidos."
                ]);
            } else {
                throw new Exception('Erro ao excluir folha de pagamento');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Marcar como pago
    public function markAsPaid() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('payroll')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
            exit;
        }

        try {
            $payrollId = $_POST['payroll_id'] ?? null;
            $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
            
            if (!$payrollId) {
                throw new Exception('ID da folha não informado');
            }

            $success = $this->payrollModel->markAsPaid($payrollId, $paymentDate);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => '✅ Pagamento registrado com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao registrar pagamento');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Estornar pagamento
    public function reversePayment() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('payroll')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
            exit;
        }

        try {
            $payrollId = $_POST['payroll_id'] ?? null;
            
            if (!$payrollId) {
                throw new Exception('ID da folha não informado');
            }

            $success = $this->payrollModel->reversePayment($payrollId);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => '✅ Pagamento estornado com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao estornar pagamento');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Buscar detalhes da folha
    public function getDetails() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('payroll')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
            exit;
        }

        try {
            $payrollId = $_GET['id'] ?? null;
            
            if (!$payrollId) {
                throw new Exception('ID não informado');
            }

            $details = $this->payrollModel->getPayrollDetails($payrollId);

            if ($details) {
                $formattedDetails = $this->formatPayrollDetails($details);
                echo json_encode([
                    'success' => true,
                    'data' => $formattedDetails
                ]);
            } else {
                throw new Exception('Registro não encontrado');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Recalcular comissões das viagens
    public function recalculateCommissions() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('payroll')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
            exit;
        }

        try {
            $updatedCount = $this->payrollModel->recalculateTripCommissions();
            
            echo json_encode([
                'success' => true,
                'message' => "✅ {$updatedCount} comissões recalculadas com sucesso!",
                'count' => $updatedCount
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => '❌ Erro ao recalcular comissões: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // Exportar folha
    public function export() {
        if (!$this->hasPermission('payroll')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $monthFilter = $_GET['month'] ?? date('Y-m');
        $format = $_GET['format'] ?? 'excel';
        
        $payrollData = $this->payrollModel->getAll($companyFilter, $monthFilter);
        $payrolls = $payrollData['data'];
        
        if ($format === 'excel') {
            $this->exportToExcel($payrolls, $monthFilter);
        } else {
            $this->exportToPDF($payrolls, $monthFilter);
        }
        
        exit;
    }

    private function exportToPDF($payrolls, $month) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Exportação PDF em desenvolvimento',
            'data' => [
                'records' => count($payrolls),
                'month' => $month
            ]
        ]);
    }

    private function exportToExcel($payrolls, $month) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Exportação Excel em desenvolvimento',
            'data' => [
                'records' => count($payrolls),
                'month' => $month
            ]
        ]);
    }

    // Formatar detalhes da folha
    private function formatPayrollDetails($payrollData) {
        return [
            'id' => $payrollData['id'],
            'employee_name' => $payrollData['employee_name'],
            'position' => $payrollData['position'],
            'cpf' => $this->formatCPF($payrollData['cpf']),
            'company_name' => $payrollData['company_name'],
            'reference_month' => $payrollData['reference_month'],
            'base_salary' => floatval($payrollData['base_salary']),
            'commissions' => floatval($payrollData['commissions']),
            'benefits' => floatval($payrollData['benefits']),
            'discounts' => floatval($payrollData['discounts']),
            'net_salary' => floatval($payrollData['net_salary']),
            'status' => $payrollData['status'],
            'payment_date' => $payrollData['payment_date'],
            'breakdown' => $payrollData['breakdown'] ?? []
        ];
    }

    private function formatCPF($cpf) {
        if (strlen($cpf) == 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return $cpf;
    }

    // Verificar permissão
    private function hasPermission($resource) {
        $userRole = $_SESSION['user_role'] ?? 'comercial';
        
        $permissions = [
            'super_admin' => ['payroll', 'financial', 'reports', 'companies', 'employees', 'drivers', 'vehicles', 'clients', 'trips'],
            'admin' => ['payroll', 'financial', 'reports', 'employees', 'drivers', 'vehicles', 'clients', 'trips'],
            'financeiro' => ['payroll', 'financial', 'reports'],
            'comercial' => ['clients', 'trips']
        ];
        
        return in_array($resource, $permissions[$userRole] ?? []);
    }

    private function redirectToUnauthorized() {
        header('Location: ' . APP_URL . '/index.php?page=unauthorized');
        exit;
    }
}
?>