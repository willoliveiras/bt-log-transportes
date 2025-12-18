<?php
// app/controllers/ContractController.php - VERSÃO CORRIGIDA

require_once __DIR__ . '/../models/ContractModel.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';

class ContractController {
    private $contractModel;
    private $companyModel;
    private $session;
    private $db;

    public function __construct() {
        $this->contractModel = new ContractModel();
        $this->companyModel = new CompanyModel();
        $this->session = new Session();
        $this->db = Database::getInstance()->getConnection();
        
        error_log("🎯 ContractController instanciado");
    }

    // ✅ MÉTODO PRINCIPAL: Listar contratos
    public function index() {
        if (!$this->hasPermission('contracts')) {
            $this->redirectToUnauthorized();
            return;
        }

        $action = $_GET['action'] ?? 'list';
        $companyFilter = $_GET['company'] ?? null;
        $statusFilter = $_GET['status'] ?? null;

        switch ($action) {
            case 'list':
                $this->listContracts($companyFilter, $statusFilter);
                break;
            case 'expiring':
                $this->listExpiringContracts($companyFilter);
                break;
            case 'renew':
                $this->showRenewContract();
                break;
            default:
                $this->listContracts($companyFilter, $statusFilter);
        }
    }

    // ✅ MÉTODO: Listar todos os contratos
    private function listContracts($companyFilter = null, $statusFilter = null) {
        try {
            $contracts = $this->contractModel->getAll($companyFilter, $statusFilter);
            $companies = $this->companyModel->getForDropdown();
            $stats = $this->contractModel->getStats($companyFilter);
            
            $pageTitle = $statusFilter === 'expiring' ? 'Contratos à Vencer' : 'Contratos';
            $currentPage = 'contracts';
            $pageScript = 'contracts.js';
            
            include '../app/views/layouts/header.php';
            include '../app/views/contracts/list.php';
            include '../app/views/layouts/footer.php';
            
        } catch (Exception $e) {
            error_log("❌ [ContractController] Erro: " . $e->getMessage());
            $this->showError("Erro ao carregar contratos");
        }
    }

    // ✅ MÉTODO: Listar contratos próximos do vencimento
    private function listExpiringContracts($companyFilter = null) {
        try {
            $contracts = $this->contractModel->getAll($companyFilter, 'expiring');
            $companies = $this->companyModel->getForDropdown();
            $stats = $this->contractModel->getStats($companyFilter);
            
            $pageTitle = 'Vencimento Próximo';
            $currentPage = 'contracts_expiring';
            $pageScript = 'contracts.js';
            
            include '../app/views/layouts/header.php';
            include '../app/views/contracts/expiring.php';
            include '../app/views/layouts/footer.php';
            
        } catch (Exception $e) {
            error_log("❌ [ContractController] Erro: " . $e->getMessage());
            $this->showError("Erro ao carregar contratos próximos do vencimento");
        }
    }

    // ✅ MÉTODO: Mostrar tela de renovação
    private function showRenewContract() {
        try {
            $contractId = $_GET['id'] ?? null;
            
            if ($contractId) {
                $contract = $this->contractModel->getById($contractId);
                if (!$contract) {
                    $this->showError("Contrato não encontrado");
                    return;
                }
                
                $renewals = $this->contractModel->getRenewals($contractId);
                
                $pageTitle = 'Renovar Contrato';
                $currentPage = 'contracts_renew';
                $pageScript = 'contracts.js';
                
                include '../app/views/layouts/header.php';
                include '../app/views/contracts/renew.php';
                include '../app/views/layouts/footer.php';
            } else {
                $companies = $this->companyModel->getForDropdown();
                
                $pageTitle = 'Renovação de Contratos';
                $currentPage = 'contracts_renew';
                $pageScript = 'contracts.js';
                
                include '../app/views/layouts/header.php';
                include '../app/views/contracts/renew_list.php';
                include '../app/views/layouts/footer.php';
            }
            
        } catch (Exception $e) {
            error_log("❌ [ContractController] Erro: " . $e->getMessage());
            $this->showError("Erro ao carregar renovação");
        }
    }

