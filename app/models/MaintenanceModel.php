<?php
//app/models/MaintenanceModel.php

class MaintenanceModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Criar nova manutenção
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO maintenances 
                (vehicle_id, type, description, maintenance_date, next_maintenance_date, 
                 cost, current_km, next_maintenance_km, service_provider, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $data['vehicle_id'],
                $data['type'],
                $data['description'],
                $data['maintenance_date'],
                $data['next_maintenance_date'] ?? null,
                $data['cost'],
                $data['current_km'] ?? null,
                $data['next_maintenance_km'] ?? null,
                $data['service_provider'] ?? null,
                $data['notes'] ?? null
            ]);
            
            if ($success) {
                $maintenanceId = $this->db->lastInsertId();
                
                // Atualizar dados do veículo
                $this->updateVehicleMaintenanceData($data['vehicle_id'], $data);
                
                // Criar alerta se necessário
                if ($data['next_maintenance_date'] || $data['next_maintenance_km']) {
                    $this->createMaintenanceAlert($maintenanceId, $data);
                }
                
                return $maintenanceId;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Erro ao criar manutenção: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar manutenção
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE maintenances 
                SET vehicle_id = ?, type = ?, description = ?, maintenance_date = ?,
                    next_maintenance_date = ?, cost = ?, current_km = ?,
                    next_maintenance_km = ?, service_provider = ?, notes = ?
                WHERE id = ?
            ");
            
            $success = $stmt->execute([
                $data['vehicle_id'],
                $data['type'],
                $data['description'],
                $data['maintenance_date'],
                $data['next_maintenance_date'] ?? null,
                $data['cost'],
                $data['current_km'] ?? null,
                $data['next_maintenance_km'] ?? null,
                $data['service_provider'] ?? null,
                $data['notes'] ?? null,
                $id
            ]);
            
            if ($success) {
                // Atualizar dados do veículo
                $this->updateVehicleMaintenanceData($data['vehicle_id'], $data);
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar manutenção: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar dados de manutenção do veículo
    private function updateVehicleMaintenanceData($vehicleId, $data) {
        try {
            $updateFields = [];
            $params = [];
            
            if (isset($data['current_km'])) {
                $updateFields[] = "current_km = ?";
                $params[] = $data['current_km'];
            }
            
            if (isset($data['next_maintenance_km'])) {
                $updateFields[] = "next_maintenance_km = ?";
                $params[] = $data['next_maintenance_km'];
            }
            
            if (isset($data['maintenance_date'])) {
                $updateFields[] = "last_maintenance_date = ?";
                $params[] = $data['maintenance_date'];
            }
            
            if (!empty($updateFields)) {
                $sql = "UPDATE vehicles SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $params[] = $vehicleId;
                
                $stmt = $this->db->prepare($sql);
                return $stmt->execute($params);
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar dados do veículo: " . $e->getMessage());
            return false;
        }
    }

    // Buscar manutenção por ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       v.plate as vehicle_plate,
                       v.brand as vehicle_brand, 
                       v.model as vehicle_model,
                       v.type as vehicle_type,
                       v.current_km as vehicle_current_km,
                       comp.name as company_name,
                       comp.color as company_color
                FROM maintenances m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                LEFT JOIN companies comp ON v.company_id = comp.id
                WHERE m.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar manutenção: " . $e->getMessage());
            return false;
        }
    }

    // Listar todas as manutenções
    public function getAll($vehicleId = null, $companyId = null, $limit = 100) {
        try {
            $sql = "
                SELECT m.*, 
                       v.plate as vehicle_plate,
                       v.brand as vehicle_brand,
                       v.model as vehicle_model,
                       v.type as vehicle_type,
                       comp.name as company_name,
                       comp.color as company_color,
                       CASE 
                         WHEN m.next_maintenance_date IS NOT NULL AND m.next_maintenance_date < CURDATE() THEN 'atrasada'
                         WHEN m.next_maintenance_km IS NOT NULL AND v.current_km >= m.next_maintenance_km THEN 'atrasada'
                         WHEN m.next_maintenance_date IS NOT NULL AND m.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'proxima'
                         WHEN m.next_maintenance_km IS NOT NULL AND (m.next_maintenance_km - v.current_km) <= 500 THEN 'proxima'
                         ELSE 'em_dia'
                       END as maintenance_status
                FROM maintenances m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                LEFT JOIN companies comp ON v.company_id = comp.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($vehicleId) {
                $sql .= " AND m.vehicle_id = ?";
                $params[] = $vehicleId;
            }
            
            if ($companyId) {
                $sql .= " AND v.company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY m.maintenance_date DESC, m.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao listar manutenções: " . $e->getMessage());
            return [];
        }
    }

    // Excluir manutenção
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM maintenances WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao excluir manutenção: " . $e->getMessage());
            return false;
        }
    }

    // Buscar manutenções próximas (alertas)
    public function getUpcomingMaintenances($days = 7, $kmThreshold = 500) {
        try {
            $sql = "
                SELECT m.*,
                       v.plate as vehicle_plate,
                       v.brand as vehicle_brand,
                       v.model as vehicle_model,
                       v.current_km as vehicle_current_km,
                       comp.name as company_name,
                       CASE 
                         WHEN m.next_maintenance_date IS NOT NULL AND m.next_maintenance_date < CURDATE() THEN 'atrasada'
                         WHEN m.next_maintenance_km IS NOT NULL AND v.current_km >= m.next_maintenance_km THEN 'atrasada'
                         WHEN m.next_maintenance_date IS NOT NULL AND m.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 'proxima_data'
                         WHEN m.next_maintenance_km IS NOT NULL AND (m.next_maintenance_km - v.current_km) <= ? THEN 'proxima_km'
                         ELSE 'em_dia'
                       END as alert_type,
                       CASE 
                         WHEN m.next_maintenance_date IS NOT NULL THEN DATEDIFF(m.next_maintenance_date, CURDATE())
                         WHEN m.next_maintenance_km IS NOT NULL THEN (m.next_maintenance_km - v.current_km)
                         ELSE NULL
                       END as days_or_km_remaining
                FROM maintenances m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                LEFT JOIN companies comp ON v.company_id = comp.id
                WHERE (m.next_maintenance_date IS NOT NULL AND m.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY))
                   OR (m.next_maintenance_km IS NOT NULL AND (m.next_maintenance_km - v.current_km) <= ?)
                ORDER BY 
                  CASE 
                    WHEN m.next_maintenance_date < CURDATE() OR v.current_km >= m.next_maintenance_km THEN 1
                    WHEN m.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) OR (m.next_maintenance_km - v.current_km) <= 100 THEN 2
                    ELSE 3
                  END,
                  m.next_maintenance_date ASC,
                  (m.next_maintenance_km - v.current_km) ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days, $kmThreshold, $days, $kmThreshold]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar manutenções próximas: " . $e->getMessage());
            return [];
        }
    }

    // Criar alerta de manutenção
    private function createMaintenanceAlert($maintenanceId, $data) {
        try {
            $maintenance = $this->getById($maintenanceId);
            if (!$maintenance) return false;
            
            $alertData = [
                'company_id' => $this->getVehicleCompanyId($data['vehicle_id']),
                'type' => 'maintenance',
                'title' => 'Manutenção Agendada',
                'message' => "Veículo {$maintenance['vehicle_plate']} precisa de manutenção",
                'related_entity_type' => 'maintenance',
                'related_entity_id' => $maintenanceId,
                'due_date' => $data['next_maintenance_date'] ?? null,
                'priority' => 'medium'
            ];
            
            $stmt = $this->db->prepare("
                INSERT INTO alerts 
                (company_id, type, title, message, related_entity_type, related_entity_id, due_date, priority) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $alertData['company_id'],
                $alertData['type'],
                $alertData['title'],
                $alertData['message'],
                $alertData['related_entity_type'],
                $alertData['related_entity_id'],
                $alertData['due_date'],
                $alertData['priority']
            ]);
            
        } catch (PDOException $e) {
            error_log("Erro ao criar alerta: " . $e->getMessage());
            return false;
        }
    }

    // Buscar empresa do veículo
    private function getVehicleCompanyId($vehicleId) {
        try {
            $stmt = $this->db->prepare("SELECT company_id FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicleId]);
            $result = $stmt->fetch();
            return $result ? $result['company_id'] : null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar empresa do veículo: " . $e->getMessage());
            return null;
        }
    }

    // Estatísticas de manutenção
    public function getMaintenanceStats($companyId = null, $startDate = null, $endDate = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_maintenances,
                    SUM(m.cost) as total_cost,
                    AVG(m.cost) as avg_cost,
                    COUNT(CASE WHEN m.type = 'preventiva' THEN 1 END) as preventive_count,
                    COUNT(CASE WHEN m.type = 'corretiva' THEN 1 END) as corrective_count,
                    SUM(CASE WHEN m.type = 'preventiva' THEN m.cost ELSE 0 END) as preventive_cost,
                    SUM(CASE WHEN m.type = 'corretiva' THEN m.cost ELSE 0 END) as corrective_cost,
                    COUNT(DISTINCT m.vehicle_id) as vehicles_with_maintenance
                FROM maintenances m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " AND v.company_id = ?";
                $params[] = $companyId;
            }
            
            if ($startDate) {
                $sql .= " AND m.maintenance_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND m.maintenance_date <= ?";
                $params[] = $endDate;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch();
            
            // Calcular percentuais
            if ($stats['total_maintenances'] > 0) {
                $stats['preventive_percentage'] = ($stats['preventive_count'] / $stats['total_maintenances']) * 100;
                $stats['corrective_percentage'] = ($stats['corrective_count'] / $stats['total_maintenances']) * 100;
            } else {
                $stats['preventive_percentage'] = 0;
                $stats['corrective_percentage'] = 0;
            }
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [];
        }
    }

    // Tipos de manutenção
    public function getMaintenanceTypes() {
        return [
            'preventiva' => 'Preventiva',
            'corretiva' => 'Corretiva',
            'preditiva' => 'Preditiva',
            'inspecao' => 'Inspeção'
        ];
    }

    // Serviços de manutenção comuns
    public function getCommonServices() {
        return [
            'troca_oleo' => 'Troca de Óleo e Filtro',
            'filtro_ar' => 'Troca do Filtro de Ar',
            'filtro_combustivel' => 'Troca do Filtro de Combustível',
            'pastilhas_freio' => 'Troca de Pastilhas de Freio',
            'discos_freio' => 'Troca de Discos de Freio',
            'pneus' => 'Troca de Pneus',
            'alinhamento' => 'Alinhamento e Balanceamento',
            'suspensao' => 'Revisão da Suspensão',
            'transmissao' => 'Troca de Óleo da Transmissão',
            'diferencial' => 'Troca de Óleo do Diferencial',
            'bateria' => 'Troca da Bateria',
            'correia' => 'Troca da Correia Dentada',
            'velas' => 'Troca de Velas',
            'injetores' => 'Limpeza de Bicos Injetores',
            'ar_condicionado' => 'Manutenção do Ar Condicionado',
            'freios' => 'Revisão Completa do Sistema de Freios',
            'motor' => 'Revisão do Motor',
            'eletrica' => 'Revisão do Sistema Elétrico',
            'outros' => 'Outros Serviços'
        ];
    }

    // Intervalos de manutenção padrão (em KM)
    public function getDefaultIntervals() {
        return [
            'troca_oleo' => 10000,
            'filtro_ar' => 15000,
            'filtro_combustivel' => 20000,
            'pastilhas_freio' => 25000,
            'pneus' => 50000,
            'alinhamento' => 10000,
            'transmissao' => 60000,
            'diferencial' => 60000,
            'correia' => 80000
        ];
    }
}
?>