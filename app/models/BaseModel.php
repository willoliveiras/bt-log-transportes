<?php
// app/models/BaseModel.php

class BaseModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ✅ MÉTODO PRINCIPAL: Buscar todas as bases com dados relacionados
    public function getAll($companyId = null, $includeInactive = false) {
		try {
			$sql = "SELECT 
						b.*,
						c.name as company_name,
						c.color as company_color,
						e.name as manager_name,
						e.position as manager_position,
						(SELECT COUNT(*) FROM vehicles v WHERE v.base_id = b.id AND v.is_active = 1) as total_vehicles,
						(SELECT COUNT(*) FROM employees emp WHERE emp.base_id = b.id AND emp.is_driver = 1 AND emp.is_active = 1) as total_drivers
					FROM bases b
					LEFT JOIN companies c ON b.company_id = c.id
					LEFT JOIN employees e ON b.manager_id = e.id
					WHERE 1=1";
			
			$params = [];
			
			if (!empty($companyId) && $companyId !== 'all') {
				$sql .= " AND b.company_id = ?";
				$params[] = $companyId;
			}
			
			if (!$includeInactive) {
				$sql .= " AND b.is_active = 1";
			}
			
			$sql .= " ORDER BY b.name ASC";
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
			
		} catch (PDOException $e) {
			error_log("❌ [BaseModel] Erro ao buscar bases: " . $e->getMessage());
			return [];
		}
	}
    
    // ✅ MÉTODO: Buscar base por ID
    public function getById($id) {
        try {
            $sql = "
                SELECT b.*, c.name as company_name, c.color as company_color,
                       e.name as manager_name, e.position as manager_position
                FROM bases b
                LEFT JOIN companies c ON b.company_id = c.id
                LEFT JOIN employees e ON b.manager_id = e.id
                WHERE b.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao buscar base: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Criar nova base
    public function create($data) {
        try {
            $sql = "INSERT INTO bases (company_id, name, address, city, state, phone, email, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $data['company_id'],
                $data['name'],
                $data['address'] ?? '',
                $data['city'] ?? '',
                $data['state'] ?? '',
                $data['phone'] ?? '',
                $data['email'] ?? '',
                $data['is_active'] ?? true
            ]);
            
            error_log("💾 [BaseModel] Base criada: " . ($success ? 'SUCESSO' : 'FALHA'));
            
            return $success;
            
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao criar: " . $e->getMessage());
            return false;
        }
    }
	
	 public function assignSelectedEmployees($baseId, $employeeIds) {
        try {
            $success = true;
            foreach ($employeeIds as $employeeId) {
                $stmt = $this->db->prepare("UPDATE employees SET base_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                if (!$stmt->execute([$baseId, $employeeId])) {
                    $success = false;
                }
            }
            return $success;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao vincular funcionários: " . $e->getMessage());
            return false;
        }
    }

    // ✅ NOVO MÉTODO: Vincular veículos selecionados
    public function assignSelectedVehicles($baseId, $vehicleIds) {
        try {
            $success = true;
            foreach ($vehicleIds as $vehicleId) {
                $stmt = $this->db->prepare("UPDATE vehicles SET base_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                if (!$stmt->execute([$baseId, $vehicleId])) {
                    $success = false;
                }
            }
            return $success;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao vincular veículos: " . $e->getMessage());
            return false;
        }
    }

    // ✅ NOVO MÉTODO: Buscar estatísticas de utilização
    public function getUtilizationStats($baseId) {
        try {
            $sql = "
                SELECT 
                    capacity_vehicles,
                    capacity_drivers,
                    (SELECT COUNT(*) FROM vehicles WHERE base_id = ? AND is_active = 1) as current_vehicles,
                    (SELECT COUNT(*) FROM drivers WHERE base_id = ? AND is_active = 1) as current_drivers
                FROM bases 
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$baseId, $baseId, $baseId]);
            $stats = $stmt->fetch();
            
            if ($stats) {
                $vehicleUtilization = $stats['capacity_vehicles'] > 0 ? 
                    round(($stats['current_vehicles'] / $stats['capacity_vehicles']) * 100) : 0;
                $driverUtilization = $stats['capacity_drivers'] > 0 ? 
                    round(($stats['current_drivers'] / $stats['capacity_drivers']) * 100) : 0;
                
                return [
                    'vehicle_utilization' => min($vehicleUtilization, 100),
                    'driver_utilization' => min($driverUtilization, 100),
                    'current_vehicles' => $stats['current_vehicles'],
                    'current_drivers' => $stats['current_drivers'],
                    'capacity_vehicles' => $stats['capacity_vehicles'],
                    'capacity_drivers' => $stats['capacity_drivers']
                ];
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao buscar estatísticas: " . $e->getMessage());
            return null;
        }
    }

    // ✅ MÉTODO: Atualizar base
    public function update($id, $data) {
        try {
            $sql = "
                UPDATE bases 
                SET company_id = ?, name = ?, address = ?, city = ?, state = ?, 
                    phone = ?, email = ?, manager_id = ?, opening_date = ?,
                    capacity_vehicles = ?, capacity_drivers = ?, operating_hours = ?,
                    latitude = ?, longitude = ?, notes = ?, is_active = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            
            $success = $stmt->execute([
                $data['company_id'],
                $data['name'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['manager_id'] ?? null,
                $data['opening_date'] ?? null,
                $data['capacity_vehicles'] ?? 0,
                $data['capacity_drivers'] ?? 0,
                $data['operating_hours'] ?? null,
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
                $data['notes'] ?? null,
                $data['is_active'] ?? true,
                $id
            ]);
            
            if ($success) {
                error_log("✅ [BaseModel] Base atualizada com sucesso - ID: " . $id);
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao atualizar base: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Excluir base (soft delete - desativar)
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("UPDATE bases SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                error_log("✅ [BaseModel] Base desativada - ID: " . $id);
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao excluir base: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Ativar base
    public function activate($id) {
        try {
            $stmt = $this->db->prepare("UPDATE bases SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                error_log("✅ [BaseModel] Base ativada - ID: " . $id);
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao ativar base: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Buscar gerentes disponíveis
    public function getAvailableManagers($companyId = null) {
        try {
            $sql = "
                SELECT id, name, position, email, phone
                FROM employees 
                WHERE is_active = 1 
                AND (position LIKE '%gerente%' OR position LIKE '%supervisor%' OR position LIKE '%coordenador%')
            ";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao buscar gerentes: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Buscar estados que possuem bases
    public function getStatesWithBases($companyId = null) {
        try {
            $sql = "SELECT DISTINCT state FROM bases WHERE is_active = 1 AND state IS NOT NULL AND state != ''";
            $params = [];
            
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $sql .= " ORDER BY state";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao buscar estados: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Buscar funcionários da base
    public function getBaseEmployees($baseId) {
        try {
            $sql = "
                SELECT 
                    e.id, e.name, e.position, e.email, e.phone, 
                    e.photo, e.is_driver, e.is_active,
                    c.name as company_name
                FROM employees e
                LEFT JOIN companies c ON e.company_id = c.id
                WHERE e.base_id = ? AND e.is_active = 1
                ORDER BY e.name
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$baseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao buscar funcionários da base: " . $e->getMessage());
            return [];
        }
    }
    
    // ✅ MÉTODO: Buscar veículos da base
    public function getBaseVehicles($baseId) {
        try {
            $sql = "
                SELECT 
                    v.*,
                    c.name as company_name,
                    CASE 
                        WHEN v.status = 'em_viagem' THEN 'Em Viagem'
                        WHEN v.status = 'manutencao' THEN 'Manutenção'
                        WHEN v.status = 'inativo' THEN 'Inativo'
                        ELSE 'Disponível'
                    END as status_text
                FROM vehicles v
                LEFT JOIN companies c ON v.company_id = c.id
                WHERE v.base_id = ? AND v.is_active = 1
                ORDER BY v.plate
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$baseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao buscar veículos da base: " . $e->getMessage());
            return [];
        }
    }
    
    // ✅ MÉTODO: Vincular funcionário à base
    public function assignEmployeeToBase($employeeId, $baseId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET base_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$baseId, $employeeId]);
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao vincular funcionário à base: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ MÉTODO: Vincular veículo à base
    public function assignVehicleToBase($vehicleId, $baseId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE vehicles 
                SET base_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$baseId, $vehicleId]);
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao vincular veículo à base: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ MÉTODO: Remover funcionário da base
    public function removeEmployeeFromBase($employeeId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET base_id = NULL, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$employeeId]);
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao remover funcionário da base: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ MÉTODO: Remover veículo da base
    public function removeVehicleFromBase($vehicleId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE vehicles 
                SET base_id = NULL, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$vehicleId]);
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao remover veículo da base: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Calcular estatísticas para dashboard
    public function getDashboardStats($companyId = null) {
        try {
            $bases = $this->getAll($companyId, true);
            
            $totalBases = count($bases);
            $activeBases = array_filter($bases, function($base) {
                return $base['is_active'];
            });
            
            $totalCapacityVehicles = array_sum(array_column($bases, 'capacity_vehicles'));
            $totalCapacityDrivers = array_sum(array_column($bases, 'capacity_drivers'));
            $totalCurrentVehicles = array_sum(array_column($bases, 'total_vehicles'));
            $totalCurrentDrivers = array_sum(array_column($bases, 'total_drivers'));
            
            // Calcular utilização média
            $utilizationVehicles = $totalCapacityVehicles > 0 ? 
                min(100, round(($totalCurrentVehicles / $totalCapacityVehicles) * 100)) : 0;
            $utilizationDrivers = $totalCapacityDrivers > 0 ? 
                min(100, round(($totalCurrentDrivers / $totalCapacityDrivers) * 100)) : 0;
            
            // Bases por estado
            $basesByState = [];
            foreach ($bases as $base) {
                if ($base['state']) {
                    $basesByState[$base['state']] = ($basesByState[$base['state']] ?? 0) + 1;
                }
            }
            
            // Bases sem gerente
            $basesWithoutManager = array_filter($bases, function($base) {
                return empty($base['manager_id']) && $base['is_active'];
            });
            
            return [
                'total_bases' => $totalBases,
                'active_bases' => count($activeBases),
                'inactive_bases' => $totalBases - count($activeBases),
                'total_capacity_vehicles' => $totalCapacityVehicles,
                'total_capacity_drivers' => $totalCapacityDrivers,
                'total_current_vehicles' => $totalCurrentVehicles,
                'total_current_drivers' => $totalCurrentDrivers,
                'utilization_vehicles' => $utilizationVehicles,
                'utilization_drivers' => $utilizationDrivers,
                'bases_by_state' => $basesByState,
                'states_count' => count($basesByState),
                'bases_without_manager' => count($basesWithoutManager),
                'avg_utilization' => round(($utilizationVehicles + $utilizationDrivers) / 2)
            ];
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao calcular estatísticas: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Verificar se nome da base já existe
    public function nameExists($name, $companyId, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM bases WHERE name = ? AND company_id = ?";
            $params = [$name, $companyId];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao verificar nome da base: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Buscar última base inserida
    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }

    // ✅ MÉTODO: Atualizar contadores de recursos
    public function updateResourceCounters($baseId) {
        try {
            // Atualizar contador de veículos
            $stmt = $this->db->prepare("
                UPDATE bases 
                SET current_vehicles = (
                    SELECT COUNT(*) FROM vehicles 
                    WHERE base_id = ? AND is_active = 1
                ) 
                WHERE id = ?
            ");
            $stmt->execute([$baseId, $baseId]);
            
            // Atualizar contador de motoristas
            $stmt = $this->db->prepare("
                UPDATE bases 
                SET current_drivers = (
                    SELECT COUNT(*) FROM drivers 
                    WHERE base_id = ? AND is_active = 1
                ) 
                WHERE id = ?
            ");
            $stmt->execute([$baseId, $baseId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("❌ [BaseModel] Erro ao atualizar contadores: " . $e->getMessage());
            return false;
        }
    }
}
?>