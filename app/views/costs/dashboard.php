<?php
// app/views/costs/dashboard.php

$pageTitle = 'Análise de Custos';
$pageScript = 'costs.js';
?>

<div class="page-header">
    <div class="header-content">
        <h1>Análise de Custos</h1>
        <p>Controle e análise detalhada de custos por veículo e viagem</p>
    </div>
    <div class="header-actions">
        <div class="filter-group">
            <select id="periodFilter" class="header-select" onchange="costsManager.filterByPeriod(this.value)">
                <option value="month">Este Mês</option>
                <option value="quarter">Este Trimestre</option>
                <option value="year">Este Ano</option>
                <option value="custom">Personalizado</option>
            </select>
        </div>
        <div class="filter-group" id="customDateRange" style="display: none;">
            <input type="date" id="startDate" class="header-select">
            <span>até</span>
            <input type="date" id="endDate" class="header-select">
            <button class="btn btn-sm btn-primary" onclick="costsManager.applyCustomDateRange()">Aplicar</button>
        </div>
    </div>
</div>

<!-- KPIs de Custos -->
<div class="metrics-grid" style="margin-bottom: 2rem;">
    <div class="metric-card">
        <div class="metric-value">R$ <?php echo number_format($costStats['total_costs'] ?? 0, 0, ',', '.'); ?></div>
        <div class="metric-label">Custos Totais</div>
        <div class="metric-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-value">R$ <?php echo number_format($costStats['fuel_costs'] ?? 0, 0, ',', '.'); ?></div>
        <div class="metric-label">Combustível</div>
        <div class="metric-icon">
            <i class="fas fa-gas-pump"></i>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-value">R$ <?php echo number_format($costStats['maintenance_costs'] ?? 0, 0, ',', '.'); ?></div>
        <div class="metric-label">Manutenção</div>
        <div class="metric-icon">
            <i class="fas fa-tools"></i>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?php echo number_format($costStats['cost_per_km'] ?? 0, 2, ',', '.'); ?></div>
        <div class="metric-label">Custo por KM</div>
        <div class="metric-icon">
            <i class="fas fa-road"></i>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Custos por Veículo</h2>
        <div class="card-actions">
            <div class="filter-group">
                <select id="vehicleFilter" class="header-select" onchange="costsManager.filterByVehicle(this.value)">
                    <option value="">Todos os Veículos</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?php echo $vehicle['id']; ?>">
                        <?php echo htmlspecialchars($vehicle['plate'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="costs-breakdown">
            <?php foreach ($vehicleCosts as $vehicle): ?>
            <div class="vehicle-cost-item">
                <div class="vehicle-header">
                    <div class="vehicle-info">
                        <h4><?php echo htmlspecialchars($vehicle['plate']); ?></h4>
                        <span class="vehicle-details">
                            <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?> • 
                            <?php echo number_format($vehicle['current_km'], 0, ',', '.'); ?> km
                        </span>
                    </div>
                    <div class="vehicle-total">
                        <strong>R$ <?php echo number_format($vehicle['total_cost'], 2, ',', '.'); ?></strong>
                        <small>Custo Total</small>
                    </div>
                </div>
                
                <div class="cost-breakdown">
                    <div class="cost-category">
                        <div class="cost-bar">
                            <div class="cost-fill fuel" style="width: <?php echo $vehicle['fuel_percentage']; ?>%"></div>
                        </div>
                        <div class="cost-info">
                            <span class="cost-type">⛽ Combustível</span>
                            <span class="cost-value">R$ <?php echo number_format($vehicle['fuel_cost'], 2, ',', '.'); ?></span>
                            <span class="cost-percentage">(<?php echo $vehicle['fuel_percentage']; ?>%)</span>
                        </div>
                    </div>
                    
                    <div class="cost-category">
                        <div class="cost-bar">
                            <div class="cost-fill maintenance" style="width: <?php echo $vehicle['maintenance_percentage']; ?>%"></div>
                        </div>
                        <div class="cost-info">
                            <span class="cost-type">🔧 Manutenção</span>
                            <span class="cost-value">R$ <?php echo number_format($vehicle['maintenance_cost'], 2, ',', '.'); ?></span>
                            <span class="cost-percentage">(<?php echo $vehicle['maintenance_percentage']; ?>%)</span>
                        </div>
                    </div>
                    
                    <div class="cost-category">
                        <div class="cost-bar">
                            <div class="cost-fill toll" style="width: <?php echo $vehicle['toll_percentage']; ?>%"></div>
                        </div>
                        <div class="cost-info">
                            <span class="cost-type">🛣️ Pedágio</span>
                            <span class="cost-value">R$ <?php echo number_format($vehicle['toll_cost'], 2, ',', '.'); ?></span>
                            <span class="cost-percentage">(<?php echo $vehicle['toll_percentage']; ?>%)</span>
                        </div>
                    </div>
                    
                    <div class="cost-category">
                        <div class="cost-bar">
                            <div class="cost-fill other" style="width: <?php echo $vehicle['other_percentage']; ?>%"></div>
                        </div>
                        <div class="cost-info">
                            <span class="cost-type">📦 Outros</span>
                            <span class="cost-value">R$ <?php echo number_format($vehicle['other_cost'], 2, ',', '.'); ?></span>
                            <span class="cost-percentage">(<?php echo $vehicle['other_percentage']; ?>%)</span>
                        </div>
                    </div>
                </div>
                
                <div class="vehicle-footer">
                    <div class="cost-metrics">
                        <div class="metric">
                            <span class="metric-label">Custo por KM:</span>
                            <span class="metric-value">R$ <?php echo number_format($vehicle['cost_per_km'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Consumo Médio:</span>
                            <span class="metric-value"><?php echo number_format($vehicle['avg_consumption'], 1, ',', '.'); ?> km/L</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Viagens:</span>
                            <span class="metric-value"><?php echo $vehicle['trip_count']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.costs-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.vehicle-cost-item {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.vehicle-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-border-light);
}

.vehicle-info h4 {
    margin: 0 0 0.25rem 0;
    color: var(--color-primary);
    font-size: 1.2rem;
}

.vehicle-details {
    color: var(--color-gray);
    font-size: 0.9rem;
}

.vehicle-total {
    text-align: right;
}

.vehicle-total strong {
    font-size: 1.4rem;
    color: var(--color-primary);
    display: block;
}

.vehicle-total small {
    color: var(--color-gray);
    font-size: 0.8rem;
}

.cost-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.cost-category {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cost-bar {
    flex: 1;
    height: 8px;
    background: var(--color-border-light);
    border-radius: 4px;
    overflow: hidden;
    min-width: 200px;
}

.cost-fill {
    height: 100%;
    border-radius: 4px;
}

.cost-fill.fuel { background: #FF6B00; }
.cost-fill.maintenance { background: #2196F3; }
.cost-fill.toll { background: #4CAF50; }
.cost-fill.other { background: #9C27B0; }

.cost-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 250px;
}

.cost-type {
    font-weight: 600;
    min-width: 100px;
}

.cost-value {
    font-weight: 600;
    color: var(--color-primary);
}

.cost-percentage {
    color: var(--color-gray);
    font-size: 0.9rem;
}

.vehicle-footer {
    padding-top: 1rem;
    border-top: 1px solid var(--color-border-light);
}

.cost-metrics {
    display: flex;
    gap: 2rem;
}

.metric {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.metric-label {
    font-size: 0.8rem;
    color: var(--color-gray);
    margin-bottom: 0.25rem;
}

.metric-value {
    font-weight: 600;
    color: var(--color-primary);
}
</style>

<?php
include __DIR__ . '/../layouts/footer.php';
?>