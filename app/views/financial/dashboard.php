<?php
// app/views/financial/dashboard.php

$pageTitle = 'Dashboard Financeiro';
$pageScript = 'financial-dashboard.js';
$pageStyle = 'financial-dashboard.css';
?>

<div class="financial-dashboard">
    <!-- Header com Gradiente -->
    <div class="dashboard-header fade-in-up">
        <div class="dashboard-header-content">
            <h1>📊 Dashboard Financeiro</h1>
            <p>Visão completa da saúde financeira em tempo real</p>
        </div>
    </div>

    <!-- Filtros Modernos -->
    <div class="dashboard-filters fade-in-up">
        <div class="filter-group">
            <label for="companyFilter">Empresa</label>
            <select id="companyFilter" class="header-select" onchange="financialDashboard.filterByCompany(this.value)">
                <option value="">Todas as Empresas</option>
                <?php foreach ($companies as $company): ?>
                <option value="<?php echo $company['id']; ?>" 
                        <?php echo ($companyFilter == $company['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($company['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="periodFilter">Período</label>
            <select id="periodFilter" class="header-select" onchange="financialDashboard.filterByPeriod(this.value)">
                <option value="week" <?php echo ($period ?? 'month') === 'week' ? 'selected' : ''; ?>>Esta Semana</option>
                <option value="month" <?php echo ($period ?? 'month') === 'month' ? 'selected' : ''; ?>>Este Mês</option>
                <option value="quarter" <?php echo ($period ?? 'month') === 'quarter' ? 'selected' : ''; ?>>Este Trimestre</option>
                <option value="year" <?php echo ($period ?? 'month') === 'year' ? 'selected' : ''; ?>>Este Ano</option>
                <option value="custom" <?php echo ($period ?? 'month') === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
            </select>
        </div>
        
        <div class="filter-group" id="customDateRange" style="display: <?php echo ($period ?? 'month') === 'custom' ? 'flex' : 'none'; ?>; gap: 0.5rem;">
            <div>
                <label for="startDate">Data Início</label>
                <input type="date" id="startDate" class="header-select" value="<?php echo $startDate ?? ''; ?>">
            </div>
            <div>
                <label for="endDate">Data Fim</label>
                <input type="date" id="endDate" class="header-select" value="<?php echo $endDate ?? ''; ?>">
            </div>
            <div style="align-self: flex-end;">
                <button class="btn btn-primary btn-sm" onclick="financialDashboard.applyCustomDateRange()">
                    <i class="fas fa-check"></i> Aplicar
                </button>
            </div>
        </div>
        
        <div style="align-self: flex-end; margin-left: auto;">
            <button class="btn btn-secondary" onclick="financialDashboard.refreshData()">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
    </div>

    <!-- Alertas -->
    <div class="alerts-container" id="financialAlerts" style="display: none;"></div>

    <!-- KPIs Principais -->
    <div class="kpi-grid">
        <div class="kpi-card positive fade-in-up">
            <div class="kpi-value" id="totalRevenue">R$ 0,00</div>
            <div class="kpi-label">Receita Total</div>
            <div class="kpi-trend">
                <i class="fas fa-arrow-up trend-up"></i>
                <span>12% vs último mês</span>
            </div>
            <div class="kpi-icon" style="background: #E8F5E8; color: #4CAF50;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
        
        <div class="kpi-card negative fade-in-up">
            <div class="kpi-value" id="totalExpenses">R$ 0,00</div>
            <div class="kpi-label">Despesas Totais</div>
            <div class="kpi-trend">
                <i class="fas fa-arrow-down trend-down"></i>
                <span>5% vs último mês</span>
            </div>
            <div class="kpi-icon" style="background: #FFEBEE; color: #F44336;">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
        
        <div class="kpi-card positive fade-in-up">
            <div class="kpi-value" id="netProfit">R$ 0,00</div>
            <div class="kpi-label">Lucro Líquido</div>
            <div class="kpi-trend">
                <i class="fas fa-arrow-up trend-up"></i>
                <span>18% vs último mês</span>
            </div>
            <div class="kpi-icon" style="background: #E3F2FD; color: #2196F3;">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        
        <div class="kpi-card warning fade-in-up">
            <div class="kpi-value" id="cashFlow">R$ 0,00</div>
            <div class="kpi-label">Fluxo de Caixa</div>
            <div class="kpi-trend">
                <i class="fas fa-minus"></i>
                <span>Estável</span>
            </div>
            <div class="kpi-icon" style="background: #FFF3E0; color: #FF9800;">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>
    </div>

    <!-- Gráficos Principais -->
    <div class="charts-grid">
        <!-- Gráfico de Receitas vs Despesas -->
        <div class="chart-card fade-in-up">
            <div class="chart-header">
                <h3>📈 Receitas vs Despesas</h3>
                <div class="chart-actions">
                    <button class="btn btn-sm btn-secondary" onclick="financialDashboard.toggleChartView('revenueExpenses')">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="chart-body">
                <div id="revenueExpensesChart">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Carregando gráfico...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Categorias -->
        <div class="chart-card fade-in-up">
            <div class="chart-header">
                <h3>🥧 Distribuição de Receitas</h3>
            </div>
            <div class="chart-body">
                <div id="revenueByCategoryChart">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Carregando gráfico...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fluxo de Caixa -->
        <div class="chart-card full-width fade-in-up">
            <div class="chart-header">
                <h3>💸 Fluxo de Caixa (12 meses)</h3>
            </div>
            <div class="chart-body large">
                <div id="cashFlowChart">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Carregando gráfico...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listas de Dados -->
    <div class="data-lists-grid">
        <!-- Contas a Vencer -->
        <div class="data-list-card fade-in-up">
            <div class="data-list-header">
                <h3>⏰ Contas a Vencer (30 dias)</h3>
                <a href="index.php?page=accounts_payable" class="btn btn-sm btn-primary">
                    Ver Todas
                </a>
            </div>
            <div class="data-list-body">
                <div id="dueAccountsList">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Carregando contas...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Clientes -->
        <div class="data-list-card fade-in-up">
            <div class="data-list-header">
                <h3>👥 Top Clientes</h3>
            </div>
            <div class="data-list-body">
                <div id="topClientsList">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Carregando clientes...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparação entre Empresas -->
    <div class="chart-card full-width fade-in-up">
        <div class="chart-header">
            <h3>🏢 Comparação entre Empresas</h3>
        </div>
        <div class="chart-body large">
            <div id="companyComparisonChart">
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Carregando comparação...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* public/assets/css/financial-dashboard.css */
.financial-dashboard {
    padding: 1rem;
}

/* Header do Dashboard */
.dashboard-header {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    margin-bottom: 2rem;
    color: var(--color-white);
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: rotate(30deg);
}

.dashboard-header-content {
    position: relative;
    z-index: 2;
}

.dashboard-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.dashboard-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Filtros Modernos */
.dashboard-filters {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 200px;
}

.filter-group label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--color-gray-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* KPIs Cards Modernos */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--color-primary);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.kpi-card.positive {
    border-left-color: var(--color-success);
}

.kpi-card.negative {
    border-left-color: var(--color-error);
}

.kpi-card.warning {
    border-left-color: var(--color-warning);
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-primary) 0%, transparent 100%);
}

