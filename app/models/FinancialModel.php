<?php
// REMOVER o require do config.php aqui - já foi carregado no controller
// O model recebe a conexão do controller

class FinancialModel {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    public function getAccountsPayable($company_id = null, $period = 'month') {
        try {
            $sql = "SELECT ap.*, coa.account_name, coa.account_code 
                    FROM accounts_payable ap 
                    LEFT JOIN chart_of_accounts coa ON ap.chart_account_id = coa.id 
                    WHERE 1=1";
            
            $params = [];
            
            if ($company_id) {
                $sql .= " AND ap.company_id = ?";
                $params[] = $company_id;
            }
            
            // Filtro por período
            $today = date('Y-m-d');
            switch ($period) {
                case 'week':
                    $sql .= " AND ap.due_date BETWEEN ? AND ?";
                    $params[] = $today;
                    $params[] = date('Y-m-d', strtotime('+7 days'));
                    break;
                case 'month':
                    $sql .= " AND ap.due_date BETWEEN ? AND ?";
                    $params[] = date('Y-m-01');
                    $params[] = date('Y-m-t');
                    break;
                case 'quarter':
                    $currentQuarter = ceil(date('n') / 3);
                    $startMonth = (($currentQuarter - 1) * 3) + 1;
                    $endMonth = $startMonth + 2;
                    $sql .= " AND MONTH(ap.due_date) BETWEEN ? AND ? AND YEAR(ap.due_date) = ?";
                    $params[] = $startMonth;
                    $params[] = $endMonth;
                    $params[] = date('Y');
                    break;
                case 'year':
                    $sql .= " AND YEAR(ap.due_date) = ?";
                    $params[] = date('Y');
                    break;
                case 'overdue':
                    $sql .= " AND ap.due_date < ? AND ap.status = 'pendente'";
                    $params[] = $today;
                    break;
            }
            
            $sql .= " ORDER BY 
                CASE 
                    WHEN ap.due_date < CURDATE() AND ap.status = 'pendente' THEN 0
                    ELSE 1 
                END,
                ap.due_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Usar a constante DEBUG do config.php que já foi carregado
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar contas a pagar: " . $e->getMessage());
            }
            return [];
        }
    }

    public function saveAccountPayable($data) {
        try {
            $sql = "INSERT INTO accounts_payable 
                    (company_id, chart_account_id, description, amount, due_date, status, 
                     is_recurring, recurrence_frequency, supplier, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['company_id'],
                $data['chart_account_id'],
                $data['description'],
                $data['amount'],
                $data['due_date'],
                $data['status'],
                $data['is_recurring'],
                $data['recurrence_frequency'],
                $data['supplier'],
                $data['notes']
            ]);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao salvar conta a pagar: " . $e->getMessage());
            }
            return false;
        }
    }

    public function markAccountPaid($id, $company_id) {
        try {
            $sql = "UPDATE accounts_payable 
                    SET status = 'pago', payment_date = CURDATE() 
                    WHERE id = ? AND company_id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id, $company_id]);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao marcar conta como paga: " . $e->getMessage());
            }
            return false;
        }
    }

    public function deleteAccountPayable($id, $company_id) {
        try {
            $sql = "DELETE FROM accounts_payable WHERE id = ? AND company_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id, $company_id]);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao excluir conta a pagar: " . $e->getMessage());
            }
            return false;
        }
    }

    public function getSuppliers($company_id) {
        try {
            $sql = "SELECT DISTINCT supplier FROM accounts_payable 
                    WHERE company_id = ? AND supplier IS NOT NULL AND supplier != '' 
                    ORDER BY supplier";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$company_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar fornecedores: " . $e->getMessage());
            }
            return [];
        }
    }

    public function saveSupplier($data) {
		try {
			// Verifica se a tabela suppliers existe
			$tableCheck = $this->db->query("SHOW TABLES LIKE 'suppliers'")->fetch();
			
			if (!$tableCheck) {
				// Se a tabela não existe, apenas retorna true (o fornecedor será salvo no campo supplier)
				return true;
			}
			
			$sql = "INSERT INTO suppliers 
					(company_id, name, contact, email, phone, address, is_active, created_at) 
					VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
			
			$stmt = $this->db->prepare($sql);
			return $stmt->execute([
				$data['company_id'],
				$data['name'],
				$data['contact'] ?? '',
				$data['email'] ?? '',
				$data['phone'] ?? '',
				$data['address'] ?? ''
			]);
			
		} catch (PDOException $e) {
			if (defined('DEBUG') && DEBUG) {
				error_log("Erro ao salvar fornecedor: " . $e->getMessage());
			}
			return false;
		}
	}

    public function getChartOfAccounts($company_id) {
        try {
            $sql = "SELECT * FROM chart_of_accounts 
                    WHERE company_id = ? AND is_active = 1 
                    ORDER BY account_code";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$company_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar plano de contas: " . $e->getMessage());
            }
            return [];
        }
    }

    public function getTripById($tripId) {
        try {
            $sql = "SELECT * FROM trips WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tripId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar viagem: " . $e->getMessage());
            }
            return null;
        }
    }

    public function getTripExpenses($tripId) {
        try {
            $sql = "SELECT * FROM trip_expenses WHERE trip_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tripId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar despesas da viagem: " . $e->getMessage());
            }
            return [];
        }
    }

    public function getExpenseAccountId() {
        try {
            $sql = "SELECT id FROM chart_of_accounts 
                    WHERE account_type = 'despesa' 
                    AND account_name LIKE '%viagem%' 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : 1;
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar conta de despesas: " . $e->getMessage());
            }
            return 1;
        }
    }

    // Métodos para Dashboard
    public function getTotalAccountsPayable($company_id = null, $period = 'month') {
        try {
            $sql = "SELECT SUM(amount) as total FROM accounts_payable WHERE status = 'pendente'";
            $params = [];
            
            if ($company_id) {
                $sql .= " AND company_id = ?";
                $params[] = $company_id;
            }
            
            switch ($period) {
                case 'week':
                    $sql .= " AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $sql .= " AND due_date BETWEEN ? AND ?";
                    $params[] = date('Y-m-01');
                    $params[] = date('Y-m-t');
                    break;
                case 'year':
                    $sql .= " AND YEAR(due_date) = YEAR(CURDATE())";
                    break;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return floatval($result['total'] ?? 0);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao buscar total de contas a pagar: " . $e->getMessage());
            }
            return 0;
        }
    }
	
	public function getAllSuppliers($company_id) {
		try {
			// Primeiro, verifica se a tabela suppliers existe
			$tableCheck = $this->db->query("SHOW TABLES LIKE 'suppliers'")->fetch();
			
			if (!$tableCheck) {
				// Se a tabela não existe, retorna fornecedores da tabela accounts_payable
				return $this->getSuppliersFromPayables($company_id);
			}
			
			$sql = "SELECT id, name, contact, phone, email 
					FROM suppliers 
					WHERE company_id = ? AND is_active = 1 
					ORDER BY name";
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute([$company_id]);
			
			$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			// Se não houver fornecedores cadastrados, busca da tabela accounts_payable
			if (empty($suppliers)) {
				return $this->getSuppliersFromPayables($company_id);
			}
			
			return $suppliers;
			
		} catch (PDOException $e) {
			if (defined('DEBUG') && DEBUG) {
				error_log("Erro ao buscar fornecedores: " . $e->getMessage());
			}
			// Fallback para fornecedores da tabela accounts_payable
			return $this->getSuppliersFromPayables($company_id);
		}
	}
	
	// Método auxiliar para buscar fornecedores da tabela accounts_payable
	private function getSuppliersFromPayables($company_id) {
		try {
			$sql = "SELECT DISTINCT supplier as name, supplier as contact 
					FROM accounts_payable 
					WHERE company_id = ? AND supplier IS NOT NULL AND supplier != '' 
					ORDER BY supplier";
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute([$company_id]);
			
			$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			// Adiciona IDs fictícios para compatibilidade
			foreach ($suppliers as &$supplier) {
				$supplier['id'] = 'temp_' . md5($supplier['name']);
				$supplier['phone'] = '';
				$supplier['email'] = '';
			}
			
			return $suppliers;
			
		} catch (PDOException $e) {
			if (defined('DEBUG') && DEBUG) {
				error_log("Erro ao buscar fornecedores de accounts_payable: " . $e->getMessage());
			}
			return [];
		}
	}

    public function getPendingAccountsCount($company_id = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM accounts_payable WHERE status = 'pendente'";
            $params = [];
            
            if ($company_id) {
                $sql .= " AND company_id = ?";
                $params[] = $company_id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['count'] ?? 0);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao contar contas pendentes: " . $e->getMessage());
            }
            return 0;
        }
    }

    public function getOverdueAccountsCount($company_id = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM accounts_payable 
                    WHERE status = 'pendente' AND due_date < CURDATE()";
            $params = [];
            
            if ($company_id) {
                $sql .= " AND company_id = ?";
                $params[] = $company_id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['count'] ?? 0);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                error_log("Erro ao contar contas atrasadas: " . $e->getMessage());
            }
            return 0;
        }
    }
}
?>