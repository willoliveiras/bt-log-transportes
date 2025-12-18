<?php
// app/models/DashboardModel.php

class DashboardModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getKPIs($companyId, $period) {
        // Implementar consultas reais ao banco
        // Por enquanto retorna dados mockados
        return [
            'totalRevenue' => 125000.50,
            'netProfit' => 35600.75,
            'completedTrips' => 47,
            'activeVehicles' => 8,
            'totalVehicles' => 12
        ];
    }

    public function getRevenueChartData($companyId, $period) {
        // Implementar consulta real
        return [
            'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            'data' => [85000, 92000, 78000, 110000, 125000, 98000]
        ];
    }

    public function getRecentTrips($companyId, $limit = 5) {
        // Implementar consulta real
        return []; // Dados mockados no controller por enquanto
    }

    public function getAlerts($companyId) {
        // Implementar consulta real
        return []; // Dados mockados no controller por enquanto
    }
}
?>