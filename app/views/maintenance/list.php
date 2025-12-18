<?php
// app/views/maintenance/list.php - VERSÃO CORRIGIDA
$pageTitle = 'Manutenções';
$pageScript = 'maintenance.js';
$pageStyle = 'maintenance.css';

// Garantir que as variáveis existam
if (!isset($stats)) {
    $stats = [
        'total_maintenances' => 0,
        'preventive_count' => 0,
        'corrective_count' => 0,
        'pending_maintenances' => 0,
        'overdue_maintenances' => 0,
        'total_cost' => 0,
        'avg_cost_vehicle' => 0,
        'cost_increase' => 0,
        'alert_vehicles' => 0,
        'critical_alerts' => 0,
        'cost_per_km' => 0,
        'current_month_cost' => 0,
        'previous_month_cost' => 0
    ];
}

if (!isset($maintenanceAlerts)) {
    $maintenanceAlerts = [];
}

if (!isset($companies)) {
    $companies = [];
}

if (!isset($vehicles)) {
    $vehicles = [];
}

if (!isset($maintenances)) {
    $maintenances = [];
}

// Função auxiliar para obter status com fallback
function getMaintenanceStatus($maintenance, $default = 'agendada') {
    return isset($maintenance['status']) && !empty($maintenance['status']) ? $maintenance['status'] : $default;
}

// Função auxiliar para obter tipo com fallback
function getMaintenanceType($maintenance, $default = 'preventiva') {
    return isset($maintenance['type']) && !empty($maintenance['type']) ? $maintenance['type'] : $default;
}
?>

<div class="bases-dashboard">
    <!-- Cabeçalho da Página -->
    <div class="page-header">
        <div class="header-content">
            <h1>Manutenções BT Log</h1>
            <p>Gerencie manutenções preventivas e corretivas da frota</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" id="newMaintenanceBtn">
                <i class="fas fa-tools"></i>
                Nova Manutenção
            </button>
            <button class="btn btn-secondary" id="refreshMaintenancesBtn">
                <i class="fas fa-sync-alt"></i>
                Atualizar
            </button>
        </div>
    </div>
