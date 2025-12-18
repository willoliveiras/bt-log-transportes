<?php
// app/views/suppliers/list.php

$pageTitle = $data['page_title'] ?? 'Fornecedores';
$currentPage = 'suppliers';
$pageScript = 'suppliers.js';

require_once __DIR__ . '/../layouts/header.php';
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
            <h1><i class="fas fa-truck"></i> Fornecedores</h1>
            <p>
                Gerencie os fornecedores da 
                <?php if (isset($data['current_company'])): ?>
                    <strong style="color: <?= $data['current_company']['color'] ?? '#FF6B00' ?>">
                        <?= htmlspecialchars($data['current_company']['name']) ?>
                    </strong>
                <?php else: ?>
                    sua empresa
                <?php endif; ?>
                <span class="text-muted">(<?= $data['total_count'] ?? 0 ?> fornecedores)</span>
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openModal('supplierModal')">
                <i class="fas fa-plus"></i> Novo Fornecedor
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="content-card">
        <div class="card-header">
            <h3>Filtros</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="page" value="suppliers">
                <div class="form-row">
                    <div class="form-group">
                        <div class="search-box">
                            <input type="text" id="searchSupplier" name="search" value="<?= htmlspecialchars($data['filters']['search'] ?? '') ?>" placeholder="Buscar por nome, fantasia ou CPF/CNPJ...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="index.php?page=suppliers" class="btn btn-outline">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Fornecedores -->
    <div class="content-card">
        <div class="card-header">
            <h2>Fornecedores Cadastrados</h2>
            <div class="card-actions">
                <span class="text-muted"><?= count($data['suppliers']) ?> de <?= $data['total_count'] ?? 0 ?> resultados</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($data['suppliers'])): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome/Razão Social</th>
                                <th>Nome Fantasia</th>
                                <th>CPF/CNPJ</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th width="150">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['suppliers'] as $supplier): ?>
                            <tr>
                                <td>
                                    <div class="account-name"><?= htmlspecialchars($supplier['name']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($supplier['fantasy_name'] ?? '-') ?></td>
                                <td>
                                    <div class="account-code"><?= htmlspecialchars($supplier['cpf_cnpj'] ?? '-') ?></div>
                                </td>
                                <td><?= htmlspecialchars($supplier['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($supplier['email'] ?? '-') ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-view" onclick="viewSupplier(<?= $supplier['id'] ?>)" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon btn-edit" onclick="editSupplier(<?= $supplier['id'] ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" onclick="deleteSupplier(<?= $supplier['id'] ?>)" title="Excluir">
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
                <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <h3>Nenhum fornecedor encontrado</h3>
                    <p>
                        <?php if (!empty($data['filters']['search'])): ?>
                            Nenhum resultado para "<?= htmlspecialchars($data['filters']['search']) ?>". 
                            <a href="index.php?page=suppliers">Limpar filtros</a>
                        <?php else: ?>
                            Clique em "Novo Fornecedor" para adicionar o primeiro.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Novo Fornecedor -->
<div id="supplierModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-truck"></i> Novo Fornecedor</h3>
            <button type="button" class="modal-close" onclick="closeModal('supplierModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="supplierForm" method="POST" action="index.php?page=suppliers&action=create">
    <!-- ✅ CAMPOS OCULTOS PARA RETORNO -->
    <?php if (isset($_GET['return_to']) && $_GET['return_to'] === 'accounts_payable'): ?>
    <input type="hidden" name="return_to" value="accounts_payable">
    <input type="hidden" name="return_url" value="<?= htmlspecialchars($_GET['return_url'] ?? 'index.php?page=accounts_payable') ?>">
    <?php endif; ?>
    
    <div class="modal-body">
        <div class="form-section">
            <h4><i class="fas fa-id-card"></i> Dados do Fornecedor</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="required">Nome/Razão Social</label>
                    <input type="text" id="name" name="name" required 
                           placeholder="Nome completo ou razão social">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fantasy_name">Nome Fantasia</label>
                    <input type="text" id="fantasy_name" name="fantasy_name" 
                           placeholder="Nome fantasia (opcional)">
                </div>
                <div class="form-group">
                    <label for="cpf_cnpj">CPF/CNPJ</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj" 
                           placeholder="CPF ou CNPJ">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4><i class="fas fa-address-book"></i> Contato</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" 
                           placeholder="email@exemplo.com">
                </div>
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input type="text" id="phone" name="phone" 
                           placeholder="(00) 00000-0000">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4><i class="fas fa-map-marker-alt"></i> Endereço</h4>
            <div class="form-group">
                <label for="address">Endereço</label>
                <textarea id="address" name="address" rows="2" 
                          placeholder="Rua, número, bairro..."></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
		<?php if (isset($_GET['return_to']) && $_GET['return_to'] === 'accounts_payable'): ?>
		<button type="button" class="btn btn-outline" onclick="returnToAccountsPayable()">
			<i class="fas fa-arrow-left"></i> Voltar para Contas a Pagar
		</button>
		<?php endif; ?>
		
		<button type="button" class="btn btn-secondary" onclick="closeModal('supplierModal')">
			<i class="fas fa-times"></i> Cancelar
		</button>
		<button type="submit" class="btn btn-primary">
			<i class="fas fa-save"></i> Salvar Fornecedor
		</button>
	</div>
</form>
    </div>
</div>

<!-- Modal para Visualizar Fornecedor -->
<div id="viewSupplierModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Detalhes do Fornecedor</h3>
            <button type="button" class="modal-close" onclick="closeModal('viewSupplierModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="supplierDetails">
                <!-- Conteúdo será carregado via JavaScript -->
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Carregando...</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewSupplierModal')">
                <i class="fas fa-times"></i> Fechar
            </button>
            <button type="button" class="btn btn-primary" onclick="editCurrentSupplier()">
                <i class="fas fa-edit"></i> Editar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Editar Fornecedor -->
<div id="editSupplierModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Fornecedor</h3>
            <button type="button" class="modal-close" onclick="closeModal('editSupplierModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editSupplierForm" method="POST">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-id-card"></i> Dados do Fornecedor</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_name">Nome/Razão Social *</label>
                            <input type="text" id="edit_name" name="name" required 
                                   placeholder="Nome completo ou razão social">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_fantasy_name">Nome Fantasia</label>
                            <input type="text" id="edit_fantasy_name" name="fantasy_name" 
                                   placeholder="Nome fantasia (opcional)">
                        </div>
                        <div class="form-group">
                            <label for="edit_cpf_cnpj">CPF/CNPJ</label>
                            <input type="text" id="edit_cpf_cnpj" name="cpf_cnpj" 
                                   placeholder="CPF ou CNPJ">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-address-book"></i> Contato</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_email">E-mail</label>
                            <input type="email" id="edit_email" name="email" 
                                   placeholder="email@exemplo.com">
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Telefone</label>
                            <input type="text" id="edit_phone" name="phone" 
                                   placeholder="(00) 00000-0000">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Endereço</h4>
                    <div class="form-group">
                        <label for="edit_address">Endereço</label>
                        <textarea id="edit_address" name="address" rows="2" 
                                  placeholder="Rua, número, bairro..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSupplierModal')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Atualizar Fornecedor
                </button>
            </div>
        </form>
    </div>
</div>
<script>
// ✅ FUNÇÃO PARA VOLTAR MANUALMENTE (se o usuário quiser)
function returnToAccountsPayable() {
    closeModal('supplierModal');
    setTimeout(() => {
        const returnUrl = '<?= $_GET['return_url'] ?? 'index.php?page=accounts_payable' ?>';
        window.location.href = returnUrl + '&returned_from=suppliers';
    }, 300);
}

// ✅ SUBMIT VIA AJAX PARA EXPERIÊNCIA MAIS FLUIDA
    const supplierForm = document.getElementById('supplierForm');
    
    if (supplierForm) {
        // ✅ REMOVER O EVENT LISTENER AJAX E DEIXAR O COMPORTAMENTO PADRÃO
        console.log('✅ Formulário de fornecedor pronto para envio normal');
        
        // Apenas adicionar loading visual
        supplierForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Mostrar loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitBtn.disabled = true;
            
            // Deixar o formulário ser enviado normalmente
            // O loading será mostrado até a página recarregar
        });
    }
});

// ✅ FUNÇÃO PARA MOSTRAR ALERTAS
function showAlert(message, type = 'info') {
    // Remover alertas existentes
    const existingAlerts = document.querySelectorAll('.supplier-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Criar novo alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `supplier-alert alert-${type}`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease-out;
    `;
    
    const bgColors = {
        'warning': '#FF9800',
        'error': '#F44336',
        'success': '#4CAF50',
        'info': '#2196F3'
    };
    
    alertDiv.style.background = bgColors[type] || '#666';
    alertDiv.innerHTML = `
        <div style="display: flex; align-items: center;">
            <span style="flex: 1;">${message}</span>
            <button style="background: none; border: none; color: white; margin-left: 10px; cursor: pointer;" 
                    onclick="this.parentElement.parentElement.remove()">✕</button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// ✅ FUNÇÃO PARA VOLTAR MANUALMENTE
function returnToAccountsPayable() {
    const returnUrl = '<?= htmlspecialchars($_GET['return_url'] ?? 'index.php?page=accounts_payable') ?>';
    console.log('🔙 Voltando para:', returnUrl);
    window.location.href = returnUrl;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>