    // ✅ MÉTODO: Buscar clientes para dropdown (função independente)
    private function getClientsForDropdown($companyId = null) {
        try {
            $sql = "SELECT id, name, fantasy_name, cpf_cnpj 
                    FROM clients 
                    WHERE is_active = 1";
            
            $params = [];
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("❌ [ContractController] Erro ao buscar clientes: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Buscar fornecedores para dropdown (função independente)
    private function getSuppliersForDropdown($companyId = null) {
        try {
            $sql = "SELECT id, name, fantasy_name, cpf_cnpj 
                    FROM suppliers 
                    WHERE is_active = 1";
            
            $params = [];
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("❌ [ContractController] Erro ao buscar fornecedores: " . $e->getMessage());
            return [];
        }
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
                case 'get':
                    $id = $_GET['id'] ?? null;
                    if ($id) {
                        $contract = $this->contractModel->getById($id);
                        if ($contract) {
                            echo json_encode([
                                'success' => true,
                                'data' => $contract
                            ]);
                        } else {
                            http_response_code(404);
                            echo json_encode([
                                'success' => false, 
                                'message' => 'Contrato não encontrado'
                            ]);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'ID não informado'
                        ]);
                    }
                    break;
                    
                case 'save':
                    $this->handleSaveContract();
                    break;
                    
                case 'renew':
                    $this->handleRenewContract();
                    break;
                    
                case 'delete':
                    $this->handleDeleteContract();
                    break;
                    
                case 'upload':
                    $this->handleFileUpload();
                    break;
                    
                case 'check_number':
                    $this->checkContractNumber();
                    break;
                    
                case 'get_clients':
                    $companyId = $_GET['company_id'] ?? null;
                    $clients = $this->getClientsForDropdown($companyId);
                    echo json_encode([
                        'success' => true,
                        'data' => $clients
                    ]);
                    break;
                    
                case 'get_suppliers':
                    $companyId = $_GET['company_id'] ?? null;
                    $suppliers = $this->getSuppliersForDropdown($companyId);
                    echo json_encode([
                        'success' => true,
                        'data' => $suppliers
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
            error_log("❌ [ContractController API] Erro: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ]);
        }
    }

    // ✅ MÉTODO: Salvar/Atualizar contrato
    private function handleSaveContract() {
		try {
			ob_start();
			
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				throw new Exception('Método não permitido');
			}
			
			$contractId = $_POST['contract_id'] ?? null;
			$isUpdate = !empty($contractId);
			
			error_log("📝 Processando salvamento de contrato. ID: " . ($contractId ?: 'novo'));
			
			// ✅ 1. VALIDAÇÃO DOS DADOS OBRIGATÓRIOS
			$required = ['company_id', 'contract_type', 'contract_number', 'title', 'start_date', 'end_date'];
			$missing = [];
			
			foreach ($required as $field) {
				if (empty($_POST[$field])) {
					$missing[] = $field;
				}
			}
			
			if (!empty($missing)) {
				throw new Exception('Campos obrigatórios não preenchidos: ' . implode(', ', $missing));
			}
			
			// ✅ 2. VALIDAR TIPO DE CONTRATO
			$contractType = $_POST['contract_type'];
			if ($contractType === 'client' && empty($_POST['client_id'])) {
				throw new Exception('Selecione um cliente para contrato com cliente');
			}
			
			if ($contractType === 'supplier' && empty($_POST['supplier_id'])) {
				throw new Exception('Selecione um fornecedor para contrato com fornecedor');
			}
			
			// ✅ 3. CONVERSÃO CORRETA DO VALOR
			$value = 0;
			if (isset($_POST['value']) && $_POST['value'] !== '') {
				$valueStr = trim($_POST['value']);
				error_log("💰 Valor original: '{$valueStr}'");
				
				// Remover R$, $ e espaços
				$valueStr = preg_replace('/[^\d,\.\-]/', '', $valueStr);
				
				// Verificar formato (1.000,50 ou 1000.50)
				if (strpos($valueStr, ',') !== false && strpos($valueStr, '.') !== false) {
					// Formato com separador de milhar e decimal (1.000,50)
					$valueStr = str_replace('.', '', $valueStr); // Remove pontos
					$valueStr = str_replace(',', '.', $valueStr); // Substitui vírgula por ponto
				} elseif (strpos($valueStr, ',') !== false) {
					// Formato com vírgula decimal (1000,50)
					$valueStr = str_replace(',', '.', $valueStr);
				}
				
				// Remover caracteres não numéricos exceto ponto
				$valueStr = preg_replace('/[^0-9\.\-]/', '', $valueStr);
				
				$value = (float)$valueStr;
				
				// Verificar se é um número válido
				if (!is_numeric($value) || $value < 0) {
					$value = 0;
				}
				
				error_log("💰 Valor convertido: {$value}");
			}
			
			// ✅ 4. VALIDAR DATAS
			$startDate = $_POST['start_date'];
			$endDate = $_POST['end_date'];
			
			if ($startDate && $endDate) {
				$startTimestamp = strtotime($startDate);
				$endTimestamp = strtotime($endDate);
				
				if ($startTimestamp > $endTimestamp) {
					throw new Exception('A data de início não pode ser posterior à data de término');
				}
				
				// Verificar se as datas são válidas
				if (!$startTimestamp || !$endTimestamp) {
					throw new Exception('Datas inválidas');
				}
			}
			
			// ✅ 5. PREPARAR DADOS
			$contractData = [
				'company_id' => (int)$_POST['company_id'],
				'client_id' => $contractType === 'client' ? ($_POST['client_id'] ? (int)$_POST['client_id'] : null) : null,
				'supplier_id' => $contractType === 'supplier' ? ($_POST['supplier_id'] ? (int)$_POST['supplier_id'] : null) : null,
				'contract_type' => $contractType,
				'contract_number' => trim($_POST['contract_number']),
				'title' => trim($_POST['title']),
				'description' => $_POST['description'] ?? '',
				'start_date' => $startDate,
				'end_date' => $endDate,
				'value' => $value,
				'currency' => $_POST['currency'] ?? 'BRL',
				'payment_terms' => $_POST['payment_terms'] ?? '',
				'renewal_terms' => $_POST['renewal_terms'] ?? '',
				'status' => $_POST['status'] ?? 'draft',
				'notes' => $_POST['notes'] ?? ''
			];
			
			error_log("📋 Dados preparados para contrato:");
			error_log(print_r($contractData, true));
			
			// ✅ 6. PROCESSAR ARQUIVO
			$file = null;
			if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
				$file = $_FILES['contract_file'];
				error_log("📁 Arquivo recebido:");
				error_log("- Nome: " . $file['name']);
				error_log("- Tamanho: " . $file['size']);
				error_log("- Tipo: " . $file['type']);
				error_log("- Error: " . $file['error']);
				error_log("- Temp: " . $file['tmp_name']);
				
				// Validar se é PDF
				$allowedTypes = ['application/pdf', 'application/x-pdf'];
				if (!in_array($file['type'], $allowedTypes)) {
					throw new Exception('Apenas arquivos PDF são permitidos');
				}
				
				// Validar tamanho (10MB)
				$maxSize = 10 * 1024 * 1024;
				if ($file['size'] > $maxSize) {
					throw new Exception('Arquivo muito grande. Tamanho máximo: 10MB');
				}
				
				// Verificar se é realmente um PDF
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mimeType = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);
				
				if ($mimeType !== 'application/pdf') {
					throw new Exception('Arquivo não é um PDF válido');
				}
			} elseif (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] !== UPLOAD_ERR_NO_FILE) {
				error_log("⚠️ Erro no upload: " . $_FILES['contract_file']['error']);
				// Não falhar se for apenas erro de não selecionar arquivo
			}
			
			// ✅ 7. SALVAR NO BANCO
			if ($isUpdate) {
				error_log("🔄 Atualizando contrato ID: " . $contractId);
				$success = $this->contractModel->update($contractId, $contractData, $file);
				$message = 'Contrato atualizado com sucesso!';
				$returnId = $contractId;
			} else {
				error_log("🆕 Criando novo contrato");
				$newContractId = $this->contractModel->create($contractData, $file);
				
				if ($newContractId) {
					$success = true;
					$returnId = $newContractId;
					$message = 'Contrato criado com sucesso!';
					error_log("✅ Contrato criado com ID: " . $newContractId);
				} else {
					$success = false;
					$returnId = null;
					$message = 'Erro ao criar contrato';
				}
			}
			
			if ($success) {
				error_log("✅ Operação realizada com sucesso");
				
				ob_clean();
				header('Content-Type: application/json');
				echo json_encode([
					'success' => true,
					'message' => $message,
					'contractId' => $returnId
				]);
				exit;
			} else {
				throw new Exception('Erro ao salvar contrato no banco de dados');
			}
			
		} catch (Exception $e) {
			ob_clean();
			
			error_log("❌ [ContractController] Erro handleSaveContract: " . $e->getMessage());
			error_log("❌ Trace: " . $e->getTraceAsString());
			error_log("❌ POST data: " . print_r($_POST, true));
			
			if (isset($_FILES['contract_file'])) {
				error_log("❌ FILE data: " . print_r($_FILES['contract_file'], true));
			}
			
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode([
				'success' => false,
				'message' => 'Erro ao salvar contrato: ' . $e->getMessage()
			]);
			exit;
		}
	}

