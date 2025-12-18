<?php
// app/controllers/SuppliersController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/SuppliersModel.php';

class SuppliersController {
    private $model;

    public function __construct() {
        $this->model = new SuppliersModel();
    }

    // ✅ PÁGINA PRINCIPAL QUE PROCESSA TODAS AS AÇÕES
    public function index() {
        $action = $_GET['action'] ?? 'list';
        $id = $_GET['id'] ?? null;

        // ✅ PROCESSAR AÇÕES ANTES DE CARREGAR A PÁGINA
        switch ($action) {
            case 'create':
                $this->create();
                break;
            case 'update':
                if ($id) $this->update($id);
                break;
            case 'delete':
                if ($id) $this->delete($id);
                break;
            case 'view':
                if ($id) $this->view($id);
                return; // Para não carregar a lista
            case 'get':
                if ($id) $this->get($id);
                return; // Para não carregar a lista
            case 'list':
            default:
                // Continua para carregar a lista
                break;
        }
		
		

        // ✅ CARREGAR A PÁGINA PRINCIPAL COM A LISTA
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            $_SESSION['error'] = 'Selecione uma empresa primeiro.';
            header('Location: ' . APP_URL . '/index.php?page=companies');
            exit;
        }

        // Filtros
        $filters = [];
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        $data = [
            'page_title' => 'Fornecedores',
            'suppliers' => $this->model->getByCompany($company_id, $filters),
            'current_company' => $this->model->getCurrentCompany($company_id),
            'total_count' => $this->model->getCountByCompany($company_id),
            'filters' => $filters
        ];

        require_once __DIR__ . '/../views/suppliers/list.php';
		
