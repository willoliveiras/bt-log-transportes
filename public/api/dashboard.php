<?php
// public/api/dashboard.php
require_once '../../app/config/config.php';
require_once '../../app/config/database.php';
require_once '../../app/core/Database.php';
require_once '../../app/core/Session.php';
require_once '../../app/models/UserModel.php';
require_once '../../app/models/DashboardModel.php';

header('Content-Type: application/json');

$session = new Session();
if (!$session->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $companyId = $_GET['company'] ?? 'all';
    $period = $_GET['period'] ?? 'monthly';
    
    $dashboardModel = new DashboardModel();
    $userId = $session->get('user_id');
    $userRole = $session->get('user_role');
    
    // Dados mockados para desenvolvimento - substituir por dados reais depois
    $data = [
        'success' => true,
        'kpis' => [
            'totalRevenue' => 125000.50,
            'netProfit' => 35600.75,
            'completedTrips' => 47,
            'activeVehicles' => 8,
            'totalVehicles' => 12
        ],
        'charts' => [
            'revenue' => [
                'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                'data' => [85000, 92000, 78000, 110000, 125000, 98000]
            ]
        ],
        'recentTrips' => [
            [
                'trip_number' => 'TRP-2024-001',
                'client_name' => 'Cliente A Ltda',
                'driver_name' => 'João Silva',
                'origin' => 'São Paulo - SP',
                'destination' => 'Rio de Janeiro - RJ',
                'freight_value' => 4500.00,
                'status' => 'completed'
            ],
            [
                'trip_number' => 'TRP-2024-002',
                'client_name' => 'Cliente B S/A',
                'driver_name' => 'Maria Santos',
                'origin' => 'Belo Horizonte - MG',
                'destination' => 'Brasília - DF',
                'freight_value' => 3200.00,
                'status' => 'in_progress'
            ]
        ],
        'alerts' => [
            [
                'title' => 'Manutenção preventiva veículo ABC-1234',
                'type' => 'warning',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'title' => 'Conta a pagar vence amanhã',
                'type' => 'error',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ],
        'topClients' => [
            ['name' => 'Cliente A Ltda', 'revenue' => 45000.00],
            ['name' => 'Cliente B S/A', 'revenue' => 38000.00],
            ['name' => 'Cliente C ME', 'revenue' => 29500.00]
        ],
        'financialStatus' => [
            'toReceive' => 12500.00,
            'toPay' => 8500.00,
            'overdue' => 2300.00
        ]
    ];
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>