    // ✅ MÉTODO: Renovar contrato
    private function handleRenewContract() {
        $contractId = $_POST['contract_id'] ?? null;
        $newEndDate = $_POST['new_end_date'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        if (!$contractId || !$newEndDate) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Dados incompletos para renovação'
            ]);
            return;
        }
        
        $success = $this->contractModel->renew($contractId, $newEndDate, $notes);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Contrato renovado com sucesso!'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao renovar contrato'
            ]);
        }
    }

    // ✅ MÉTODO: Verificar se número de contrato já existe
    private function checkContractNumber() {
        $number = $_GET['number'] ?? '';
        $companyId = $_GET['company_id'] ?? '';
        $excludeId = $_GET['exclude_id'] ?? null;
        
        if (!$number || !$companyId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Dados incompletos'
            ]);
            return;
        }
        
        $exists = $this->contractModel->contractNumberExists($number, $companyId, $excludeId);
        
        echo json_encode([
            'success' => true,
            'exists' => $exists
        ]);
    }

    // ✅ MÉTODO: Upload de arquivo
    private function handleFileUpload() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Nenhum arquivo enviado'
            ]);
            return;
        }
        
        $file = $_FILES['file'];
        
        // Validar tipo de arquivo
        $allowedTypes = ['application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Apenas arquivos PDF são permitidos'
            ]);
            return;
        }
        
        // Validar tamanho (10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Arquivo muito grande. Tamanho máximo: 10MB'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Arquivo validado com sucesso',
            'filename' => $file['name'],
            'size' => $file['size']
        ]);
    }

    // ✅ MÉTODO: Deletar contrato
    private function handleDeleteContract() {
		// Forçar cabeçalho JSON
		header('Content-Type: application/json; charset=utf-8');
		
		// Iniciar buffer para capturar erros
		ob_start();
		
		try {
			// Log simples
			error_log("[CONTRACTS] Delete request received");
			
			// Verificar método
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				throw new Exception('Método não permitido');
			}
			
			// Obter ID
			$contractId = $_POST['id'] ?? null;
			
			if (!$contractId) {
				throw new Exception('ID do contrato não fornecido');
			}
			
			// Converter para inteiro
			$contractId = (int)$contractId;
			error_log("[CONTRACTS] Cancelling contract ID: " . $contractId);
			
			// CONEXÃO SIMPLES AO BANCO
			$db = Database::getInstance()->getConnection();
			
			// 1. Verificar se o contrato existe
			$stmt = $db->prepare("SELECT id, title FROM contracts WHERE id = ?");
			$stmt->execute([$contractId]);
			$contract = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$contract) {
				throw new Exception('Contrato não encontrado');
			}
			
			// 2. Atualizar status para cancelled
			$stmt = $db->prepare("UPDATE contracts SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
			$result = $stmt->execute([$contractId]);
			
			if (!$result) {
				throw new Exception('Falha ao atualizar contrato');
			}
			
			// Limpar buffer
			ob_clean();
			
			// Retornar sucesso
			echo json_encode([
				'success' => true,
				'message' => 'Contrato cancelado com sucesso!',
				'contractId' => $contractId
			]);
			
			error_log("[CONTRACTS] Contract cancelled successfully: " . $contractId);
			
		} catch (Exception $e) {
			// Limpar buffer
			ob_clean();
			
			// Log do erro
			error_log("[CONTRACTS] ERROR: " . $e->getMessage());
			
			// Retornar erro em JSON
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Erro: ' . $e->getMessage(),
				'error' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
			]);
		}
		
		// Encerrar script
		exit;
	}

    // ✅ MÉTODO: Verificar permissão do usuário
    private function hasPermission($resource) {
        $userRole = $this->session->get('user_role');
        $allowedRoles = ['super_admin', 'admin', 'financeiro', 'comercial'];
        return in_array($userRole, $allowedRoles);
    }

    // ✅ MÉTODO: Redirecionar para página não autorizada
    private function redirectToUnauthorized() {
        header('Location: /bt-log-transportes/public/index.php?page=unauthorized');
        exit;
    }

    // ✅ MÉTODO: Mostrar erro
    private function showError($message) {
        echo "<div class='error-message'>$message</div>";
    }
}
?>