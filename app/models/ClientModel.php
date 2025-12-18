<?php
class ClientModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Criar novo cliente - CORRIGIDO
    public function create($data) {
        try {
            error_log("🎯 Tentando criar cliente: " . print_r($data, true));

            // Validar tipo e categoria
            if ($data['type'] === 'pessoa_fisica' && $data['client_category'] === 'empresa_parceira') {
                throw new Exception('Pessoa física não pode ser empresa parceira');
            }

            $stmt = $this->db->prepare("
                INSERT INTO clients 
                (company_id, name, fantasy_name, type, client_category, cpf_cnpj, email, phone, address, 
                 contact_name, contact_phone, contact_email, client_segment, 
                 client_size, payment_terms, credit_limit, registration_date, 
                 partner_company_id, notes, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $params = [
                $data['company_id'],
                $data['name'],
                $data['fantasy_name'] ?? null,
                $data['type'],
                $data['client_category'] ?? 'cliente_comum',
                $data['cpf_cnpj'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['contact_name'] ?? null,
                $data['contact_phone'] ?? null,
                $data['contact_email'] ?? null,
                $data['client_segment'] ?? 'outros',
                $data['client_size'] ?? 'medio',
                $data['payment_terms'] ?? null,
                $data['credit_limit'] ?? 0.00,
                $data['registration_date'] ?? null,
                $data['partner_company_id'] ?? null,
                $data['notes'] ?? null,
                $data['is_active'] ?? true
            ];

            error_log("📊 Parâmetros para INSERT: " . print_r($params, true));
            
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("✅ Cliente criado com sucesso!");
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("❌ Erro no INSERT: " . print_r($errorInfo, true));
                throw new Exception("Erro de banco: " . $errorInfo[2]);
            }
            
        } catch (PDOException $e) {
            error_log("❌ PDOException ao criar cliente: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("❌ Exception ao criar cliente: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar cliente - CORRIGIDO
    public function update($id, $data) {
        try {
            error_log("🎯 Tentando atualizar cliente ID $id: " . print_r($data, true));

            // Validar tipo e categoria
            if ($data['type'] === 'pessoa_fisica' && $data['client_category'] === 'empresa_parceira') {
                throw new Exception('Pessoa física não pode ser empresa parceira');
            }

            $stmt = $this->db->prepare("
                UPDATE clients 
                SET company_id = ?, name = ?, fantasy_name = ?, type = ?, client_category = ?, cpf_cnpj = ?, email = ?, 
                    phone = ?, address = ?, contact_name = ?, contact_phone = ?, 
                    contact_email = ?, client_segment = ?, client_size = ?, 
                    payment_terms = ?, credit_limit = ?, registration_date = ?, 
                    partner_company_id = ?, notes = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $params = [
                $data['company_id'],
                $data['name'],
                $data['fantasy_name'] ?? null,
                $data['type'],
                $data['client_category'] ?? 'cliente_comum',
                $data['cpf_cnpj'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['contact_name'] ?? null,
                $data['contact_phone'] ?? null,
                $data['contact_email'] ?? null,
                $data['client_segment'] ?? 'outros',
                $data['client_size'] ?? 'medio',
                $data['payment_terms'] ?? null,
                $data['credit_limit'] ?? 0.00,
                $data['registration_date'] ?? null,
                $data['partner_company_id'] ?? null,
                $data['notes'] ?? null,
                $data['is_active'] ?? true,
                $id
            ];

            error_log("📊 Parâmetros para UPDATE: " . print_r($params, true));
            
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("✅ Cliente atualizado com sucesso!");
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("❌ Erro no UPDATE: " . print_r($errorInfo, true));
                throw new Exception("Erro de banco: " . $errorInfo[2]);
            }
            
        } catch (PDOException $e) {
            error_log("❌ PDOException ao atualizar cliente: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("❌ Exception ao atualizar cliente: " . $e->getMessage());
            return false;
        }
    }

    // Buscar cliente por ID - COMPLETO
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       comp.name as company_name, 
                       comp.color as company_color,
                       partner.name as partner_company_name
                FROM clients c
                LEFT JOIN companies comp ON c.company_id = comp.id
                LEFT JOIN clients partner ON c.partner_company_id = partner.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("❌ Erro ao buscar cliente: " . $e->getMessage());
            return false;
        }
    }

    // Buscar todos os clientes
    public function getAll($companyId = null) {
        try {
            $sql = "
                SELECT c.*, 
                       comp.name as company_name, 
                       comp.color as company_color,
                       COUNT(t.id) as total_trips,
                       COALESCE(SUM(t.freight_value), 0) as total_revenue
                FROM clients c
                LEFT JOIN companies comp ON c.company_id = comp.id
                LEFT JOIN trips t ON c.id = t.client_id
            ";

            if ($companyId) {
                $sql .= " WHERE c.company_id = ?";
                $sql .= " GROUP BY c.id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$companyId]);
            } else {
                $sql .= " GROUP BY c.id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ Erro ao buscar clientes: " . $e->getMessage());
            return [];
        }
    }

    // Buscar empresas parceiras
    public function getPartnerCompanies($companyId = null) {
        try {
            $sql = "
                SELECT c.*, comp.name as company_name
                FROM clients c
                LEFT JOIN companies comp ON c.company_id = comp.id
                WHERE c.client_category = 'empresa_parceira' 
                AND c.is_active = 1
            ";

            if ($companyId) {
                $sql .= " AND c.company_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$companyId]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ Erro ao buscar empresas parceiras: " . $e->getMessage());
            return [];
        }
    }

    // Verificar se documento já existe
    public function documentExists($document, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM clients WHERE cpf_cnpj = ?";
            $params = [$document];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("❌ Erro ao verificar documento: " . $e->getMessage());
            return false;
        }
    }

    // Excluir cliente
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM clients WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("❌ Erro ao excluir cliente: " . $e->getMessage());
            return false;
        }
    }

    // Buscar estatísticas de clientes
    public function getClientStats($companyId = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_clients,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_clients,
                    SUM(CASE WHEN client_category = 'cliente_comum' THEN 1 ELSE 0 END) as common_clients,
                    SUM(CASE WHEN client_category = 'empresa_parceira' THEN 1 ELSE 0 END) as partner_companies,
                    SUM(CASE WHEN client_category = 'cliente_empresa_parceira' THEN 1 ELSE 0 END) as referred_clients
                FROM clients
            ";

            if ($companyId) {
                $sql .= " WHERE company_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$companyId]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("❌ Erro ao buscar estatísticas: " . $e->getMessage());
            return [
                'total_clients' => 0,
                'active_clients' => 0,
                'common_clients' => 0,
                'partner_companies' => 0,
                'referred_clients' => 0
            ];
        }
    }
}
?>