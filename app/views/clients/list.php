<div class="container-fluid clients-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-users me-2"></i>Clientes</h1>
            <p>Gerencie os clientes do sistema BT Log</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" id="newClientBtn">
                <i class="fas fa-plus me-2"></i>Novo Cliente
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-section">
        <h3>Filtros</h3>
        <div class="filter-grid">
            <div class="filter-group">
                <label for="companyFilter">Empresa</label>
                <select class="form-select" id="companyFilter" onchange="filterClients()">
                    <option value="">Todas as Empresas</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>" 
                            <?= ($companyFilter == $company['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="categoryFilter">Categoria</label>
                <select class="form-select" id="categoryFilter" onchange="filterClients()">
                    <option value="">Todas as Categorias</option>
                    <option value="cliente_comum" <?= ($categoryFilter == 'cliente_comum') ? 'selected' : '' ?>>Cliente Comum</option>
                    <option value="empresa_parceira" <?= ($categoryFilter == 'empresa_parceira') ? 'selected' : '' ?>>Empresa Parceira</option>
                    <option value="cliente_empresa_parceira" <?= ($categoryFilter == 'cliente_empresa_parceira') ? 'selected' : '' ?>>Cliente Indicado</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="segmentFilter">Segmento</label>
                <select class="form-select" id="segmentFilter" onchange="filterClients()">
                    <option value="">Todos os Segmentos</option>
                    <option value="industria" <?= ($segmentFilter == 'industria') ? 'selected' : '' ?>>Indústria</option>
                    <option value="varejo" <?= ($segmentFilter == 'varejo') ? 'selected' : '' ?>>Varejo</option>
                    <option value="atacado" <?= ($segmentFilter == 'atacado') ? 'selected' : '' ?>>Atacado</option>
                    <option value="servicos" <?= ($segmentFilter == 'servicos') ? 'selected' : '' ?>>Serviços</option>
                    <option value="agronegocio" <?= ($segmentFilter == 'agronegocio') ? 'selected' : '' ?>>Agronegócio</option>
                    <option value="construcao" <?= ($segmentFilter == 'construcao') ? 'selected' : '' ?>>Construção Civil</option>
                    <option value="outros" <?= ($segmentFilter == 'outros') ? 'selected' : '' ?>>Outros</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="statusFilter">Status</label>
                <select class="form-select" id="statusFilter" onchange="filterClients()">
                    <option value="">Todos</option>
                    <option value="active" <?= ($statusFilter == 'active') ? 'selected' : '' ?>>Ativos</option>
                    <option value="inactive" <?= ($statusFilter == 'inactive') ? 'selected' : '' ?>>Inativos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <?php if ($clientStats): ?>
    <div class="stats-section">
        <h3>Visão Geral</h3>
        <div class="stats-grid-discreet">
            <div class="stat-card-discreet primary">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?= $clientStats['total_clients'] ?></div>
                        <div class="stat-label-discreet">Total Clientes</div>
                        <div class="stat-description-discreet">Cadastrados no sistema</div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-discreet success">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?= $clientStats['active_clients'] ?></div>
                        <div class="stat-label-discreet">Ativos</div>
                        <div class="stat-description-discreet">Clientes ativos</div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-discreet info">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?= $clientStats['common_clients'] ?></div>
                        <div class="stat-label-discreet">Comuns</div>
                        <div class="stat-description-discreet">Clientes diretos</div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-discreet warning">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?= $clientStats['partner_companies'] ?></div>
                        <div class="stat-label-discreet">Parceiras</div>
                        <div class="stat-description-discreet">Empresas parceiras</div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-handshake"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-discreet secondary">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?= $clientStats['referred_clients'] ?></div>
                        <div class="stat-label-discreet">Indicados</div>
                        <div class="stat-description-discreet">Por parceiros</div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-user-friends"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabela -->
    <div class="content-card-clean">
        <div class="card-header-clean">
            <h2>Lista de Clientes</h2>
            <div class="card-actions-clean">
                <div class="search-box-clean">
                    <input type="text" id="searchClients" placeholder="Buscar clientes...">
                    <i class="fas fa-search"></i>
                </div>
                <button class="btn-clean btn-clean-secondary" onclick="location.reload()">
                    <i class="fas fa-redo"></i>
                    Atualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table-clean" id="clientsTable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>CPF/CNPJ</th>
                            <th>Empresa</th>
                            <th>Status</th>
                            <th>Viagens</th>
                            <th>Faturamento</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="empty-state-clean">
                                        <i class="fas fa-users"></i>
                                        <h3>Nenhum cliente encontrado</h3>
                                        <p>Comece cadastrando o primeiro cliente no sistema.</p>
                                        <button class="btn-clean btn-clean-primary" id="newClientBtn2">
                                            <i class="fas fa-plus me-2"></i>Cadastrar Primeiro Cliente
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <tr data-client-id="<?= $client['id'] ?>">
                                    <td>
                                        <div class="client-info-clean">
                                            <div class="client-avatar-clean">
                                                <?= strtoupper(substr($client['name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <strong class="client-name"><?= htmlspecialchars($client['fantasy_name'] ?: $client['name']) ?></strong>
                                                <?php if ($client['email']): ?>
                                                    <div class="client-details-clean"><?= $client['email'] ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge-clean primary">
                                            <?= $client['type'] === 'pessoa_fisica' ? 'PF' : 'PJ' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $categoryClass = [
                                            'cliente_comum' => 'primary',
                                            'empresa_parceira' => 'success', 
                                            'cliente_empresa_parceira' => 'info'
                                        ][$client['client_category']] ?? 'secondary';
                                        ?>
                                        <span class="category-badge-clean <?= $categoryClass ?>">
                                            <?= $client['client_category'] === 'cliente_comum' ? 'Comum' : 
                                                 ($client['client_category'] === 'empresa_parceira' ? 'Parceira' : 'Indicado') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?= $client['cpf_cnpj'] ?: 'N/I' ?></code>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="color-indicator me-2" style="background-color: <?= $client['company_color'] ?>"></div>
                                            <?= htmlspecialchars($client['company_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge-clean <?= $client['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $client['is_active'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $client['total_trips'] ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-success">R$ <?= number_format($client['total_revenue'], 2, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <div class="action-buttons-clean">
                                            <button class="btn-icon-clean btn-edit-clean" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon-clean btn-delete-clean" title="Excluir">
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
    </div>
</div>

<!-- Modal de Cliente COMPLETO -->
<div class="modal" id="clientModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="clientModalLabel">Novo Cliente</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="clientForm">
                <input type="hidden" id="clientId" name="id">
                
                <div class="form-section">
                    <h4>Tipo e Categoria</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Tipo de Pessoa *</label>
                            <select id="type" name="type" required>
                                <option value="">Selecione o tipo</option>
                                <option value="pessoa_fisica">Pessoa Física</option>
                                <option value="pessoa_juridica">Pessoa Jurídica</option>
                            </select>
                            <div class="form-text">Defina se é Pessoa Física ou Jurídica</div>
                        </div>
                        <div class="form-group">
                            <label for="client_category">Categoria do Cliente *</label>
                            <select id="client_category" name="client_category" required>
                                <option value="">Selecione a categoria</option>
                                <option value="cliente_comum">Cliente Comum</option>
                                <option value="empresa_parceira">Empresa Parceira</option>
                                <option value="cliente_empresa_parceira">Cliente de Empresa Parceira</option>
                            </select>
                            <div class="form-text">
                                <span id="categoryHelp">Cliente comum: cliente direto da BT Log</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campos para Cliente de Empresa Parceira -->
                <div class="form-section" id="referredClientFields" style="display: none;">
                    <h4>Empresa Parceira Referência</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-handshake me-2"></i>
                        Este cliente foi indicado por uma empresa parceira.
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="partner_company_id">Empresa Parceira *</label>
                            <select id="partner_company_id" name="partner_company_id">
                                <option value="">Selecione uma empresa parceira</option>
                                <?php foreach ($partnerCompanies as $partner): ?>
                                    <option value="<?= $partner['id'] ?>">
                                        <?= htmlspecialchars($partner['fantasy_name'] ?: $partner['name']) ?> - <?= $partner['cpf_cnpj'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione a empresa parceira que indicou este cliente</div>
                        </div>
                    </div>
                </div>

                <!-- Campos Comuns -->
                <div class="form-section" id="commonFields">
                    <h4>Informações Básicas</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_id">Empresa BT Log *</label>
                            <select id="company_id" name="company_id" required>
                                <option value="">Selecione a empresa</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Empresa do grupo BT Log responsável</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome Completo *</label>
                            <input type="text" id="name" name="name" required placeholder="Nome completo da pessoa">
                        </div>
                        <div class="form-group" id="fantasyNameGroup" style="display: none;">
                            <label for="fantasy_name">Nome Fantasia</label>
                            <input type="text" id="fantasy_name" name="fantasy_name" placeholder="Nome comercial (opcional)">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf_cnpj">CPF/CNPJ</label>
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj" placeholder="Digite o CPF ou CNPJ">
                        </div>
                        <div class="form-group">
                            <label for="registration_date">Data de Cadastro</label>
                            <input type="date" id="registration_date" name="registration_date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="client_segment">Segmento</label>
                            <select id="client_segment" name="client_segment">
                                <option value="outros">Outros</option>
                                <option value="industria">Indústria</option>
                                <option value="varejo">Varejo</option>
                                <option value="atacado">Atacado</option>
                                <option value="servicos">Serviços</option>
                                <option value="agronegocio">Agronegócio</option>
                                <option value="construcao">Construção Civil</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="client_size">Porte</label>
                            <select id="client_size" name="client_size">
                                <option value="medio">Médio</option>
                                <option value="pequeno">Pequeno</option>
                                <option value="grande">Grande</option>
                                <option value="corporativo">Corporativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Contato</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="email@empresa.com.br">
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefone</label>
                            <input type="text" id="phone" name="phone" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Endereço</label>
                        <textarea id="address" name="address" rows="2" placeholder="Endereço completo"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_name">Nome do Contato</label>
                            <input type="text" id="contact_name" name="contact_name" placeholder="Nome da pessoa de contato">
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Telefone do Contato</label>
                            <input type="text" id="contact_phone" name="contact_phone" placeholder="(11) 98888-7777">
                        </div>
                        <div class="form-group">
                            <label for="contact_email">Email do Contato</label>
                            <input type="email" id="contact_email" name="contact_email" placeholder="contato@empresa.com.br">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Informações Financeiras</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="credit_limit">Limite de Crédito (R$)</label>
                            <input type="text" id="payment_terms" name="payment_terms" placeholder="Ex: 30/60/90 dias">
							<div class="form-text">Digite 0 para sem limite</div>
                        </div>
                        <div class="form-group">
                            <label for="credit_limit">Limite de Crédito (R$)</label>
                            <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0" placeholder="0.00" oninput="validateCreditLimit(this)">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Observações</h4>
                    <div class="form-group">
                        <label for="notes">Observações</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Observações adicionais sobre o cliente..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <span class="checkmark"></span>
                            Cliente ativo
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelClientButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveClientButton">
                <span class="btn-text">Salvar Cliente</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<script>
// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Botão alternativo para novo cliente - CORRIGIDO
    const newClientBtn2 = document.getElementById('newClientBtn2');
    if (newClientBtn2) {
        newClientBtn2.addEventListener('click', function() {
            if (window.clientsManager) {
                window.clientsManager.openModal();
            } else {
                console.error('ClientsManager não disponível');
                // Fallback: abrir modal manualmente
                const modal = document.getElementById('clientModal');
                if (modal) {
                    modal.style.display = 'block';
                    setTimeout(() => modal.classList.add('show'), 10);
                }
            }
        });
    }

    // Busca em tempo real
    const searchInput = document.getElementById('searchClients');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#clientsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Validação dinâmica dos campos de nome
    const typeSelect = document.getElementById('type');
    const nameField = document.getElementById('name');
    const fantasyNameField = document.getElementById('fantasy_name');
    const fantasyNameGroup = document.getElementById('fantasyNameGroup');
    const nameLabel = nameField ? nameField.previousElementSibling : null;
    
    if (typeSelect && nameLabel) {
        typeSelect.addEventListener('change', function() {
            updateNameFields(this.value);
        });
        
        // Inicializar campos
        updateNameFields(typeSelect.value);
    }
    
    function updateNameFields(type) {
        if (type === 'pessoa_fisica') {
            if (nameLabel) nameLabel.textContent = 'Nome Completo *';
            if (nameField) nameField.placeholder = 'Nome completo da pessoa';
            if (fantasyNameGroup) {
                fantasyNameGroup.style.display = 'none';
            }
        } else {
            if (nameLabel) nameLabel.textContent = 'Razão Social *';
            if (nameField) nameField.placeholder = 'Razão social completa';
            if (fantasyNameGroup) {
                fantasyNameGroup.style.display = 'block';
            }
        }
    }

    // Validação em tempo real do tipo e categoria
    const categorySelect = document.getElementById('client_category');
    
    if (typeSelect && categorySelect) {
        typeSelect.addEventListener('change', function() {
            const type = this.value;
            const currentCategory = categorySelect.value;
            
            // Pessoa física não pode ser empresa parceira
            if (type === 'pessoa_fisica') {
                const partnerOption = categorySelect.querySelector('option[value="empresa_parceira"]');
                if (partnerOption) {
                    partnerOption.disabled = true;
                    if (currentCategory === 'empresa_parceira') {
                        categorySelect.value = 'cliente_comum';
                        updateCategoryFields('cliente_comum');
                    }
                }
            } else {
                // Pessoa jurídica - habilitar todas as opções
                const options = categorySelect.querySelectorAll('option');
                options.forEach(option => {
                    option.disabled = false;
                });
            }
            
            updateCategoryFields(categorySelect.value);
        });
        
        categorySelect.addEventListener('change', function() {
            updateCategoryFields(this.value);
        });
    }
    
    function updateCategoryFields(category) {
        const referredFields = document.getElementById('referredClientFields');
        
        if (referredFields) {
            referredFields.style.display = category === 'cliente_empresa_parceira' ? 'block' : 'none';
        }
        
        // Atualizar texto de ajuda
        const helpText = document.getElementById('categoryHelp');
        if (helpText) {
            switch(category) {
                case 'cliente_comum':
                    helpText.textContent = 'Cliente comum: cliente direto da BT Log';
                    break;
                case 'empresa_parceira':
                    helpText.textContent = 'Empresa parceira: pode indicar clientes para a BT Log';
                    break;
                case 'cliente_empresa_parceira':
                    helpText.textContent = 'Cliente indicado por uma empresa parceira';
                    break;
                default:
                    helpText.textContent = 'Cliente comum: cliente direto da BT Log';
            }
        }
    }
	
	// Validação em tempo real do limite de crédito
	function validateCreditLimit(input) {
		if (input.value < 0) {
			input.value = 0;
		}
	}

	// Também adicione esta validação no evento de change do campo
	const creditLimitField = document.getElementById('credit_limit');
	if (creditLimitField) {
		creditLimitField.addEventListener('change', function() {
			if (this.value < 0) {
				this.value = 0;
			}
		});
		
		creditLimitField.addEventListener('blur', function() {
			if (this.value === '' || this.value < 0) {
				this.value = 0;
			}
		});
	}
    
    // Formatação automática de CPF/CNPJ
    const cpfCnpjField = document.getElementById('cpf_cnpj');
    if (cpfCnpjField) {
        cpfCnpjField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            const type = typeSelect ? typeSelect.value : 'pessoa_fisica';
            
            if (type === 'pessoa_fisica') {
                if (value.length > 11) value = value.substring(0, 11);
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                if (value.length > 14) value = value.substring(0, 14);
                value = value.replace(/(\d{2})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1/$2');
                value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }
            
            e.target.value = value;
        });
    }
});

function filterClients() {
    const company = document.getElementById('companyFilter').value;
    const category = document.getElementById('categoryFilter').value;
    const segment = document.getElementById('segmentFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (company) params.append('company', company);
    if (category) params.append('category', category);
    if (segment) params.append('segment', segment);
    if (status) params.append('status', status);
    
    window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?' + params.toString();
}
</script>