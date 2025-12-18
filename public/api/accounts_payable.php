<?php
// public/api/accounts_payable.php - API CORRIGIDA

// ✅ HEADERS PRIMEIRO - SEMPRE
header('Content-Type: application/json; charset=utf-8');

// ✅ Limpar qualquer output anterior
while (ob_get_level()) ob_end_clean();

// ✅ Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    // ✅ Caminhos absolutos
    $rootPath = realpath(__DIR__ . '/../../');
    
    // ✅ Incluir configuração básica primeiro
    $configFile = $rootPath . '/app/config/config.php';
    if (!file_exists($configFile)) {
        throw new Exception('Arquivo de configuração não encontrado');
    }
    require_once $configFile;

    // ✅ Incluir database
    $databaseFile = $rootPath . '/app/config/database.php';
    if (!file_exists($databaseFile)) {
        throw new Exception('Arquivo de database não encontrado');
    }
    require_once $databaseFile;

    // ✅ Incluir core
    $databaseCore = $rootPath . '/app/core/Database.php';
    if (!file_exists($databaseCore)) {
        throw new Exception('Arquivo Database.php não encontrado');
    }
    require_once $databaseCore;

    // ✅ Ação
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Ação não especificada');
    }

    // ✅ Criar instância do modelo
    $model = new AccountsPayableModel();

    switch ($action) {
        case 'get_suppliers':
            handleGetSuppliers($model);
            break;
            
        case 'save':
            handleSavePayable($model);
            break;
            
        case 'mark_paid':
            handleMarkPaid($model);
            break;
            
        case 'delete':
            handleDeletePayable($model);
            break;
            
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }

} catch (Exception $e) {
    // ✅ SEMPRE retornar JSON em caso de erro
    error_log("❌ [ACCOUNTS PAYABLE API] Erro: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}

exit;

// ✅ FUNÇÃO: Buscar fornecedores
function handleGetSuppliers($model) {
    $company_id = $_SESSION['company_id'] ?? 1;
    
    try {
        $suppliers = $model->getSuppliersByCompany($company_id);
        
        echo json_encode([
            'success' => true,
            'data' => $suppliers ?: []
        ]);
    } catch (Exception $e) {
        throw new Exception('Erro ao buscar fornecedores: ' . $e->getMessage());
    }
}

// ✅ FUNÇÃO: Salvar conta
function handleSavePayable($model) {
    // Validar campos obrigatórios
    $required = ['description', 'amount', 'due_date', 'chart_account_id'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('Campos obrigatórios: ' . implode(', ', $missing));
    }

    $company_id = $_SESSION['company_id'] ?? 1;
    
    // Preparar dados
    $data = [
        'company_id' => $company_id,
        'description' => trim($_POST['description']),
        'amount' => floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['amount'])),
        'due_date' => $_POST['due_date'],
        'chart_account_id' => intval($_POST['chart_account_id']),
        'notes' => trim($_POST['notes'] ?? ''),
        'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
        'recurrence_frequency' => $_POST['recurrence_frequency'] ?? null,
        'status' => 'pendente'
    ];

    // Tratar fornecedor
    $supplierType = $_POST['supplier_type'] ?? 'custom';
    if ($supplierType === 'registered') {
        $supplierId = intval($_POST['supplier_selection'] ?? 0);
        if ($supplierId > 0) {
            $data['supplier'] = $model->getSupplierNameById($supplierId);
        } else {
            throw new Exception('Fornecedor inválido');
        }
    } else {
        $data['supplier'] = trim($_POST['supplier_custom'] ?? '');
        if (empty($data['supplier'])) {
            throw new Exception('Nome do fornecedor é obrigatório');
        }
    }

    // Salvar
    $success = $model->create($data);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Conta a pagar salva com sucesso!'
        ]);
    } else {
        throw new Exception('Erro ao salvar no banco de dados');
    }
}

// ✅ FUNÇÃO: Marcar como pago
function handleMarkPaid($model) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $success = $model->markAsPaid($id);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Conta marcada como paga!'
        ]);
    } else {
        throw new Exception('Erro ao marcar conta como paga');
    }
}

// ✅ FUNÇÃO: Excluir conta
function handleDeletePayable($model) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $success = $model->delete($id);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Conta excluída com sucesso!'
        ]);
    } else {
        throw new Exception('Erro ao excluir conta');
    }
}

// ✅ CLASSE SIMPLIFICADA DO MODELO (se não existir)
class AccountsPayableModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getSuppliersByCompany($company_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, phone 
                FROM suppliers 
                WHERE company_id = ? AND is_active = 1 
                ORDER BY name
            ");
            $stmt->execute([$company_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar fornecedores: " . $e->getMessage());
            return [];
        }
    }
    
    public function getSupplierNameById($supplierId) {
        try {
            $stmt = $this->db->prepare("SELECT name FROM suppliers WHERE id = ?");
            $stmt->execute([$supplierId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['name'] : 'Fornecedor Desconhecido';
        } catch (Exception $e) {
            return 'Fornecedor Desconhecido';
        }
    }
    
    public function create($data) {
        try {
            $sql = "INSERT INTO accounts_payable 
                    (company_id, description, amount, due_date, chart_account_id, supplier, notes, is_recurring, recurrence_frequency, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['company_id'],
                $data['description'],
                $data['amount'],
                $data['due_date'],
                $data['chart_account_id'],
                $data['supplier'],
                $data['notes'],
                $data['is_recurring'],
                $data['recurrence_frequency'],
                $data['status']
            ]);
        } catch (Exception $e) {
            error_log("Erro ao criar conta: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAsPaid($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE accounts_payable 
                SET status = 'pago', payment_date = CURDATE(), updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Erro ao marcar como pago: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM accounts_payable WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Erro ao excluir conta: " . $e->getMessage());
            return false;
        }
    }
}
?>