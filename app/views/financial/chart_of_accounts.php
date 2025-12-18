<?php
// app/views/financial/chart_of_accounts.php

$pageTitle = 'Plano de Contas';
$currentPage = 'chart_of_accounts';
$pageScript = 'chart_of_accounts.js';

require_once __DIR__ . '/../layouts/header.php';

// Organizar por grupos
$grupos = [
    'ativo' => ['nome' => 'Ativo', 'icon' => 'fa-wallet', 'color' => '#4CAF50'],
    'passivo' => ['nome' => 'Passivo', 'icon' => 'fa-file-invoice-dollar', 'color' => '#F44336'],
    'patrimonio' => ['nome' => 'Patrimônio Líquido', 'icon' => 'fa-balance-scale', 'color' => '#2196F3'],
    'receita' => ['nome' => 'Receitas', 'icon' => 'fa-arrow-up', 'color' => '#FF9800'],
    'despesa' => ['nome' => 'Despesas', 'icon' => 'fa-arrow-down', 'color' => '#9C27B0']
];

// Separar contas por grupo
$contasPorGrupo = [];
foreach ($data['accounts'] as $account) {
    $grupo = $account['account_type'];
    if (!isset($contasPorGrupo[$grupo])) {
        $contasPorGrupo[$grupo] = [];
    }
    $contasPorGrupo[$grupo][] = $account;
}
?>

<div class="content-area">
    <!-- Alertas -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-sitemap"></i> Plano de Contas</h1>
            <p>Estrutura contábil completa da sua empresa</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openModal('accountModal')">
                <i class="fas fa-plus-circle"></i> Nova Conta
            </button>
        </div>
    </div>

    <!-- Cards de Resumo por Grupo -->
    <div class="metrics-grid">
        <?php foreach ($grupos as $key => $grupo): 
            $quantidade = isset($contasPorGrupo[$key]) ? count($contasPorGrupo[$key]) : 0;
            $ativas = isset($contasPorGrupo[$key]) ? 
                count(array_filter($contasPorGrupo[$key], function($a) { return $a['is_active'] == 1; })) : 0;
        ?>
        <div class="metric-card" style="border-left-color: <?= $grupo['color'] ?>">
            <div class="metric-icon" style="background: <?= $grupo['color'] ?>20; color: <?= $grupo['color'] ?>">
                <i class="fas <?= $grupo['icon'] ?>"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?= $quantidade ?></div>
                <div class="metric-label"><?= $grupo['nome'] ?></div>
                <div class="metric-subtext"><?= $ativas ?> ativas</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Grupos de Contas -->
    <div class="accounts-groups">
        <?php foreach ($grupos as $key => $grupo): 
            $contas = isset($contasPorGrupo[$key]) ? $contasPorGrupo[$key] : [];
        ?>
        <div class="content-card account-group">
            <div class="card-header group-header" style="border-left-color: <?= $grupo['color'] ?>">
                <div class="group-title">
                    <div class="group-icon" style="background: <?= $grupo['color'] ?>">
                        <i class="fas <?= $grupo['icon'] ?>"></i>
                    </div>
                    <div>
                        <h3><?= $grupo['nome'] ?></h3>
                        <span class="group-count"><?= count($contas) ?> contas</span>
                    </div>
                </div>
                <button class="btn btn-outline" onclick="toggleGroup('<?= $key ?>')">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <div class="card-body group-body" id="group-<?= $key ?>">
                <?php if (!empty($contas)): ?>
                    <div class="accounts-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nome da Conta</th>
                                    <th>Categoria</th>
                                    <th>Cor</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contas as $account): ?>
                                <tr>
                                    <td>
                                        <div class="account-code" style="background: <?= $account['color'] ?? '#f5f5f5' ?>">
                                            <?= htmlspecialchars($account['account_code']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="account-name"><?= htmlspecialchars($account['account_name']) ?></div>
                                        <?php if ($account['description']): ?>
                                            <div class="account-description"><?= htmlspecialchars($account['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($account['category'] ?? 'Geral') ?></td>
                                    <td>
                                        <div class="color-preview" style="background: <?= $account['color'] ?? '#666' ?>"></div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $account['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $account['is_active'] ? 'Ativa' : 'Inativa' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-edit" onclick="editAccount(<?= $account['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon btn-delete" onclick="deleteAccount(<?= $account['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-group">
                        <i class="fas <?= $grupo['icon'] ?>" style="color: <?= $grupo['color'] ?>"></i>
                        <h4>Nenhuma conta cadastrada</h4>
                        <p>Adicione contas ao grupo <?= $grupo['nome'] ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para Nova Conta -->
<div id="accountModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Nova Conta Contábil</h3>
            <button type="button" class="modal-close" onclick="closeModal('accountModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="accountForm" method="POST" action="index.php?page=chart_of_accounts&action=create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_group">Grupo da Conta *</label>
                        <select id="account_group" name="account_group" required>
                            <option value="">Selecione o grupo</option>
                            <option value="ativo">📊 Ativo</option>
                            <option value="passivo">💰 Passivo</option>
                            <option value="patrimonio">⚖️ Patrimônio Líquido</option>
                            <option value="receita">📈 Receitas</option>
                            <option value="despesa">📉 Despesas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="account_code">Código da Conta *</label>
                        <input type="text" id="account_code" name="account_code" required 
                               placeholder="Ex: 1.01.001">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="account_name">Nome da Conta *</label>
                        <input type="text" id="account_name" name="account_name" required 
                               placeholder="Ex: Caixa">
                    </div>
                    <div class="form-group">
                        <label for="category">Categoria</label>
                        <input type="text" id="category" name="category" 
                               placeholder="Ex: Circulante">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="account_color">Cor da Conta</label>
                        <div class="color-selector">
                            <input type="color" id="account_color" name="account_color" value="#4CAF50">
                            <div class="color-presets">
                                <?php 
                                $cores = ['#4CAF50', '#2196F3', '#FF9800', '#F44336', '#9C27B0', '#607D8B', '#FF6B00'];
                                foreach ($cores as $cor): 
                                ?>
                                <div class="color-option" style="background: <?= $cor ?>" 
                                     onclick="document.getElementById('account_color').value = '<?= $cor ?>'"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Descrição detalhada da conta..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('accountModal')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Conta
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>