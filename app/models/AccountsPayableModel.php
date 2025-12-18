<?php
// app/models/AccountsPayableModel.php

// ✅ Verificar se Database existe
$databasePath = __DIR__ . '/../core/Database.php';
if (!file_exists($databasePath)) {
    error_log("❌ [MODEL] Arquivo Database.php não encontrado em: " . $databasePath);
    die("Erro crítico: Arquivo Database.php não encontrado");
}

require_once $databasePath;

class AccountsPayableModel {
    private $db;

    public function __construct() {
        error_log("🎯 [MODEL] Iniciando AccountsPayableModel...");
        try {
            $this->db = Database::getInstance()->getConnection();
            error_log("✅ [MODEL] Conexão com banco estabelecida com sucesso");
        } catch (Exception $e) {
            error_log("❌ [MODEL] Erro ao conectar com banco: " . $e->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
    }

    // ✅ MÉTODOS PARA CONTAS A PAGAR
    public function getByCompany($company_id, $filters = []) {
        try {
            $sql = "SELECT ap.*, ca.account_name, ca.account_code, 
                           c.name as company_name 
                    FROM accounts_payable ap 
                    LEFT JOIN chart_of_accounts ca ON ap.chart_account_id = ca.id 
                    LEFT JOIN companies c ON ap.company_id = c.id 
                    WHERE ap.company_id = ?";
            
            $params = [$company_id];
            
            // Filtros
            if (!empty($filters['status'])) {
                $sql .= " AND ap.status = ?";
                $params[] = $filters['status'];
            }
            
            $sql .= " ORDER BY ap.due_date ASC, ap.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("❌ [MODEL] Erro ao buscar contas a pagar: " . $e->getMessage());
            return [];
        }
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO accounts_payable 
                    (company_id, chart_account_id, description, amount, due_date, 
                     is_recurring, recurrence_frequency, supplier, notes, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())";
            
            $params = [
                $data['company_id'],
                $data['chart_account_id'],
                $data['description'],
                $data['amount'],
                $data['due_date'],
                $data['is_recurring'] ?? 0,
                $data['recurrence_frequency'] ?? null,
                $data['supplier'],
                $data['notes'] ?? null
            ];

            error_log("💾 [MODEL] Criando conta: " . json_encode($data));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("✅ [MODEL] Conta criada com sucesso");
                return true;
            } else {
                error_log("❌ [MODEL] Erro ao criar conta");
                return false;
            }
        } catch (PDOException $e) {
            error_log("💥 [MODEL] Exception ao criar conta: " . $e->getMessage());
            return false;
        }
    }

    public function markAsPaid($id) {
        try {
            $sql = "UPDATE accounts_payable 
                    SET status = 'pago', payment_date = CURDATE(), updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("❌ [MODEL] Erro ao marcar conta como paga: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $sql = "DELETE FROM accounts_payable WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("❌ [MODEL] Erro ao excluir conta: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODOS PARA FORNECEDORES
    public function getSuppliersByCompany($company_id) {
        try {
            // Primeiro verifica se a tabela suppliers existe
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'suppliers'")->fetch();
            
            if (!$tableCheck) {
                error_log("ℹ️ [MODEL] Tabela suppliers não existe, retornando array vazio");
                return [];
            }
            
            $sql = "SELECT * FROM suppliers WHERE company_id = ? AND is_active = 1 ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$company_id]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("❌ [MODEL] Erro ao buscar fornecedores: " . $e->getMessage());
            return [];
        }
    }

    public function createSupplier($data) {
        try {
            // Verificar se a tabela suppliers existe
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'suppliers'")->fetch();
            
            if (!$tableCheck) {
                error_log("❌ [MODEL] Tabela suppliers não existe");
                return false;
            }

            // Validar dados obrigatórios
            if (empty($data['company_id']) || empty($data['name'])) {
                error_log("❌ [MODEL] Dados obrigatórios faltando: company_id ou name");
                return false;
            }

            $sql = "INSERT INTO suppliers 
                    (company_id, name, fantasy_name, cpf_cnpj, email, phone, address, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $data['company_id'],
                $data['name'],
                $data['fantasy_name'] ?? '',
                $data['cpf_cnpj'] ?? '',
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['address'] ?? ''
            ];

            error_log("🏭 [MODEL] Salvando fornecedor: " . json_encode($params));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $lastId = $this->db->lastInsertId();
                error_log("✅ [MODEL] Fornecedor criado com sucesso - ID: " . $lastId);
                return true;
            } else {
                error_log("❌ [MODEL] Erro ao executar SQL do fornecedor");
                return false;
            }
        } catch (PDOException $e) {
            error_log("💥 [MODEL] Exception ao criar fornecedor: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODOS PARA PLANO DE CONTAS
    public function getChartOfAccounts($company_id) {
        try {
            $sql = "SELECT * FROM chart_of_accounts 
                    WHERE company_id = ? AND is_active = 1 
                    ORDER BY account_code";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$company_id]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("❌ [MODEL] Erro ao buscar plano de contas: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODOS PARA EMPRESAS (adicionados diretamente aqui)
    public function getAllActiveCompanies() {
        try {
            $sql = "SELECT id, name, color FROM companies WHERE is_active = 1 ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("❌ [MODEL] Erro ao buscar empresas ativas: " . $e->getMessage());
            return [];
        }
    }

    public function getError() {
        return $this->db->errorInfo()[2] ?? 'Erro desconhecido';
    }

    // ✅ MÉTODO PARA TESTAR CONEXÃO
    public function testConnection() {
        try {
            $stmt = $this->db->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("💥 [MODEL] Erro de conexão: " . $e->getMessage());
            return false;
        }
    }
}
?>