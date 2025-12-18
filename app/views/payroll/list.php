<?php
// app/views/payroll/list.php

$pageTitle = 'Folha de Pagamento';
$pageScript = 'payroll.js';
$pageStyle = 'payroll.css';


// Dados de paginação
$currentPage = $payrollData['page'] ?? 1;
$totalPages = $payrollData['total_pages'] ?? 1;
$totalRecords = $payrollData['total'] ?? 0;
?>

<div class="payroll-page">
    <!-- Header da Página -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>📊 Folha de Pagamento</h1>
                <p>Gerencie pagamentos, benefícios e comissões dos colaboradores</p>
            </div>
            <div class="header-stats">
                <?php if ($totalRecords > 0): ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $totalRecords; ?></span>
                    <span class="stat-label">Registros</span>
                </div>
                <?php if (isset($payrollStats['total_payroll'])): ?>
                <div class="stat-item">
                    <span class="stat-number">R$ <?php echo number_format($payrollStats['total_payroll'], 0, ',', '.'); ?></span>
                    <span class="stat-label">Total Folha</span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
		   <div class="header-actions">
			<button class="btn btn-primary" id="generatePayrollBtn">
				<i class="fas fa-calculator"></i>
				Gerar Folha
			</button>
			<button class="btn btn-secondary" id="exportPayrollBtn">
				<i class="fas fa-file-export"></i>
				Exportar
			</button>
			<button class="btn btn-danger" id="deletePayrollBtn">
				<i class="fas fa-trash"></i>
				Excluir Folha
			</button>
		</div>
    </div>

    <!-- Cards de Estatísticas -->
    <?php if ($payrollStats && $totalRecords > 0): ?>
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-content">
                <span class="metric-value"><?php echo $payrollStats['total_records']; ?></span>
                <span class="metric-label">Funcionários</span>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="metric-content">
                <span class="metric-value"><?php echo $payrollStats['paid_count'] ?? 0; ?></span>
                <span class="metric-label">Pagamentos Realizados</span>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="metric-content">
                <span class="metric-value"><?php echo $payrollStats['pending_count'] ?? 0; ?></span>
                <span class="metric-label">Pendentes</span>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon info">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="metric-content">
                <span class="metric-value">R$ <?php echo number_format($payrollStats['total_commissions'] ?? 0, 0, ',', '.'); ?></span>
                <span class="metric-label">Total Comissões</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

