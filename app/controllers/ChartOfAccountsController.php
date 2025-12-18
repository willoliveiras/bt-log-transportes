<?php
require_once __DIR__ . '/../models/ChartOfAccountsModel.php';

class ChartOfAccountsController {
    private $model;

    public function __construct() {
        $this->model = new ChartOfAccountsModel();
    }

    public function index() {
        $company_id = $_SESSION['company_id'] ?? null;
        
        if (!$company_id) {
            header('Location: index.php?page=companies');
            exit;
        }
        
        $data = [
            'page_title' => 'Plano de Contas',
            'accounts' => $this->model->getByCompany($company_id),
            'companies' => $this->model->getAllActiveCompanies(),
            'summary' => $this->model->getAccountsSummary($company_id)
        ];

        require_once __DIR__ . '/../views/financial/chart_of_accounts.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $company_id = $_POST['company_id'] ?? $_SESSION['company_id'];
            
            // Validar dados obrigatórios
            if (empty($_POST['account_code']) || empty($_POST['account_name']) || empty($_POST['account_type'])) {
                $_SESSION['error'] = 'Todos os campos obrigatórios devem ser preenchidos.';
                header('Location: index.php?page=chart_of_accounts');
                exit;
            }

            $data = [
                'company_id' => $company_id,
                'account_code' => trim($_POST['account_code']),
                'account_name' => trim($_POST['account_name']),
                'account_type' => $_POST['account_type'],
                'category' => trim($_POST['category'] ?? '')
            ];

            // Verificar se código da conta já existe
            if ($this->model->accountCodeExists($company_id, $data['account_code'])) {
                $_SESSION['error'] = 'Código da conta já existe. Use um código único.';
                header('Location: index.php?page=chart_of_accounts');
                exit;
            }

            $result = $this->model->create($data);
            
            if ($result) {
                $_SESSION['success'] = 'Conta cadastrada com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao cadastrar conta. Verifique os dados.';
            }
            
            header('Location: index.php?page=chart_of_accounts');
            exit;
        }
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'account_code' => trim($_POST['account_code']),
                'account_name' => trim($_POST['account_name']),
                'account_type' => $_POST['account_type'],
                'category' => trim($_POST['category'] ?? '')
            ];

            if ($this->model->update($id, $data)) {
                $_SESSION['success'] = 'Conta atualizada com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao atualizar conta.';
            }
            
            header('Location: index.php?page=chart_of_accounts');
            exit;
        }
    }

    public function delete($id) {
        // Verificar se a conta está sendo usada antes de excluir
        if ($this->model->isAccountUsed($id)) {
            $_SESSION['error'] = 'Esta conta não pode ser excluída pois está sendo utilizada em lançamentos.';
        } else {
            if ($this->model->delete($id)) {
                $_SESSION['success'] = 'Conta excluída com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao excluir conta.';
            }
        }
        
        header('Location: index.php?page=chart_of_accounts');
        exit;
    }

    public function toggleStatus($id) {
        if ($this->model->toggleStatus($id)) {
            $_SESSION['success'] = 'Status da conta alterado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao alterar status da conta.';
        }
        
        header('Location: index.php?page=chart_of_accounts');
        exit;
    }

    public function getApiData() {
        $company_id = $_GET['company_id'] ?? $_SESSION['company_id'];
        
        $data = [
            'accounts' => $this->model->getByCompany($company_id),
            'summary' => $this->model->getAccountsSummary($company_id)
        ];
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
?>