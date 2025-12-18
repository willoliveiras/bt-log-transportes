<?php
class VehicleModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Criar novo veículo
    public function create($data) {
        try {
            $sql = "INSERT INTO vehicles 
                    (company_id, plate, brand, model, year, color, chassis_number, type, vehicle_subtype, 
                     capacity, capacity_unit, fuel_type, fuel_capacity, average_consumption, 
                     insurance_company, insurance_number, insurance_expiry, registration_number, 
                     registration_expiry, status, is_active, notes, current_km) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                $data['company_id'],
                $data['plate'],
                $data['brand'],
                $data['model'],
                $data['year'],
                $data['color'],
                $data['chassis_number'] ?? null,
                $data['type'],
                $data['vehicle_subtype'] ?? null,
                $data['capacity'] ?? null,
                $data['capacity_unit'] ?? 'kg',
                $data['fuel_type'],
                $data['fuel_capacity'] ?? null,
                $data['average_consumption'] ?? null,
                $data['insurance_company'] ?? null,
                $data['insurance_number'] ?? null,
                $data['insurance_expiry'] ?? null,
                $data['registration_number'] ?? null,
                $data['registration_expiry'] ?? null,
                $data['status'] ?? 'disponivel',
                $data['is_active'] ?? 1,
                $data['notes'] ?? null,
                $data['current_km'] ?? 0
            ]);
            
        } catch (PDOException $e) {
            error_log("❌ [VEHICLE MODEL] Erro ao criar veículo: " . $e->getMessage());
            return false;
        }
    }

    // Buscar veículo por ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT v.*, c.name as company_name, c.color as company_color
                FROM vehicles v
                LEFT JOIN companies c ON v.company_id = c.id
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ [VEHICLE MODEL] Erro ao buscar veículo: " . $e->getMessage());
            return false;
        }
    }

    // Listar todos os veículos
    public function getAll($companyId = null, $includeInactive = false) {
        try {
            $sql = "
                SELECT v.*, c.name as company_name, c.color as company_color
                FROM vehicles v
                LEFT JOIN companies c ON v.company_id = c.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " AND v.company_id = ?";
                $params[] = $companyId;
            }
            
            if (!$includeInactive) {
                $sql .= " AND v.is_active = 1";
            }
            
            $sql .= " ORDER BY v.type, v.brand, v.model";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ [VEHICLE MODEL] Erro ao listar veículos: " . $e->getMessage());
            return [];
        }
    }

    // Atualizar veículo
    public function update($id, $data) {
        try {
            $sql = "UPDATE vehicles 
                    SET company_id = ?, plate = ?, brand = ?, model = ?, year = ?, color = ?, 
                        chassis_number = ?, type = ?, vehicle_subtype = ?, capacity = ?, 
                        capacity_unit = ?, fuel_type = ?, fuel_capacity = ?, average_consumption = ?,
                        insurance_company = ?, insurance_number = ?, insurance_expiry = ?,
                        registration_number = ?, registration_expiry = ?, status = ?, 
                        is_active = ?, notes = ?, current_km = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                $data['company_id'],
                $data['plate'],
                $data['brand'],
                $data['model'],
                $data['year'],
                $data['color'],
                $data['chassis_number'] ?? null,
                $data['type'],
                $data['vehicle_subtype'] ?? null,
                $data['capacity'] ?? null,
                $data['capacity_unit'] ?? 'kg',
                $data['fuel_type'],
                $data['fuel_capacity'] ?? null,
                $data['average_consumption'] ?? null,
                $data['insurance_company'] ?? null,
                $data['insurance_number'] ?? null,
                $data['insurance_expiry'] ?? null,
                $data['registration_number'] ?? null,
                $data['registration_expiry'] ?? null,
                $data['status'] ?? 'disponivel',
                $data['is_active'] ?? 1,
                $data['notes'] ?? null,
                $data['current_km'] ?? 0,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("❌ [VEHICLE MODEL] Erro ao atualizar veículo: " . $e->getMessage());
            return false;
        }
    }

    // Excluir veículo
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM vehicles WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("❌ [VEHICLE MODEL] Erro ao excluir veículo: " . $e->getMessage());
            return false;
        }
    }

    // Buscar ID do último insert
    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }

    // Verificar se placa já existe
    public function plateExists($plate, $excludeId = null) {
        try {
            $sql = "SELECT id FROM vehicles WHERE plate = ?";
            $params = [$plate];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("❌ [VEHICLE MODEL] Erro ao verificar placa: " . $e->getMessage());
            return false;
        }
    }

    // Métodos auxiliares para tipos
    public function getVehicleTypes() {
        return [
            'carro' => 'Carro',
            'motocicleta' => 'Motocicleta',
            'caminhonete' => 'Caminhonete',
            'pickup' => 'Pickup',
            'van' => 'Van',
            'minivan' => 'Minivan',
            'onibus' => 'Ônibus',
            'microonibus' => 'Microônibus',
            'caminhao' => 'Caminhão',
            'caminhao_toco' => 'Caminhão Toco',
            'caminhao_truck' => 'Caminhão Truck',
            'caminhao_carreta' => 'Carreta',
            'caminhao_bitrem' => 'Bitrem',
            'caminhao_rodotrem' => 'Rodotrem',
            'utilitario' => 'Utilitário',
            'suv' => 'SUV',
            'hatch' => 'Hatch',
            'sedan' => 'Sedan',
            'hatchback' => 'Hatchback',
            'outros' => 'Outros'
        ];
    }

    public function getFuelTypes() {
        return [
            'diesel' => 'Diesel',
            'gasolina' => 'Gasolina',
            'etanol' => 'Etanol',
            'gnv' => 'GNV',
            'eletrico' => 'Elétrico',
            'hibrido' => 'Híbrido'
        ];
    }

    public function getCapacityUnits() {
        return [
            'kg' => 'Quilogramas (kg)',
            'ton' => 'Toneladas (t)',
            'litros' => 'Litros (L)',
            'unidades' => 'Unidades',
            'passageiros' => 'Passageiros'
        ];
    }

    public function getStatusTypes() {
        return [
            'disponivel' => 'Disponível',
            'em_viagem' => 'Em Viagem',
            'manutencao' => 'Manutenção',
            'inativo' => 'Inativo'
        ];
    }
}
?>