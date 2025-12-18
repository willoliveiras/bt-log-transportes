<?php
// app/views/financial/accounts_receivable.php

// Definir variáveis para o header
$pageTitle = $data['page_title'] ?? 'Contas a Receber';
$currentPage = 'accounts_receivable';
$pageScript = 'accounts_receivable.js';

// Usar o header existente
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="content-area">
    <!-- Alertas -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="header-content">
            <h1>Contas a Receber</h1>
            <p>Gerencie as contas a receber da sua empresa</p>
        </div>
        <div class="header-actions">
            <select id="periodSelect" class="header-select">
                <option value="weekly" <?= $data['period'] === 'weekly' ? 'selected' : '' ?>>Semanal</option>
                <option value="monthly" <?= $data['period'] === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                <option value="quarterly" <?= $data['period'] === 'quarterly' ? 'selected' : '' ?>>Trimestral</option>
                <option value="yearly" <?= $data['period'] === 'yearly' ? 'selected' : '' ?>>Anual</option>
            </select>
            <button class="btn btn-primary" onclick="openModal('receivableModal')">
                <i class="fas fa-plus"></i> Nova Conta
            </button>
        </div>
    </div>

    <!-- Resumo Financeiro -->
    <div class="financial-summary">
        <div class="financial-summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total a Receber:</span>
                <span class="summary-value">R$ <?= number_format($data['summary']['total_amount'] ?? 0, 2, ',', '.') ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Pendentes:</span>
                <span class="summary-value" style="color: var(--color-warning);">
                    R$ <?= number_format($data['summary']['pending_amount'] ?? 0, 2, ',', '.') ?>
                </span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Atrasadas:</span>
                <span class="summary-value" style="color: var(--color-error);">
                    R$ <?= number_format($data['summary']['overdue_amount'] ?? 0, 2, ',', '.') ?>
                </span>
            </div>
            <div class="summary-item total">
                <span class="summary-label">Recebido:</span>
                <span class="summary-value" style="color: var(--color-success);">
                    R$ <?= number_format(($data['summary']['received_amount'] ?? 0), 2, ',', '.') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Lista de Contas -->
    <div class="content-card">
        <div class="card-header">
            <h2>Contas a Receber</h2>
            <div class="card-actions">
                <div class="search-box">
                    <input type="text" id="searchReceivable" placeholder="Buscar contas...">
                    <i class="fas fa-search"></i>
                </div>
                <select id="statusFilter" class="header-select">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendente</option>
                    <option value="recebido">Recebido</option>
                    <option value="atrasado">Atrasado</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Cliente</th>
                            <th>Conta</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Recorrente</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['accounts'])): ?>
                            <?php foreach ($data['accounts'] as $account): ?>
                            <tr>
                                <td><?= htmlspecialchars($account['description']) ?></td>
                                <td><?= htmlspecialchars($account['client_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?></td>
                                <td>R$ <?= number_format($account['amount'], 2, ',', '.') ?></td>
                                <td><?= date('d/m/Y', strtotime($account['due_date'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $account['status'] ?>">
                                        <?= ucfirst($account['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $account['is_recurring'] ? 
                                        '<i class="fas fa-sync-alt" title="Recorrente"></i>' : 
                                        '<i class="fas fa-times" style="color: #ccc;"></i>' ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($account['status'] === 'pendente'): ?>
                                            <button class="btn-icon btn-view" 
                                                    onclick="markAsReceived(<?= $account['id'] ?>)"
                                                    title="Marcar como Recebido">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-icon btn-edit" 
                                                onclick="editReceivable(<?= $account['id'] ?>)"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" 
                                                onclick="deleteReceivable(<?= $account['id'] ?>)"
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h3>Nenhuma conta a receber encontrada</h3>
                                        <p>Clique em "Nova Conta" para adicionar a primeira.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nova Conta -->
<div id="receivableModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nova Conta a Receber</h3>
            <button type="button" class="modal-close" onclick="closeModal('receivableModal')">&times;</button>
        </div>
        <form id="receivableForm" method="POST" action="index.php?page=accounts_receivable&action=create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Descrição *</label>
                        <input type="text" id="description" name="description" required>
                    </div>
                    <div class="form-row">
						<div class="form-group">
							<label for="client_type">Tipo de Recebedor *</label>
							<select id="client_type" name="client_type" required onchange="toggleClientFields()">
								<option value="client">Cliente Cadastrado</option>
								<option value="avulso">Nome Avulso</option>
							</select>
						</div>
					</div>

					<div class="form-row" id="client_field">
						<div class="form-group">
							<label for="client_id">Cliente</label>
							<select id="client_id" name="client_id">
								<option value="">Selecione um cliente</option>
								<?php foreach ($data['clients'] as $client): ?>
									<option value="<?= $client['id'] ?>">
										<?= htmlspecialchars($client['name']) ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="form-row" id="avulso_field" style="display: none;">
						<div class="form-group">
							<label for="avulso_name">Nome do Recebedor *</label>
							<input type="text" id="avulso_name" name="avulso_name" 
								   placeholder="Digite o nome do recebedor">
						</div>
					</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="chart_account_id">Conta Contábil *</label>
                        <select id="chart_account_id" name="chart_account_id" required>
                            <option value="">Selecione uma conta</option>
                            <?php foreach ($data['chart_accounts'] as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Valor *</label>
                        <div class="currency-input">
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Data de Vencimento *</label>
                        <input type="date" id="due_date" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_recurring" name="is_recurring" value="1">
                            <span class="checkmark"></span>
                            Conta Recorrente
                        </label>
                    </div>
                </div>
                
                <div class="form-row" id="recurrence_fields" style="display: none;">
                    <div class="form-group">
                        <label for="recurrence_frequency">Frequência</label>
                        <select id="recurrence_frequency" name="recurrence_frequency">
                            <option value="mensal">Mensal</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Observações</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('receivableModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Conta</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>