<!-- Filtros Avançados -->
<div class="content-card">
    <div class="card-header">
        <h2>
            <i class="fas fa-filter"></i>
            Filtros e Busca
        </h2>
        <div class="card-actions">
            <button class="btn btn-text" onclick="payrollManager.clearFilters()">
                <i class="fas fa-eraser"></i>
                Limpar Filtros
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filters-grid">
            <div class="filter-group">
                <label for="companyFilter" class="filter-label">
                    <i class="fas fa-building"></i>
                    Empresa
                </label>
                <select id="companyFilter" class="filter-select" onchange="payrollManager.filterByCompany(this.value)">
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
                <label for="monthFilter" class="filter-label">
                    <i class="fas fa-calendar"></i>
                    Período
                </label>
                <select id="monthFilter" class="filter-select" onchange="payrollManager.filterByMonth(this.value)">
                    <option value="">Todos os Meses</option>
                    <?php foreach ($months as $month): ?>
                    <option value="<?php echo $month['value']; ?>" 
                            <?php echo ($monthFilter == $month['value']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($month['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="statusFilter" class="filter-label">
                    <i class="fas fa-tag"></i>
                    Status
                </label>
                <select id="statusFilter" class="filter-select" onchange="payrollManager.filterByStatus(this.value)">
                    <option value="">Todos os Status</option>
                    <option value="pendente" <?php echo ($statusFilter == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                    <option value="pago" <?php echo ($statusFilter == 'pago') ? 'selected' : ''; ?>>Pago</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="searchPayroll" class="filter-label">
                    <i class="fas fa-search"></i>
                    Buscar Funcionário
                </label>
                <div class="search-box-wrapper">
                    <input type="text" id="searchPayroll" class="search-input" 
                           placeholder="Digite o nome do funcionário..."
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Lançamentos -->
<div class="content-card">
    <div class="card-header">
        <h2>
            <i class="fas fa-file-invoice-dollar"></i>
            Lançamentos de Folha
        </h2>
        <div class="card-actions">
            <div class="table-info">
                <?php if ($totalRecords > 0): ?>
                <span class="records-count"><?php echo $totalRecords; ?> registros encontrados</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Loading State -->
        <div class="payroll-loading" id="payrollLoading">
            <div class="loading-spinner large"></div>
            <p>Carregando dados da folha de pagamento...</p>
        </div>

        <?php if (empty($payrolls)): ?>
        <!-- Estado Vazio -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h3>Nenhum lançamento encontrado</h3>
            <p>Não há registros de folha de pagamento para os filtros selecionados.</p>
            <div class="empty-actions">
                <button class="btn btn-primary" onclick="payrollManager.openGenerateModal()">
                    <i class="fas fa-calculator"></i>
                    Gerar Primeira Folha
                </button>
                <button class="btn btn-secondary" onclick="payrollManager.clearFilters()">
                    <i class="fas fa-eraser"></i>
                    Limpar Filtros
                </button>
            </div>
        </div>
        <?php else: ?>
        <!-- Tabela com Dados -->
        <div class="table-responsive">
            <table class="data-table" id="payrollTable">
                <thead>
                    <tr>
                        <th class="text-left">
                            <i class="fas fa-user"></i>
                            Funcionário
                        </th>
                        <th>
                            <i class="fas fa-calendar"></i>
                            Mês/Ano
                        </th>
                        <th class="text-right">
                            <i class="fas fa-money-bill"></i>
                            Salário Base
                        </th>
                        <th class="text-right">
                            <i class="fas fa-chart-line"></i>
                            Comissões
                        </th>
                        <th class="text-right">
                            <i class="fas fa-gift"></i>
                            Benefícios
                        </th>
                        <th class="text-right">
                            <i class="fas fa-minus-circle"></i>
                            Descontos
                        </th>
                        <th class="text-right">
                            <i class="fas fa-wallet"></i>
                            Líquido
                        </th>
                        <th>
                            <i class="fas fa-tag"></i>
                            Status
                        </th>
                        <th class="text-center">
                            <i class="fas fa-cog"></i>
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrolls as $payroll): ?>
                    <tr class="payroll-row" data-payroll-id="<?php echo $payroll['id']; ?>">
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($payroll['employee_name'], 0, 2)); ?>
                                </div>
                                <div class="employee-details">
                                    <strong class="employee-name"><?php echo htmlspecialchars($payroll['employee_name']); ?></strong>
                                    <span class="employee-position"><?php echo htmlspecialchars($payroll['position']); ?></span>
                                    <small class="employee-company"><?php echo htmlspecialchars($payroll['company_name']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="month-badge">
                                <?php echo date('m/Y', strtotime($payroll['reference_month'])); ?>
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="salary-amount base-salary">
                                R$ <?php echo number_format($payroll['base_salary'], 2, ',', '.'); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="salary-amount commission-amount">
                                + R$ <?php echo number_format($payroll['commissions'], 2, ',', '.'); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="salary-amount benefits-amount">
                                + R$ <?php echo number_format($payroll['benefits'], 2, ',', '.'); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="salary-amount discounts-amount">
                                - R$ <?php echo number_format($payroll['discounts'], 2, ',', '.'); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <strong class="salary-amount net-salary">
                                R$ <?php echo number_format($payroll['net_salary'], 2, ',', '.'); ?>
                            </strong>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $payroll['status']; ?>">
                                <i class="fas fa-<?php echo $payroll['status'] === 'pago' ? 'check-circle' : 'clock'; ?>"></i>
                                <?php echo ucfirst($payroll['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
								<button class="btn-action btn-view" 
										onclick="payrollManager.viewDetails(<?php echo $payroll['id']; ?>)"
										title="Ver Detalhes">
									<i class="fas fa-eye"></i>
								</button>
								
								<?php if ($payroll['status'] === 'pendente'): ?>
								<button class="btn-action btn-success"
										onclick="payrollManager.markAsPaid(<?php echo $payroll['id']; ?>)"
										title="Marcar como Pago">
									<i class="fas fa-check"></i>
								</button>
								<?php else: ?>
								<button class="btn-action btn-danger"
										onclick="payrollManager.reversePayment(<?php echo $payroll['id']; ?>)"
										title="Estornar Pagamento">
									<i class="fas fa-undo"></i>
								</button>
								<?php endif; ?>
								
								<button class="btn-action btn-print"
										onclick="payrollManager.printPayroll(<?php echo $payroll['id']; ?>)"
										title="Imprimir">
									<i class="fas fa-print"></i>
								</button>
							</div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <button class="pagination-btn" id="prevPage" <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>>
                <i class="fas fa-chevron-left"></i>
                <span>Anterior</span>
            </button>
            
            <div class="pagination-info">
                <span class="page-info">Página <?php echo $currentPage; ?> de <?php echo $totalPages; ?></span>
                <span class="total-info">(<?php echo $totalRecords; ?> registros)</span>
            </div>
            
            <button class="pagination-btn" id="nextPage" <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>>
                <span>Próxima</span>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal" id="payrollModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-file-invoice-dollar"></i>
                <h3 id="modalPayrollTitle">Detalhes da Folha de Pagamento</h3>
            </div>
            <button class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="payrollDetails">
                <!-- Detalhes serão carregados via AJAX -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="closePayrollButton">
                <i class="fas fa-times"></i>
                Fechar
            </button>
            <button type="button" class="btn btn-primary" id="printPayrollButton" style="display: none;">
                <i class="fas fa-print"></i>
                Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Modal de Geração de Folha -->
<div class="modal" id="generatePayrollModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-calculator"></i>
                <h3>Gerar Folha de Pagamento</h3>
            </div>
            <button class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="generatePayrollForm">
                <div class="form-group">
                    <label for="generate_company" class="form-label">
                        <i class="fas fa-building"></i>
                        Empresa *
                    </label>
                    <select id="generate_company" name="company_id" class="form-select" required>
                        <option value="">Selecione a empresa</option>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="generate_month" class="form-label">
                        <i class="fas fa-calendar"></i>
                        Mês/Ano de Referência *
                    </label>
                    <input type="month" id="generate_month" name="reference_month" 
                           class="form-input"
                           value="<?php echo date('Y-m'); ?>" 
                           required>
                </div>
                
                <div class="form-info alert alert-warning">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Atenção:</strong> Esta ação irá:
                        <ul>
                            <li>Calcular folha para todos os funcionários ativos</li>
                            <li>Incluir comissões do período</li>
                            <li>Aplicar benefícios e descontos cadastrados</li>
                            <li>Gerar registros com status "Pendente"</li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="payrollManager.closeGenerateModal()">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" onclick="payrollManager.generatePayroll()">
                <i class="fas fa-calculator"></i>
                <span class="btn-text">Gerar Folha</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Processando...</span>
                </div>
            </button>
        </div>
    </div>
</div>



<script>
// Configurar eventos dos botões
document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generatePayrollBtn');
    const exportBtn = document.getElementById('exportPayrollBtn');
    const deleteBtn = document.getElementById('deletePayrollBtn');
    
    if (generateBtn) {
        generateBtn.addEventListener('click', () => {
            payrollManager.openGenerateModal();
        });
    }
    
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            payrollManager.exportPayroll();
        });
    }
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            payrollManager.openDeleteModal();
        });
    }
});
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>