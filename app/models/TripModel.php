<?php
// app/models/TripModel.php

class TripModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getDb() {
        return $this->db;
    }

    // Criar nova viagem
    public function create($data) {
		try {
			error_log("📥 [TRIP MODEL] Dados para criação: " . print_r($data, true));

			// ✅ CORREÇÃO: Incluir commission_amount se existir
			$hasCommission = isset($data['commission_amount']);
			
			$stmt = $this->db->prepare("
				INSERT INTO trips 
				(company_id, client_id, driver_id, vehicle_id, origin_base_id, 
				 destination_base_id, trip_number, description, origin_address, 
				 destination_address, distance_km, scheduled_date, start_date, 
				 end_date, freight_value, status" . ($hasCommission ? ", commission_amount" : "") . ") 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . ($hasCommission ? ", ?" : "") . ")
			");
			
			$tripNumber = $this->generateTripNumber($data['company_id']);
			
			$params = [
				$data['company_id'],
				$data['client_id'],
				$data['driver_id'], 
				$data['vehicle_id'],
				$data['origin_base_id'] ?? null,
				$data['destination_base_id'] ?? null,
				$tripNumber,
				$data['description'] ?? null,
				$data['origin_address'],
				$data['destination_address'],
				$data['distance_km'] ?? null,
				$data['scheduled_date'] ?? null,
				$data['start_date'] ?? null,
				$data['end_date'] ?? null,
				$data['freight_value'],
				$data['status'] ?? 'agendada'
			];

			// ✅ Adicionar commission_amount se existir
			if ($hasCommission) {
				$params[] = $data['commission_amount'];
			}

			error_log("📤 [TRIP MODEL] Parâmetros: " . print_r($params, true));
			
			$success = $stmt->execute($params);
			
			if ($success) {
				$lastInsertId = $this->db->lastInsertId();
				error_log("✅ [TRIP MODEL] Viagem criada com ID: " . $lastInsertId);
				return $lastInsertId;
			} else {
				$errorInfo = $stmt->errorInfo();
				error_log("❌ [TRIP MODEL] Erro na execução: " . print_r($errorInfo, true));
				return false;
			}
			
		} catch (PDOException $e) {
			error_log("❌ [TRIP MODEL] PDOException: " . $e->getMessage());
			error_log("📋 SQL: " . $e->getTraceAsString());
			return false;
		}
	}

    // Atualizar viagem
    public function update($id, $data) {
		try {
			error_log("📥 [TRIP MODEL] Dados para atualização ID {$id}: " . print_r($data, true));

			// ✅ CORREÇÃO: Incluir commission_amount se existir
			$hasCommission = isset($data['commission_amount']);
			
			$stmt = $this->db->prepare("
				UPDATE trips 
				SET company_id = ?, client_id = ?, driver_id = ?, vehicle_id = ?,
					origin_base_id = ?, destination_base_id = ?, description = ?,
					origin_address = ?, destination_address = ?, distance_km = ?,
					scheduled_date = ?, start_date = ?, end_date = ?,
					freight_value = ?, status = ?, updated_at = CURRENT_TIMESTAMP
					" . ($hasCommission ? ", commission_amount = ?" : "") . "
				WHERE id = ?
			");
			
			$params = [
				$data['company_id'],
				$data['client_id'],
				$data['driver_id'],
				$data['vehicle_id'],
				$data['origin_base_id'] ?? null,
				$data['destination_base_id'] ?? null,
				$data['description'] ?? null,
				$data['origin_address'],
				$data['destination_address'],
				$data['distance_km'] ?? null,
				$data['scheduled_date'] ?? null,
				$data['start_date'] ?? null,
				$data['end_date'] ?? null,
				$data['freight_value'],
				$data['status'] ?? 'agendada'
			];

			// ✅ Adicionar commission_amount se existir
			if ($hasCommission) {
				$params[] = $data['commission_amount'];
			}

			$params[] = $id;

			error_log("📤 [TRIP MODEL] Parâmetros update: " . print_r($params, true));
			
			$success = $stmt->execute($params);
			
			if ($success) {
				error_log("✅ [TRIP MODEL] Viagem atualizada com ID: " . $id);
				return true;
			} else {
				$errorInfo = $stmt->errorInfo();
				error_log("❌ [TRIP MODEL] Erro na execução update: " . print_r($errorInfo, true));
				return false;
			}
			
		} catch (PDOException $e) {
			error_log("❌ [TRIP MODEL] PDOException no update: " . $e->getMessage());
			error_log("📋 SQL: " . $e->getTraceAsString());
			return false;
		}
	}

    // ✅ NOVO MÉTODO: Salvar serviços da viagem
    public function saveTripServices($tripId, $serviceIds) {
		try {
			error_log("💾 Salvando serviços para viagem {$tripId}: " . print_r($serviceIds, true));
			
			// Primeiro remover serviços existentes
			$deleteSuccess = $this->deleteTripServices($tripId);
			error_log("🗑️ Serviços antigos removidos: " . ($deleteSuccess ? 'SIM' : 'NÃO'));
			
			if (empty($serviceIds)) {
				error_log("📭 Nenhum serviço para salvar");
				return true;
			}
			
			// Inserir novos serviços
			$stmt = $this->db->prepare("
				INSERT INTO trip_services (trip_id, service_id, custom_price, was_performed, created_at)
				VALUES (?, ?, ?, 0, NOW())
			");
			
			$successCount = 0;
			$errorCount = 0;
			
			foreach ($serviceIds as $serviceId) {
				if (empty($serviceId)) {
					continue;
				}
				
				// Buscar preço base do serviço
				$serviceStmt = $this->db->prepare("SELECT base_price FROM services WHERE id = ?");
				$serviceStmt->execute([$serviceId]);
				$service = $serviceStmt->fetch();
				
				$customPrice = $service['base_price'] ?? 0;
				
				error_log("💰 Serviço {$serviceId} - Preço: {$customPrice}");
				
				if ($stmt->execute([$tripId, $serviceId, $customPrice])) {
					$successCount++;
					error_log("✅ Serviço {$serviceId} salvo com sucesso");
				} else {
					$errorCount++;
					$errorInfo = $stmt->errorInfo();
					error_log("❌ Erro ao salvar serviço {$serviceId}: " . print_r($errorInfo, true));
				}
			}
			
			error_log("📊 Resultado: {$successCount} salvos, {$errorCount} erros");
			return $successCount > 0;
			
		} catch (PDOException $e) {
			error_log("❌ [TRIP MODEL] Erro ao salvar serviços: " . $e->getMessage());
			error_log("📋 SQL Error: " . $e->getTraceAsString());
			return false;
		}
	}
    
    // ✅ NOVO MÉTODO: Deletar serviços da viagem
    public function deleteTripServices($tripId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trip_services WHERE trip_id = ?");
            return $stmt->execute([$tripId]);
        } catch (PDOException $e) {
            error_log("❌ [TRIP MODEL] Erro ao deletar serviços: " . $e->getMessage());
            return false;
        }
    }

    // Buscar todas as viagens
    public function getAll($companyId = null) {
		try {
			$sql = "
				SELECT DISTINCT
					t.*,
					c.name as company_name,
					c.color as company_color,
					cl.name as client_name,
					d.name as driver_name,
					d.driver_type,
					d.custom_commission_rate as driver_commission_rate,
					-- ✅ CORREÇÃO: Buscar comissão do funcionário
					e.commission_rate as employee_commission_rate,
					v.plate as vehicle_plate,
					v.brand as vehicle_brand,
					v.model as vehicle_model,
					ob.name as origin_base_name,
					ob.city as origin_base_city,
					ob.state as origin_base_state,
					db.name as destination_base_name,
					db.city as destination_base_city,
					db.state as destination_base_state,
					
					t.freight_value as freight_value,
					
					-- Total de serviços via subquery
					(SELECT COALESCE(SUM(ts2.custom_price), 0) 
					 FROM trip_services ts2 
					 WHERE ts2.trip_id = t.id) as total_services_value,
					
					-- Total de gastos via subquery  
					(SELECT COALESCE(SUM(te2.amount), 0)
					 FROM trip_expenses te2 
					 WHERE te2.trip_id = t.id) as total_expenses,
					
					-- ✅ CORREÇÃO: Buscar comissão calculada
					COALESCE(
						(SELECT commission_rate FROM driver_commissions WHERE trip_id = t.id LIMIT 1),
						CASE 
							WHEN d.driver_type = 'employee' THEN 
								COALESCE(d.custom_commission_rate, e.commission_rate, 0.00)
							ELSE 
								COALESCE(d.custom_commission_rate, 0.00)
						END
					) as commission_rate,
					
					-- ✅ CORREÇÃO: Buscar valor da comissão
					COALESCE(
						(SELECT commission_amount FROM driver_commissions WHERE trip_id = t.id LIMIT 1),
						(t.freight_value * 
							CASE 
								WHEN d.driver_type = 'employee' THEN 
									COALESCE(d.custom_commission_rate, e.commission_rate, 0.00)
								ELSE 
									COALESCE(d.custom_commission_rate, 0.00)
							END
						) / 100
					) as commission_amount
					
				FROM trips t
				LEFT JOIN companies c ON t.company_id = c.id
				LEFT JOIN clients cl ON t.client_id = cl.id
				LEFT JOIN drivers d ON t.driver_id = d.id
				-- ✅ CORREÇÃO: Join com employees para buscar comissão
				LEFT JOIN employees e ON d.employee_id = e.id
				LEFT JOIN vehicles v ON t.vehicle_id = v.id
				LEFT JOIN bases ob ON t.origin_base_id = ob.id
				LEFT JOIN bases db ON t.destination_base_id = db.id
				WHERE 1=1
			";
			
			
			$where = [];
			$params = [];
			
			if ($companyId) {
				$where[] = "t.company_id = ?";
				$params[] = $companyId;
			}
			
			if (!empty($where)) {
				$sql .= " AND " . implode(" AND ", $where);
			}
			
			$sql .= " ORDER BY t.created_at DESC";
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$trips = $stmt->fetchAll();
			
			// ✅ CORREÇÃO: Recalcular valores para garantir precisão
			foreach ($trips as &$trip) {
				$trip = $this->recalculateTripValues($trip);
			}
			
			return $trips;
			
		} catch (PDOException $e) {
			error_log("❌ [TRIP MODEL] Erro ao buscar viagens: " . $e->getMessage());
			return [];
		}
	}
	
	private function recalculateTripValues($trip) {
		// Valores básicos
		$freightValue = floatval($trip['freight_value'] ?? 0);
		$totalServicesValue = floatval($trip['total_services_value'] ?? 0);
		$totalExpenses = floatval($trip['total_expenses'] ?? 0);
		
		// ✅ CORREÇÃO CRÍTICA: CALCULAR COMISSÃO SEMPRE
		$commissionRate = $this->calculateCorrectCommissionRate($trip);
		$commissionAmount = ($freightValue * $commissionRate) / 100;
		
		// ✅ FORÇAR: Se for motorista funcionário com 1% de comissão
		if ($commissionRate == 0 && ($trip['driver_type'] ?? '') == 'employee') {
			$employeeCommissionRate = floatval($trip['employee_commission_rate'] ?? 0);
			if ($employeeCommissionRate > 0) {
				$commissionRate = $employeeCommissionRate;
				$commissionAmount = ($freightValue * $commissionRate) / 100;
				error_log("🚨 CORREÇÃO DE EMERGÊNCIA: Aplicando comissão do funcionário: $commissionRate%");
			}
		}
		
		error_log("💰 [FINAL CALC] Frete: $freightValue, Comissão: $commissionRate% = $commissionAmount, Gastos: $totalExpenses");
		
		// ✅ CORREÇÃO: Cálculos corretos
		$totalRevenue = $freightValue + $totalServicesValue;
		$totalCost = $commissionAmount + $totalExpenses;
		$profit = $totalRevenue - $totalCost;
		
		error_log("💰 [FINAL RESULT] Receita: $totalRevenue, Despesa: $totalCost, Lucro: $profit");
		
		// Atualizar valores
		$trip['commission_rate'] = $commissionRate;
		$trip['commission_amount'] = $commissionAmount;
		$trip['total_revenue'] = $totalRevenue;
		$trip['total_cost'] = $totalCost;
		$trip['profit'] = $profit;
		
		return $trip;
	}
    
    // ✅ NOVO MÉTODO: Calcular comissão correta
    private function calculateCorrectCommissionRate($trip) {
        $driverType = $trip['driver_type'] ?? 'external';
        $customCommissionRate = $trip['driver_commission_rate'] ?? null;
        
        // ✅ CORREÇÃO: Se tem comissão personalizada no motorista, usa ela
        if ($customCommissionRate !== null && $customCommissionRate > 0) {
            return floatval($customCommissionRate);
        }
        
        // ✅ CORREÇÃO: Se não tem comissão personalizada, NÃO TEM COMISSÃO (0%)
        return 0.00;
    }

    // Buscar viagem com detalhes
    public function getTripWithDetails($id) {
		try {
			$sql = "
				SELECT 
					t.*,
					c.name as company_name,
					c.color as company_color,
					cl.name as client_name,
					d.name as driver_name,
					d.driver_type,
					d.custom_commission_rate as driver_commission_rate,
					v.plate as vehicle_plate,
					v.brand as vehicle_brand,
					v.model as vehicle_model,
					ob.name as origin_base_name,
					ob.city as origin_base_city,
					ob.state as origin_base_state,
					db.name as destination_base_name,
					db.city as destination_base_city,
					db.state as destination_base_state,
					
					-- ✅ CORREÇÃO: Buscar todos os campos necessários
					t.freight_value as freight_value,
					t.origin_address,
					t.destination_address,
					t.distance_km,
					t.description,
					t.status,
					
					-- Total de serviços
					(SELECT COALESCE(SUM(ts2.custom_price), 0) 
					 FROM trip_services ts2 
					 WHERE ts2.trip_id = t.id) as total_services_value,
					
					-- Total de gastos  
					(SELECT COALESCE(SUM(te2.amount), 0)
					 FROM trip_expenses te2 
					 WHERE te2.trip_id = t.id) as total_expenses,
					
					-- Comissão
					(SELECT COALESCE(commission_amount, 0)
					 FROM driver_commissions dc2 
					 WHERE dc2.trip_id = t.id LIMIT 1) as commission_amount,
					 
					-- Taxa de comissão
					(SELECT COALESCE(commission_rate, 
						CASE 
							WHEN d.custom_commission_rate IS NOT NULL AND d.custom_commission_rate > 0 THEN d.custom_commission_rate
							ELSE 0.00 
						END)
					 FROM driver_commissions dc3 
					 WHERE dc3.trip_id = t.id LIMIT 1) as commission_rate
					
				FROM trips t
				LEFT JOIN companies c ON t.company_id = c.id
				LEFT JOIN clients cl ON t.client_id = cl.id
				LEFT JOIN drivers d ON t.driver_id = d.id
				LEFT JOIN vehicles v ON t.vehicle_id = v.id
				LEFT JOIN bases ob ON t.origin_base_id = ob.id
				LEFT JOIN bases db ON t.destination_base_id = db.id
				WHERE t.id = ?
			";
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute([$id]);
			$trip = $stmt->fetch();
			
			if ($trip) {
				// ✅ CORREÇÃO: Recalcular valores
				$trip = $this->recalculateTripValues($trip);
				
				// ✅ CORREÇÃO: Buscar serviços detalhados
				$servicesStmt = $this->db->prepare("
					SELECT ts.*, s.name as service_name, s.base_price
					FROM trip_services ts
					LEFT JOIN services s ON ts.service_id = s.id
					WHERE ts.trip_id = ?
				");
				$servicesStmt->execute([$id]);
				$trip['services'] = $servicesStmt->fetchAll();
				
				// ✅ CORREÇÃO: Buscar IDs dos serviços para o form
				$serviceIdsStmt = $this->db->prepare("
					SELECT GROUP_CONCAT(service_id) as service_ids
					FROM trip_services 
					WHERE trip_id = ?
				");
				$serviceIdsStmt->execute([$id]);
				$serviceIdsResult = $serviceIdsStmt->fetch();
				$trip['service_ids'] = $serviceIdsResult['service_ids'] ?? '';
				
				// ✅ CORREÇÃO: Buscar dados de mudança de rota se existirem
				if (!empty($trip['actual_origin_address']) || !empty($trip['actual_destination_address'])) {
					$trip['route_change'] = true;
				}
			}
			
			return $trip;
			
		} catch (PDOException $e) {
			error_log("❌ [TRIP MODEL] Erro ao buscar viagem {$id}: " . $e->getMessage());
			return null;
		}
	}
	
	// Calcular comissão do motorista - CORRIGIDO
    public function calculateDriverCommission($tripId) {
        try {
            // Buscar dados da viagem
            $trip = $this->getTripWithDetails($tripId);
            if (!$trip) return false;

            // ✅ CORREÇÃO: Usar comissão já calculada corretamente
            $commissionRate = $trip['commission_rate'] ?? 10.00;
            $commissionAmount = $trip['commission_amount'] ?? 0;

            // Salvar comissão
            $stmt = $this->db->prepare("
                INSERT INTO driver_commissions 
                (trip_id, driver_id, commission_rate, commission_amount, payment_status) 
                VALUES (?, ?, ?, ?, 'pendente')
                ON DUPLICATE KEY UPDATE 
                commission_rate = VALUES(commission_rate),
                commission_amount = VALUES(commission_amount)
            ");
            
            return $stmt->execute([
                $tripId,
                $trip['driver_id'],
                $commissionRate,
                $commissionAmount
            ]);

        } catch (Exception $e) {
            error_log("❌ [TRIP MODEL] Erro ao calcular comissão: " . $e->getMessage());
            return false;
        }
    }

    // Excluir viagem
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trips WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("❌ [TRIP MODEL] Erro ao excluir viagem {$id}: " . $e->getMessage());
            return false;
        }
    }

    // Adicionar gasto à viagem
    public function addExpense($tripId, $expenseData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO trip_expenses 
                (trip_id, expense_type, description, amount, expense_date, receipt_image) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $tripId,
                $expenseData['expense_type'],
                $expenseData['description'],
                $expenseData['amount'],
                $expenseData['expense_date'],
                $expenseData['receipt_image']
            ]);
            
        } catch (PDOException $e) {
            error_log("❌ [TRIP MODEL] Erro ao adicionar gasto: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO ADICIONADO: Buscar estatísticas de viagens
    public function getTripStats($companyId = null, $startDate = null, $endDate = null) {
		try {
			$sql = "
				SELECT 
					COUNT(*) as total_trips,
					
					-- Receitas
					SUM(t.freight_value) as total_freight,
					SUM((SELECT COALESCE(SUM(ts.custom_price), 0) FROM trip_services ts WHERE ts.trip_id = t.id)) as total_services,
					(SUM(t.freight_value) + SUM((SELECT COALESCE(SUM(ts.custom_price), 0) FROM trip_services ts WHERE ts.trip_id = t.id))) as total_revenue,
					
					-- Despesas
					SUM((SELECT COALESCE(SUM(amount), 0) FROM trip_expenses te WHERE te.trip_id = t.id)) as total_expenses,
					
					-- ✅ CORREÇÃO CRÍTICA: Incluir comissões das viagens
					SUM(COALESCE(t.commission_amount, 0)) as total_commissions,
					
					-- Outras métricas
					AVG(distance_km) as avg_distance,
					COUNT(CASE WHEN status = 'concluida' THEN 1 END) as completed_trips,
					COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as in_progress_trips,
					COUNT(CASE WHEN status = 'agendada' THEN 1 END) as scheduled_trips
				FROM trips t
				WHERE 1=1
			";
			
			$params = [];
			
			if ($companyId) {
				$sql .= " AND t.company_id = ?";
				$params[] = $companyId;
			}
			
			if ($startDate) {
				$sql .= " AND t.scheduled_date >= ?";
				$params[] = $startDate;
			}
			
			if ($endDate) {
				$sql .= " AND t.scheduled_date <= ?";
				$params[] = $endDate;
			}
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stats = $stmt->fetch();
			
			// ✅ CORREÇÃO: Lucro = Receita Total - Despesas - Comissões
			$totalRevenue = ($stats['total_revenue'] ?? 0);
			$totalExpenses = ($stats['total_expenses'] ?? 0);
			$totalCommissions = ($stats['total_commissions'] ?? 0);
			
			$stats['total_profit'] = $totalRevenue - $totalExpenses - $totalCommissions;
			
			error_log("💰 [DASHBOARD STATS] Receita: $totalRevenue, Gastos: $totalExpenses, Comissões: $totalCommissions, Lucro: {$stats['total_profit']}");
			
			return $stats;
			
		} catch (PDOException $e) {
			error_log("❌ [TRIP MODEL] Erro ao buscar estatísticas: " . $e->getMessage());
			return [
				'total_trips' => 0,
				'total_freight' => 0,
				'total_services' => 0,
				'total_revenue' => 0,
				'total_expenses' => 0,
				'total_commissions' => 0,
				'total_profit' => 0,
				'avg_distance' => 0,
				'completed_trips' => 0,
				'in_progress_trips' => 0,
				'scheduled_trips' => 0
			];
		}
	}

    // ✅ MÉTODO: Buscar serviços para dropdown
    public function getServicesForDropdown($companyId = null) {
        try {
            $sql = "SELECT id, name, base_price FROM services WHERE is_active = 1";
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
            error_log("Erro ao buscar serviços: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Buscar comissão do motorista
    public function getDriverCommission($driverId) {
        try {
            $sql = "SELECT custom_commission_rate, name, driver_type FROM drivers WHERE id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch();
            
            if (!$driver) {
                return 0.00; // ✅ CORREÇÃO: 0% se motorista não encontrado
            }
            
            // ✅ CORREÇÃO: Só tem comissão se for personalizada
            $commissionRate = $driver['custom_commission_rate'] ?? 0.00;
            
            return floatval($commissionRate);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar comissão do motorista: " . $e->getMessage());
            return 0.00; // ✅ CORREÇÃO: 0% em caso de erro
        }
    }

    // Gerar número da viagem
    private function generateTripNumber($companyId) {
        try {
            $prefix = 'TRP';
            $year = date('Y');
            $month = date('m');
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM trips 
                WHERE company_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
            ");
            $stmt->execute([$companyId, $year, $month]);
            $result = $stmt->fetch();
            
            $sequence = $result['count'] + 1;
            return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("❌ [TRIP MODEL] Erro ao gerar número da viagem: " . $e->getMessage());
            return 'TRP' . date('YmdHis');
        }
    }
}
?>