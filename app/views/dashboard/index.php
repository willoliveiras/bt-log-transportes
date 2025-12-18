<?php
// app/views/dashboard/index.php
$pageTitle = 'Dashboard';
$pageScript = 'dashboard.js';

include __DIR__ . '/../layouts/header.php';
?>

<div class="dashboard">
    <!-- Welcome Section -->
    <div class="section-header">
        <h1>Dashboard BT Log</h1>
        <p>Bem-vindo, <?php echo $currentUser['name']; ?>! Aqui está o resumo do seu negócio.</p>
    </div>

    <!-- Filtros Rápidos -->


    <!-- Incluir Componente de KPIs -->
    <?php include __DIR__ . '/kpis.php'; ?>

    <!-- Incluir Componente de Gráficos -->
    <?php include __DIR__ . '/charts.php'; ?>

    <!-- Viagens Recentes -->
    <div class="recent-section">
        <div class="section-header">
            <h2>Viagens Recentes</h2>
            <a href="index.php?page=trips" class="btn-link">
                Ver todas
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-container">
            <table class="data-table" id="recentTripsTable">
                <thead>
                    <tr>
                        <th>Nº Viagem</th>
                        <th>Cliente</th>
                        <th>Motorista</th>
                        <th>Origem → Destino</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Lucro</th>
                    </tr>
                </thead>
                <tbody id="recentTripsBody">
                    <!-- Será preenchido via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alertas do Sistema -->
    <div class="alerts-section">
        <div class="section-header">
            <h2>Alertas do Sistema</h2>
        </div>
        <div class="alerts-container" id="alertsContainer">
            <!-- Será preenchido via JavaScript -->
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layouts/footer.php';
?>