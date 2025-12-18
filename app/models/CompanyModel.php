<?php
// app/models/CompanyModel.php

class CompanyModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Criar nova empresa
    public function create($data) {
		try {
			$stmt = $this->db->prepare("
				INSERT INTO companies 
				(name, razao_social, cnpj, inscricao_estadual, isento_ie, atuacao, email, phone, phone2, address, color, logo, is_active) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			");
			
			$success = $stmt->execute([
				$data['name'] ?? '',
				$data['razao_social'] ?? '',
				$data['cnpj'] ?? null,
				$data['inscricao_estadual'] ?? null,
				$data['isento_ie'] ?? false,
				$data['atuacao'] ?? '',
				$data['email'] ?? null,
				$data['phone'] ?? null,
				$data['phone2'] ?? null,
				$data['address'] ?? null,
				$data['color'] ?? '#FF6B00',
				$data['logo'] ?? null,
				$data['is_active'] ?? true
			]);

			return $success ? $this->db->lastInsertId() : false;
		} catch (PDOException $e) {
			error_log("Erro ao criar empresa: " . $e->getMessage());
			return false;
		}
	}

    // Buscar empresa por ID
	   public function getById($id) {
		try {
			$stmt = $this->db->prepare("
				SELECT * FROM companies 
				WHERE id = ?
			");
			$stmt->execute([$id]);
			$company = $stmt->fetch();
			
			// Garantir que todos os campos existam
			return $this->ensureCompanyFields($company);
		} catch (PDOException $e) {
			error_log("Erro ao buscar empresa: " . $e->getMessage());
			return false;
		}
	}

    // Listar todas as empresas
    public function getAll($includeInactive = false) {
        try {
            $sql = "SELECT * FROM companies";
            if (!$includeInactive) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $companies = $stmt->fetchAll();
            
            // Garantir que todos os campos existam para cada empresa
            return array_map([$this, 'ensureCompanyFields'], $companies);
        } catch (PDOException $e) {
            error_log("Erro ao listar empresas: " . $e->getMessage());
            return [];
        }
    }

    // Buscar empresas para dropdown
    public function getForDropdown() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, color 
                FROM companies 
                WHERE is_active = 1 
                ORDER BY name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao buscar empresas para dropdown: " . $e->getMessage());
            return [];
        }
    }

    // Verificar se CNPJ já existe
    public function cnpjExists($cnpj, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM companies WHERE cnpj = ?";
            $params = [$cnpj];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar CNPJ: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar empresa
    public function update($id, $data) {
		try {
			$fields = [];
			$values = [];
			
			$allowedFields = [
				'name', 'razao_social', 'cnpj', 'inscricao_estadual', 'isento_ie', 
				'atuacao', 'email', 'phone', 'phone2', 'address', 'color', 'logo', 'is_active'
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
				UPDATE companies 
				SET " . implode(', ', $fields) . ", updated_at = NOW()
				WHERE id = ?
			");
			
			return $stmt->execute($values);
		} catch (PDOException $e) {
			error_log("Erro ao atualizar empresa: " . $e->getMessage());
			return false;
		}
	}

    // Excluir empresa (soft delete)
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE companies 
                SET is_active = 0, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao excluir empresa: " . $e->getMessage());
            return false;
        }
    }

	// Garantir que todos os campos da empresa existam
	private function ensureCompanyFields($company) {
		if (!$company) {
			return $company;
		}

		return array_merge([
			'id' => 0,
			'name' => '',
			'razao_social' => '',
			'cnpj' => null,
			'inscricao_estadual' => null,
			'isento_ie' => false,
			'atuacao' => '',
			'email' => null,
			'phone' => null,
			'phone2' => null,
			'address' => null,
			'color' => '#FF6B00',
			'logo' => null, // ← GARANTIR QUE ESTÁ AQUI
			'is_active' => true,
			'created_at' => null,
			'updated_at' => null
		], $company);
	}
}
?>