<?php
require_once __DIR__ . '/../core/Database.php';

class AccountsReceivableModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getByCompany($company_id, $filters = []) {
        $sql = "SELECT ar.*, ca.account_name, ca.account_code, 
                       c.name as company_name, cl.name as client_name,
                       t.trip_number
                FROM accounts_receivable ar 
                LEFT JOIN chart_of_accounts ca ON ar.chart_account_id = ca.id 
                LEFT JOIN companies c ON ar.company_id = c.id 
                LEFT JOIN clients cl ON ar.client_id = cl.id
                LEFT JOIN trips t ON ar.trip_id = t.id
                WHERE ar.company_id = ?";
        
        $params = [$company_id];
        
        // Filtros
        if (!empty($filters['status'])) {
            $sql .= " AND ar.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND ar.due_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND ar.due_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY ar.due_date ASC, ar.created_at DESC";
        
        $result = $this->db->query($sql, $params);
        return $this->fetchAll($result);
    }

    public function create($data) {
        $sql = "INSERT INTO accounts_receivable 
                (company_id, chart_account_id, client_id, description, amount, due_date, 
                 is_recurring, recurrence_frequency, trip_id, notes, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())";
        
        $params = [
            $data['company_id'],
            $data['chart_account_id'],
            $data['client_id'],
            $data['description'],
            $data['amount'],
            $data['due_date'],
            $data['is_recurring'],
            $data['recurrence_frequency'],
            $data['trip_id'],
            $data['notes']
        ];

        return $this->db->execute($sql, $params);
    }

    public function update($id, $data) {
        $sql = "UPDATE accounts_receivable 
                SET chart_account_id = ?, client_id = ?, description = ?, amount = ?, 
                    due_date = ?, is_recurring = ?, recurrence_frequency = ?, 
                    trip_id = ?, notes = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $params = [
            $data['chart_account_id'],
            $data['client_id'],
            $data['description'],
            $data['amount'],
            $data['due_date'],
            $data['is_recurring'],
            $data['recurrence_frequency'],
            $data['trip_id'],
            $data['notes'],
            $id
        ];

        return $this->db->execute($sql, $params);
    }

    public function delete($id) {
        $sql = "DELETE FROM accounts_receivable WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function markAsReceived($id) {
        $sql = "UPDATE accounts_receivable 
                SET status = 'recebido', receipt_date = CURDATE(), updated_at = NOW() 
                WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function getFinancialSummary($company_id, $period = 'monthly') {
        $dateCondition = $this->getDateCondition($period);
        
        $sql = "SELECT 
                COUNT(*) as total_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'pendente' THEN amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'recebido' THEN amount ELSE 0 END) as received_amount,
                SUM(CASE WHEN status = 'atrasado' THEN amount ELSE 0 END) as overdue_amount,
                COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'recebido' THEN 1 END) as received_count,
                COUNT(CASE WHEN status = 'atrasado' THEN 1 END) as overdue_count
                FROM accounts_receivable 
                WHERE company_id = ? AND due_date {$dateCondition}";
        
        $result = $this->db->query($sql, [$company_id]);
        $data = $this->fetch($result);
        return $data ?: [];
    }

    private function getDateCondition($period) {
        switch ($period) {
            case 'weekly':
                return "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            case 'monthly':
                return "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
            case 'quarterly':
                return "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
            case 'yearly':
                return "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 YEAR)";
            default:
                return "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    public function getOverdueAccounts($company_id) {
        $sql = "SELECT * FROM accounts_receivable 
                WHERE company_id = ? AND status = 'pendente' AND due_date < CURDATE() 
                ORDER BY due_date ASC";
        
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetchAll($result);
    }

    public function getUpcomingReceipts($company_id, $days = 7) {
        $sql = "SELECT * FROM accounts_receivable 
                WHERE company_id = ? AND status = 'pendente' 
                AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                ORDER BY due_date ASC";
        
        $result = $this->db->query($sql, [$company_id, $days]);
        return $this->fetchAll($result);
    }

    // MÉTODOS PARA CLIENTES (implementados diretamente aqui)
    public function getClientsByCompany($company_id) {
        $sql = "SELECT id, name FROM clients WHERE company_id = ? AND is_active = 1 ORDER BY name";
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetchAll($result);
    }

    public function getClientById($id) {
        $sql = "SELECT * FROM clients WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $this->fetch($result);
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