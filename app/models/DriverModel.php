<?php
// app/models/DriverModel.php - VERSÃO COMPLETA

class DriverModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Criar novo motorista
    public function create($data) {
        try {
            error_log("📝 [DRIVERS MODEL] Criando motorista - Tipo: " . ($data['driver_type'] ?? 'undefined'));
            
            if (!isset($data['company_id'])) {
                error_log("❌ [DRIVERS MODEL] company_id não fornecido!");
                return false;
            }
            
            if (($data['driver_type'] ?? 'external') === 'employee') {
                // Motorista que é funcionário
                $stmt = $this->db->prepare("
                    INSERT INTO drivers 
                    (company_id, employee_id, driver_type, cnh_number, cnh_category, cnh_expiration, custom_commission_rate, is_active) 
                    VALUES (?, ?, 'employee', ?, ?, ?, ?, ?)
                ");
                
                $success = $stmt->execute([
                    $data['company_id'],
                    $data['employee_id'] ?? null,
                    $data['cnh_number'] ?? null,
                    $data['cnh_category'] ?? null,
                    $data['cnh_expiration'] ?? null,
                    $data['custom_commission_rate'] ?? null,
                    $data['is_active'] ?? true
                ]);
            } else {
                // Motorista externo
                $stmt = $this->db->prepare("
                    INSERT INTO drivers 
                    (company_id, driver_type, name, cpf, rg, birth_date, phone, address, email,
                     cnh_number, cnh_category, cnh_expiration, custom_commission_rate, is_active) 
                    VALUES (?, 'external', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $success = $stmt->execute([
                    $data['company_id'],
                    $data['name'] ?? '',
                    $data['cpf'] ?? null,
                    $data['rg'] ?? null,
                    $data['birth_date'] ?? null,
                    $data['phone'] ?? null,
                    $data['address'] ?? null,
                    $data['email'] ?? null,
                    $data['cnh_number'] ?? null,
                    $data['cnh_category'] ?? null,
                    $data['cnh_expiration'] ?? null,
                    $data['custom_commission_rate'] ?? null,
                    $data['is_active'] ?? true
                ]);
            }

            $lastInsertId = $success ? $this->db->lastInsertId() : false;
            error_log("✅ [DRIVERS MODEL] Motorista criado com ID: " . $lastInsertId);
            
            return $lastInsertId;
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro ao criar motorista: " . $e->getMessage());
            return false;
        }
    }

    // Buscar motorista por ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    d.*,
                    e.name as employee_name, 
                    e.cpf as employee_cpf, 
                    e.rg as employee_rg, 
                    e.birth_date as employee_birth_date, 
                    e.phone as employee_phone, 
                    e.email as employee_email,
                    e.address as employee_address,
                    e.position,
                    e.is_active as employee_active,
                    c.name as company_name, 
                    c.color as company_color
                FROM drivers d
                LEFT JOIN employees e ON d.employee_id = e.id
                LEFT JOIN companies c ON d.company_id = c.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $driver = $stmt->fetch();
            
            return $this->ensureDriverFields($driver);
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro ao buscar motorista: " . $e->getMessage());
            return false;
        }
    }

    // Listar todos os motoristas - VERSÃO CORRIGIDA
    public function getAll($companyId = null, $includeInactive = false) {
        try {
            error_log("🔍 [DRIVERS MODEL] Buscando motoristas - CompanyID: " . ($companyId ?: 'null'));
            
            $sql = "
                SELECT 
                    d.*,
                    e.name as employee_name,
                    e.position,
                    e.is_active as employee_active,
                    e.address as employee_address,
                    c.name as company_name,
                    c.color as company_color,
                    e.cpf as employee_cpf,
                    e.rg as employee_rg,
                    e.birth_date as employee_birth_date,
                    e.phone as employee_phone,
                    e.email as employee_email
                FROM drivers d
                LEFT JOIN employees e ON d.employee_id = e.id
                LEFT JOIN companies c ON d.company_id = c.id
                WHERE 1=1
            ";
            
            $params = array();
            
            if (!$includeInactive) {
                $sql .= " AND d.is_active = 1";
            }
            
            if ($companyId) {
                $sql .= " AND d.company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY 
                CASE 
                    WHEN d.driver_type = 'employee' THEN e.name
                    ELSE d.name 
                END ASC";
            
            error_log("📋 [DRIVERS MODEL] Query: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $drivers = $stmt->fetchAll();
            
            error_log("✅ [DRIVERS MODEL] Motoristas encontrados: " . count($drivers));
            
            $enhancedDrivers = array();
            foreach ($drivers as $driver) {
                $enhancedDriver = $this->ensureDriverFields($driver);
                $enhancedDrivers[] = $enhancedDriver;
            }
            
            return $enhancedDrivers;
            
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro no getAll: " . $e->getMessage());
            return array();
        }
    }

    // Atualizar motorista
    public function update($id, $data) {
        try {
            if (!isset($data['company_id'])) {
                error_log("❌ [DRIVERS MODEL] company_id não fornecido para update!");
                return false;
            }
            
            if ($data['driver_type'] === 'employee') {
                // Atualizar motorista funcionário
                $stmt = $this->db->prepare("
                    UPDATE drivers 
                    SET company_id = ?, employee_id = ?, driver_type = 'employee', name = NULL, cpf = NULL, rg = NULL,
                        birth_date = NULL, phone = NULL, address = NULL, email = NULL,
                        cnh_number = ?, cnh_category = ?, cnh_expiration = ?, 
                        custom_commission_rate = ?, is_active = ?
                    WHERE id = ?
                ");
                
                return $stmt->execute([
                    $data['company_id'],
                    $data['employee_id'] ?? null,
                    $data['cnh_number'] ?? null,
                    $data['cnh_category'] ?? null,
                    $data['cnh_expiration'] ?? null,
                    $data['custom_commission_rate'] ?? null,
                    $data['is_active'] ?? true,
                    $id
                ]);
            } else {
                // Atualizar motorista externo
                $stmt = $this->db->prepare("
                    UPDATE drivers 
                    SET company_id = ?, employee_id = NULL, driver_type = 'external', 
                        name = ?, cpf = ?, rg = ?, birth_date = ?, phone = ?, 
                        address = ?, email = ?,
                        cnh_number = ?, cnh_category = ?, cnh_expiration = ?, 
                        custom_commission_rate = ?, is_active = ?
                    WHERE id = ?
                ");
                
                return $stmt->execute([
                    $data['company_id'],
                    $data['name'] ?? '',
                    $data['cpf'] ?? null,
                    $data['rg'] ?? null,
                    $data['birth_date'] ?? null,
                    $data['phone'] ?? null,
                    $data['address'] ?? null,
                    $data['email'] ?? null,
                    $data['cnh_number'] ?? null,
                    $data['cnh_category'] ?? null,
                    $data['cnh_expiration'] ?? null,
                    $data['custom_commission_rate'] ?? null,
                    $data['is_active'] ?? true,
                    $id
                ]);
            }
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro ao atualizar motorista: " . $e->getMessage());
            return false;
        }
    }

    // Buscar motoristas para dropdown
    public function getForDropdown($companyId = null) {
        try {
            $sql = "
                SELECT d.id, 
                       CASE 
                           WHEN d.driver_type = 'employee' THEN e.name 
                           ELSE d.name 
                       END as name,
                       d.cnh_category, d.cnh_number,
                       d.driver_type
                FROM drivers d
                LEFT JOIN employees e ON d.employee_id = e.id
                WHERE d.is_active = 1
            ";
            
            $params = array();
            
            if ($companyId) {
                $sql .= " AND (e.company_id = ? OR d.driver_type = 'external')";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro ao buscar motoristas para dropdown: " . $e->getMessage());
            return array();
        }
    }

    // Garantir que todos os campos do motorista existam
    private function ensureDriverFields($driver) {
        if (!$driver) {
            return $driver;
        }

        // Campos padrão
        $defaultFields = array(
            'id' => 0,
            'company_id' => null,
            'employee_id' => null,
            'driver_type' => 'external',
            'name' => '',
            'cpf' => null,
            'rg' => null,
            'birth_date' => null,
            'phone' => null,
            'address' => null,
            'email' => null,
            'employee_name' => '',
            'employee_cpf' => null,
            'employee_rg' => null,
            'employee_birth_date' => null,
            'employee_phone' => null,
            'employee_email' => null,
            'employee_address' => null,
            'position' => '',
            'cnh_number' => null,
            'cnh_category' => null,
            'cnh_expiration' => null,
            'custom_commission_rate' => null,
            'is_active' => true,
            'employee_active' => true,
            'company_name' => '',
            'company_color' => '#FF6B00',
            'created_at' => null,
            'updated_at' => null
        );

        $driver = array_merge($defaultFields, $driver);

        // Campos calculados
        $driver['display_name'] = $driver['driver_type'] === 'employee' 
            ? $driver['employee_name'] 
            : $driver['name'];

        $driver['display_cpf'] = $driver['driver_type'] === 'employee'
            ? $driver['employee_cpf']
            : $driver['cpf'];

        $driver['display_birth_date'] = $driver['driver_type'] === 'employee'
            ? $driver['employee_birth_date']
            : $driver['birth_date'];

        $driver['display_phone'] = $driver['driver_type'] === 'employee'
            ? $driver['employee_phone']
            : $driver['phone'];

        $driver['display_email'] = $driver['driver_type'] === 'employee'
            ? $driver['employee_email']
            : $driver['email'];

        $driver['display_address'] = $driver['driver_type'] === 'employee'
            ? $driver['employee_address']
            : $driver['address'];

        return $driver;
    }

    // Buscar funcionários disponíveis para motorista
    public function getPotentialDrivers($companyId = null) {
        try {
            error_log("🔍 [DRIVERS MODEL] Buscando funcionários disponíveis - CompanyID: " . ($companyId ?: 'Todas'));
            
            $sql = "
                SELECT 
                    e.id, 
                    e.name, 
                    e.cpf, 
                    e.rg, 
                    e.birth_date, 
                    e.phone, 
                    e.email,
                    e.address,
                    e.position,
                    e.is_driver,
                    e.is_active,
                    c.name as company_name
                FROM employees e
                LEFT JOIN companies c ON e.company_id = c.id
                WHERE e.is_active = 1 
                AND e.is_driver = 1
                AND e.id NOT IN (
                    SELECT employee_id 
                    FROM drivers 
                    WHERE employee_id IS NOT NULL
                    AND is_active = 1
                )
            ";
            
            $params = array();
            
            if ($companyId) {
                $sql .= " AND e.company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY e.name";
            
            error_log("📋 [DRIVERS MODEL] SQL: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            error_log("✅ [DRIVERS MODEL] Funcionários disponíveis encontrados: " . count($result));
            
            return $result;
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro ao buscar funcionários para motorista: " . $e->getMessage());
            return array();
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM drivers WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("❌ [DRIVERS MODEL] Erro ao excluir motorista: " . $e->getMessage());
            return false;
        }
    }

    // Calcular idade
    public function calculateAge($birthDate) {
        if (!$birthDate) return null;
        
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birth);
        
        return $age->y;
    }

    // Verificar se CNH está expirada
    public function isCNHExpired($expirationDate) {
        if (!$expirationDate) return false;
        
        $expiration = new DateTime($expirationDate);
        $today = new DateTime();
        
        return $today > $expiration;
    }

    // Dias até expirar a CNH
    public function daysUntilCNHExpiration($expirationDate) {
        if (!$expirationDate) return null;
        
        $expiration = new DateTime($expirationDate);
        $today = new DateTime();
        $interval = $today->diff($expiration);
        
        return $interval->days * ($interval->invert ? -1 : 1);
    }
}
?>