.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--color-black);
}

.kpi-label {
    font-size: 0.9rem;
    color: var(--color-gray);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.trend-up {
    color: var(--color-success);
}

.trend-down {
    color: var(--color-error);
}

.kpi-icon {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Grid de Gráficos */
.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.chart-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-gray-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--color-black);
}

.chart-actions {
    display: flex;
    gap: 0.5rem;
}

.chart-body {
    padding: 1.5rem;
    height: 300px;
}

.chart-body.large {
    height: 400px;
}

/* Listas de Dados */
.data-lists-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.data-list-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.data-list-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-gray-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.data-list-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--color-black);
}

.data-list-body {
    max-height: 400px;
    overflow-y: auto;
}

/* Itens das Listas */
.due-account-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-gray-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s ease;
}

.due-account-item:hover {
    background: var(--color-primary-light);
}

.due-account-item:last-child {
    border-bottom: none;
}

.account-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.account-description {
    font-weight: 600;
    color: var(--color-black);
}

.account-meta {
    font-size: 0.8rem;
    color: var(--color-gray);
}

.account-amount {
    font-weight: 700;
    font-size: 1.1rem;
}

.account-due {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
}

.due-soon {
    background: #FFF3E0;
    color: #FF9800;
}

.due-today {
    background: #FFEBEE;
    color: #F44336;
}

.due-future {
    background: #E8F5E8;
    color: #4CAF50;
}

/* Alertas */
.alerts-container {
    margin-bottom: 2rem;
}

.alert-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.alert-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-gray-light);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-item.high {
    border-left: 4px solid var(--color-error);
}

.alert-item.medium {
    border-left: 4px solid var(--color-warning);
}

.alert-item.low {
    border-left: 4px solid var(--color-success);
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.alert-item.high .alert-icon {
    background: #FFEBEE;
    color: var(--color-error);
}

.alert-item.medium .alert-icon {
    background: #FFF3E0;
    color: var(--color-warning);
}

.alert-item.low .alert-icon {
    background: #E8F5E8;
    color: var(--color-success);
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--color-black);
}

.alert-message {
    font-size: 0.9rem;
    color: var(--color-gray);
}

/* Loading States */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--color-gray);
}

.loading-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Responsividade */
@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .data-lists-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-body {
        height: 250px;
    }
    
    .chart-body.large {
        height: 300px;
    }
}

/* Animações */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* Badges */
.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success {
    background: #E8F5E8;
    color: var(--color-success);
}

.badge-warning {
    background: #FFF3E0;
    color: var(--color-warning);
}

.badge-danger {
    background: #FFEBEE;
    color: var(--color-error);
}

.badge-info {
    background: #E3F2FD;
    color: var(--color-info);
}
</style>

<script>
// Inicialização otimizada do dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('💰 Inicializando Dashboard Financeiro Moderno...');
    
    // Aguardar um pouco para garantir que tudo carregou
    setTimeout(() => {
        if (window.financialDashboard) {
            window.financialDashboard.init();
        } else {
            console.error('❌ FinancialDashboard não encontrado');
        }
    }, 500);
});

// Função global para acesso fácil
window.refreshFinancialDashboard = function() {
    if (window.financialDashboard) {
        window.financialDashboard.refreshData();
    }
};
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>