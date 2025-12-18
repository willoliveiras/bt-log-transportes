<?php
// app/views/contracts/expiring.php - CONTRATOS PRÓXIMOS DO VENCIMENTO
$pageTitle = 'Contratos à Vencer';
?>

<div class="contracts-dashboard">
    <!-- Cabeçalho da Página -->
    <div class="page-header">
        <div class="header-content">
            <h1>Contratos à Vencer</h1>
            <p>Contratos com vencimento nos próximos 30 dias</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="window.location.href='index.php?page=contracts&action=list'">
                <i class="fas fa-arrow-left"></i>
                Voltar para Contratos
            </button>
        </div>
    </div>

    <!-- Alertas Importantes -->
    <?php if (count($contracts) > 0): ?>
    <div class="alerts-section">
        <div class="alert alert-warning">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="alert-content">
                <h4>Atenção: <?php echo count($contracts); ?> contrato(s) próximo(s) do vencimento</h4>
                <p>Recomendamos a revisão e renovação destes contratos para evitar interrupções.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard de Vencimentos -->
    <div class="stats-section">
        <h3>Resumo de Vencimentos</h3>
        <div class="stats-grid-discreet">
            <!-- Total à Vencer -->
            <div class="stat-card-discreet warning">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['expiring_soon']; ?></div>
                        <div class="stat-label-discreet">Contratos à Vencer</div>
                        <div class="stat-description-discreet">
                            Próximos 30 dias
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-clock"></i>
                                Necessário atenção
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Valor Total à Vencer -->
            <div class="stat-card-discreet info">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet">R$ <?php 
                            $totalValue = 0;
                            foreach ($contracts as $contract) {
                                $totalValue += $contract['value'];
                            }
                            echo number_format($totalValue, 2, ',', '.');
                        ?></div>
                        <div class="stat-label-discreet">Valor em Risco</div>
                        <div class="stat-description-discreet">
                            Total dos contratos à vencer
                            <?php if ($totalValue > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-money-bill-wave"></i>
                                Valor significativo
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Vencimento Mais Próximo -->
            <div class="stat-card-discreet primary">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <?php if (!empty($contracts)): 
                            $nearest = min(array_column($contracts, 'end_date'));
                            $days = floor((strtotime($nearest) - time()) / (60 * 60 * 24));
                        ?>
                        <div class="stat-value-discreet"><?php echo $days; ?> dias</div>
                        <div class="stat-label-discreet">Próximo Vencimento</div>
                        <div class="stat-description-discreet">
                            <?php echo date('d/m/Y', strtotime($nearest)); ?>
                            <div class="stat-trend-discreet <?php echo $days <= 7 ? 'trend-down-discreet' : 'trend-up-discreet'; ?>">
                                <i class="fas fa-<?php echo $days <= 7 ? 'exclamation-triangle' : 'calendar-alt'; ?>"></i>
                                <?php echo $days <= 7 ? 'Crítico' : 'Atenção'; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="stat-value-discreet">-</div>
                        <div class="stat-label-discreet">Sem vencimentos</div>
                        <div class="stat-description-discreet">
                            Nenhum contrato próximo do vencimento
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Tudo em dia
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Contratos à Vencer -->
    <div class="contracts-table-container">
        <div class="contracts-table-header">
            <h2><i class="fas fa-list"></i> Lista de Contratos à Vencer</h2>
            <div class="table-actions">
                <div class="search-box">
                    <input type="text" id="searchExpiring" placeholder="Buscar contratos...">
                    <i class="fas fa-search"></i>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="exportExpiringContracts()">
                    <i class="fas fa-download"></i> Exportar Lista
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="contracts-table" id="expiringTable">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Partes</th>
                        <th>Vencimento</th>
                        <th>Dias Restantes</th>
                        <th>Valor</th>
                        <th>Documento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="expiringTableBody">
                    <?php if (empty($contracts)): ?>
						<tr>
							<td colspan="7">
								<div class="empty-state-modern" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; text-align: center;">
									<div class="empty-icon-modern">
										<i class="fas fa-check-circle"></i>
									</div>
									<h3 style="text-align: center; width: 100%;">Nenhum Contrato à Vencer</h3>
									<p style="text-align: center; width: 100%; max-width: 400px; margin: 0 auto 1.5rem auto;">Todos os contratos estão em dia!</p>
									<button class="btn btn-primary" onclick="window.location.href='index.php?page=contracts&action=list'" style="margin: 0 auto;">
										<i class="fas fa-list"></i>
										Ver Todos os Contratos
									</button>
								</div>
							</td>
						</tr>
					<?php else: ?>
                        <?php foreach ($contracts as $contract): ?>
                        <?php 
                            $endDate = new DateTime($contract['end_date']);
                            $today = new DateTime();
                            $interval = $today->diff($endDate);
                            $daysRemaining = $interval->days;
                            
                            // Determinar nível de urgência
                            $urgencyClass = '';
                            if ($daysRemaining <= 7) {
                                $urgencyClass = 'critical';
                            } elseif ($daysRemaining <= 15) {
                                $urgencyClass = 'high';
                            } else {
                                $urgencyClass = 'medium';
                            }
                        ?>
                        <tr data-contract-id="<?php echo $contract['id']; ?>" class="urgency-<?php echo $urgencyClass; ?>">
                            
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
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Coluna Vencimento -->
                            <td>
                                <div class="expiration-card-modern">
                                    <div class="expiration-date">
                                        <i class="fas fa-calendar-times"></i>
                                        <?php echo date('d/m/Y', strtotime($contract['end_date'])); ?>
                                    </div>
                                    <div class="expiration-days">
                                        <span class="days-badge days-<?php echo $urgencyClass; ?>">
                                            <?php echo $daysRemaining; ?> dias
                                        </span>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Dias Restantes -->
                            <td>
                                <div class="countdown-modern">
                                    <div class="countdown-bar">
                                        <div class="countdown-fill fill-<?php echo $urgencyClass; ?>" 
                                             style="width: <?php echo min(100, (30 - $daysRemaining) / 30 * 100); ?>%">
                                        </div>
                                    </div>
                                    <div class="countdown-text">
                                        <span class="countdown-days"><?php echo $daysRemaining; ?> dias</span>
                                        <span class="countdown-status status-<?php echo $urgencyClass; ?>">
                                            <?php 
                                                if ($daysRemaining <= 7) echo 'Crítico';
                                                elseif ($daysRemaining <= 15) echo 'Urgente';
                                                else echo 'Atenção';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Valor -->
                            <td>
                                <div class="value-card-modern">
                                    <div class="value-amount-modern">
                                        R$ <?php echo number_format($contract['value'], 2, ',', '.'); ?>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Coluna Documento -->
                            <td>
                                <?php if ($contract['contract_file']): ?>
                                    <button class="btn-view-document-sm" 
                                            onclick="window.contractsManager.viewDocument(<?php echo $contract['id']; ?>)"
                                            title="Visualizar PDF">
                                        <i class="fas fa-file-pdf"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <span class="no-document">Sem doc.</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Coluna Ações -->
                            <td>
                                <div class="actions-toolbar-modern">
                                    <button class="action-btn-modern btn-renew-modern" 
                                            onclick="window.contractsManager.renewContract(<?php echo $contract['id']; ?>)"
                                            title="Renovar Contrato">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button class="action-btn-modern btn-edit-modern" 
                                            onclick="window.contractsManager.editContract(<?php echo $contract['id']; ?>)"
                                            title="Editar Contrato">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn-modern btn-view-modern" 
                                            onclick="window.contractsManager.viewContract(<?php echo $contract['id']; ?>)"
                                            title="Visualizar Contrato">
                                        <i class="fas fa-eye"></i>
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

<!-- No final do body -->
<script src="/bt-log-transportes/public/assets/js/expiring.js"></script>
<link rel="stylesheet" href="/bt-log-transportes/public/assets/css/expiring.css">
<link rel="stylesheet" href="/bt-log-transportes/public/assets/css/contracts_common.css">


<script>
// Funções específicas para página de vencimentos
function exportExpiringContracts() {
    // Implementar exportação
    alert('Exportação de contratos à vencer será implementada em breve!');
}

// Inicializar filtro de busca
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchExpiring');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#expiringTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>