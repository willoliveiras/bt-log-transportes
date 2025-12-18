<?php
// app/views/contracts/renew_list.php - LISTA PARA RENOVAÇÃO
$pageTitle = 'Renovação de Contratos';
?>

<div class="contracts-dashboard">
    <!-- Cabeçalho da Página -->
    <div class="page-header">
        <div class="header-content">
            <h1>Renovação de Contratos</h1>
            <p>Gerencie a renovação dos contratos da empresa</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="window.location.href='index.php?page=contracts&action=list'">
                <i class="fas fa-arrow-left"></i>
                Voltar para Contratos
            </button>
        </div>
    </div>

    <!-- Instruções -->
    <div class="instructions-section">
        <div class="card">
            <div class="card-body">
                <h4><i class="fas fa-info-circle"></i> Como funciona a renovação</h4>
                <ul>
                    <li>Selecione um contrato da lista para iniciar o processo de renovação</li>
                    <li>Defina a nova data de término do contrato</li>
                    <li>O sistema criará um registro de renovação para auditoria</li>
                    <li>Os alertas de vencimento serão atualizados automaticamente</li>
                    <li>O histórico de renovações ficará disponível para consulta</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filtros para Renovação -->
    <div class="filter-section">
        <h3>Filtrar Contratos para Renovação</h3>
        <div class="filter-grid">
            <div class="filter-group">
                <label for="renewCompanyFilter">
                    <i class="fas fa-building"></i>
                    Empresa
                </label>
                <select id="renewCompanyFilter" class="form-select">
                    <option value="all">Todas as Empresas</option>
                    <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>">
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="renewStatusFilter">
                    <i class="fas fa-filter"></i>
                    Status para Renovação
                </label>
                <select id="renewStatusFilter" class="form-select">
                    <option value="expiring_soon">À Vencer (30 dias)</option>
                    <option value="active">Todos Ativos</option>
                    <option value="expired">Vencidos</option>
                    <option value="all">Todos os Contratos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button class="btn-clean btn-clean-secondary" id="resetRenewalFilters">
                    <i class="fas fa-times"></i> Limpar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Contratos para Renovação -->
    <div class="contracts-table-container">
        <div class="contracts-table-header">
            <h2><i class="fas fa-redo"></i> Contratos para Renovação</h2>
            <div class="table-actions">
                <div class="search-box">
                    <input type="text" id="searchRenewal" placeholder="Buscar contratos...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="contracts-table" id="renewalTable">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Vencimento Atual</th>
                        <th>Status</th>
                        <th>Última Renovação</th>
                        <th>Próxima Sugestão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="renewalTableBody">
					<!-- Os contratos serão carregados via AJAX -->
					<tr>
						<td colspan="6">
							<div class="loading-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; text-align: center; width: 100%;">
								<i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
								<p style="text-align: center; width: 100%;">Carregando contratos...</p>
							</div>
						</td>
					</tr>
				</tbody>

<!-- E também a parte de renovações recentes: -->

	<div class="renewals-list" id="recentRenewals">
		<div class="loading-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 150px; text-align: center; width: 100%;">
			<i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 1rem;"></i>
			<p style="text-align: center; width: 100%;">Carregando renovações recentes...</p>
		</div>
	</div>

<!-- Carregar CSS específico -->
<link rel="stylesheet" href="/bt-log-transportes/public/assets/css/renewals-list.css">
<link rel="stylesheet" href="/bt-log-transportes/public/assets/css/contracts_common.css">

<!-- Carregar JavaScript específico -->
<script src="/bt-log-transportes/public/assets/js/renew_list.js"></script>