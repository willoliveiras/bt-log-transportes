<?php
// public/index.php - NO INÍCIO
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sessão apenas uma vez
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ DEFINIR BASE_PATH CORRETAMENTE
$base_path = dirname(__DIR__); // Isso vai para a raiz do projeto (bt-log-transportes)


require_once '../app/config/config.php';
require_once '../app/config/database.php';
require_once '../app/core/Database.php';
require_once '../app/core/Session.php';
require_once '../app/middleware/AuthMiddleware.php';
require_once '../app/middleware/LogMiddleware.php';

// Inicializar middlewares
$authMiddleware = new AuthMiddleware();
$logMiddleware = new LogMiddleware();

// Determinar a rota atual
$page = $_GET['page'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'];

// Log da requisição
$logMiddleware->logRequest($page, $method);

// Verificar autenticação e permissões
if (!$authMiddleware->handle($page)) {
    exit;
}

// Carregar modelos necessários
require_once '../app/models/UserModel.php';

// Roteamento principal
switch ($page) {
    case 'dashboard':
        require_once '../app/views/dashboard/index.php';
        break;
        
    case 'companies':
        require_once '../app/controllers/CompanyController.php';
        $controller = new CompanyController();
        $controller->index(); // Chama o método index do controller
        break;
		
	case 'services':
		require_once '../app/controllers/ServiceController.php';
		$controller = new ServiceController();
		$controller->index();
		break;
		
	case 'employees':
		require_once '../app/views/employees/list.php';
		break;
		
	case 'drivers':
		require_once '../app/views/drivers/list.php';
		break;
		
	case 'vehicles':
		require_once '../app/controllers/VehicleController.php';
		$controller = new VehicleController();
		$controller->index();
		break;
	
	case 'bases':
		require_once '../app/controllers/BaseController.php';
		$controller = new BaseController();
		$controller->index();
		break;
		
	case 'clients':
		require_once '../app/controllers/ClientController.php';
		$controller = new ClientController();
		$controller->index();
		break;
	
	case 'trips':
		require_once '../app/controllers/TripController.php';
		$controller = new TripController();
		$controller->index();
		break;
	
	case 'maintenance':
		require_once '../app/controllers/MaintenanceController.php';
		$controller = new MaintenanceController();
		$controller->index();
		break;
		
	case 'payroll':
        require_once '../app/controllers/PayrollController.php';
        $controller = new PayrollController();
        $controller->index();
        break;
        
    case 'costs':
        require_once '../app/controllers/CostController.php';
        $controller = new CostController();
        $controller->index();
        break;
		
	case 'suppliers':
		require_once '../app/controllers/SuppliersController.php';
		$controller = new SuppliersController();
		$controller->index();
		break;
		
	case 'financial':
        require_once '../app/controllers/FinancialController.php';
        $controller = new FinancialController();
        $controller->index();
        break;
		
	case 'accounts_payable':
		require_once '../app/controllers/AccountsPayableController.php';
		$controller = new AccountsPayableController();
		$controller->index();
		break;
		
	case 'contracts':
		require_once '../app/controllers/ContractController.php';
		$controller = new ContractController();
		$controller->index();
		break;
		
	case 'accounts_receivable':
		require_once '../app/controllers/AccountsReceivableController.php';
		$controller = new AccountsReceivableController();
		
		$action = $_GET['action'] ?? 'index';
		switch ($action) {
			case 'create':
				$controller->create();
				break;
			case 'update':
				$controller->update($_GET['id']);
				break;
			case 'mark_received':
				$controller->markAsReceived($_GET['id']);
				break;
			case 'delete':
				$controller->delete($_GET['id']);
				break;
			case 'api':
				$controller->getApiData();
				break;
			default:
				$controller->index();
				break;
		}
		break;
		
	
	case 'chart_of_accounts':
		require_once '../app/controllers/ChartOfAccountsController.php';
		$controller = new ChartOfAccountsController();
		
		$action = $_GET['action'] ?? 'index';
		switch ($action) {
			case 'create':
				$controller->create();
				break;
			case 'update':
				$controller->update($_GET['id']);
				break;
			case 'toggle_status':
				$controller->toggleStatus($_GET['id']);
				break;
			case 'delete':
				$controller->delete($_GET['id']);
				break;
			case 'api':
				$controller->getApiData();
				break;
			default:
				$controller->index();
				break;
		}
		break;
			
    // ... outros cases permanecem iguais
    default:
        http_response_code(404);
        require_once '../app/views/errors/404.php';
        break;
}
?>