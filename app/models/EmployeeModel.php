<?php
// app/models/EmployeeModel.php - CORREÇÃO COMPLETA
class EmployeeModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
		try {
			$stmt = $this->db->prepare("
				INSERT INTO employees 
				(company_id, photo, name, cpf, rg, birth_date, ctps, pis_pasep, titulo_eleitor, 
				 reservista, nome_mae, nome_pai, naturalidade, nacionalidade, email, phone, address,
				 estado_civil, grau_instrucao, tipo_sanguineo, position, salary, inss, irrf, fgts,
				 vale_transporte, vale_refeicao, plano_saude, outros_descontos, 
				 commission_rate, is_driver, is_active) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			");
			
			$success = $stmt->execute([
				$data['company_id'] ?? null,
				$data['photo'] ?? null, // ✅ GARANTIR QUE A FOTO SEJA SALVA
				$data['name'] ?? '',
				$data['cpf'] ?? null,
				$data['rg'] ?? null,
				$data['birth_date'] ?? null,
				$data['ctps'] ?? null,
				$data['pis_pasep'] ?? null,
				$data['titulo_eleitor'] ?? null,
				$data['reservista'] ?? null,
				$data['nome_mae'] ?? null,
				$data['nome_pai'] ?? null,
				$data['naturalidade'] ?? null,
				$data['nacionalidade'] ?? null,
				$data['email'] ?? null,
				$data['phone'] ?? null,
				$data['address'] ?? null,
				$data['estado_civil'] ?? null,
				$data['grau_instrucao'] ?? null,
				$data['tipo_sanguineo'] ?? null,
				$data['position'] ?? '',
				$data['salary'] ?? 0,
				$data['inss'] ?? 0,
				$data['irrf'] ?? 0,
				$data['fgts'] ?? 0,
				$data['vale_transporte'] ?? 0,
				$data['vale_refeicao'] ?? 0,
				$data['plano_saude'] ?? 0,
				$data['outros_descontos'] ?? 0,
				$data['commission_rate'] ?? 0,
				$data['is_driver'] ?? false,
				$data['is_active'] ?? true
			]);

			return $success ? $this->db->lastInsertId() : false;
		} catch (PDOException $e) {
			error_log("Erro ao criar funcionário: " . $e->getMessage());
			return false;
		}
	}

    // ✅ CORREÇÃO: Buscar funcionário por ID com TODOS os campos
    public function getById($id) {
		try {
			$stmt = $this->db->prepare("
				SELECT 
					e.*,
					c.name as company_name,
					c.color as company_color
				FROM employees e 
				LEFT JOIN companies c ON e.company_id = c.id 
				WHERE e.id = ?
			");
			$stmt->execute([$id]);
			$employee = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$employee) {
				error_log("❌ EmployeeModel::getById - Funcionário não encontrado ID: " . $id);
				return false;
			}
			
			// ✅ DEBUG: Log dos dados brutos do banco
			error_log("✅ EmployeeModel::getById - Dados BRUTOS do BD:");
			error_log("- ID: " . $employee['id']);
			error_log("- Nome: " . $employee['name']);
			error_log("- Foto: " . ($employee['photo'] ?? 'NULL'));
			
			return $this->ensureEmployeeFields($employee);
			
		} catch (PDOException $e) {
			error_log("❌ EmployeeModel::getById - Erro: " . $e->getMessage());
			return false;
		}
	}

    public function getAll($companyId = null, $includeInactive = false) {
        try {
            $sql = "
                SELECT e.*, c.name as company_name, c.color as company_color 
                FROM employees e 
                LEFT JOIN companies c ON e.company_id = c.id 
            ";
            
            $params = [];
            
            if ($companyId) {
                $sql .= " WHERE e.company_id = ?";
                $params[] = $companyId;
                
                if (!$includeInactive) {
                    $sql .= " AND e.is_active = 1";
                }
            } else {
                if (!$includeInactive) {
                    $sql .= " WHERE e.is_active = 1";
                }
            }
            
            $sql .= " ORDER BY e.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map([$this, 'ensureEmployeeFields'], $employees);
        } catch (PDOException $e) {
            error_log("Erro ao listar funcionários: " . $e->getMessage());
            return [];
        }
    }

    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'company_id', 'photo', 'name', 'cpf', 'rg', 'birth_date', 'ctps', 'position',
                'salary', 'commission_rate', 'is_driver', 'is_active',
                'pis_pasep', 'titulo_eleitor', 'reservista', 'nome_mae', 'nome_pai', 
                'naturalidade', 'nacionalidade', 'estado_civil', 'grau_instrucao', 'tipo_sanguineo',
                'email', 'phone', 'address',
                'inss', 'irrf', 'fgts', 'vale_transporte', 'vale_refeicao', 'plano_saude', 'outros_descontos'
            ];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $fields[] = "{$field} = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $id;
            
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET " . implode(', ', $fields) . ", updated_at = NOW()
                WHERE id = ?
            ");
            
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar funcionário: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET is_active = 0, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao excluir funcionário: " . $e->getMessage());
            return false;
        }
    }

    public function cpfExists($cpf, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM employees WHERE cpf = ?";
            $params = [$cpf];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar CPF: " . $e->getMessage());
            return false;
        }
    }

    // ✅ CORREÇÃO CRÍTICA: Garantir que todos os campos do funcionário existam
    private function ensureEmployeeFields($employee) {
		if (!$employee) {
			error_log("❌ ensureEmployeeFields - employee é NULL");
			return null;
		}

		// ✅ CAMPOS PADRÃO COM VALORES CORRETOS - INCLUINDO TODOS OS CAMPOS
		$defaultFields = [
			'id' => 0,
			'company_id' => null,
			'photo' => null, // ✅ GARANTIR QUE O CAMPO PHOTO EXISTA
			'name' => '',
			'cpf' => null,
			'rg' => null,
			'birth_date' => null,
			'ctps' => null,
			'pis_pasep' => null,
			'titulo_eleitor' => null,
			'reservista' => null,
			'nome_mae' => null,
			'nome_pai' => null,
			'naturalidade' => null,
			'nacionalidade' => null,
			'email' => null,
			'phone' => null,
			'address' => null,
			'estado_civil' => null,
			'grau_instrucao' => null,
			'tipo_sanguineo' => null,
			'position' => '',
			'salary' => '0.00',
			'benefits' => null,
			'discounts' => '0.00',
			'inss' => '0.00',
			'irrf' => '0.00',
			'fgts' => '0.00',
			'vale_transporte' => '0.00',
			'vale_refeicao' => '0.00',
			'plano_saude' => '0.00',
			'outros_descontos' => '0.00',
			'commission_rate' => '0.00',
			'is_driver' => 0,
			'is_active' => 1,
			'created_at' => null,
			'updated_at' => null,
			'company_name' => '',
			'company_color' => '#FF6B00'
		];

		$result = array_merge($defaultFields, $employee);
		
		// ✅ CORREÇÃO: Converter para booleanos corretamente
		$result['is_driver'] = (bool)$result['is_driver'];
		$result['is_active'] = (bool)$result['is_active'];
		
		// ✅ CORREÇÃO: Garantir que campos nulos sejam strings vazias para o formulário
		if ($result['photo'] === null) $result['photo'] = '';
		if ($result['email'] === null) $result['email'] = '';
		if ($result['phone'] === null) $result['phone'] = '';
		if ($result['address'] === null) $result['address'] = '';
		if ($result['cpf'] === null) $result['cpf'] = '';
		if ($result['rg'] === null) $result['rg'] = '';
		if ($result['birth_date'] === null) $result['birth_date'] = '';
		if ($result['ctps'] === null) $result['ctps'] = '';
		if ($result['pis_pasep'] === null) $result['pis_pasep'] = '';
		if ($result['titulo_eleitor'] === null) $result['titulo_eleitor'] = '';
		if ($result['reservista'] === null) $result['reservista'] = '';
		if ($result['nome_mae'] === null) $result['nome_mae'] = '';
		if ($result['nome_pai'] === null) $result['nome_pai'] = '';
		if ($result['naturalidade'] === null) $result['naturalidade'] = '';
		if ($result['nacionalidade'] === null) $result['nacionalidade'] = '';
		
		// ✅ DEBUG: Log do resultado final
		error_log("✅ ensureEmployeeFields - Dados FINAIS:");
		error_log("- Foto: " . $result['photo']);
		error_log("- Nome: " . $result['name']);
		error_log("- Ativo: " . ($result['is_active'] ? 'Sim' : 'Não'));
		
		return $result;
	}

    public function calculateAge($birthDate) {
        if (!$birthDate) return null;
        
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birth);
        
        return $age->y;
    }

    public function formatCPF($cpf) {
        if (!$cpf) return null;
        
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        
        return substr($cpf, 0, 3) . '.' . 
               substr($cpf, 3, 3) . '.' . 
               substr($cpf, 6, 3) . '-' . 
               substr($cpf, 9, 2);
    }

    public function formatSalary($salary) {
        return 'R$ ' . number_format($salary, 2, ',', '.');
    }

    public function uploadPhoto($file) {
        try {
            $uploadDir = '../../public/assets/images/employees/';
            
            // Criar diretório se não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Validar tipo de arquivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG ou GIF.');
            }

            // Validar tamanho (máximo 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
            }

            // Gerar nome único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'employee_' . time() . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            // Mover arquivo
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                return 'assets/images/employees/' . $fileName;
            } else {
                throw new Exception('Erro ao fazer upload do arquivo.');
            }
            
        } catch (Exception $e) {
            error_log("Erro no upload da foto: " . $e->getMessage());
            throw $e;
        }
    }

    public function deletePhoto($photoPath) {
        try {
            if ($photoPath && file_exists('../../public/' . $photoPath)) {
                unlink('../../public/' . $photoPath);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Erro ao excluir foto: " . $e->getMessage());
            return false;
        }
    }
}
?>