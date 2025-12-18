<?php
// app/views/contracts/list.php - DESIGN MODERNO BT LOG
$pageTitle = 'Contratos';
?>

<div class="contracts-dashboard">
    <!-- Cabeçalho da Página -->
    <div class="page-header">
        <div class="header-content">
            <h1>Contratos BT Log</h1>
            <p>Gerencie os contratos da empresa</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" id="newContractBtn">
                <i class="fas fa-file-contract"></i>
                Novo Contrato
            </button>
        </div>
    </div>

    <!-- Dashboard de Contratos -->
    <div class="stats-section">
        <h3>Visão Geral</h3>
        <div class="stats-grid-discreet">
            <!-- Total de Contratos -->
            <div class="stat-card-discreet primary" id="statTotalContracts">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['total']; ?></div>
                        <div class="stat-label-discreet">Total de Contratos</div>
                        <div class="stat-description-discreet">
                            <?php echo $stats['active']; ?> ativos
                            <?php if($stats['expired'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <?php echo $stats['expired']; ?> vencidos
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Nenhum vencido
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-file-contract"></i>
                    </div>
                </div>
            </div>

            <!-- Valor Total -->
            <div class="stat-card-discreet info" id="statTotalValue">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet">R$ <?php echo number_format($stats['total_value'], 2, ',', '.'); ?></div>
                        <div class="stat-label-discreet">Valor Total</div>
                        <div class="stat-description-discreet">
                            Em contratos ativos
                            <?php if($stats['total_value'] > 0): ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-chart-line"></i>
                                Valor significativo
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <!-- Contratos à Vencer -->
            <div class="stat-card-discreet warning" id="statExpiringSoon">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['expiring_soon']; ?></div>
                        <div class="stat-label-discreet">À Vencer (30 dias)</div>
                        <div class="stat-description-discreet">
                            Atenção necessária
                            <?php if($stats['expiring_soon'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-exclamation-triangle"></i>
                                Necessário revisar
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Tudo em dia
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Tipos de Contrato -->
            <div class="stat-card-discreet success" id="statByType">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo count($stats['by_type']); ?></div>
                        <div class="stat-label-discreet">Tipos Diferentes</div>
                        <div class="stat-description-discreet">
                            Diversificação
                            <?php if(count($stats['by_type']) > 1): ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-layer-group"></i>
                                Boa diversificação
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-tags"></i>
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
                    <option value="all">Todas as Empresas</option>
                    <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>">
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterStatus">
                    <i class="fas fa-toggle-on"></i>
                    Status
                </label>
                <select id="filterStatus" class="form-select">
                    <option value="all">Todos os Status</option>
                    <option value="active">Ativos</option>
                    <option value="expired">Vencidos</option>
                    <option value="expiring">À Vencer (30 dias)</option>
                    <option value="draft">Rascunho</option>
                    <option value="cancelled">Cancelados</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterType">
                    <i class="fas fa-file-signature"></i>
                    Tipo
                </label>
                <select id="filterType" class="form-select">
                    <option value="all">Todos os Tipos</option>
                    <option value="client">Com Cliente</option>
                    <option value="supplier">Com Fornecedor</option>
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

    <!-- Tabela de Contratos -->
    <div class="contracts-table-container">
        <div class="contracts-table-header">
            <h2><i class="fas fa-list"></i> Lista de Contratos</h2>
            <div class="table-actions">
                <div class="search-box">
                    <input type="text" id="searchContracts" placeholder="Buscar contratos...">
                    <i class="fas fa-search"></i>
                </div>
                <button class="btn btn-secondary btn-sm" id="exportContracts">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="contracts-table" id="contractsTable">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Partes</th>
                        <th>Período</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Documento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="contractsTableBody">
                    <?php if (empty($contracts)): ?>
                        <tr class="empty-row">
                            <td colspan="7">
                                <div class="empty-state-modern">
                                    <div class="empty-icon-modern">
                                        <i class="fas fa-file-contract"></i>
                                    </div>
                                    <h3>Nenhum Contrato Cadastrado</h3>
                                    <p>Comece cadastrando o primeiro contrato do sistema.</p>
                                    <button class="btn btn-primary" id="firstContractBtn">
                                        <i class="fas fa-plus"></i>
                                        Cadastrar Primeiro Contrato
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $contract): ?>
                        <?php 
                            $displayStatus = $contract['display_status'] ?? $contract['status'];
                            $statusClass = '';
                            $statusText = '';
                            
                            switch($displayStatus) {
                                case 'active':
                                    $statusClass = 'active';
                                    $statusText = 'Ativo';
                                    break;
                                case 'expired':
                                    $statusClass = 'inactive';
                                    $statusText = 'Vencido';
                                    break;
                                case 'expiring_soon':
                                    $statusClass = 'warning';
                                    $statusText = 'À Vencer';
                                    break;
                                case 'draft':
                                    $statusClass = 'draft';
                                    $statusText = 'Rascunho';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'cancelled';
                                    $statusText = 'Cancelado';
                                    break;
                                default:
                                    $statusClass = 'draft';
                                    $statusText = ucfirst($displayStatus);
                            }
                            
                            // Calcular dias restantes
                            $endDate = new DateTime($contract['end_date']);
                            $today = new DateTime();
                            $interval = $today->diff($endDate);
                            $daysRemaining = $interval->days;
                            $daysRemaining = $interval->invert ? -$daysRemaining : $daysRemaining;
                        ?>
                        <tr data-contract-id="<?php echo $contract['id']; ?>" 
                            data-company-id="<?php echo $contract['company_id']; ?>"
                            data-status="<?php echo $displayStatus; ?>"
                            data-type="<?php echo $contract['contract_type']; ?>">
                            
                            <!-- Coluna Contrato -->
                            <td>
                                <div class="contract-card-modern">
                                    <div class="contract-avatar-modern" 
                                         style="background: linear-gradient(135deg, <?php echo $contract['company_color'] ?? '#FF6B00'; ?>, <?php echo $contract['company_color'] ?? '#E55A00'; ?>);">
                                        <i class="fas fa-file-contract"></i>
                                    </div>
                                    <div class="contract-info-modern">
                                        <div class="contract-name-modern">
                                            <?php echo htmlspecialchars($contract['title']); ?>
                                        </div>
                                        <div class="contract-details-modern">
                                            <span class="contract-number">
                                                <i class="fas fa-hashtag"></i>
                                                <?php echo htmlspecialchars($contract['contract_number']); ?>
                                            </span>
                                            <span class="contract-company">
                                                <i class="fas fa-building"></i>
                                                <?php echo htmlspecialchars($contract['company_name']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Partes -->
                            <td>
                                <div class="parties-card-modern">
                                    <?php if ($contract['contract_type'] === 'client' && $contract['client_name']): ?>
                                        <div class="party-item-modern">
                                            <div class="party-icon-modern client">
                                                <i class="fas fa-user-friends"></i>
                                            </div>
                                            <div class="party-info-modern">
                                                <div class="party-type-modern">Cliente</div>
                                                <div class="party-name-modern"><?php echo htmlspecialchars($contract['client_name']); ?></div>
                                                <?php if ($contract['client_fantasy_name']): ?>
                                                <div class="party-alias-modern"><?php echo htmlspecialchars($contract['client_fantasy_name']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php elseif ($contract['contract_type'] === 'supplier' && $contract['supplier_name']): ?>
                                        <div class="party-item-modern">
                                            <div class="party-icon-modern supplier">
                                                <i class="fas fa-truck-loading"></i>
                                            </div>
                                            <div class="party-info-modern">
                                                <div class="party-type-modern">Fornecedor</div>
                                                <div class="party-name-modern"><?php echo htmlspecialchars($contract['supplier_name']); ?></div>
                                                <?php if ($contract['supplier_fantasy_name']): ?>
                                                <div class="party-alias-modern"><?php echo htmlspecialchars($contract['supplier_fantasy_name']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Período -->
                            <td>
                                <div class="period-card-modern">
                                    <div class="period-dates-modern">
                                        <div class="date-item-modern">
                                            <div class="date-label-modern">Início</div>
                                            <div class="date-value-modern">
                                                <i class="fas fa-calendar-plus"></i>
                                                <?php echo date('d/m/Y', strtotime($contract['start_date'])); ?>
                                            </div>
                                        </div>
                                        <div class="date-item-modern">
                                            <div class="date-label-modern">Término</div>
                                            <div class="date-value-modern <?php echo $displayStatus === 'expired' ? 'expired' : ''; ?>">
                                                <i class="fas fa-calendar-times"></i>
                                                <?php echo date('d/m/Y', strtotime($contract['end_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="period-remaining-modern <?php echo $daysRemaining <= 0 ? 'expired' : ($daysRemaining <= 30 ? 'warning' : 'ok'); ?>">
                                        <?php if ($daysRemaining > 0): ?>
                                            <i class="fas fa-clock"></i>
                                            <?php echo $daysRemaining; ?> dias restantes
                                        <?php elseif ($daysRemaining == 0): ?>
                                            <i class="fas fa-exclamation-circle"></i>
                                            Vence hoje
                                        <?php else: ?>
                                            <i class="fas fa-times-circle"></i>
                                            Vencido há <?php echo abs($daysRemaining); ?> dias
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Valor -->
                            <td>
                                <div class="value-card-modern">
                                    <div class="value-amount-modern">
                                        R$ <?php echo number_format($contract['value'], 2, ',', '.'); ?>
                                    </div>
                                    <div class="value-currency-modern">
                                        <?php echo $contract['currency']; ?>
                                    </div>
                                    <?php if ($contract['payment_terms']): ?>
                                    <div class="value-terms-modern">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo htmlspecialchars($contract['payment_terms']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Status -->
                            <td>
                                <span class="status-pill-modern <?php echo $statusClass; ?>">
                                    <?php if ($statusClass === 'active'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif ($statusClass === 'inactive'): ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php elseif ($statusClass === 'warning'): ?>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    <?php elseif ($statusClass === 'draft'): ?>
                                        <i class="fas fa-edit"></i>
                                    <?php elseif ($statusClass === 'cancelled'): ?>
                                        <i class="fas fa-ban"></i>
                                    <?php endif; ?>
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            
                            <!-- Coluna Documento -->
                            <td>
                                <div class="document-card-modern">
                                    <?php if ($contract['contract_file']): ?>
                                        <div class="document-item-modern">
                                            <div class="document-icon-modern">
                                                <i class="fas fa-file-pdf"></i>
                                            </div>
                                            <div class="document-info-modern">
                                                <div class="document-name-modern">Contrato PDF</div>
                                                <div class="document-actions-modern">
                                                    <button class="btn-view-document" 
                                                            data-contract-id="<?php echo $contract['id']; ?>"
                                                            title="Visualizar PDF">
                                                        <i class="fas fa-eye"></i> Visualizar
                                                    </button>
                                                    <button class="btn-download-document" 
                                                            data-filename="<?php echo htmlspecialchars($contract['contract_file']); ?>"
                                                            title="Baixar PDF">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-document-modern">
                                            <i class="fas fa-file-upload"></i>
                                            <span>Sem documento</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Ações -->
                            <td>
                                <div class="actions-toolbar-modern">
                                    <button class="action-btn-modern btn-view-modern" 
                                            data-contract-id="<?php echo $contract['id']; ?>"
                                            title="Visualizar Contrato">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn-modern btn-edit-modern" 
                                            data-contract-id="<?php echo $contract['id']; ?>"
                                            title="Editar Contrato">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($contract['status'] === 'active' && $displayStatus !== 'expired'): ?>
                                    <button class="action-btn-modern btn-renew-modern" 
                                            data-contract-id="<?php echo $contract['id']; ?>"
                                            title="Renovar Contrato">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="action-btn-modern btn-delete-modern" 
                                            data-contract-id="<?php echo $contract['id']; ?>"
                                            title="Excluir Contrato">
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
        
        <!-- Paginação -->
        <div class="table-footer">
            <div class="pagination-info" id="paginationInfo">
                Mostrando <?php echo count($contracts); ?> de <?php echo count($contracts); ?> contratos
            </div>
            <div class="pagination" id="paginationContainer"></div>
        </div>
    </div>
</div>

<!-- Modal para Cadastro/Edição de Contrato -->
<div class="modal" id="contractModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="contractModalLabel">Novo Contrato</h5>
                <button type="button" class="btn-close" id="closeContractModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="contractForm" enctype="multipart/form-data">
                <input type="hidden" name="contract_id" id="contract_id" value="">
                
                <div class="modal-body">
                    <div class="form-section">
                        <h6><i class="fas fa-building"></i> Empresa Responsável</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_company_id" class="form-label required">Empresa *</label>
                                <select id="modal_company_id" name="company_id" class="form-control" required>
                                    <option value="">Selecione a Empresa</option>
                                    <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-handshake"></i> Tipo de Contrato</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <div class="contract-type-selector">
                                    <label class="radio-label">
                                        <input type="radio" name="contract_type" value="client" checked 
                                               id="contract_type_client">
                                        <span class="radio-mark"></span>
                                        <span class="radio-text">
                                            <i class="fas fa-user-friends"></i>
                                            Contrato com Cliente
                                        </span>
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="contract_type" value="supplier"
                                               id="contract_type_supplier">
                                        <span class="radio-mark"></span>
                                        <span class="radio-text">
                                            <i class="fas fa-truck-loading"></i>
                                            Contrato com Fornecedor
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="clientSection">
                        <h6><i class="fas fa-user-friends"></i> Cliente</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_client_id" class="form-label">Cliente *</label>
                                <select id="modal_client_id" name="client_id" class="form-control">
                                    <option value="">Selecione o Cliente</option>
                                    <!-- Clientes serão carregados dinamicamente via AJAX -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="supplierSection" style="display: none;">
                        <h6><i class="fas fa-truck-loading"></i> Fornecedor</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_supplier_id" class="form-label">Fornecedor *</label>
                                <select id="modal_supplier_id" name="supplier_id" class="form-control">
                                    <option value="">Selecione o Fornecedor</option>
                                    <!-- Fornecedores serão carregados dinamicamente via AJAX -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-file-alt"></i> Informações do Contrato</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_contract_number" class="form-label required">Número do Contrato *</label>
                                <input type="text" id="modal_contract_number" name="contract_number" 
                                       class="form-control" required placeholder="Ex: CT-2024-001">
                                <div class="form-text">Número único para identificação</div>
                            </div>
                            <div class="form-group">
                                <label for="modal_title" class="form-label required">Título do Contrato *</label>
                                <input type="text" id="modal_title" name="title" class="form-control" required 
                                       placeholder="Ex: Contrato de Prestação de Serviços">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_description" class="form-label">Descrição</label>
                                <textarea id="modal_description" name="description" class="form-control" rows="3"
                                          placeholder="Descreva os termos principais do contrato..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-calendar"></i> Período de Vigência</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_start_date" class="form-label required">Data de Início *</label>
                                <input type="date" id="modal_start_date" name="start_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="modal_end_date" class="form-label required">Data de Término *</label>
                                <input type="date" id="modal_end_date" name="end_date" class="form-control" required>
                                <div class="form-text">O sistema alertará 30 dias antes do vencimento</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-money-bill-wave"></i> Valor e Condições</h6>
                        <div class="form-row">
                            <div class="form-group">
								<label for="modal_value" class="form-label">Valor do Contrato</label>
								<div class="currency-input">
									<input type="text" id="modal_value" name="value" class="form-control" 
										   placeholder="0,00" pattern="[0-9.,]+" 
										   oninput="formatCurrency(this)">
								</div>
								<div class="form-text">Use vírgula para decimais (ex: 1.000,50)</div>
							</div>
                            <div class="form-group">
                                <label for="modal_currency" class="form-label">Moeda</label>
                                <select id="modal_currency" name="currency" class="form-control">
                                    <option value="BRL" selected>Real (R$)</option>
                                    <option value="USD">Dólar (US$)</option>
                                    <option value="EUR">Euro (€)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_payment_terms" class="form-label">Condições de Pagamento</label>
                                <input type="text" id="modal_payment_terms" name="payment_terms" class="form-control"
                                       placeholder="Ex: 30 dias após emissão da NF">
                            </div>
                            <div class="form-group">
                                <label for="modal_renewal_terms" class="form-label">Condições de Renovação</label>
                                <input type="text" id="modal_renewal_terms" name="renewal_terms" class="form-control"
                                       placeholder="Ex: Renovação automática por 12 meses">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-file-pdf"></i> Documento do Contrato</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_contract_file" class="form-label">Upload do PDF</label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="file-upload-text">
                                        <span>Arraste ou clique para fazer upload</span>
                                        <small>Apenas arquivos PDF, máximo 10MB</small>
                                    </div>
                                    <input type="file" id="modal_contract_file" name="contract_file" 
                                           class="file-upload-input" accept=".pdf">
                                    <div class="file-preview" id="filePreview"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-cog"></i> Status e Observações</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_status" class="form-label">Status</label>
                                <select id="modal_status" name="status" class="form-control">
                                    <option value="draft">Rascunho</option>
                                    <option value="active" selected>Ativo</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_notes" class="form-label">Observações</label>
                                <textarea id="modal_notes" name="notes" class="form-control" rows="2"
                                          placeholder="Observações adicionais..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelContractModal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveContractBtn">
                        <i class="fas fa-save"></i> Salvar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Documento PDF -->
<div class="modal" id="documentModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="documentModalLabel">Visualizar Contrato</h5>
                <button type="button" class="btn-close" id="closeDocumentModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="pdf-viewer-container">
                    <div class="pdf-viewer-toolbar">
                        <button class="btn-toolbar" id="zoomOutBtn">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <span class="zoom-level" id="zoomLevel">100%</span>
                        <button class="btn-toolbar" id="zoomInBtn">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button class="btn-toolbar" id="downloadPdfBtn">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="btn-toolbar" id="printPdfBtn">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                    <div class="pdf-viewer" id="pdfViewer">
                        <div class="pdf-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Carregando documento...</p>
                        </div>
                        <iframe id="pdfFrame" style="display: none; width: 100%; height: 500px; border: none;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Renovação de Contrato -->
<div class="modal" id="renewModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Renovar Contrato</h5>
                <button type="button" class="btn-close" id="closeRenewModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="renewForm">
                <input type="hidden" name="contract_id" id="renewContractId" value="">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_end_date" class="form-label">Data de Término Atual</label>
                        <input type="text" id="current_end_date" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_end_date" class="form-label required">Nova Data de Término *</label>
                        <input type="date" id="new_end_date" name="new_end_date" class="form-control" required>
                        <div class="form-text">Selecione a nova data de vencimento do contrato</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="renewal_notes" class="form-label">Observações da Renovação</label>
                        <textarea id="renewal_notes" name="notes" class="form-control" rows="3"
                                  placeholder="Descreva o motivo da renovação e alterações..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Atenção:</strong> Ao renovar o contrato, um novo registro de renovação será criado
                            e o alerta de vencimento será atualizado automaticamente.
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelRenewModal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="confirmRenewBtn">
                        <i class="fas fa-redo"></i> Confirmar Renovação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ... mantém todo o HTML anterior (até a linha antes dos scripts) ... -->

<!-- Carregar CSS -->
<link rel="stylesheet" href="/bt-log-transportes/public/assets/css/contracts_common.css">
<link rel="stylesheet" href="/bt-log-transportes/public/assets/css/contracts.css">

<!-- SISTEMA DE CONTRATOS - SCRIPT ÚNICO COM SUPORTE -->
<script>
(function() {
    'use strict';
    
    console.log('📋 Contracts List Script - Carregando...');
    
    // ============================================
    // CONFIGURAÇÕES
    // ============================================
    const CONFIG = {
        apiUrl: '/bt-log-transportes/public/api/contracts.php',
        storagePath: '/bt-log-transportes/storage/contracts/'
    };
    
    // ============================================
    // ESTADO
    // ============================================
    const STATE = {
        isInitialized: false,
        isDeleting: false,
        selectedContracts: new Set()
    };
    
    // ============================================
    // API DE SUPORTE PARA OUTROS SCRIPTS
    // ============================================
    window.contractsListSupport = {
        // ✅ ABRIR MODAL DE CONTRATO (suporte para contracts_manager.js)
        openContractModal: function(contractId = null) {
            console.log('📋 [Support] Abrindo modal, ID:', contractId);
            
            const modal = document.getElementById('contractModal');
            if (!modal) {
                console.error('❌ Modal não encontrado');
                showNotification('Modal não encontrado', 'error');
                return;
            }
            
            // Resetar formulário
            resetContractForm();
            
            // Definir título
            const modalLabel = document.getElementById('contractModalLabel');
            if (modalLabel) {
                modalLabel.textContent = contractId ? 'Editar Contrato' : 'Novo Contrato';
            }
            
            // Se for edição, carregar dados
            if (contractId) {
                loadContractData(contractId);
            } else {
                toggleContractType('client');
            }
            
            // Mostrar modal
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }, 10);
            
            console.log('✅ Modal aberto via support');
        },
        
        // ✅ FECHAR MODAL DE CONTRATO
        closeContractModal: function() {
            const modal = document.getElementById('contractModal');
            if (!modal) return;
            
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                resetContractForm();
            }, 300);
        },
        
        // ✅ EDITAR CONTRATO (suporte para contracts_manager.js)
        editContract: function(contractId) {
            console.log('✏️ [Support] Editando contrato:', contractId);
            this.openContractModal(contractId);
        },
        
        // ✅ RENOVAR CONTRATO
        renewContract: function(contractId) {
            console.log('🔄 [Support] Renovando contrato:', contractId);
            
            const modal = document.getElementById('renewModal');
            if (!modal) {
                console.error('❌ Modal de renovação não encontrado');
                return;
            }
            
            // Preencher ID
            const contractIdInput = modal.querySelector('input[name="contract_id"]');
            if (contractIdInput) {
                contractIdInput.value = contractId;
            }
            
            // Mostrar modal
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }, 10);
        },
        
        // ✅ DELETAR CONTRATO (suporte para contracts_manager.js)
        deleteContract: function(contractId) {
            console.log('🗑️ [Support] Deletando contrato:', contractId);
            deleteContractAction(contractId);
        },
        
        // ✅ VISUALIZAR DOCUMENTO (suporte para contracts_viewer.js)
        viewDocument: function(contractId) {
            console.log('📄 [Support] Visualizando documento:', contractId);
            openDocumentModal(contractId);
        },
        
        // ✅ DOWNLOAD DOCUMENTO (suporte para contracts_viewer.js)
        downloadDocument: function(filename) {
            console.log('📥 [Support] Download documento:', filename);
            downloadDocumentAction(filename);
        },
        
        // ✅ VISUALIZAR CONTRATO (página - suporte para contracts_viewer.js)
        viewContract: function(contractId) {
            console.log('📋 [Support] Visualizando contrato página:', contractId);
            window.location.href = `/bt-log-transportes/public/index.php?page=contracts&action=view&id=${contractId}`;
        },
        
        // ✅ MOSTRAR NOTIFICAÇÃO
        showNotification: function(message, type = 'info') {
            showNotification(message, type);
        },
        
        // ✅ INICIALIZAR PÁGINA (para outros scripts chamarem)
        initializePage: function() {
            console.log('🌐 [Support] Inicializando página via support');
            init();
        }
    };
    
    // ============================================
    // FUNÇÕES AUXILIARES
    // ============================================
    
    // ✅ MOSTRAR NOTIFICAÇÃO
    function showNotification(message, type = 'info', duration = 3000) {
        console.log(`📢 ${type.toUpperCase()}: ${message}`);
        
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                  type === 'error' ? 'exclamation-circle' : 
                                  type === 'warning' ? 'exclamation-triangle' : 
                                  'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            color: #333;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 99999;
            border-left: 4px solid;
            border-left-color: ${type === 'success' ? '#4CAF50' : 
                               type === 'error' ? '#F44336' : 
                               type === 'warning' ? '#FF9800' : '#2196F3'};
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }
    
    // ✅ FORMATAR MOEDA
    function formatCurrency(input) {
        if (!input) return;
        
        let value = input.value.replace(/\D/g, '');
        if (value === '') {
            input.value = '';
            return;
        }
        
        const number = parseFloat(value) / 100;
        input.value = number.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // ✅ TOGGLE CONTRACT TYPE
    function toggleContractType(type) {
        const clientSection = document.getElementById('clientSection');
        const supplierSection = document.getElementById('supplierSection');
        
        if (clientSection) clientSection.style.display = type === 'client' ? 'block' : 'none';
        if (supplierSection) supplierSection.style.display = type === 'supplier' ? 'block' : 'none';
    }
    
    // ✅ RESETAR FORMULÁRIO
    function resetContractForm() {
        const form = document.getElementById('contractForm');
        if (form) {
            form.reset();
            
            const contractIdInput = form.querySelector('input[name="contract_id"]');
            if (contractIdInput) contractIdInput.value = '';
            
            const clientSelect = document.getElementById('modal_client_id');
            const supplierSelect = document.getElementById('modal_supplier_id');
            
            if (clientSelect) clientSelect.innerHTML = '<option value="">Selecione o Cliente</option>';
            if (supplierSelect) supplierSelect.innerHTML = '<option value="">Selecione o Fornecedor</option>';
            
            toggleContractType('client');
        }
    }
    
    // ✅ CARREGAR DADOS DO CONTRATO
    function loadContractData(contractId) {
        fetch(`${CONFIG.apiUrl}?action=get&id=${contractId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const contract = data.data;
                    const form = document.getElementById('contractForm');
                    
                    if (!form) return;
                    
                    // Preencher campos básicos
                    const contractIdInput = form.querySelector('input[name="contract_id"]');
                    const companySelect = document.getElementById('modal_company_id');
                    const contractNumber = document.getElementById('modal_contract_number');
                    const title = document.getElementById('modal_title');
                    const startDate = document.getElementById('modal_start_date');
                    const endDate = document.getElementById('modal_end_date');
                    const value = document.getElementById('modal_value');
                    
                    if (contractIdInput) contractIdInput.value = contract.id;
                    if (companySelect) companySelect.value = contract.company_id;
                    if (contractNumber) contractNumber.value = contract.contract_number || '';
                    if (title) title.value = contract.title || '';
                    
                    if (startDate && contract.start_date) {
                        const date = new Date(contract.start_date);
                        startDate.value = date.toISOString().split('T')[0];
                    }
                    
                    if (endDate && contract.end_date) {
                        const date = new Date(contract.end_date);
                        endDate.value = date.toISOString().split('T')[0];
                    }
                    
                    if (value && contract.value) {
                        value.value = parseFloat(contract.value).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar contrato:', error);
                showNotification('Erro ao carregar contrato', 'error');
            });
    }
    
    // ✅ ABRIR MODAL DE DOCUMENTO
    function openDocumentModal(contractId) {
        fetch(`${CONFIG.apiUrl}?action=get&id=${contractId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.file_url) {
                    window.open(data.data.file_url, '_blank');
                    showNotification('Documento aberto em nova aba', 'info');
                } else {
                    showNotification('Documento não disponível', 'warning');
                }
            })
            .catch(error => {
                console.error('❌ Erro ao abrir documento:', error);
                showNotification('Erro ao abrir documento', 'error');
            });
    }
    
    // ✅ DOWNLOAD DOCUMENTO
    function downloadDocumentAction(filename) {
        const url = `${CONFIG.storagePath}${encodeURIComponent(filename)}`;
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Download iniciado', 'info');
    }
    
    // ✅ DELETAR CONTRATO
    function deleteContractAction(contractId) {
		if (STATE.isDeleting) {
			console.log('⚠️ Delete já em andamento');
			return;
		}
		
		if (!confirm('Tem certeza que deseja cancelar este contrato?\n\nEsta ação marcará o contrato como cancelado, mas manterá os dados no sistema.')) {
			return;
		}
		
		console.log('🗑️ Iniciando cancelamento do contrato:', contractId);
		STATE.isDeleting = true;
		
		const formData = new FormData();
		formData.append('id', contractId);
		
		// Adicionar token CSRF se existir
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
		if (csrfToken) {
			formData.append('csrf_token', csrfToken);
		}
		
		console.log('📨 Enviando requisição DELETE...');
		
		fetch(`${CONFIG.apiUrl}?action=delete`, {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			}
		})
		.then(async response => {
			console.log('📨 Status da resposta:', response.status);
			console.log('📨 Content-Type:', response.headers.get('content-type'));
			
			const text = await response.text();
			console.log('📄 Resposta completa (primeiros 500 chars):', text.substring(0, 500));
			
			// Tentar parsear como JSON
			try {
				const data = JSON.parse(text);
				return { response, data, isJson: true };
			} catch (jsonError) {
				console.warn('⚠️ Resposta não é JSON válido, tratando como erro:', jsonError);
				
				// Se for HTML de erro, extrair mensagem
				let errorMessage = 'Erro no servidor';
				
				// Tentar extrair mensagem de erro do HTML
				const errorMatch = text.match(/<b>([^<]+)<\/b>/);
				if (errorMatch && errorMatch[1]) {
					errorMessage = `Erro no servidor: ${errorMatch[1]}`;
				} else if (text.includes('PDOException') || text.includes('SQLSTATE')) {
					errorMessage = 'Erro no banco de dados';
				} else if (text.includes('Parse error') || text.includes('syntax error')) {
					errorMessage = 'Erro de sintaxe no código do servidor';
				} else if (text.includes('Fatal error')) {
					errorMessage = 'Erro fatal no servidor';
				}
				
				return { 
					response, 
					data: { 
						success: false, 
						message: errorMessage,
						raw: text.substring(0, 200) 
					}, 
					isJson: false 
				};
			}
		})
		.then(({ response, data, isJson }) => {
			console.log('📊 Dados processados:', { isJson, success: data.success, message: data.message });
			
			if (!response.ok || !data.success) {
				// Se a resposta HTTP não for OK ou o JSON não indicar sucesso
				let errorMsg = data.message || `Erro ${response.status}: ${response.statusText}`;
				
				if (response.status === 500) {
					errorMsg = 'Erro interno do servidor (500). Verifique os logs do servidor.';
				} else if (response.status === 404) {
					errorMsg = 'API não encontrada. Verifique o endpoint.';
				} else if (response.status === 403) {
					errorMsg = 'Acesso negado. Você tem permissão para esta ação?';
				} else if (response.status === 401) {
					errorMsg = 'Não autenticado. Faça login novamente.';
				}
				
				throw new Error(errorMsg);
			}
			
			// Sucesso!
			showNotification(data.message || 'Contrato cancelado com sucesso!', 'success');
			
			// Recarregar após 1.5 segundos
			setTimeout(() => {
				console.log('🔄 Recarregando página...');
				location.reload();
			}, 1500);
			
		})
		.catch(error => {
			console.error('❌ Erro ao deletar contrato:', error);
			console.error('❌ Stack trace:', error.stack);
			
			let userMessage = error.message || 'Erro ao cancelar contrato';
			
			// Mensagens mais amigáveis
			if (userMessage.includes('Failed to fetch') || userMessage.includes('NetworkError')) {
				userMessage = 'Erro de conexão. Verifique sua internet e tente novamente.';
			} else if (userMessage.includes('Unexpected token')) {
				userMessage = 'Erro no servidor: resposta inválida. O servidor pode estar com problemas.';
			}
			
			showNotification(userMessage, 'error');
			
			// Mostrar botão para tentar novamente
			const retry = confirm(`${userMessage}\n\nDeseja tentar novamente?`);
			if (retry) {
				setTimeout(() => {
					deleteContractAction(contractId);
				}, 1000);
			}
		})
		.finally(() => {
			STATE.isDeleting = false;
			console.log('✅ Processo de delete finalizado');
		});
	}
    
    // ✅ SALVAR CONTRATO
    function saveContract(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const saveBtn = document.getElementById('saveContractBtn');
        
        // Validação básica
        if (!formData.get('company_id') || !formData.get('contract_number') || !formData.get('title')) {
            showNotification('Preencha os campos obrigatórios', 'error');
            return;
        }
        
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        }
        
        fetch(`${CONFIG.apiUrl}?action=save`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Contrato salvo com sucesso!', 'success');
                setTimeout(() => {
                    window.contractsListSupport.closeContractModal();
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Erro ao salvar contrato', 'error');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao salvar:', error);
            showNotification('Erro ao salvar contrato', 'error');
        })
        .finally(() => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Contrato';
            }
        });
    }
    
    // ✅ RENOVAR CONTRATO
    function renewContractAction(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const renewBtn = document.getElementById('confirmRenewBtn');
        
        if (renewBtn) {
            renewBtn.disabled = true;
            renewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Renovando...';
        }
        
        fetch(`${CONFIG.apiUrl}?action=renew`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Contrato renovado com sucesso!', 'success');
                setTimeout(() => {
                    closeRenewModal();
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Erro ao renovar contrato', 'error');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao renovar:', error);
            showNotification('Erro ao renovar contrato', 'error');
        })
        .finally(() => {
            if (renewBtn) {
                renewBtn.disabled = false;
                renewBtn.innerHTML = '<i class="fas fa-redo"></i> Confirmar Renovação';
            }
        });
    }
    
    // ✅ FECHAR MODAL DE RENOVAÇÃO
    function closeRenewModal() {
        const modal = document.getElementById('renewModal');
        if (!modal) return;
        
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }, 300);
    }
    
    // ============================================
    // CONFIGURAÇÃO DE EVENT LISTENERS
    // ============================================
    
    function setupEventListeners() {
        console.log('🔗 Configurando event listeners...');
        
        // Botão Novo Contrato
        const newContractBtn = document.getElementById('newContractBtn');
        if (newContractBtn) {
            newContractBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.contractsListSupport.openContractModal();
            });
        }
        
        // Botão Primeiro Contrato
        const firstContractBtn = document.getElementById('firstContractBtn');
        if (firstContractBtn) {
            firstContractBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.contractsListSupport.openContractModal();
            });
        }
        
        // Botões de fechar modais
        const closeButtons = [
            { id: 'closeContractModal', handler: window.contractsListSupport.closeContractModal },
            { id: 'cancelContractModal', handler: window.contractsListSupport.closeContractModal },
            { id: 'closeRenewModal', handler: closeRenewModal },
            { id: 'cancelRenewModal', handler: closeRenewModal }
        ];
        
        closeButtons.forEach(btn => {
            const element = document.getElementById(btn.id);
            if (element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    btn.handler();
                });
            }
        });
        
        // Formulários
        const contractForm = document.getElementById('contractForm');
        if (contractForm) {
            contractForm.addEventListener('submit', saveContract);
        }
        
        const renewForm = document.getElementById('renewForm');
        if (renewForm) {
            renewForm.addEventListener('submit', renewContractAction);
        }
        
        // Tipo de contrato
        const contractTypeRadios = document.querySelectorAll('input[name="contract_type"]');
        contractTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                toggleContractType(this.value);
            });
        });
        
        // Event delegation para botões na tabela
        document.addEventListener('click', function(e) {
            const target = e.target;
            
            // Visualizar contrato (página)
            if (target.closest('.btn-view-modern')) {
                e.preventDefault();
                const button = target.closest('.btn-view-modern');
                const contractId = button?.dataset.contractId;
                if (contractId) {
                    window.contractsListSupport.viewContract(contractId);
                }
            }
            
            // Editar contrato
            else if (target.closest('.btn-edit-modern')) {
                e.preventDefault();
                const button = target.closest('.btn-edit-modern');
                const contractId = button?.dataset.contractId;
                if (contractId) {
                    window.contractsListSupport.editContract(contractId);
                }
            }
            
            // Renovar contrato
            else if (target.closest('.btn-renew-modern')) {
                e.preventDefault();
                const button = target.closest('.btn-renew-modern');
                const contractId = button?.dataset.contractId;
                if (contractId) {
                    window.contractsListSupport.renewContract(contractId);
                }
            }
            
            // Deletar contrato
            else if (target.closest('.btn-delete-modern')) {
                e.preventDefault();
                const button = target.closest('.btn-delete-modern');
                const contractId = button?.dataset.contractId;
                if (contractId) {
                    window.contractsListSupport.deleteContract(contractId);
                }
            }
            
            // Visualizar documento
            else if (target.closest('.btn-view-document')) {
                e.preventDefault();
                const button = target.closest('.btn-view-document');
                const contractId = button?.dataset.contractId;
                if (contractId) {
                    window.contractsListSupport.viewDocument(contractId);
                }
            }
            
            // Download documento
            else if (target.closest('.btn-download-document')) {
                e.preventDefault();
                const button = target.closest('.btn-download-document');
                const filename = button?.dataset.filename;
                if (filename) {
                    window.contractsListSupport.downloadDocument(filename);
                }
            }
        });
        
        // Filtros
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                const companyFilter = document.getElementById('companyFilter');
                const statusFilter = document.getElementById('filterStatus');
                const typeFilter = document.getElementById('filterType');
                const searchInput = document.getElementById('searchContracts');
                
                if (companyFilter) companyFilter.value = 'all';
                if (statusFilter) statusFilter.value = 'all';
                if (typeFilter) typeFilter.value = 'all';
                if (searchInput) searchInput.value = '';
                
                showNotification('Filtros limpos', 'info');
            });
        }
        
        console.log('✅ Event listeners configurados');
    }
    
    // ============================================
    // INICIALIZAÇÃO
    // ============================================
    
    function init() {
        if (STATE.isInitialized) {
            console.log('📋 Sistema já inicializado');
            return;
        }
        
        console.log('🚀 Inicializando sistema de contratos...');
        
        try {
            setupEventListeners();
            STATE.isInitialized = true;
            
            console.log('✅ Sistema de contratos inicializado');
            console.log('✅ API de suporte disponível em: window.contractsListSupport');
            
        } catch (error) {
            console.error('❌ Erro na inicialização:', error);
            showNotification('Erro ao inicializar sistema de contratos', 'error');
        }
    }
    
    // ============================================
    // INICIALIZAÇÃO AUTOMÁTICA
    // ============================================
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM carregado, inicializando sistema...');
            init();
        });
    } else {
        console.log('📄 DOM já carregado, inicializando agora...');
        init();
    }
    
})();
</script>