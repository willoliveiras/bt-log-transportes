<?php
// app/models/UserModel.php

class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function authenticate($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, c.name as company_name, c.color as company_color 
                FROM users u 
                LEFT JOIN companies c ON u.company_id = c.id 
                WHERE u.email = ? AND u.is_active = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Remover password do array antes de retornar
                unset($user['password']);
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, c.name as company_name, c.color as company_color 
                FROM users u 
                LEFT JOIN companies c ON u.company_id = c.id 
                WHERE u.id = ? AND u.is_active = 1
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if ($user) {
                unset($user['password']);
            }

            return $user;
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    public function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET last_login = NOW() WHERE id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllByCompany($companyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, role, is_active, last_login, created_at
                FROM users 
                WHERE company_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$companyId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get users by company error: " . $e->getMessage());
            return [];
        }
    }
}
?>