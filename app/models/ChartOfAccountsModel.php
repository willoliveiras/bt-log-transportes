<?php
require_once __DIR__ . '/../core/Database.php';

class ChartOfAccountsModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getByCompany($company_id) {
        $sql = "SELECT * FROM chart_of_accounts 
                WHERE company_id = ? 
                ORDER BY account_type, account_code ASC";
        
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetchAll($result);
    }

    public function getActiveByCompany($company_id) {
        $sql = "SELECT * FROM chart_of_accounts 
                WHERE company_id = ? AND is_active = 1 
                ORDER BY account_code ASC";
        
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetchAll($result);
    }

    public function getById($id) {
        $sql = "SELECT * FROM chart_of_accounts WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $this->fetch($result);
    }

    public function create($data) {
		$sql = "INSERT INTO chart_of_accounts 
				(company_id, account_code, account_name, account_type, category, color, description) 
				VALUES (?, ?, ?, ?, ?, ?, ?)";
		
		return $this->db->execute($sql, [
			$data['company_id'],
			$data['account_code'],
			$data['account_name'],
			$data['account_group'], // Agora usa account_group
			$data['category'] ?? null,
			$data['color'] ?? null,
			$data['description'] ?? null
		]);
	}

    public function update($id, $data) {
        $sql = "UPDATE chart_of_accounts 
                SET account_code = ?, account_name = ?, account_type = ?, 
                    category = ?, updated_at = NOW() 
                WHERE id = ?";
        
        return $this->db->execute($sql, [
            $data['account_code'],
            $data['account_name'],
            $data['account_type'],
            $data['category'] ?? null,
            $id
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM chart_of_accounts WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function toggleStatus($id) {
        $sql = "UPDATE chart_of_accounts 
                SET is_active = NOT is_active, updated_at = NOW() 
                WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function accountCodeExists($company_id, $account_code, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM chart_of_accounts 
                WHERE company_id = ? AND account_code = ?";
        
        $params = [$company_id, $account_code];
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $result = $this->db->query($sql, $params);
        $data = $this->fetch($result);
        return $data['count'] > 0;
    }

    public function isAccountUsed($account_id) {
        // Verificar se a conta está sendo usada em contas a pagar
        $sql_payable = "SELECT COUNT(*) as count FROM accounts_payable WHERE chart_account_id = ?";
        $result_payable = $this->db->query($sql_payable, [$account_id]);
        $data_payable = $this->fetch($result_payable);
        
        // Verificar se a conta está sendo usada em contas a receber
        $sql_receivable = "SELECT COUNT(*) as count FROM accounts_receivable WHERE chart_account_id = ?";
        $result_receivable = $this->db->query($sql_receivable, [$account_id]);
        $data_receivable = $this->fetch($result_receivable);
        
        return ($data_payable['count'] > 0) || ($data_receivable['count'] > 0);
    }

    public function getAccountsSummary($company_id) {
        $sql = "SELECT 
                account_type,
                COUNT(*) as total_count,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN id IN (
                    SELECT chart_account_id FROM accounts_payable WHERE company_id = ?
                    UNION 
                    SELECT chart_account_id FROM accounts_receivable WHERE company_id = ?
                ) THEN 1 ELSE 0 END) as used_count
                FROM chart_of_accounts 
                WHERE company_id = ? 
                GROUP BY account_type";
        
        $result = $this->db->query($sql, [$company_id, $company_id, $company_id]);
        return $this->fetchAll($result);
    }

    public function getForDropdown($company_id) {
        $sql = "SELECT id, CONCAT(account_code, ' - ', account_name) as name 
                FROM chart_of_accounts 
                WHERE company_id = ? AND is_active = 1 
                ORDER BY account_code";
        
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetchAll($result);
    }

    // MÉTODOS PARA EMPRESAS
    public function getAllActiveCompanies() {
        $sql = "SELECT * FROM companies WHERE is_active = 1 ORDER BY name";
        $result = $this->db->query($sql);
        return $this->fetchAll($result);
    }

    public function getCompanyForDropdown() {
        $sql = "SELECT id, name, color FROM companies WHERE is_active = 1 ORDER BY name";
        $result = $this->db->query($sql);
        return $this->fetchAll($result);
    }

    public function getCompanyById($id) {
        $sql = "SELECT * FROM companies WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $this->fetch($result);
    }

    // Métodos auxiliares para trabalhar com PDOStatement
    private function fetchAll($stmt) {
        if ($stmt instanceof PDOStatement) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    private function fetch($stmt) {
        if ($stmt instanceof PDOStatement) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
}
?>