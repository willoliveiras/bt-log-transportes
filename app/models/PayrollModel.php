<?php
// app/models/PayrollModel.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class PayrollModel {
    private $db;
    private $table = 'payroll';

    public function __construct() {
        try {
            $this->db = new PDO(
                DatabaseConfig::getDSN(), 
                DatabaseConfig::USERNAME, 
                DatabaseConfig::PASSWORD, 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
    }

    // Buscar empresas para dropdown
    public function getCompaniesForDropdown() {
        $sql = "SELECT id, name FROM companies WHERE is_active = 1 ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar funcionários para dropdown
    public function getEmployeesForDropdown($companyId = null) {
        $sql = "SELECT id, name FROM employees WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $sql .= " ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar todos os registros de folha
    public function getAll($companyId = null, $month = null, $status = '', $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, 
                       e.name as employee_name, 
                       e.position,
                       e.cpf,
                       c.name as company_name,
                       (p.base_salary + p.commissions + p.benefits - p.discounts) as net_salary
                FROM {$this->table} p
                JOIN employees e ON p.employee_id = e.id
                JOIN companies c ON p.company_id = c.id
                WHERE 1=1";
        
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} p
                     JOIN employees e ON p.employee_id = e.id
                     WHERE 1=1";
        
        $params = [];
        $countParams = [];
        
        if ($companyId) {
            $sql .= " AND p.company_id = ?";
            $countSql .= " AND p.company_id = ?";
            $params[] = $companyId;
            $countParams[] = $companyId;
        }
        
        if ($month) {
            $sql .= " AND DATE_FORMAT(p.reference_month, '%Y-%m') = ?";
            $countSql .= " AND DATE_FORMAT(p.reference_month, '%Y-%m') = ?";
            $params[] = $month;
            $countParams[] = $month;
        }
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $countSql .= " AND p.status = ?";
            $params[] = $status;
            $countParams[] = $status;
        }
        
        $sql .= " ORDER BY p.reference_month DESC, e.name ASC 
                 LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total para paginação
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'data' => $payrolls,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar dados da folha: " . $e->getMessage());
        }
    }

    // Gerar folha de pagamento
    public function generatePayroll($companyId, $referenceMonth) {
        try {
            $this->db->beginTransaction();

            // Buscar funcionários ativos da empresa
            $employees = $this->getActiveEmployees($companyId);
            $generatedCount = 0;

            foreach ($employees as $employee) {
                // Verificar se já existe folha para este mês
                $existing = $this->getPayrollByEmployeeMonth($employee['id'], $referenceMonth);
                
                if (!$existing) {
                    // Calcular valores da folha
                    $payrollData = $this->calculatePayroll($employee, $referenceMonth);
                    
                    // Inserir registro
                    if ($this->createPayrollRecord($payrollData)) {
                        $generatedCount++;
                    }
                }
            }

            $this->db->commit();
            return $generatedCount;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Buscar funcionários ativos
    private function getActiveEmployees($companyId) {
        $sql = "SELECT * FROM employees WHERE company_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calcular valores da folha
    private function calculatePayroll($employee, $referenceMonth) {
        $baseSalary = $employee['salary'] ?? 0;
        
        // Calcular comissões do mês - AGORA USA O CAMPO commission_amount
        $commissions = $this->calculateCommissions($employee['id'], $referenceMonth);
        
        // Calcular benefícios
        $benefits = $this->calculateBenefits($employee);
        
        // Calcular descontos
        $discounts = $this->calculateDiscounts($employee);
        
        // Calcular salário líquido
        $netSalary = $baseSalary + $commissions + $benefits - $discounts;

        return [
            'company_id' => $employee['company_id'],
            'employee_id' => $employee['id'],
            'reference_month' => $referenceMonth . '-01',
            'base_salary' => $baseSalary,
            'commissions' => $commissions,
            'benefits' => $benefits,
            'discounts' => $discounts,
            'net_salary' => max(0, $netSalary),
            'status' => 'pendente'
        ];
    }

    // Calcular comissões - MÉTODO CORRIGIDO (usa commission_amount)
    private function calculateCommissions($employeeId, $month) {
        // Buscar comissões diretamente das viagens usando commission_amount
        $sql = "SELECT COALESCE(SUM(t.commission_amount), 0) as total_commissions
                FROM trips t
                LEFT JOIN drivers d ON t.driver_id = d.id
                WHERE (d.employee_id = ? OR t.driver_id IS NULL)
                AND DATE_FORMAT(t.created_at, '%Y-%m') = ?
                AND t.status = 'concluida'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return floatval($result['total_commissions'] ?? 0);
    }

    // Calcular benefícios
    private function calculateBenefits($employee) {
        $benefits = 0;
        $benefits += floatval($employee['vale_refeicao'] ?? 0);
        $benefits += floatval($employee['vale_transporte'] ?? 0);
        $benefits += floatval($employee['plano_saude'] ?? 0);
        return $benefits;
    }

    // Calcular descontos
    private function calculateDiscounts($employee) {
        $discounts = 0;
        $discounts += floatval($employee['inss'] ?? 0);
        $discounts += floatval($employee['irrf'] ?? 0);
        $discounts += floatval($employee['fgts'] ?? 0);
        $discounts += floatval($employee['outros_descontos'] ?? 0);
        return $discounts;
    }

    // Criar registro de folha
    private function createPayrollRecord($data) {
        $sql = "INSERT INTO {$this->table} 
                (company_id, employee_id, reference_month, base_salary, 
                 commissions, benefits, discounts, net_salary, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['company_id'],
            $data['employee_id'],
            $data['reference_month'],
            $data['base_salary'],
            $data['commissions'],
            $data['benefits'],
            $data['discounts'],
            $data['net_salary'],
            $data['status']
        ]);
    }

    // Buscar folha por funcionário e mês
    private function getPayrollByEmployeeMonth($employeeId, $month) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE employee_id = ? 
                AND DATE_FORMAT(reference_month, '%Y-%m') = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $month]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verificar se folha existe
    public function payrollExists($companyId, $referenceMonth) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE company_id = ? 
                AND DATE_FORMAT(reference_month, '%Y-%m') = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId, $referenceMonth]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Buscar informações da folha do mês
    public function getPayrollMonthInfo($companyId, $referenceMonth) {
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    SUM(net_salary) as total_value,
                    MIN(created_at) as created_at
                FROM {$this->table} 
                WHERE company_id = ? 
                AND DATE_FORMAT(reference_month, '%Y-%m') = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId, $referenceMonth]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Excluir folha do mês
    public function deletePayrollByMonth($companyId, $referenceMonth) {
        try {
            $this->db->beginTransaction();
            
            $sql = "DELETE FROM {$this->table} 
                    WHERE company_id = ? 
                    AND DATE_FORMAT(reference_month, '%Y-%m') = ?";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$companyId, $referenceMonth]);
            
            $this->db->commit();
            return $success;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Marcar como pago
    public function markAsPaid($payrollId, $paymentDate) {
        $sql = "UPDATE {$this->table} 
                SET status = 'pago', payment_date = ? 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$paymentDate, $payrollId]);
    }

    // Estornar pagamento
    public function reversePayment($payrollId) {
        $sql = "UPDATE {$this->table} 
                SET status = 'pendente', payment_date = NULL 
                WHERE id = ? AND status = 'pago'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$payrollId]);
    }

    // Buscar detalhes da folha
    public function getPayrollDetails($payrollId) {
        $sql = "SELECT p.*, 
                       e.name as employee_name,
                       e.position,
                       e.cpf,
                       e.rg,
                       e.ctps,
                       e.pis_pasep,
                       c.name as company_name,
                       c.cnpj as company_cnpj
                FROM {$this->table} p
                JOIN employees e ON p.employee_id = e.id
                JOIN companies c ON p.company_id = c.id
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$payrollId]);
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payroll) {
            $payroll['breakdown'] = $this->getPayrollBreakdown($payrollId, $payroll);
        }
        
        return $payroll;
    }

    // Buscar breakdown da folha
    private function getPayrollBreakdown($payrollId, $payrollData) {
        $breakdown = [
            'proventos' => [],
            'descontos' => []
        ];

        // Proventos
        if ($payrollData['base_salary'] > 0) {
            $breakdown['proventos']['Salário Base'] = [
                'value' => floatval($payrollData['base_salary']),
                'type' => 'fixed'
            ];
        }

        if ($payrollData['commissions'] > 0) {
            $breakdown['proventos']['Comissões'] = [
                'value' => floatval($payrollData['commissions']),
                'type' => 'variable'
            ];
        }

        if ($payrollData['benefits'] > 0) {
            $breakdown['proventos']['Benefícios'] = [
                'value' => floatval($payrollData['benefits']),
                'type' => 'benefit'
            ];
        }

        // Descontos
        if ($payrollData['discounts'] > 0) {
            $employeeDiscounts = $this->getEmployeeDiscounts($payrollData['employee_id']);
            
            foreach ($employeeDiscounts as $discount) {
                if ($discount['value'] > 0) {
                    $breakdown['descontos'][$discount['name']] = [
                        'value' => $discount['value'],
                        'type' => $discount['type']
                    ];
                }
            }
        }

        return $breakdown;
    }

    // Buscar descontos do funcionário
    private function getEmployeeDiscounts($employeeId) {
        $sql = "SELECT 
                    'INSS' as name, inss as value, 'tax' as type
                FROM employees WHERE id = ? AND inss > 0
                UNION ALL
                SELECT 
                    'IRRF' as name, irrf as value, 'tax' as type
                FROM employees WHERE id = ? AND irrf > 0
                UNION ALL
                SELECT 
                    'FGTS' as name, fgts as value, 'tax' as type
                FROM employees WHERE id = ? AND fgts > 0
                UNION ALL
                SELECT 
                    'Vale Transporte' as name, vale_transporte as value, 'benefit' as type
                FROM employees WHERE id = ? AND vale_transporte > 0
                UNION ALL
                SELECT 
                    'Outros Descontos' as name, outros_descontos as value, 'other' as type
                FROM employees WHERE id = ? AND outros_descontos > 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $employeeId, $employeeId, $employeeId, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar estatísticas
    public function getPayrollStats($companyId = null, $month = null) {
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    SUM(net_salary) as total_payroll,
                    AVG(net_salary) as avg_salary,
                    COUNT(CASE WHEN status = 'pago' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pending_count,
                    SUM(commissions) as total_commissions,
                    SUM(benefits) as total_benefits,
                    SUM(discounts) as total_discounts
                FROM {$this->table} 
                WHERE 1=1";
        
        $params = [];
        
        if ($companyId) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        if ($month) {
            $sql .= " AND DATE_FORMAT(reference_month, '%Y-%m') = ?";
            $params[] = $month;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Gerar opções de meses
    public function generateMonthOptions() {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = (new DateTime())->modify("-$i months");
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('m/Y')
            ];
        }
        return $months;
    }

    // MÉTODO PARA RECALCULAR COMISSÕES DAS VIAGENS EXISTENTES
    public function recalculateTripCommissions() {
        try {
            $this->db->beginTransaction();
            
            // Buscar todas as viagens concluídas
            $sql = "SELECT t.id, t.freight_value, 
                           COALESCE(d.custom_commission_rate, e.commission_rate, 0) as commission_rate
                    FROM trips t
                    LEFT JOIN drivers d ON t.driver_id = d.id
                    LEFT JOIN employees e ON d.employee_id = e.id OR t.driver_id IS NULL
                    WHERE t.status = 'concluida'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updatedCount = 0;
            
            foreach ($trips as $trip) {
                $commissionAmount = ($trip['freight_value'] * $trip['commission_rate']) / 100;
                
                $updateSql = "UPDATE trips SET commission_amount = ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$commissionAmount, $trip['id']]);
                
                $updatedCount++;
            }
            
            $this->db->commit();
            return $updatedCount;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>