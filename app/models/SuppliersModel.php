<?php
// app/models/SuppliersModel.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

class SuppliersModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // ✅ BUSCAR TODOS OS FORNECEDORES DA EMPRESA
    public function getByCompany($company_id, $filters = []) {
        $sql = "SELECT * FROM suppliers WHERE company_id = ? AND is_active = 1";
        
        $params = [$company_id];
        
        // Filtros
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR fantasy_name LIKE ? OR cpf_cnpj LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY name";
        
        $result = $this->db->query($sql, $params);
        return $this->fetchAll($result);
    }

    // ✅ BUSCAR FORNECEDOR POR ID
    public function getById($id) {
        $sql = "SELECT s.*, c.name as company_name, c.color as company_color 
                FROM suppliers s 
                LEFT JOIN companies c ON s.company_id = c.id 
                WHERE s.id = ? AND s.is_active = 1";
        $result = $this->db->query($sql, [$id]);
        return $this->fetch($result);
    }

    // ✅ CRIAR FORNECEDOR
    public function create($data) {
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

        error_log("🏭 [SUPPLIERS MODEL] Criando fornecedor: " . json_encode($data));
        
        try {
            $result = $this->db->query($sql, $params);
            
            if ($result) {
                $lastIdResult = $this->db->query("SELECT LAST_INSERT_ID() as last_id");
                $lastIdData = $this->fetch($lastIdResult);
                $lastId = $lastIdData['last_id'] ?? null;
                
                error_log("✅ [SUPPLIERS MODEL] Fornecedor criado - ID: " . $lastId);
                return $lastId;
            } else {
                error_log("❌ [SUPPLIERS MODEL] Erro ao criar fornecedor");
                return false;
            }
        } catch (Exception $e) {
            error_log("💥 [SUPPLIERS MODEL] Exception: " . $e->getMessage());
            return false;
        }
    }

    // ✅ ATUALIZAR FORNECEDOR
    public function update($id, $data) {
        $sql = "UPDATE suppliers 
                SET name = ?, fantasy_name = ?, cpf_cnpj = ?, email = ?, 
                    phone = ?, address = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $params = [
            $data['name'],
            $data['fantasy_name'] ?? '',
            $data['cpf_cnpj'] ?? '',
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $id
        ];

        $result = $this->db->query($sql, $params);
        return $result !== false;
    }

    // ✅ EXCLUIR FORNECEDOR (soft delete)
    public function delete($id) {
        $sql = "UPDATE suppliers SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result !== false;
    }

    // ✅ BUSCAR PARA DROPDOWN
    public function getForDropdown($company_id) {
        $sql = "SELECT id, name FROM suppliers WHERE company_id = ? AND is_active = 1 ORDER BY name";
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetchAll($result);
    }

    // ✅ VERIFICAR SE CPF/CNPJ JÁ EXISTE
    public function cpfCnpjExists($company_id, $cpf_cnpj, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM suppliers 
                WHERE company_id = ? AND cpf_cnpj = ? AND is_active = 1";
        
        $params = [$company_id, $cpf_cnpj];
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $result = $this->db->query($sql, $params);
        $data = $this->fetch($result);
        return $data['count'] > 0;
    }

    // ✅ VERIFICAR SE EMPRESA EXISTE
    public function companyExists($company_id) {
        $sql = "SELECT COUNT(*) as count FROM companies WHERE id = ? AND is_active = 1";
        $result = $this->db->query($sql, [$company_id]);
        $data = $this->fetch($result);
        return $data['count'] > 0;
    }

    // ✅ BUSCAR EMPRESA ATUAL
    public function getCurrentCompany($company_id) {
        $sql = "SELECT id, name, color FROM companies WHERE id = ? AND is_active = 1";
        $result = $this->db->query($sql, [$company_id]);
        return $this->fetch($result);
    }

    // ✅ CONTAGEM DE FORNECEDORES
    public function getCountByCompany($company_id) {
        $sql = "SELECT COUNT(*) as count FROM suppliers WHERE company_id = ? AND is_active = 1";
        $result = $this->db->query($sql, [$company_id]);
        $data = $this->fetch($result);
        return $data['count'] ?? 0;
    }

    // ✅ MÉTODOS AUXILIARES
    private function fetchAll($stmt) {
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    private function fetch($stmt) {
        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function getError() {
        return $this->db->getError();
    }
}
?>