		// Adicionar tratamento de retorno
		$this->handleReturnToAccountsPayable();
    }

    // ✅ BUSCAR FORNECEDOR PARA VISUALIZAÇÃO (JSON)
    private function view($id) {
        header('Content-Type: application/json; charset=utf-8');
        
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Empresa não selecionada.']);
            exit;
        }

        $supplier = $this->model->getById($id);
        
        if (!$supplier || $supplier['company_id'] != $company_id) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $supplier
        ]);
        exit;
    }

    // ✅ BUSCAR FORNECEDOR PARA EDIÇÃO (JSON)
    private function get($id) {
        header('Content-Type: application/json; charset=utf-8');
        
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Empresa não selecionada.']);
            exit;
        }

        $supplier = $this->model->getById($id);
        
        if (!$supplier || $supplier['company_id'] != $company_id) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $supplier
        ]);
        exit;
    }

    // ✅ CRIAR FORNECEDOR
    private function create() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$company_id = $_SESSION['company_id'] ?? null;
			
			if (!$company_id) {
				$_SESSION['error'] = 'Empresa não selecionada.';
				$this->redirectBasedOnReturn();
				return;
			}

			if (empty(trim($_POST['name']))) {
				$_SESSION['error'] = 'Nome do fornecedor é obrigatório.';
				$this->redirectBasedOnReturn();
				return;
			}

			$data = [
				'company_id' => $company_id,
				'name' => trim($_POST['name']),
				'fantasy_name' => trim($_POST['fantasy_name'] ?? ''),
				'cpf_cnpj' => trim($_POST['cpf_cnpj'] ?? ''),
				'email' => trim($_POST['email'] ?? ''),
				'phone' => trim($_POST['phone'] ?? ''),
				'address' => trim($_POST['address'] ?? '')
			];

			$result = $this->model->create($data);
			
			if ($result) {
				$_SESSION['success'] = 'Fornecedor cadastrado com sucesso!';
				$this->redirectBasedOnReturn();
			} else {
				$error = $this->model->getError();
				$_SESSION['error'] = 'Erro ao cadastrar fornecedor: ' . $error;
				$this->redirectBasedOnReturn();
			}
		}
	}
	
	// ✅ MÉTODO AUXILIAR PARA REDIRECIONAR
	private function redirectToSuppliers() {
		header('Location: index.php?page=suppliers');
		exit;
	}
	
	// ✅ NOVO MÉTODO: Lidar com resposta (AJAX ou normal)
	private function handleResponse($message, $success, $data = null) {
		$returnTo = $_POST['return_to'] ?? $_GET['return_to'] ?? '';
		$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
				  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		
		// Se for requisição AJAX ou tiver return_to, responder com JSON
		if ($isAjax || $returnTo) {
			header('Content-Type: application/json');
			echo json_encode([
				'success' => $success,
				'message' => $message,
				'data' => $data,
				'return_to' => $returnTo,
				'return_url' => $_POST['return_url'] ?? $_GET['return_url'] ?? ''
			]);
			exit;
		}
		
		// Se não for AJAX, usar sistema de sessão normal
		if ($success) {
			$_SESSION['success'] = $message;
		} else {
			$_SESSION['error'] = $message;
		}
		
		// Redirecionar
		if ($returnTo === 'accounts_payable') {
			$returnUrl = $_POST['return_url'] ?? $_GET['return_url'] ?? 'index.php?page=accounts_payable';
			header('Location: ' . $returnUrl . '&returned_from=suppliers&supplier_saved=true');
		} else {
			header('Location: ' . APP_URL . '/index.php?page=suppliers');
		}
		exit;
	}
	
	// ✅ NOVO MÉTODO: Redirecionamento inteligente
	private function redirectBasedOnReturn() {
		$returnTo = $_POST['return_to'] ?? $_GET['return_to'] ?? '';
		
		if ($returnTo === 'accounts_payable') {
			$returnUrl = $_POST['return_url'] ?? $_GET['return_url'] ?? 'index.php?page=accounts_payable';
			header('Location: ' . $returnUrl . '&returned_from=suppliers&supplier_saved=true');
		} else {
			header('Location: index.php?page=suppliers');
		}
		exit;
	}

    // ✅ ATUALIZAR FORNECEDOR
    private function update($id) {
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            $_SESSION['error'] = 'Empresa não selecionada.';
            header('Location: ' . APP_URL . '/index.php?page=suppliers');
            exit;
        }

        // Verificar se o fornecedor existe e pertence à empresa
        $supplier = $this->model->getById($id);
        if (!$supplier || $supplier['company_id'] != $company_id) {
            $_SESSION['error'] = 'Fornecedor não encontrado.';
            header('Location: ' . APP_URL . '/index.php?page=suppliers');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['name'])) {
                $_SESSION['error'] = 'Nome do fornecedor é obrigatório.';
                header('Location: ' . APP_URL . '/index.php?page=suppliers');
                exit;
            }

            $data = [
                'name' => trim($_POST['name']),
                'fantasy_name' => trim($_POST['fantasy_name'] ?? ''),
                'cpf_cnpj' => trim($_POST['cpf_cnpj'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? '')
            ];

            if ($this->model->update($id, $data)) {
                $_SESSION['success'] = 'Fornecedor atualizado com sucesso!';
            } else {
                $error = $this->model->getError();
                $_SESSION['error'] = 'Erro ao atualizar fornecedor: ' . $error;
            }
            
            header('Location: ' . APP_URL . '/index.php?page=suppliers');
            exit;
        }
    }
	
	// No SuppliersController, adicione esta função
	private function handleReturnToAccountsPayable() {
		$returnTo = $_GET['return_to'] ?? '';
		$openModal = $_GET['open_modal'] ?? false;
		
		if ($returnTo === 'accounts_payable' && $openModal) {
			// Adicionar script para abrir modal automaticamente
			echo "<script>
				window.onload = function() {
					setTimeout(function() {
						if (typeof openModal === 'function') {
							openModal('supplierModal');
						}
					}, 500);
				};
			</script>";
		}
	}
	

    // ✅ EXCLUIR FORNECEDOR
    private function delete($id) {
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            $_SESSION['error'] = 'Empresa não selecionada.';
            header('Location: ' . APP_URL . '/index.php?page=suppliers');
            exit;
        }

        $supplier = $this->model->getById($id);
        if (!$supplier || $supplier['company_id'] != $company_id) {
            $_SESSION['error'] = 'Fornecedor não encontrado ou não pertence à empresa.';
            header('Location: ' . APP_URL . '/index.php?page=suppliers');
            exit;
        }

        if ($this->model->delete($id)) {
            $_SESSION['success'] = 'Fornecedor excluído com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir fornecedor.';
        }
        
        header('Location: ' . APP_URL . '/index.php?page=suppliers');
        exit;
    }
}
?>