<?php
// app/views/dashboard/kpis.php
?>
<!-- KPIs em Grid -->
<!-- KPIs em Grid -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-header">
            <h3>Receita Total</h3>
            <span class="kpi-trend positive">+12%</span>
        </div>
        <div class="kpi-value" id="totalRevenue">R$ 0,00</div>
        <div class="kpi-footer">este mês</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3>Lucro Líquido</h3>
            <span class="kpi-trend positive">+8%</span>
        </div>
        <div class="kpi-value" id="netProfit">R$ 0,00</div>
        <div class="kpi-footer">este mês</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3>Despesas Totais</h3>
            <span class="kpi-trend negative">+15%</span>
        </div>
        <div class="kpi-value" id="totalExpenses">R$ 0,00</div>
        <div class="kpi-footer">este mês</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3>Viagens Realizadas</h3>
            <span class="kpi-trend positive">+5%</span>
        </div>
        <div class="kpi-value" id="completedTrips">0</div>
        <div class="kpi-footer">este mês</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3>Veículos Ativos</h3>
            <span class="kpi-trend neutral">0%</span>
        </div>
        <div class="kpi-value" id="activeVehicles">0/0</div>
        <div class="kpi-footer">disponíveis/total</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3>Eficiência</h3>
            <span class="kpi-trend positive">+3%</span>
        </div>
        <div class="kpi-value" id="efficiencyRate">0%</div>
        <div class="kpi-footer">taxa de ocupação</div>
    </div>
</div>