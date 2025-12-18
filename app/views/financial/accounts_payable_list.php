<?php
// app/views/financial/accounts_payable_list.php - VERSÃO DISCRETA
$pageTitle = 'Contas a Pagar';
$pageScript = 'accounts_payable.js';

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- ESTILOS DISCRETOS PARA A LISTA DE CONTAS A PAGAR -->
<style>
/* === ESTILOS DISCRETOS PARA AS LISTAS === */

/* === SEÇÃO DE FILTROS (DISCRETA) === */
.filter-section {
    background: var(--btl-white);
    border-radius: 12px;
    padding: 20px;
    margin: 0 0 24px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.08);
}

.filter-section h3 {
    color: var(--btl-gray-dark);
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.filter-section h3 i {
    color: var(--btl-gray);
    font-size: 16px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--btl-gray);
    font-weight: 500;
    margin-bottom: 8px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group label i {
    color: var(--btl-gray);
    font-size: 14px;
}

.filter-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: var(--btl-white);
    font-weight: 400;
    cursor: pointer;
    color: var(--btl-gray-dark);
}

.filter-group select:focus {
    outline: none;
    border-color: var(--btl-orange-light);
    box-shadow: 0 0 0 2px rgba(255,107,0,0.1);
}

/* Botão Limpar Filtros (Discreto) */
.btn-clean {
    padding: 10px 20px;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--btl-white);
    color: var(--btl-gray);
    height: 42px;
    justify-content: center;
}

.btn-clean:hover {
    border-color: var(--btl-gray);
    color: var(--btl-gray-dark);
    background: rgba(0,0,0,0.02);
}

.btn-clean-secondary {
    background: rgba(0,0,0,0.02);
    border-color: rgba(0,0,0,0.1);
}

.btn-clean-secondary:hover {
    background: rgba(0,0,0,0.04);
    border-color: rgba(0,0,0,0.2);
    color: var(--btl-gray-dark);
}

/* === TABELA DE CONTAS A PAGAR (DISCRETA) === */
.bases-table-container {
    background: var(--btl-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 28px;
    border: 1px solid rgba(0,0,0,0.08);
}

.bases-table-header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(0,0,0,0.02);
}

.bases-table-header h2 {
    color: var(--btl-gray-dark);
    font-size: 18px;
    font-weight: 500; /* Mais discreto */
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.3px;
}

.bases-table-header h2 i {
    color: var(--btl-gray);
    font-size: 18px;
}

.table-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    padding: 8px 16px;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--btl-white);
    color: var(--btl-gray);
}

.btn-action:hover {
    border-color: var(--btl-gray);
    color: var(--btl-gray-dark);
    background: rgba(0,0,0,0.02);
}

.btn-action.primary {
    background: var(--btl-orange);
    color: var(--btl-white);
    border-color: var(--btl-orange);
}

.btn-action.primary:hover {
    background: var(--btl-orange-dark);
    border-color: var(--btl-orange-dark);
}

/* Tabela Discreta */
.table-responsive {
    overflow-x: auto;
    border-radius: 0 0 12px 12px;
}