</div>

    <!-- Dashboard Discreto -->
    <div class="stats-section">
        <h3>Visão Geral</h3>
        <div class="stats-grid-discreet">
            <!-- Total de Manutenções -->
            <div class="stat-card-discreet primary" id="statTotalMaintenance">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['total_maintenances']; ?></div>
                        <div class="stat-label-discreet">Total de Manutenções</div>
                        <div class="stat-description-discreet">
                            <?php echo $stats['preventive_count']; ?> preventivas
                            <?php if($stats['corrective_count'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <?php echo $stats['corrective_count']; ?> corretivas
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Sem corretivas
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>

            <!-- Manutenções Pendentes -->
            <div class="stat-card-discreet warning" id="statPendingMaintenance">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['pending_maintenances']; ?></div>
                        <div class="stat-label-discreet">Pendentes</div>
                        <div class="stat-description-discreet">
                            Próximos 7 dias
                            <?php if($stats['overdue_maintenances'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo $stats['overdue_maintenances']; ?> atrasadas
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Em dia
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Custo Total -->
            <div class="stat-card-discreet danger" id="statTotalCost">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet">R$ <?php echo number_format($stats['total_cost'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="stat-label-discreet">Custo Total</div>
                        <div class="stat-description-discreet">
                            <?php echo ($stats['avg_cost_vehicle'] ?? 0) > 0 ? 'Média R$ ' . number_format($stats['avg_cost_vehicle'], 2, ',', '.') . '/veículo' : 'Sem custos'; ?>
                            <div class="stat-trend-discreet <?php echo ($stats['cost_increase'] ?? 0) > 0 ? 'trend-down-discreet' : 'trend-up-discreet'; ?>">
                                <i class="fas fa-<?php echo ($stats['cost_increase'] ?? 0) > 0 ? 'chart-line' : 'chart-bar'; ?>"></i>
                                <?php echo ($stats['cost_increase'] ?? 0) > 0 ? '+' . number_format($stats['cost_increase'], 1) . '%' : 'Estável'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <!-- Veículos com Alerta -->
            <div class="stat-card-discreet info" id="statAlertVehicles">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['alert_vehicles']; ?></div>
                        <div class="stat-label-discreet">Veículos com Alerta</div>
                        <div class="stat-description-discreet">
                            Monitoramento ativo
                            <?php if($stats['critical_alerts'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $stats['critical_alerts']; ?> críticos
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-shield-alt"></i>
                                Sem críticos
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-section">
        <h3>Filtros</h3>
        <div class="filter-grid">
            <div class="filter-group">
                <label for="companyFilter">
                    <i class="fas fa-building"></i>
                    Empresa
                </label>
                <select id="companyFilter" class="form-select">
                    <option value="">Todas as Empresas</option>
                    <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>">
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="vehicleFilter">
                    <i class="fas fa-truck"></i>
                    Veículo
                </label>
                <select id="vehicleFilter" class="form-select">
                    <option value="">Todos os Veículos</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?php echo $vehicle['id']; ?>">
                        <?php echo htmlspecialchars($vehicle['plate'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="typeFilter">
                    <i class="fas fa-cogs"></i>
                    Tipo
                </label>
                <select id="typeFilter" class="form-select">
                    <option value="">Todos os Tipos</option>
                    <option value="preventiva">Preventiva</option>
                    <option value="corretiva">Corretiva</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="statusFilter">
                    <i class="fas fa-toggle-on"></i>
                    Status
                </label>
                <select id="statusFilter" class="form-select">
                    <option value="">Todos os Status</option>
                    <option value="agendada">Agendada</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="concluida">Concluída</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            
            <div class="filter-group search-group">
                <label for="searchMaintenance">
                    <i class="fas fa-search"></i>
                    Buscar
                </label>
                <input type="text" id="searchMaintenance" class="form-control" placeholder="Buscar por descrição, placa...">
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button class="btn btn-secondary" id="clearFiltersBtn">
                    <i class="fas fa-times"></i> Limpar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Alertas de Manutenção -->
    <?php if (!empty($maintenanceAlerts)): ?>
    <div class="alert-section">
        <h3><i class="fas fa-exclamation-triangle"></i> Alertas de Manutenção</h3>
        <div class="alerts-grid">
            <?php foreach (array_slice($maintenanceAlerts, 0, 3) as $alert): ?>
            <div class="alert-card <?php echo $alert['priority'] ?? 'low'; ?>">
                <div class="alert-icon">
                    <i class="fas fa-<?php echo ($alert['priority'] ?? 'low') == 'high' ? 'exclamation-circle' : 'clock'; ?>"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title"><?php echo htmlspecialchars($alert['vehicle_plate'] ?? 'N/A'); ?></div>
                    <div class="alert-description">
                        <?php 
                        $daysUntil = $alert['days_until_maintenance'] ?? 0;
                        $kmUntil = $alert['km_until_maintenance'] ?? 0;
                        
                        if ($daysUntil <= 0) {
                            echo 'Manutenção atrasada há ' . abs($daysUntil) . ' dias';
                        } elseif ($kmUntil <= 500) {
                            echo 'Próxima manutenção em ' . number_format($kmUntil) . ' km';
                        } else {
                            echo 'Manutenção em ' . $daysUntil . ' dias';
                        }
                        ?>
                    </div>
                </div>
                <div class="alert-actions">
                    <button class="btn-action" onclick="window.maintenanceManager.registerMaintenance(<?php echo $alert['vehicle_id'] ?? 0; ?>)">
                        Registrar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabela de Manutenções -->
    <div class="bases-table-container">
        <div class="bases-table-header">
            <h2><i class="fas fa-list"></i> Histórico de Manutenções</h2>
            <div class="results-count" id="resultsCount">
                <?php echo count($maintenances); ?> manutenção<?php echo count($maintenances) != 1 ? 'ões' : ''; ?> encontrada<?php echo count($maintenances) != 1 ? 's' : ''; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="bases-table" id="maintenancesTable">
                <thead>
                    <tr>
                        <th>Veículo</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Data/KM</th>
                        <th>Custo</th>
                        <th>Fornecedor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="maintenanceTableBody">
                    <?php if (empty($maintenances)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state-modern">
                                    <div class="empty-icon-modern">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                    <h3>Nenhuma Manutenção Registrada</h3>
                                    <p>Comece registrando a primeira manutenção do sistema.</p>
                                    <button class="btn btn-primary" id="firstMaintenanceBtn">
                                        <i class="fas fa-plus"></i>
                                        Registrar Primeira Manutenção
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($maintenances as $maintenance): ?>
                        <?php
                            // Usar funções auxiliares para garantir valores
                            $status = getMaintenanceStatus($maintenance);
                            $type = getMaintenanceType($maintenance);
                            $vehiclePlate = $maintenance['vehicle_plate'] ?? 'N/A';
                            $companyName = $maintenance['company_name'] ?? 'N/A';
                            $companyColor = $maintenance['company_color'] ?? '#FF6B00';
                            $vehicleStatus = $maintenance['vehicle_status'] ?? 'disponivel';
                            $vehicleModel = $maintenance['vehicle_model'] ?? '';
                            $description = $maintenance['description'] ?? '';
                            $maintenanceDate = $maintenance['maintenance_date'] ?? '';
                            $currentKm = $maintenance['current_km'] ?? 0;
                            $nextMaintenanceDate = $maintenance['next_maintenance_date'] ?? '';
                            $cost = $maintenance['cost'] ?? 0;
                            $serviceProvider = $maintenance['service_provider'] ?? '';
                            $companyId = $maintenance['company_id'] ?? '';
                            $vehicleId = $maintenance['vehicle_id'] ?? '';
                        ?>
                        <tr data-maintenance-id="<?php echo $maintenance['id']; ?>"
                            data-company-id="<?php echo $companyId; ?>"
                            data-vehicle-id="<?php echo $vehicleId; ?>"
                            data-type="<?php echo $type; ?>"
                            data-status="<?php echo $status; ?>">
                            
                            <!-- Coluna Veículo -->
                            <td>
                                <div class="base-card-modern">
                                    <div class="base-avatar-modern" style="background: linear-gradient(135deg, <?php echo $companyColor; ?>, #E55A00);">
                                        <i class="fas fa-truck"></i>
                                        <div class="avatar-status <?php echo $vehicleStatus == 'disponivel' ? 'active' : 'inactive'; ?>"></div>
                                    </div>
                                    <div class="base-info-modern">
                                        <div class="base-name-modern">
                                            <?php echo htmlspecialchars($vehiclePlate); ?>
                                        </div>
                                        <div class="base-company-modern">
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($companyName); ?>
                                            <?php if (!empty($vehicleModel)): ?>
                                                <span class="vehicle-model">• <?php echo htmlspecialchars($vehicleModel); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Tipo -->
                            <td>
                                <span class="maintenance-type-badge <?php echo $type; ?>">
                                    <i class="fas fa-<?php echo $type == 'preventiva' ? 'shield-alt' : 'wrench'; ?>"></i>
                                    <?php echo $type == 'preventiva' ? 'Preventiva' : 'Corretiva'; ?>
                                </span>
                            </td>
                            
                            <!-- Coluna Descrição -->
                            <td>
                                <div class="maintenance-description">
                                    <div class="description-text">
                                        <?php echo htmlspecialchars(substr($description, 0, 60)); ?>
                                        <?php if (strlen($description) > 60): ?>
                                            <span class="text-muted">...</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-type">
                                        <small><?php echo !empty($maintenanceDate) ? date('d/m/Y', strtotime($maintenanceDate)) : 'N/A'; ?></small>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Data/KM -->
                            <td>
                                <div class="maintenance-dates">
                                    <?php if (!empty($maintenanceDate)): ?>
                                    <div class="date-main">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($maintenanceDate)); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($currentKm) && $currentKm > 0): ?>
                                        <div class="date-km">
                                            <i class="fas fa-tachometer-alt"></i>
                                            <?php echo number_format($currentKm, 0, ',', '.'); ?> km
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($nextMaintenanceDate)): ?>
                                        <div class="date-next">
                                            <small>Próxima: <?php echo date('d/m/Y', strtotime($nextMaintenanceDate)); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Custo -->
                            <td>
                                <?php if (!empty($cost) && $cost > 0): ?>
                                    <div class="maintenance-cost">
                                        <div class="cost-value">
                                            R$ <?php echo number_format($cost, 2, ',', '.'); ?>
                                        </div>
                                        <?php if (!empty($currentKm) && $currentKm > 0 && $cost > 0): ?>
                                            <?php 
                                            $costPerKm = $cost / $currentKm;
                                            if ($costPerKm > 0): ?>
                                            <div class="cost-per-km">
                                                <small><?php echo number_format($costPerKm, 2, ',', '.'); ?>/km</small>
                                            </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Coluna Fornecedor -->
                            <td>
                                <?php if (!empty($serviceProvider)): ?>
                                    <div class="supplier-info">
                                        <div class="supplier-name">
                                            <?php echo htmlspecialchars($serviceProvider); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Coluna Status -->
                            <td>
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                
                                switch($status) {
                                    case 'concluida':
                                        $statusClass = 'success';
                                        $statusIcon = 'check';
                                        break;
                                    case 'em_andamento':
                                        $statusClass = 'warning';
                                        $statusIcon = 'sync-alt';
                                        break;
                                    case 'cancelada':
                                        $statusClass = 'danger';
                                        $statusIcon = 'times';
                                        break;
                                    default:
                                        $statusClass = 'info';
                                        $statusIcon = 'clock';
                                }
                                ?>
                                <span class="status-pill-modern <?php echo $statusClass; ?>">
                                    <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                    <?php 
                                    $statusText = [
                                        'agendada' => 'Agendada',
                                        'em_andamento' => 'Em Andamento',
                                        'concluida' => 'Concluída',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    echo $statusText[$status] ?? ucfirst($status);
                                    ?>
                                </span>
                            </td>
                            
                            <!-- Coluna Ações -->
                            <td>
                                <div class="actions-toolbar-modern">
                                    <button class="action-btn-modern btn-view-modern" 
                                            onclick="window.maintenanceManager.viewMaintenance(<?php echo $maintenance['id']; ?>);"
                                            title="Visualizar Manutenção">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn-modern btn-edit-modern" 
                                            onclick="window.maintenanceManager.editMaintenance(<?php echo $maintenance['id']; ?>);"
                                            title="Editar Manutenção">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($status !== 'concluida'): ?>
                                    <button class="action-btn-modern btn-complete-modern" 
                                            onclick="window.maintenanceManager.openCompleteModal(<?php echo $maintenance['id']; ?>);"
                                            title="Concluir Manutenção">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="action-btn-modern btn-delete-modern" 
                                            onclick="window.maintenanceManager.deleteMaintenance(<?php echo $maintenance['id']; ?>, '<?php echo htmlspecialchars(addslashes($vehiclePlate)); ?>');"
                                            title="Excluir Manutenção">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Cadastro/Edição de Manutenção -->
<div class="modal" id="maintenanceModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalMaintenanceTitle">Nova Manutenção</h5>
                <button type="button" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="maintenanceForm">
                <input type="hidden" name="id" id="maintenanceId">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="vehicle_id" class="form-label">Veículo *</label>
                            <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                                <option value="">Selecione o Veículo</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>"
                                        data-current-km="<?php echo $vehicle['current_km']; ?>"
                                        data-next-maintenance-km="<?php echo $vehicle['next_maintenance_km']; ?>">
                                    <?php echo htmlspecialchars($vehicle['plate'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type" class="form-label">Tipo *</label>
                            <select id="type" name="type" class="form-control" required>
                                <option value="">Selecione o Tipo</option>
                                <option value="preventiva">Preventiva</option>
                                <option value="corretiva">Corretiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="service_type" class="form-label">Tipo de Serviço</label>
                            <select id="service_type" name="service_type" class="form-control">
                                <option value="">Selecione o Serviço</option>
                                <option value="troca_oleo">Troca de Óleo e Filtro</option>
                                <option value="filtro_ar">Filtro de Ar</option>
                                <option value="filtro_combustivel">Filtro de Combustível</option>
                                <option value="pastilhas_freio">Pastilhas de Freio</option>
                                <option value="pneus">Pneus</option>
                                <option value="alinhamento">Alinhamento/Balanceamento</option>
                                <option value="suspensao">Suspensão</option>
                                <option value="transmissao">Transmissão</option>
                                <option value="bateria">Bateria</option>
                                <option value="correia">Correia Dentada</option>
                                <option value="ar_condicionado">Ar Condicionado</option>
                                <option value="motor">Motor</option>
                                <option value="eletrica">Parte Elétrica</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="maintenance_interval" class="form-label">Intervalo (KM)</label>
                            <input type="number" id="maintenance_interval" name="maintenance_interval" class="form-control" 
                                   placeholder="Intervalo em quilômetros">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="maintenance_date" class="form-label">Data da Manutenção *</label>
                            <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="current_km" class="form-label">KM Atual</label>
                            <input type="number" id="current_km" name="current_km" class="form-control" 
                                   placeholder="Quilometragem atual" step="0.01">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Descrição do Serviço *</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required 
                                  placeholder="Descreva os serviços realizados..."></textarea>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-user-tie"></i> Fornecedor/Prestador</h6>
                        <div class="form-check mb-3">
                            <input type="checkbox" id="use_supplier" name="use_supplier" class="form-check-input">
                            <label for="use_supplier" class="form-check-label">Usar fornecedor cadastrado</label>
                        </div>
                        
                        <div id="custom_supplier_field">
                            <div class="form-group">
                                <label for="service_provider" class="form-label">Nome do Prestador *</label>
                                <input type="text" id="service_provider" name="service_provider" class="form-control" 
                                       placeholder="Nome da oficina ou mecânico">
                            </div>
                        </div>
                        
                        <div id="registered_supplier_field" style="display: none;">
                            <div class="form-group">
                                <label for="supplier_selection" class="form-label">Fornecedor Cadastrado *</label>
                                <select id="supplier_selection" name="supplier_id" class="form-control">
                                    <option value="">Selecione um fornecedor</option>
                                    <!-- Fornecedores carregados via AJAX -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group cost-field">
                            <label for="cost" class="form-label">Custo (R$) *</label>
                            <input type="number" id="cost" name="cost" class="form-control" step="0.01" min="0" required
                                   placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="agendada">Agendada</option>
                                <option value="em_andamento">Em Andamento</option>
                                <option value="concluida">Concluída</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                    </div>

                    <div id="completion_section" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="next_maintenance_date">Próxima Data</label>
                                <input type="date" id="next_maintenance_date" name="next_maintenance_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="next_maintenance_km">Próximo KM</label>
                                <input type="number" id="next_maintenance_km" name="next_maintenance_km" class="form-control" 
                                       placeholder="Próxima manutenção em KM" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2" 
                                  placeholder="Observações adicionais, detalhes do serviço..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelMaintenanceButton">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="saveMaintenanceButton">
                        <span class="btn-text">
                            <i class="fas fa-save"></i> Salvar Manutenção
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <div class="loading-spinner"></div> Salvando...
                        </span>
                    </button>
                    <button type="button" class="btn btn-success" id="completeMaintenanceButton" style="display: none;">
                        <span class="btn-text">
                            <i class="fas fa-check-circle"></i> Concluir Manutenção
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <div class="loading-spinner"></div> Concluindo...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmação de Conclusão -->
<div class="modal" id="completeMaintenanceModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Concluir Manutenção</h5>
                <button type="button" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="completeMaintenanceForm">
                <input type="hidden" name="id" id="complete_maintenance_id">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Ao concluir a manutenção, você pode gerar uma conta a pagar automaticamente.
                    </div>
                    
                    <div class="form-group">
                        <label for="complete_cost" class="form-label">Custo Final (R$) *</label>
                        <input type="number" id="complete_cost" name="cost" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="complete_generate_payable" name="generate_payable" class="form-check-input" checked>
                        <label for="complete_generate_payable" class="form-check-label">Gerar conta a pagar automaticamente</label>
                    </div>
                    
                    <div class="form-group">
                        <label for="complete_notes" class="form-label">Observações Finais</label>
                        <textarea id="complete_notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.maintenanceManager.closeCompleteModal()">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="confirmCompleteButton">
                        <span class="btn-text">
                            <i class="fas fa-check-circle"></i> Confirmar Conclusão
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <div class="loading-spinner"></div> Processando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>

</style>

<script>
// Configurar evento do botão de confirmação
document.addEventListener('DOMContentLoaded', function() {
    const confirmCompleteBtn = document.getElementById('confirmCompleteButton');
    if (confirmCompleteBtn) {
        confirmCompleteBtn.addEventListener('click', function() {
            window.maintenanceManager.confirmCompleteMaintenance();
        });
    }
    
    // Configurar fechamento do modal de conclusão
    const completeModal = document.getElementById('completeMaintenanceModal');
    if (completeModal) {
        // Fechar com X
        const closeBtn = completeModal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                completeModal.style.display = 'none';
                document.body.classList.remove('modal-open');
            });
        }
        
        // Fechar clicando fora
        completeModal.addEventListener('click', function(e) {
            if (e.target === completeModal) {
                completeModal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
    }
    
    // Botão Primeira Manutenção
    const firstBtn = document.getElementById('firstMaintenanceBtn');
    if (firstBtn) {
        firstBtn.addEventListener('click', function() {
            window.maintenanceManager.openMaintenanceForm();
        });
    }
});
</script>