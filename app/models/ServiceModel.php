<?php
//app/models/ServiceModel.php

class ServiceModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Buscar serviço por ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, comp.name as company_name
                FROM services s
                LEFT JOIN companies comp ON s.company_id = comp.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar serviço: " . $e->getMessage());
            return false;
        }
    }

    // Listar serviços por empresa
    public function getByCompany($companyId, $onlyActive = true) {
		try {
			$sql = "SELECT s.*, comp.name as company_name 
					FROM services s 
					LEFT JOIN companies comp ON s.company_id = comp.id 
					WHERE 1=1";
			$params = [];
			
			if ($companyId) {
				$sql .= " AND s.company_id = ?";
				$params[] = $companyId;
			}
			
			if ($onlyActive) {
				$sql .= " AND s.is_active = 1";
			}
			
			$sql .= " ORDER BY s.name";
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log("Erro ao listar serviços: " . $e->getMessage());
			return [];
		}
	}

    // Criar serviço
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO services 
                (company_id, name, description, base_price, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $data['company_id'],
                $data['name'],
                $data['description'] ?? null,
                $data['base_price'],
                $data['is_active'] ?? true
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao criar serviço: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar serviço
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE services 
                SET name = ?, description = ?, base_price = ?, is_active = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['base_price'],
                $data['is_active'] ?? true,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar serviço: " . $e->getMessage());
            return false;
        }
    }

    // Excluir serviço
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM services WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao excluir serviço: " . $e->getMessage());
            return false;
        }
    }

    // Adicionar serviço à viagem
    public function addToTrip($tripId, $serviceId, $customPrice = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO trip_services 
                (trip_id, service_id, custom_price, was_performed) 
                VALUES (?, ?, ?, FALSE)
            ");
            
            return $stmt->execute([
                $tripId,
                $serviceId,
                $customPrice
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao adicionar serviço à viagem: " . $e->getMessage());
            return false;
        }
    }

    // Remover serviço da viagem
    public function removeFromTrip($tripServiceId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trip_services WHERE id = ?");
            return $stmt->execute([$tripServiceId]);
        } catch (PDOException $e) {
            error_log("Erro ao remover serviço da viagem: " . $e->getMessage());
            return false;
        }
    }

    // Buscar serviços da viagem
    public function getByTrip($tripId) {
        try {
            $stmt = $this->db->prepare("
                SELECT ts.*, 
                       s.name as service_name,
                       s.description as service_description,
                       s.base_price as service_base_price,
                       COALESCE(ts.custom_price, s.base_price) as final_price
                FROM trip_services ts
                LEFT JOIN services s ON ts.service_id = s.id
                WHERE ts.trip_id = ?
                ORDER BY s.name
            ");
            $stmt->execute([$tripId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao buscar serviços da viagem: " . $e->getMessage());
            return [];
        }
    }

    // Marcar serviço como realizado/não realizado
    public function updateServicePerformance($tripServiceId, $wasPerformed, $performedDate = null, $notes = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE trip_services 
                SET was_performed = ?, performed_date = ?, notes = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $wasPerformed,
                $performedDate,
                $notes,
                $tripServiceId
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar performance do serviço: " . $e->getMessage());
            return false;
        }
    }

    // Calcular total de serviços da viagem
    public function calculateTripServicesTotal($tripId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(COALESCE(ts.custom_price, s.base_price)) as total_value,
                    SUM(CASE WHEN ts.was_performed THEN COALESCE(ts.custom_price, s.base_price) ELSE 0 END) as performed_value,
                    COUNT(*) as total_services,
                    SUM(CASE WHEN ts.was_performed THEN 1 ELSE 0 END) as performed_services
                FROM trip_services ts
                LEFT JOIN services s ON ts.service_id = s.id
                WHERE ts.trip_id = ?
            ");
            $stmt->execute([$tripId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao calcular total de serviços: " . $e->getMessage());
            return ['total_value' => 0, 'performed_value' => 0, 'total_services' => 0, 'performed_services' => 0];
        }
    }

    // Serviços padrão do sistema
    public function getDefaultServices() {
        return [
            'carregamento' => [
                'name' => 'Carregamento',
                'description' => 'Serviço de carregamento da mercadoria no veículo',
                'base_price' => 150.00
            ],
            'descarga' => [
                'name' => 'Descarga', 
                'description' => 'Serviço de descarga da mercadoria do veículo',
                'base_price' => 150.00
            ],
            'armazenagem' => [
                'name' => 'Armazenagem',
                'description' => 'Armazenagem temporária da carga',
                'base_price' => 200.00
            ],
            'seguro' => [
                'name' => 'Seguro da Carga',
                'description' => 'Seguro adicional para a mercadoria durante o transporte',
                'base_price' => 100.00
            ],
            'waiting_time' => [
                'name' => 'Tempo de Espera',
                'description' => 'Cobrança por tempo de espera adicional além do combinado',
                'base_price' => 80.00
            ],
            'emergency' => [
                'name' => 'Serviço de Emergência',
                'description' => 'Serviço realizado fora do horário comercial ou com urgência',
                'base_price' => 300.00
            ],
            'palletizing' => [
                'name' => 'Paletização',
                'description' => 'Serviço de organização da carga em paletes',
                'base_price' => 120.00
            ],
            'wrapping' => [
                'name' => 'Enfilmamento',
                'description' => 'Enfilmamento da carga para proteção',
                'base_price' => 80.00
            ],
            'customs_clearance' => [
                'name' => 'Despacho Aduaneiro',
                'description' => 'Serviço de liberação aduaneira para cargas internacionais',
                'base_price' => 500.00
            ]
        ];
    }

    // Inicializar serviços padrão para uma empresa
    public function initializeDefaultServices($companyId) {
        try {
            $defaultServices = $this->getDefaultServices();
            $createdCount = 0;

            foreach ($defaultServices as $serviceData) {
                $stmt = $this->db->prepare("
                    INSERT IGNORE INTO services 
                    (company_id, name, description, base_price, is_active) 
                    VALUES (?, ?, ?, ?, 1)
                ");
                
                $success = $stmt->execute([
                    $companyId,
                    $serviceData['name'],
                    $serviceData['description'],
                    $serviceData['base_price']
                ]);
                
                if ($success) {
                    $createdCount++;
                }
            }

            return $createdCount;
        } catch (PDOException $e) {
            error_log("Erro ao inicializar serviços padrão: " . $e->getMessage());
            return 0;
        }
    }

    // Estatísticas de serviços
    public function getServiceStats($companyId = null, $startDate = null, $endDate = null) {
        try {
            $sql = "
                SELECT 
                    s.name as service_name,
                    COUNT(ts.id) as total_uses,
                    SUM(CASE WHEN ts.was_performed THEN 1 ELSE 0 END) as performed_uses,
                    SUM(COALESCE(ts.custom_price, s.base_price)) as total_revenue,
                    AVG(COALESCE(ts.custom_price, s.base_price)) as avg_price,
                    (SUM(CASE WHEN ts.was_performed THEN 1 ELSE 0 END) / COUNT(ts.id)) * 100 as performance_rate
                FROM services s
                LEFT JOIN trip_services ts ON s.id = ts.service_id
                LEFT JOIN trips t ON ts.trip_id = t.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " AND s.company_id = ?";
                $params[] = $companyId;
            }
            
            if ($startDate) {
                $sql .= " AND t.created_at >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND t.created_at <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " GROUP BY s.id, s.name
                      ORDER BY total_revenue DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar estatísticas de serviços: " . $e->getMessage());
            return [];
        }
    }
}
?>