.bases-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.bases-table thead {
    background: rgba(0,0,0,0.02);
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.bases-table th {
    padding: 16px;
    text-align: left;
    font-weight: 500; /* Mais discreto - sem negrito */
    color: var(--btl-gray);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    white-space: nowrap;
}

.bases-table td {
    padding: 16px;
    border-bottom: 1px solid rgba(0,0,0,0.04);
    vertical-align: top;
    font-weight: 400;
}

.bases-table tbody tr {
    transition: all 0.2s ease;
}

.bases-table tbody tr:hover {
    background: rgba(0,0,0,0.01);
}

.bases-table tbody tr.row-overdue {
    background: rgba(220, 53, 69, 0.03);
}

.bases-table tbody tr.row-due-soon {
    background: rgba(255, 193, 7, 0.03);
}

.bases-table tbody tr:last-child td {
    border-bottom: none;
}

/* === ESTILOS ESPECÍFICOS DA TABELA (DISCRETOS) === */
.payable-description {
    max-width: 250px;
}

.description-main {
    font-weight: 500; /* Mais discreto */
    color: var(--btl-gray-dark);
    margin-bottom: 4px;
    font-size: 14px;
    line-height: 1.4;
}

.description-notes {
    font-size: 12px;
    color: var(--btl-gray);
    margin-top: 4px;
    line-height: 1.4;
}

.badge-recurring {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: rgba(40, 167, 69, 0.08);
    color: var(--btl-success);
    border-radius: 10px;
    font-size: 10px;
    font-weight: 500;
    margin-top: 6px;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.supplier-info {
    max-width: 180px;
}

.supplier-name {
    font-weight: 500; /* Mais discreto */
    font-size: 13px;
    color: var(--btl-gray-dark);
    margin-bottom: 4px;
    line-height: 1.4;
}

.account-info {
    max-width: 150px;
}

.account-code {
    font-family: 'Courier New', monospace;
    font-size: 10px;
    color: var(--btl-gray);
    margin-bottom: 2px;
}

.account-name {
    font-size: 13px;
    font-weight: 500; /* Mais discreto */
    color: var(--btl-gray-dark);
    line-height: 1.4;
}

.payable-amount {
    text-align: right;
}

.amount-value {
    font-weight: 400; /* Mais discreto - sem negrito */
    color: var(--btl-danger);
    font-size: 14px;
    font-family: 'Roboto Mono', 'Courier New', monospace;
}

.payable-dates {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.date-due {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 400; /* Mais discreto */
    color: var(--btl-gray-dark);
}

.date-due i {
    color: var(--btl-gray);
    font-size: 12px;
}

.date-overdue {
    color: var(--btl-danger);
}

.date-due-soon {
    color: var(--btl-warning);
}

.date-paid {
    font-size: 11px;
    color: var(--btl-success);
}

.date-overdue-days {
    font-size: 11px;
    color: var(--btl-danger);
    font-weight: 500;
}

/* === STATUS BADGES (DISCRETOS) === */
.status-pill-modern {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
}

.status-pill-modern.success {
    background: rgba(40, 167, 69, 0.1);
    color: var(--btl-success);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.status-pill-modern.warning {
    background: rgba(255, 193, 7, 0.1);
    color: var(--btl-warning);
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.status-pill-modern.danger {
    background: rgba(220, 53, 69, 0.1);
    color: var(--btl-danger);
    border: 1px solid rgba(220, 53, 69, 0.2);
}

/* === BOTÕES DE AÇÃO NA TABELA (DISCRETOS) === */
.actions-toolbar-modern {
    display: flex;
    gap: 6px;
    justify-content: center;
}

.action-btn-modern {
    width: 32px;
    height: 32px;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 13px;
    background: var(--btl-white);
    position: relative;
    overflow: hidden;
    color: var(--btl-gray);
}

.action-btn-modern:hover {
    border-color: var(--btl-gray);
    color: var(--btl-gray-dark);
    background: rgba(0,0,0,0.02);
    transform: translateY(-1px);
}

.btn-view-modern:hover {
    color: var(--btl-info);
    border-color: var(--btl-info);
}

.btn-edit-modern:hover {
    color: var(--btl-warning);
    border-color: var(--btl-warning);
}

.btn-pay-modern:hover {
    color: var(--btl-success);
    border-color: var(--btl-success);
}

.btn-delete-modern:hover {
    color: var(--btl-danger);
    border-color: var(--btl-danger);
}

.btn-reopen-modern:hover {
    color: var(--btl-orange);
    border-color: var(--btl-orange);
}

/* === EMPTY STATE (DISCRETO) === */
.empty-state-modern {
    text-align: center;
    padding: 50px 30px;
    color: var(--btl-gray);
}

.empty-icon-modern {
    font-size: 50px;
    color: rgba(0,0,0,0.1);
    margin-bottom: 16px;
    opacity: 0.7;
}

.empty-state-modern h3 {
    color: var(--btl-gray-dark);
    font-size: 18px;
    font-weight: 500;
    margin: 0 0 10px 0;
    letter-spacing: 0.2px;
}

.empty-state-modern p {
    color: var(--btl-gray);
    margin: 0 0 20px 0;
    font-size: 14px;
    line-height: 1.5;
}

/* === RESPONSIVO PARA AS LISTAS === */
@media (max-width: 1200px) {
    .filter-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .filter-section {
        padding: 16px;
    }
    
    .bases-table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .table-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .filter-section {
        padding: 14px;
    }
    
    .bases-table {
        font-size: 13px;
    }
    
    .bases-table th,
    .bases-table td {
        padding: 12px 8px;
    }
    
    .actions-toolbar-modern {
        flex-direction: column;
        gap: 4px;
    }
    
    .action-btn-modern {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .btn-action {
        padding: 6px 12px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .filter-section {
        padding: 12px;
    }
    
    .bases-table-header {
        padding: 14px;
    }
    
    .table-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="bases-dashboard">
    <!-- Cabeçalho da Página (NÃO ALTERAR) -->
    <div class="page-header">
        <div class="header-content">
            <h1>Contas a Pagar BT Log</h1>
            <p>Gerencie as contas a pagar da empresa</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" id="newPayableBtn">
                <i class="fas fa-plus-circle"></i>
                Nova Conta
            </button>
            <button class="btn btn-secondary" id="newSupplierBtn">
                <i class="fas fa-truck"></i>
                Novo Fornecedor
            </button>
        </div>
    </div>
</div>

    <!-- Dashboard Discreto (NÃO ALTERAR) -->
    <div class="stats-section">
        <h3>Visão Geral</h3>
        <div class="stats-grid-discreet">
            <!-- Total a Pagar -->
            <div class="stat-card-discreet primary" id="statTotalPayable">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet">R$ <?php echo isset($stats['total_amount']) ? number_format($stats['total_amount'], 0, ',', '.') : '0'; ?></div>
                        <div class="stat-label-discreet">Total a Pagar</div>
                        <div class="stat-description-discreet">
                            <?php echo isset($stats['total_count']) ? $stats['total_count'] . ' contas' : '0 contas'; ?>
                            <?php if(isset($stats['overdue_amount']) && $stats['overdue_amount'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-exclamation-triangle"></i>
                                R$ <?php echo number_format($stats['overdue_amount'], 0, ',', '.'); ?> atrasado
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Nenhum atraso
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <!-- Contas Pendentes -->
            <div class="stat-card-discreet warning" id="statPendingPayable">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo isset($stats['pending_count']) ? $stats['pending_count'] : '0'; ?></div>
                        <div class="stat-label-discreet">Contas Pendentes</div>
                        <div class="stat-description-discreet">
                            Vencimento próximo
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-clock"></i>
                                <?php echo isset($stats['due_soon_count']) ? $stats['due_soon_count'] : '0'; ?> vencem em 7 dias
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Contas Atrasadas -->
            <div class="stat-card-discreet danger" id="statOverduePayable">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo isset($stats['overdue_count']) ? $stats['overdue_count'] : '0'; ?></div>
                        <div class="stat-label-discreet">Contas Atrasadas</div>
                        <div class="stat-description-discreet">
                            <?php echo isset($stats['overdue_30_count']) ? $stats['overdue_30_count'] . ' +30 dias' : '0 +30 dias'; ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-exclamation-circle"></i>
                                Atenção necessária
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <!-- Média por Conta -->
            <div class="stat-card-discreet info" id="statAveragePayable">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet">R$ <?php echo isset($stats['average_amount']) ? number_format($stats['average_amount'], 2, ',', '.') : '0,00'; ?></div>
                        <div class="stat-label-discreet">Média por Conta</div>
                        <div class="stat-description-discreet">
                            <?php echo isset($stats['recurring_count']) ? $stats['recurring_count'] . ' recorrentes' : '0 recorrentes'; ?>
                            <div class="stat-trend-discreet <?php echo isset($stats['trend']) && $stats['trend'] > 0 ? 'trend-down-discreet' : 'trend-up-discreet'; ?>">
                                <i class="fas fa-chart-<?php echo isset($stats['trend']) && $stats['trend'] > 0 ? 'line' : 'bar'; ?>"></i>
                                <?php 
                                if (isset($stats['trend']) && $stats['trend'] > 0) {
                                    echo '+' . $stats['trend'] . '%';
                                } else {
                                    echo 'Estável';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros (VERSÃO DISCRETA) -->
    <div class="filter-section">
        <h3><i class="fas fa-filter"></i> Filtros</h3>
        <div class="filter-grid">
            <div class="filter-group">
                <label for="companyFilter">
                    <i class="fas fa-building"></i>
                    Empresa
                </label>
                <select id="companyFilter" class="form-select">
                    <option value="all">Todas as Empresas</option>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>" <?php echo (isset($_SESSION['company_id']) && $_SESSION['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">Empresa Principal</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterStatus">
                    <i class="fas fa-toggle-on"></i>
                    Status
                </label>
                <select id="filterStatus" class="form-select">
                    <option value="all">Todos os Status</option>
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                    <option value="atrasado">Atrasado</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterSupplier">
                    <i class="fas fa-truck"></i>
                    Fornecedor
                </label>
                <select id="filterSupplier" class="form-select">
                    <option value="all">Todos Fornecedores</option>
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>">
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterPeriod">
                    <i class="fas fa-calendar"></i>
                    Período
                </label>
                <select id="filterPeriod" class="form-select">
                    <option value="all">Todo Período</option>
                    <option value="today">Hoje</option>
                    <option value="week">Esta Semana</option>
                    <option value="month">Este Mês</option>
                    <option value="quarter">Este Trimestre</option>
                    <option value="year">Este Ano</option>
                    <option value="overdue">Atrasadas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button class="btn-clean btn-clean-secondary" id="clearFilters">
                    <i class="fas fa-times"></i> Limpar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Contas a Pagar (VERSÃO DISCRETA) -->
    <div class="bases-table-container">
        <div class="bases-table-header">
            <h2><i class="fas fa-list"></i> Lista de Contas a Pagar</h2>
            <div class="table-actions">
                <button class="btn-action" id="exportBtn">
                    <i class="fas fa-file-export"></i> Exportar
                </button>
                <button class="btn-action" id="printBtn">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="bases-table" id="payableTable">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Fornecedor</th>
                        <th>Conta Contábil</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Recorrência</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="payableTableBody">
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state-modern">
                                    <div class="empty-icon-modern">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <h3>Nenhuma Conta a Pagar</h3>
                                    <p>Comece cadastrando a primeira conta do sistema.</p>
                                    <button class="btn btn-primary" onclick="window.payableManager.openPayableModal()">
                                        <i class="fas fa-plus"></i>
                                        Cadastrar Primeira Conta
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $account): 
                            $isOverdue = ($account['status'] ?? 'pendente') === 'pendente' && 
                                        !empty($account['due_date']) && 
                                        strtotime($account['due_date']) < strtotime(date('Y-m-d'));
                            $isDueSoon = ($account['status'] ?? 'pendente') === 'pendente' && 
                                        !empty($account['due_date']) && 
                                        strtotime($account['due_date']) <= strtotime(date('Y-m-d', strtotime('+7 days'))) &&
                                        strtotime($account['due_date']) >= strtotime(date('Y-m-d'));
                        ?>
                        <tr data-account-id="<?php echo $account['id']; ?>" 
                            class="<?php echo $isOverdue ? 'row-overdue' : ($isDueSoon ? 'row-due-soon' : ''); ?>">
                            
                            <!-- Coluna Descrição -->
                            <td>
                                <div class="payable-description">
                                    <div class="description-main">
                                        <?php echo htmlspecialchars($account['description'] ?? ''); ?>
                                    </div>
                                    <?php if (!empty($account['notes'])): ?>
                                        <div class="description-notes">
                                            <?php echo htmlspecialchars(substr($account['notes'], 0, 80)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($account['is_recurring']) && $account['is_recurring'] == 1): ?>
                                        <div class="badge-recurring">
                                            <i class="fas fa-sync-alt"></i> Recorrente
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Fornecedor -->
                            <td>
                                <?php if (!empty($account['supplier'])): ?>
                                    <div class="supplier-info">
                                        <div class="supplier-name">
                                            <?php echo htmlspecialchars($account['supplier']); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Coluna Conta Contábil -->
                            <td>
                                <div class="account-info">
                                    <?php if (!empty($account['account_code'])): ?>
                                        <div class="account-code">
                                            <?php echo htmlspecialchars($account['account_code']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="account-name">
                                        <?php echo htmlspecialchars($account['account_name'] ?? 'Não informado'); ?>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Valor -->
                            <td>
                                <div class="payable-amount">
                                    <div class="amount-value">
                                        R$ <?php echo number_format($account['amount'] ?? 0, 2, ',', '.'); ?>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Vencimento -->
                            <td>
                                <div class="payable-dates">
                                    <?php if (!empty($account['due_date'])): ?>
                                        <div class="date-due <?php echo $isOverdue ? 'date-overdue' : ($isDueSoon ? 'date-due-soon' : ''); ?>">
                                            <i class="fas fa-calendar-day"></i>
                                            <?php echo date('d/m/Y', strtotime($account['due_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($account['payment_date'])): ?>
                                        <div class="date-paid">
                                            <i class="fas fa-check"></i> <?php echo date('d/m/Y', strtotime($account['payment_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isOverdue && !empty($account['due_date'])): ?>
                                        <div class="date-overdue-days">
                                            <?php echo floor((strtotime(date('Y-m-d')) - strtotime($account['due_date'])) / (60 * 60 * 24)); ?> dias atrasado
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Recorrência -->
                            <td>
                                <?php if (!empty($account['is_recurring']) && $account['is_recurring'] == 1 && !empty($account['recurrence_frequency'])): ?>
                                    <div class="recurrence-info">
                                        <span class="badge-recurring">
                                            <i class="fas fa-sync-alt"></i>
                                            <?php 
                                            $freqMap = [
                                                'mensal' => 'Mensal',
                                                'trimestral' => 'Trimestral',
                                                'semestral' => 'Semestral',
                                                'anual' => 'Anual'
                                            ];
                                            echo $freqMap[$account['recurrence_frequency']] ?? ucfirst($account['recurrence_frequency']);
                                            ?>
                                        </span>
                                        <?php if (!empty($account['recurrence_end_date'])): ?>
                                            <div class="recurrence-end">
                                                Até: <?php echo date('d/m/Y', strtotime($account['recurrence_end_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Não recorrente</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Coluna Status -->
                            <td>
                                <?php
                                $status = $account['status'] ?? 'pendente';
                                $statusClass = '';
                                $statusIcon = '';
                                switch($status) {
                                    case 'pago':
                                        $statusClass = 'success';
                                        $statusIcon = 'check';
                                        break;
                                    case 'atrasado':
                                        $statusClass = 'danger';
                                        $statusIcon = 'exclamation-triangle';
                                        break;
                                    default:
                                        $statusClass = 'warning';
                                        $statusIcon = 'clock';
                                }
                                ?>
                                <span class="status-pill-modern <?php echo $statusClass; ?>">
                                    <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            
                            <!-- Coluna Ações -->
                            <td>
                                <div class="actions-toolbar-modern">
                                    <button class="action-btn-modern btn-view-modern" 
                                            onclick="window.payableManager.viewPayable(<?php echo $account['id']; ?>)"
                                            title="Visualizar Conta">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn-modern btn-edit-modern" 
                                            onclick="window.payableManager.editPayable(<?php echo $account['id']; ?>)"
                                            title="Editar Conta">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($status !== 'pago'): ?>
                                    <button class="action-btn-modern btn-pay-modern" 
                                            onclick="window.payableManager.markAsPaid(<?php echo $account['id']; ?>)"
                                            title="Marcar como Pago">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="action-btn-modern btn-reopen-modern" 
                                            onclick="window.payableManager.reopenPayable(<?php echo $account['id']; ?>)"
                                            title="Reabrir Conta">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="action-btn-modern btn-delete-modern" 
                                            onclick="window.payableManager.deletePayable(<?php echo $account['id']; ?>)"
                                            title="Excluir Conta">
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

<!-- Modal para Cadastro/Edição de Conta a Pagar (MANTIDO COMO ESTAVA) -->
<div class="modal" id="payableModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="payableModalLabel">Nova Conta a Pagar</h5>
                <button type="button" class="btn-close" onclick="window.payableManager.closePayableModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="payableForm">
                <input type="hidden" name="account_id" value="">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description" class="form-label">Descrição *</label>
                            <input type="text" id="description" name="description" class="form-control" required 
                                   placeholder="Ex: Pagamento de aluguel, Fornecedor XYZ...">
                        </div>
                        <div class="form-group">
                            <label for="chart_account_id" class="form-label">Conta Contábil *</label>
                            <select id="chart_account_id" name="chart_account_id" class="form-control" required>
                                <option value="">Selecione a Conta</option>
                                <?php if (!empty($chartAccounts)): ?>
                                    <?php foreach ($chartAccounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars(($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount" class="form-label">Valor (R$) *</label>
                            <input type="text" id="amount" name="amount" class="form-control" required 
                                   placeholder="0,00" oninput="formatCurrencyInput(this)">
                        </div>
                        <div class="form-group">
                            <label for="due_date" class="form-label">Data de Vencimento *</label>
                            <input type="date" id="due_date" name="due_date" class="form-control" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipo de Fornecedor</label>
                            <div class="supplier-type-selector">
                                <div class="form-check">
                                    <input type="radio" id="supplier_type_custom" name="supplier_type" value="custom" class="form-check-input" checked>
                                    <label for="supplier_type_custom" class="form-check-label">Fornecedor personalizado</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="supplier_type_registered" name="supplier_type" value="registered" class="form-check-input">
                                    <label for="supplier_type_registered" class="form-check-label">Fornecedor cadastrado</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" id="customSupplierField">
                            <label for="supplier_custom" class="form-label">Nome do Fornecedor *</label>
                            <input type="text" id="supplier_custom" name="supplier_custom" class="form-control" 
                                   placeholder="Digite o nome do fornecedor">
                        </div>
                        <div class="form-group" id="registeredSupplierField" style="display: none;">
                            <label for="supplier_selection" class="form-label">Fornecedor Cadastrado *</label>
                            <select id="supplier_selection" name="supplier_selection" class="form-control">
                                <option value="">Selecione o Fornecedor</option>
                                <?php if (!empty($suppliers)): ?>
                                    <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-sync-alt"></i> Recorrência</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" id="is_recurring" name="is_recurring" value="1" class="form-check-input">
                                    <label for="is_recurring" class="form-check-label">Esta conta é recorrente</label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="recurrenceFields" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="recurrence_frequency">Frequência *</label>
                                    <select id="recurrence_frequency" name="recurrence_frequency" class="form-control">
                                        <option value="">Selecione a frequência</option>
                                        <option value="mensal">Mensal</option>
                                        <option value="trimestral">Trimestral</option>
                                        <option value="semestral">Semestral</option>
                                        <option value="anual">Anual</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="recurrence_end_date">Data Final</label>
                                    <input type="date" id="recurrence_end_date" name="recurrence_end_date" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="recurrence_count">Número de Repetições</label>
                                    <input type="number" id="recurrence_count" name="recurrence_count" class="form-control" min="1" 
                                           placeholder="Ex: 12 (para 1 ano mensal)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                  placeholder="Observações adicionais..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="pendente">Pendente</option>
                                <option value="pago">Pago</option>
                                <option value="atrasado">Atrasado</option>
                            </select>
                        </div>
                        <div class="form-group" id="paymentDateField" style="display: none;">
                            <label for="payment_date" class="form-label">Data de Pagamento</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.payableManager.closePayableModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Novo Fornecedor (MANTIDO COMO ESTAVA) -->
<div class="modal" id="supplierModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Novo Fornecedor</h5>
                <button type="button" class="btn-close" onclick="window.payableManager.closeSupplierModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="supplierForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supplier_name" class="form-label">Nome do Fornecedor *</label>
                            <input type="text" id="supplier_name" name="supplier_name" class="form-control" required 
                                   placeholder="Nome completo ou razão social">
                        </div>
                        <div class="form-group">
                            <label for="fantasy_name" class="form-label">Nome Fantasia</label>
                            <input type="text" id="fantasy_name" name="fantasy_name" class="form-control" 
                                   placeholder="Nome fantasia (opcional)">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf_cnpj" class="form-label">CPF/CNPJ</label>
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="form-control" 
                                   placeholder="000.000.000-00 ou 00.000.000/0000-00">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="email@fornecedor.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" id="phone" name="phone" class="form-control" 
                                   placeholder="(11) 99999-9999">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Endereço</label>
                        <textarea id="address" name="address" class="form-control" rows="2" 
                                  placeholder="Endereço completo"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.payableManager.closeSupplierModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Fornecedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    window.payableManager.init();
    
    // Adicionar estilos auxiliares discretos
    const style = document.createElement('style');
    style.textContent = `
        .text-muted {
            color: var(--btl-gray);
            font-style: italic;
            font-size: 13px;
        }
        
        .recurrence-end {
            font-size: 11px;
            color: var(--btl-gray);
            margin-top: 4px;
        }
        
        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: var(--btl-white);
            font-weight: 400;
            cursor: pointer;
            color: var(--btl-gray-dark);
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--btl-orange-light);
            box-shadow: 0 0 0 2px rgba(255,107,0,0.1);
        }
    `;
    document.head.appendChild(style);
});
</script>