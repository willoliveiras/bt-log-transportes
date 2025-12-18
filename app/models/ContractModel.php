<?php
// app/models/ContractModel.php

class ContractModel {
    private $db;
    private $uploadPath = __DIR__ . '/../../storage/contracts/';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Criar diretório de contratos se não existir
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0777, true);
        }
    }

    // ✅ MÉTODO: Buscar todos os contratos
    public function getAll($companyId = null, $status = null) {
        try {
            $sql = "SELECT 
                        c.*,
                        com.name as company_name,
                        com.color as company_color,
                        cli.name as client_name,
                        cli.fantasy_name as client_fantasy_name,
                        sup.name as supplier_name,
                        sup.fantasy_name as supplier_fantasy_name,
                        CASE 
                            WHEN c.status = 'active' AND c.end_date < CURDATE() THEN 'expired'
                            WHEN c.status = 'active' AND DATEDIFF(c.end_date, CURDATE()) <= 30 THEN 'expiring_soon'
                            ELSE c.status
                        END as display_status
                    FROM contracts c
                    LEFT JOIN companies com ON c.company_id = com.id
                    LEFT JOIN clients cli ON c.client_id = cli.id
                    LEFT JOIN suppliers sup ON c.supplier_id = sup.id
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($companyId) && $companyId !== 'all') {
                $sql .= " AND c.company_id = ?";
                $params[] = $companyId;
            }
            
            if ($status === 'active') {
                $sql .= " AND c.status = 'active' AND c.end_date >= CURDATE()";
            } elseif ($status === 'expired') {
                $sql .= " AND c.status = 'active' AND c.end_date < CURDATE()";
            } elseif ($status === 'expiring') {
                $sql .= " AND c.status = 'active' 
                         AND c.end_date >= CURDATE() 
                         AND DATEDIFF(c.end_date, CURDATE()) <= 30";
            } elseif ($status) {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY c.end_date ASC, c.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("❌ [ContractModel] Erro ao buscar contratos: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Buscar contrato por ID
    public function getById($id) {
        try {
            $sql = "SELECT 
                        c.*,
                        com.name as company_name,
                        com.color as company_color,
                        cli.name as client_name,
                        cli.fantasy_name as client_fantasy_name,
                        cli.cpf_cnpj as client_cpf_cnpj,
                        cli.email as client_email,
                        cli.phone as client_phone,
                        sup.name as supplier_name,
                        sup.fantasy_name as supplier_fantasy_name,
                        sup.cpf_cnpj as supplier_cpf_cnpj,
                        sup.email as supplier_email,
                        sup.phone as supplier_phone
                    FROM contracts c
                    LEFT JOIN companies com ON c.company_id = com.id
                    LEFT JOIN clients cli ON c.client_id = cli.id
                    LEFT JOIN suppliers sup ON c.supplier_id = sup.id
                    WHERE c.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $contract = $stmt->fetch();
            
            if ($contract && $contract['contract_file']) {
                $contract['file_url'] = $this->getFileUrl($contract['contract_file']);
                $contract['file_path'] = $this->uploadPath . $contract['contract_file'];
            }
            
            return $contract;
        } catch (PDOException $e) {
            error_log("❌ [ContractModel] Erro ao buscar contrato: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Criar novo contrato
    public function create($data, $file = null) {
		try {
			error_log("📦 ContractModel::create - Iniciando criação");
			error_log("📦 Dados recebidos: " . json_encode($data));
			
			// ✅ VALIDAÇÃO BÁSICA DOS DADOS
			if (empty($data['company_id']) || empty($data['contract_number']) || empty($data['title'])) {
				throw new Exception("Dados incompletos para criação do contrato");
			}
			
			$this->db->beginTransaction();
			
			// ✅ UPLOAD DO ARQUIVO COM TRATAMENTO DE ERROS
			$fileName = null;
			if ($file && $file['error'] === UPLOAD_ERR_OK) {
				error_log("📁 Fazendo upload do arquivo: " . $file['name']);
				
				try {
					$fileName = $this->uploadContractFile($file);
					if (!$fileName) {
						throw new Exception("Erro ao fazer upload do arquivo");
					}
					error_log("✅ Arquivo salvo como: " . $fileName);
				} catch (Exception $e) {
					error_log("❌ Erro no upload: " . $e->getMessage());
					// Não falha a criação se houver erro no upload, apenas não anexa o arquivo
					$fileName = null;
				}
			}
			
			$sql = "INSERT INTO contracts (
						company_id, 
						client_id, 
						supplier_id,
						contract_type,
						contract_number,
						title,
						description,
						start_date,
						end_date,
						value,
						currency,
						payment_terms,
						renewal_terms,
						status,
						contract_file,
						notes,
						created_by
					) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			
			error_log("📝 SQL: " . $sql);
			
			$stmt = $this->db->prepare($sql);
			
			// ✅ PREPARAR VALORES COM VALIDAÇÃO
			$values = [
				(int)$data['company_id'],
				isset($data['client_id']) && !empty($data['client_id']) ? (int)$data['client_id'] : null,
				isset($data['supplier_id']) && !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
				$data['contract_type'],
				$data['contract_number'],
				$data['title'],
				$data['description'] ?? '',
				$data['start_date'],
				$data['end_date'],
				(float)$data['value'] ?? 0,
				$data['currency'] ?? 'BRL',
				$data['payment_terms'] ?? '',
				$data['renewal_terms'] ?? '',
				$data['status'] ?? 'draft',
				$fileName,
				$data['notes'] ?? '',
				$this->getCurrentUserId()
			];
			
			error_log("📦 Valores para inserção: " . json_encode($values));
			
			$success = $stmt->execute($values);
			
			if (!$success) {
				$errorInfo = $stmt->errorInfo();
				error_log("❌ Erro na execução SQL: " . json_encode($errorInfo));
				throw new Exception("Erro SQL: " . ($errorInfo[2] ?? 'Erro desconhecido'));
			}
			
			$contractId = $this->db->lastInsertId();
			$this->db->commit();
			
			// ✅ CRIAR ALERTA PARA VENCIMENTO PRÓXIMO
			if (isset($data['end_date'])) {
				$this->createExpirationAlert($contractId, $data['end_date']);
			}
			
			error_log("✅ Contrato criado com sucesso - ID: " . $contractId);
			return $contractId;
			
		} catch (Exception $e) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			error_log("❌ [ContractModel] Erro ao criar contrato: " . $e->getMessage());
			error_log("❌ Trace: " . $e->getTraceAsString());
			return false;
		}
	}

    // ✅ MÉTODO: Atualizar contrato
    public function update($id, $data, $file = null) {
		try {
			error_log("🔄 ContractModel::update - Iniciando atualização ID: " . $id);
			error_log("🔄 Dados recebidos: " . json_encode($data));
			
			$this->db->beginTransaction();
			
			$currentContract = $this->getById($id);
			if (!$currentContract) {
				throw new Exception("Contrato não encontrado");
			}
			
			// Upload do arquivo se existir
			$fileName = $currentContract['contract_file'];
			if ($file && $file['error'] === UPLOAD_ERR_OK) {
				error_log("📁 Atualizando arquivo: " . $file['name']);
				
				// Remover arquivo antigo se existir
				if ($fileName && file_exists($this->uploadPath . $fileName)) {
					unlink($this->uploadPath . $fileName);
				}
				
				$fileName = $this->uploadContractFile($file);
				if (!$fileName) {
					throw new Exception("Erro ao fazer upload do arquivo");
				}
				error_log("✅ Novo arquivo salvo como: " . $fileName);
			}
			
			$sql = "UPDATE contracts SET
						company_id = ?,
						client_id = ?,
						supplier_id = ?,
						contract_type = ?,
						contract_number = ?,
						title = ?,
						description = ?,
						start_date = ?,
						end_date = ?,
						value = ?,
						currency = ?,
						payment_terms = ?,
						renewal_terms = ?,
						status = ?,
						contract_file = ?,
						notes = ?,
						updated_at = CURRENT_TIMESTAMP
					WHERE id = ?";
			
			error_log("📝 SQL Update: " . $sql);
			
			$stmt = $this->db->prepare($sql);
			
			// Preparar valores
			$values = [
				$data['company_id'],
				$data['client_id'] ?? null,
				$data['supplier_id'] ?? null,
				$data['contract_type'],
				$data['contract_number'],
				$data['title'],
				$data['description'] ?? '',
				$data['start_date'],
				$data['end_date'],
				$data['value'] ?? 0,
				$data['currency'] ?? 'BRL',
				$data['payment_terms'] ?? '',
				$data['renewal_terms'] ?? '',
				$data['status'] ?? 'draft',
				$fileName,
				$data['notes'] ?? '',
				$id
			];
			
			error_log("🔄 Valores para atualização: " . json_encode($values));
			
			$success = $stmt->execute($values);
			
			if (!$success) {
				$errorInfo = $stmt->errorInfo();
				error_log("❌ Erro na execução SQL Update: " . json_encode($errorInfo));
				throw new Exception("Erro SQL Update: " . ($errorInfo[2] ?? 'Erro desconhecido'));
			}
			
			$this->db->commit();
			
			// Atualizar alerta de vencimento se data mudou
			if ($currentContract['end_date'] !== $data['end_date']) {
				$this->updateExpirationAlert($id, $data['end_date']);
			}
			
			error_log("✅ Contrato atualizado com sucesso - ID: " . $id);
			return true;
			
		} catch (Exception $e) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			error_log("❌ [ContractModel] Erro ao atualizar contrato: " . $e->getMessage());
			error_log("❌ Trace: " . $e->getTraceAsString());
			return false;
		}
	}

    // ✅ MÉTODO: Renovar contrato
    public function renew($id, $newEndDate, $notes = null) {
        try {
            $contract = $this->getById($id);
            if (!$contract) {
                throw new Exception("Contrato não encontrado");
            }
            
            // Criar registro de renovação
            $sql = "INSERT INTO contract_renewals (
                        contract_id,
                        previous_end_date,
                        new_end_date,
                        renewed_by,
                        notes
                    ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id,
                $contract['end_date'],
                $newEndDate,
                $this->getCurrentUserId(),
                $notes
            ]);
            
            // Atualizar data de término do contrato
            $sql = "UPDATE contracts SET 
                        end_date = ?,
                        status = 'active',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$newEndDate, $id]);
            
            if ($success) {
                // Atualizar alerta de vencimento
                $this->updateExpirationAlert($id, $newEndDate);
                
                error_log("✅ [ContractModel] Contrato renovado - ID: " . $id);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("❌ [ContractModel] Erro ao renovar contrato: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Upload de arquivo PDF
    private function uploadContractFile($file) {
		try {
			error_log("📤 Iniciando upload do arquivo");
			error_log("📤 Nome: " . $file['name']);
			error_log("📤 Tamanho: " . $file['size']);
			error_log("📤 Tipo: " . $file['type']);
			error_log("📤 Erro: " . $file['error']);
			
			if ($file['error'] !== UPLOAD_ERR_OK) {
				throw new Exception("Erro no upload do arquivo: " . $file['error']);
			}
			
			$allowedTypes = ['application/pdf', 'application/x-pdf'];
			$maxSize = 10 * 1024 * 1024; // 10MB
			
			// Verificar tipo
			if (!in_array($file['type'], $allowedTypes)) {
				throw new Exception("Tipo de arquivo não permitido. Apenas PDF são aceitos. Tipo recebido: " . $file['type']);
			}
			
			// Verificar tamanho
			if ($file['size'] > $maxSize) {
				throw new Exception("Arquivo muito grande. Tamanho máximo: 10MB. Tamanho recebido: " . $file['size'] . " bytes");
			}
			
			// Verificar se é realmente um PDF
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $file['tmp_name']);
			finfo_close($finfo);
			
			if ($mimeType !== 'application/pdf') {
				throw new Exception("Arquivo não é um PDF válido. MIME type detectado: " . $mimeType);
			}
			
			// Gerar nome seguro
			$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
			$safeName = preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $originalName);
			$fileName = uniqid() . '_' . $safeName . '.pdf';
			$filePath = $this->uploadPath . $fileName;
			
			error_log("📤 Salvando arquivo como: " . $fileName);
			error_log("📤 Caminho: " . $filePath);
			
			// Garantir que o diretório existe
			if (!file_exists($this->uploadPath)) {
				mkdir($this->uploadPath, 0777, true);
			}
			
			// Mover arquivo
			if (move_uploaded_file($file['tmp_name'], $filePath)) {
				error_log("✅ Arquivo movido com sucesso para: " . $filePath);
				
				// Verificar se o arquivo foi salvo
				if (file_exists($filePath)) {
					error_log("✅ Arquivo existe no destino. Tamanho: " . filesize($filePath) . " bytes");
					return $fileName;
				} else {
					throw new Exception("Arquivo não foi salvo no destino");
				}
			} else {
				throw new Exception("Falha ao mover arquivo para o diretório de destino");
			}
			
		} catch (Exception $e) {
			error_log("❌ Erro no uploadContractFile: " . $e->getMessage());
			throw $e; // Re-lançar a exceção
		}
	}

    // ✅ MÉTODO: Criar alerta de vencimento
    private function createExpirationAlert($contractId, $endDate) {
        try {
            $daysUntilExpiry = floor((strtotime($endDate) - time()) / (60 * 60 * 24));
            
            if ($daysUntilExpiry <= 30) {
                $sql = "INSERT INTO alerts (
                            company_id,
                            type,
                            title,
                            message,
                            related_entity_type,
                            related_entity_id,
                            due_date,
                            priority
                        ) VALUES (
                            (SELECT company_id FROM contracts WHERE id = ?),
                            'account_due',
                            'Contrato próximo do vencimento',
                            CONCAT('Contrato ID: ', ?, ' vence em ', ?, ' dias'),
                            'contract',
                            ?,
                            ?,
                            CASE 
                                WHEN ? <= 7 THEN 'high'
                                WHEN ? <= 15 THEN 'medium'
                                ELSE 'low'
                            END
                        )";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $contractId, $contractId, $daysUntilExpiry, $contractId, 
                    $endDate, $daysUntilExpiry, $daysUntilExpiry
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("❌ [ContractModel] Erro ao criar alerta: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Atualizar alerta de vencimento
    private function updateExpirationAlert($contractId, $newEndDate) {
        try {
            // Remover alertas antigos
            $sql = "DELETE FROM alerts 
                    WHERE related_entity_type = 'contract' 
                    AND related_entity_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contractId]);
            
            // Criar novo alerta se necessário
            return $this->createExpirationAlert($contractId, $newEndDate);
            
        } catch (Exception $e) {
            error_log("❌ [ContractModel] Erro ao atualizar alerta: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Buscar estatísticas para dashboard
    public function getStats($companyId = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_contracts,
                        SUM(CASE WHEN status = 'active' AND end_date >= CURDATE() THEN 1 ELSE 0 END) as active_contracts,
                        SUM(CASE WHEN status = 'active' AND end_date < CURDATE() THEN 1 ELSE 0 END) as expired_contracts,
                        SUM(CASE WHEN status = 'active' 
                                 AND end_date >= CURDATE() 
                                 AND DATEDIFF(end_date, CURDATE()) <= 30 
                                THEN 1 ELSE 0 END) as expiring_soon,
                        SUM(value) as total_value
                    FROM contracts
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($companyId) && $companyId !== 'all') {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch();
            
            // Buscar contratos por tipo
            $sql = "SELECT 
                        contract_type,
                        COUNT(*) as count,
                        SUM(value) as total_value
                    FROM contracts
                    WHERE 1=1";
            
            if (!empty($companyId) && $companyId !== 'all') {
                $sql .= " AND company_id = ?";
            }
            
            $sql .= " GROUP BY contract_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $byType = $stmt->fetchAll();
            
            // Buscar próximos vencimentos
            $sql = "SELECT 
                        c.*,
                        com.name as company_name,
                        cli.name as client_name,
                        sup.name as supplier_name,
                        DATEDIFF(end_date, CURDATE()) as days_to_expiry
                    FROM contracts c
                    LEFT JOIN companies com ON c.company_id = com.id
                    LEFT JOIN clients cli ON c.client_id = cli.id
                    LEFT JOIN suppliers sup ON c.supplier_id = sup.id
                    WHERE c.status = 'active' 
                    AND c.end_date >= CURDATE()
                    AND c.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            
            if (!empty($companyId) && $companyId !== 'all') {
                $sql .= " AND c.company_id = ?";
            }
            
            $sql .= " ORDER BY end_date ASC LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $upcoming = $stmt->fetchAll();
            
            return [
                'total' => $stats['total_contracts'] ?? 0,
                'active' => $stats['active_contracts'] ?? 0,
                'expired' => $stats['expired_contracts'] ?? 0,
                'expiring_soon' => $stats['expiring_soon'] ?? 0,
                'total_value' => $stats['total_value'] ?? 0,
                'by_type' => $byType,
                'upcoming_expirations' => $upcoming
            ];
            
        } catch (Exception $e) {
            error_log("❌ [ContractModel] Erro ao buscar estatísticas: " . $e->getMessage());
            return [];
        }
    }

    // ✅ MÉTODO: Verificar se número de contrato já existe
    public function contractNumberExists($number, $companyId, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM contracts 
                    WHERE contract_number = ? AND company_id = ?";
            $params = [$number, $companyId];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("❌ [ContractModel] Erro ao verificar número: " . $e->getMessage());
            return false;
        }
    }

    // ✅ MÉTODO: Buscar URL do arquivo
    private function getFileUrl($fileName) {
        return '/bt-log-transportes/storage/contracts/' . $fileName;
    }

    // ✅ MÉTODO: Buscar ID do usuário atual
    private function getCurrentUserId() {
		try {
			// Garantir que a sessão está iniciada
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			
			// Verificar se o usuário está logado
			if (!isset($_SESSION['user_id'])) {
				error_log("⚠️ Usuário não autenticado. Usando ID 1 como fallback.");
				return 1; // Usuário fallback
			}
			
			$userId = $_SESSION['user_id'];
			error_log("👤 ID do usuário atual: " . $userId);
			return $userId;
			
		} catch (Exception $e) {
			error_log("❌ Erro ao obter ID do usuário: " . $e->getMessage());
			return 1; // Fallback
		}
	}

    // ✅ MÉTODO: Buscar renovações do contrato
    public function getRenewals($contractId) {
        try {
            $sql = "SELECT 
                        cr.*,
                        u.name as renewed_by_name
                    FROM contract_renewals cr
                    LEFT JOIN users u ON cr.renewed_by = u.id
                    WHERE cr.contract_id = ?
                    ORDER BY cr.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contractId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("❌ [ContractModel] Erro ao buscar renovações: " . $e->getMessage());
            return [];
        }
    }
}
?>