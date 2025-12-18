<?php
// app/controllers/FinancialController.php

require_once __DIR__ . '/../models/FinancialModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class FinancialController {
    private $financialModel;
    private $companyModel;
    private $session;

    public function __construct() {
        $this->financialModel = new FinancialModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    // Dashboard Financeiro
    public function index() {
        if (!$this->hasPermission('financial')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        $period = $_GET['period'] ?? 'month';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        // Ajustar datas baseado no período
        $dateRange = $this->calculateDateRange($period, $startDate, $endDate);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Buscar dados para o dashboard
        $financialData = $this->financialModel->getDashboardData($companyFilter, $startDate, $endDate);
        $companies = $this->companyModel->getForDropdown();
        
        // DEBUG: Verificar dados
        error_log("📊 [FINANCIAL] Dados carregados: " . json_encode($financialData['kpis']));
        
        $pageTitle = 'Dashboard Financeiro';
        $currentPage = 'financial';
        
        include '../app/views/layouts/header.php';
        include '../app/views/financial/dashboard.php';
        include '../app/views/layouts/footer.php';
    }

    // Calcular range de datas baseado no período
    private function calculateDateRange($period, $startDate, $endDate) {
        $today = new DateTime();
        
        switch ($period) {
            case 'week':
                $start = $today->modify('monday this week')->format('Y-m-d');
                $end = $today->modify('sunday this week')->format('Y-m-d');
                break;
                
            case 'month':
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                break;
                
            case 'quarter':
                $quarter = ceil(date('n') / 3);
                $start = date('Y-m-d', strtotime(date('Y') . '-' . (($quarter - 1) * 3 + 1) . '-01'));
                $end = date('Y-m-t', strtotime(date('Y') . '-' . ($quarter * 3) . '-01'));
                break;
                
            case 'year':
                $start = date('Y-01-01');
                $end = date('Y-12-31');
                break;
                
            case 'custom':
                $start = $startDate;
                $end = $endDate;
                break;
                
            default:
                $start = date('Y-m-01');
                $end = date('Y-m-t');
        }
        
        return ['start' => $start, 'end' => $end];
    }

    
	public function getPayableDataForDashboard($company_id, $period) {
		require_once '../app/models/AccountsPayableModel.php';
		$model = new AccountsPayableModel();
		
		return [
			'payable_summary' => $model->getFinancialSummary($company_id, $period),
			'overdue_accounts' => $model->getOverdueAccounts($company_id),
			'upcoming_payments' => $model->getUpcomingPayments($company_id)
		];
	}
	
	// API para dados do dashboard
    public function getDashboardData() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('financial')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $companyFilter = $_GET['company'] ?? null;
            $period = $_GET['period'] ?? 'month';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $dateRange = $this->calculateDateRange($period, $startDate, $endDate);
            
            $data = $this->financialModel->getDashboardData($companyFilter, $dateRange['start'], $dateRange['end']);
            
            // DEBUG
            error_log("📊 [FINANCIAL API] Dados enviados: " . json_encode($data['kpis']));
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        $allowedRoles = ['super_admin', 'admin', 'financeiro'];
        return in_array($userRole, $allowedRoles);
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
}
?>