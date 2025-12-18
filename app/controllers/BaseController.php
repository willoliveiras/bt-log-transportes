<?php
// app/controllers/BaseController.php

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/VehicleModel.php';
require_once __DIR__ . '/../core/Session.php';

class BaseController {
    private $baseModel;
    private $companyModel;
    private $employeeModel;
    private $vehicleModel;
    private $session;

    public function __construct() {
        $this->baseModel = new BaseModel();
        $this->companyModel = new CompanyModel();
        $this->employeeModel = new EmployeeModel();
        $this->vehicleModel = new VehicleModel();
        $this->session = new Session();
        
        // Debug
        error_log("🎯 BaseController instanciado");
    }

    // ✅ MÉTODO PRINCIPAL: Listar bases com dashboard integrado
    public function index() {
        if (!$this->hasPermission('bases')) {
            $this->redirectToUnauthorized();
            return;
        }

        // Debug
        error_log("📊 BaseController::index() chamado");

        $companyFilter = $_GET['company'] ?? null;
        $stateFilter = $_GET['state'] ?? null;
        $statusFilter = $_GET['status'] ?? null;
        
        // ✅ CORREÇÃO: Buscar dados com parâmetros corretos
        $bases = $this->baseModel->getAll($companyFilter, true); // Incluir inativas para estatísticas
        
        // Debug
        error_log("📦 Bases encontradas: " . count($bases));
        
        // Aplicar filtros adicionais se necessário
        if ($stateFilter) {
            $bases = array_filter($bases, function($base) use ($stateFilter) {
                return $base['state'] === $stateFilter;
            });
        }
        
        if ($statusFilter === 'active') {
            $bases = array_filter($bases, function($base) {
                return $base['is_active'];
            });
        } elseif ($statusFilter === 'inactive') {
            $bases = array_filter($bases, function($base) {
                return !$base['is_active'];
            });
        }
        
        $companies = $this->companyModel->getForDropdown();
        $states = $this->baseModel->getStatesWithBases($companyFilter);
        
        // ✅ CORREÇÃO: Buscar funcionários e veículos para os modais
        $employees = $this->employeeModel->getAll($companyFilter, true);
        $vehicles = $this->vehicleModel->getAll($companyFilter, true);
        
        // Buscar estatísticas para o dashboard
        $stats = $this->calculateDashboardStatistics($bases);
        
        $pageTitle = 'Bases';
        $currentPage = 'bases';
        $pageScript = 'bases.js';
        
        // Debug final
        error_log("🎉 Dados preparados para view:");
        error_log("   - Bases: " . count($bases));
        error_log("   - Empresas: " . count($companies));
        error_log("   - Funcionários: " . count($employees));
        error_log("   - Veículos: " . count($vehicles));
        
        include '../app/views/layouts/header.php';
        include '../app/views/bases/list.php';
        include '../app/views/layouts/footer.php';
    }

    // ✅ MÉTODO: Calcular estatísticas para dashboard
    private function calculateDashboardStatistics($bases) {
        $totalBases = count($bases);
        $activeBases = array_filter($bases, function($base) {
            return $base['is_active'];
        });
        
        $inactiveBases = array_filter($bases, function($base) {
            return !$base['is_active'];
        });
        
        $totalCapacityVehicles = array_sum(array_column($bases, 'capacity_vehicles'));
        $totalCapacityDrivers = array_sum(array_column($bases, 'capacity_drivers'));
        $totalCurrentVehicles = array_sum(array_column($bases, 'total_vehicles'));
        $totalCurrentDrivers = array_sum(array_column($bases, 'total_drivers'));
        
        // Calcular utilização média
        $utilizationVehicles = $totalCapacityVehicles > 0 ? 
            min(100, round(($totalCurrentVehicles / $totalCapacityVehicles) * 100)) : 0;
        $utilizationDrivers = $totalCapacityDrivers > 0 ? 
            min(100, round(($totalCurrentDrivers / $totalCapacityDrivers) * 100)) : 0;
        
        // Bases por estado
        $basesByState = [];
        foreach ($bases as $base) {
            if ($base['state']) {
                $basesByState[$base['state']] = ($basesByState[$base['state']] ?? 0) + 1;
            }
        }
        
        // Bases sem gerente
        $basesWithoutManager = array_filter($bases, function($base) {
            return empty($base['manager_id']) && $base['is_active'];
        });
        
        return [
            'total_bases' => $totalBases,
            'active_bases' => count($activeBases),
            'inactive_bases' => count($inactiveBases),
            'total_capacity_vehicles' => $totalCapacityVehicles,
            'total_capacity_drivers' => $totalCapacityDrivers,
            'total_current_vehicles' => $totalCurrentVehicles,
            'total_current_drivers' => $totalCurrentDrivers,
            'utilization_vehicles' => $utilizationVehicles,
            'utilization_drivers' => $utilizationDrivers,
            'bases_by_state' => $basesByState,
            'states_count' => count($basesByState),
            'bases_without_manager' => count($basesWithoutManager),
            'avg_utilization' => round(($utilizationVehicles + $utilizationDrivers) / 2)
        ];
    }

    // ✅ MÉTODO: Verificar permissão do usuário
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        $allowedRoles = ['super_admin', 'admin', 'comercial'];
        return in_array($userRole, $allowedRoles);
    }

    // ✅ MÉTODO: Redirecionar para página não autorizada
    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }

    // ✅ MÉTODO: API para ações AJAX
    public function api() {
        header('Content-Type: application/json');
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Verificar se usuário está logado
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Não autorizado'
                ]);
                exit;
            }

            $action = $_GET['action'] ?? '';
            
            if (empty($action)) {
                throw new Exception('Ação não especificada');
            }

            switch ($action) {
                case 'getAll':
                    $companyId = $_GET['company_id'] ?? null;
                    $includeInactive = isset($_GET['include_inactive']) ? filter_var($_GET['include_inactive'], FILTER_VALIDATE_BOOLEAN) : false;
                    
                    $bases = $this->baseModel->getAll($companyId, $includeInactive);
                    
                    echo json_encode([
                        'success' => true,
                        'bases' => $bases
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Ação não reconhecida: ' . $action
                    ]);
            }

        } catch (Exception $e) {
            error_log("❌ [BaseController API] Erro: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
    }
}
?>