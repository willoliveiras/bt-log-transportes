<?php
// app/views/dashboard/charts.php
?>
<!-- Gráficos e Métricas -->
<!-- Gráficos e Métricas -->
<div class="metrics-grid">
    <!-- Gráfico de Receita vs Despesas -->
    <div class="metric-card large">
        <div class="metric-header">
            <h3>Receita vs Despesas</h3>
            <div class="metric-actions">
                <button class="btn-icon active" data-period="month">Mês</button>
                <button class="btn-icon" data-period="quarter">Trimestre</button>
                <button class="btn-icon" data-period="year">Ano</button>
            </div>
        </div>
        <div class="metric-content">
            <div class="chart-container">
                <canvas id="revenueExpensesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de Pizza - Despesas -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>Distribuição de Despesas</h3>
        </div>
        <div class="metric-content">
            <div class="chart-container">
                <canvas id="expensesPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Financeiro -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>Status Financeiro</h3>
        </div>
        <div class="metric-content">
            <div class="financial-status">
                <div class="status-item">
                    <div class="status-info">
                        <span class="status-label">A Receber</span>
                        <span class="status-desc">Próximos 30 dias</span>
                    </div>
                    <span class="status-value positive" id="toReceive">R$ 0,00</span>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <span class="status-label">A Pagar</span>
                        <span class="status-desc">Próximos 30 dias</span>
                    </div>
                    <span class="status-value negative" id="toPay">R$ 0,00</span>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <span class="status-label">Vencidas</span>
                        <span class="status-desc">Atrasos</span>
                    </div>
                    <span class="status-value warning" id="overdue">R$ 0,00</span>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <span class="status-label">Fluxo de Caixa</span>
                        <span class="status-desc">Saldo projetado</span>
                    </div>
                    <span class="status-value positive" id="cashFlow">R$ 0,00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Clientes -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>Top 5 Clientes</h3>
        </div>
        <div class="metric-content">
            <div class="top-clients-list" id="topClientsList">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>
    </div>
</div>