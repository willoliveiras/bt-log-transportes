<?php
require_once __DIR__ . '/../models/ServiceModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';

class ServiceController {
    private $serviceModel;
    private $companyModel;
    private $session;

    public function __construct() {
        $this->serviceModel = new ServiceModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
    }

    // Listar serviços
    public function index() {
        if (!$this->hasPermission('services')) {
            $this->redirectToUnauthorized();
            return;
        }

        $companyFilter = $_GET['company'] ?? null;
        
        $services = $this->serviceModel->getByCompany($companyFilter);
        $companies = $this->companyModel->getForDropdown();
        $serviceStats = $this->serviceModel->getServiceStats($companyFilter);
        
        $pageTitle = 'Serviços Adicionais';
        $currentPage = 'services';
        
        include '../app/views/layouts/header.php';
        include '../app/views/services/list.php';
        include '../app/views/layouts/footer.php';
    }

    // Salvar serviço (create/update)
    public function save() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('services')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $validation = $this->validateServiceData($_POST);
            if (!$validation['success']) {
                http_response_code(400);
                echo json_encode($validation);
                exit;
            }

            $serviceData = [
                'company_id' => $_POST['company_id'],
                'name' => trim($_POST['name']),
                'description' => $_POST['description'] ?? null,
                'base_price' => (float)$_POST['base_price'],
                'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true
            ];

            $serviceId = $_POST['id'] ?? null;
            
            if ($serviceId) {
                $success = $this->serviceModel->update($serviceId, $serviceData);
                $message = 'Serviço atualizado com sucesso!';
            } else {
                $success = $this->serviceModel->create($serviceData);
                $message = 'Serviço criado com sucesso!';
            }

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'serviceId' => $serviceId
                ]);
            } else {
                throw new Exception('Erro ao salvar serviço no banco de dados');
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

    // Adicionar serviço à viagem
    public function addToTrip() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('trips')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $tripId = $_POST['trip_id'] ?? null;
            $serviceId = $_POST['service_id'] ?? null;
            $customPrice = $_POST['custom_price'] ?? null;

            if (!$tripId || !$serviceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
                exit;
            }

            $success = $this->serviceModel->addToTrip($tripId, $serviceId, $customPrice);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Serviço adicionado à viagem com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao adicionar serviço à viagem');
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

    // Remover serviço da viagem
    public function removeFromTrip() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('trips')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $tripServiceId = $_POST['trip_service_id'] ?? null;

            if (!$tripServiceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID do serviço não informado']);
                exit;
            }

            $success = $this->serviceModel->removeFromTrip($tripServiceId);

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Serviço removido da viagem com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao remover serviço da viagem');
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

    // Atualizar status do serviço (realizado/não realizado)
    public function updatePerformance() {
        header('Content-Type: application/json');
        
        if (!$this->hasPermission('trips')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        try {
            $tripServiceId = $_POST['trip_service_id'] ?? null;
            $wasPerformed = $_POST['was_performed'] ?? false;
            $performedDate = $_POST['performed_date'] ?? null;
            $notes = $_POST['notes'] ?? null;

            if (!$tripServiceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID do serviço não informado']);
                exit;
            }

            $success = $this->serviceModel->updateServicePerformance(
                $tripServiceId, 
                (bool)$wasPerformed, 
                $performedDate, 
                $notes
            );

            if ($success) {
                $status = $wasPerformed ? 'realizado' : 'não realizado';
                echo json_encode([
                    'success' => true, 
                    'message' => 'Serviço marcado como ' . $status . ' com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao atualizar status do serviço');
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

    // Buscar serviços por empresa (para dropdown)
    public function getByCompany() {
        header('Content-Type: application/json');
        
        $companyId = $_GET['company_id'] ?? null;
        
        if (!$companyId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Empresa não especificada']);
            exit;
        }

        try {
            $services = $this->serviceModel->getByCompany($companyId);
            
            echo json_encode([
                'success' => true,
                'data' => $services
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar serviços: ' . $e->getMessage()]);
        }
        
        exit;
    }



    // Validar dados do serviço
    private function validateServiceData($data) {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = 'O nome do serviço é obrigatório';
        }

        if (empty($data['company_id'])) {
            $errors[] = 'A empresa é obrigatória';
        }

        if (empty($data['base_price']) || $data['base_price'] < 0) {
            $errors[] = 'O preço base deve ser maior ou igual a zero';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        return ['success' => true];
    }

    // Verificar permissão
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        $allowedRoles = ['super_admin', 'admin', 'comercial'];
        return in_array($userRole, $allowedRoles);
    }

    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }
}
?>