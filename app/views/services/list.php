<?php
// app/views/drivers/list.php

// Definir variáveis para o header
$pageTitle = 'Motoristas';
$pageScript = 'drivers.js';

// Carregar modelos
require_once __DIR__ . '/../../models/DriverModel.php';
require_once __DIR__ . '/../../models/CompanyModel.php';

$driverModel = new DriverModel();
$companyModel = new CompanyModel();

// Obter parâmetros de filtro
$companyFilter = $_GET['company'] ?? null;

// Buscar motoristas
$drivers = $driverModel->getAll($companyFilter);
$companies = $companyModel->getForDropdown();
?>

<div class="page-header">
    <div class="header-content">
        <h1>Serviços Adicionais</h1>
        <p>Gerencie serviços extras que podem ser realizados nas viagens</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" id="newServiceBtn">
            <i class="fas fa-plus"></i>
            Novo Serviço
        </button>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Serviços Cadastrados</h2>
        <div class="card-actions">
            <div class="filter-group">
                <select id="companyFilter" class="header-select" onchange="servicesManager.filterByCompany(this.value)">
                    <option value="">Todas as Empresas</option>
                    <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>" 
                            <?php echo ($companyFilter == $company['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-box">
                <input type="text" id="searchServices" placeholder="Buscar serviços...">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>

    <div class="card-body">
    <div class="table-responsive">
        <table class="data-table" id="servicesTable">
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th>Empresa</th>
                    <th>Descrição</th>
                    <th>Preço Base</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-concierge-bell"></i>
                            <h3>Nenhum serviço cadastrado</h3>
                            <p>Comece criando um novo serviço.</p>
                            <!-- CORREÇÃO: Adicionar ID ao botão -->
                            <button class="btn btn-primary" id="emptyStateBtn">
                                Criar Primeiro Serviço
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                    <tr data-service-id="<?php echo $service['id']; ?>">
                        <td>
                            <div class="service-info">
                                <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                            </div>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($service['company_name'] ?? 'N/A'); ?>
                        </td>
                        <td>
                            <div class="service-description">
                                <?php echo htmlspecialchars($service['description'] ?? 'Sem descrição'); ?>
                            </div>
                        </td>
                        <td>
                            <strong class="price-value">
                                R$ <?php echo number_format($service['base_price'], 2, ',', '.'); ?>
                            </strong>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $service['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $service['is_active'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <!-- CORREÇÃO: Remover onclick e confiar no event delegation -->
                                <button class="btn-icon btn-view" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-delete" title="Excluir">
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

<!-- Modal de Visualização de Serviço -->
<div class="modal" id="viewServiceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalViewServiceTitle">Detalhes do Serviço</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="service-details">
                <div class="detail-row">
                    <strong>Nome:</strong>
                    <span id="viewServiceName"></span>
                </div>
                <div class="detail-row">
                    <strong>Empresa:</strong>
                    <span id="viewServiceCompany"></span>
                </div>
                <div class="detail-row">
                    <strong>Descrição:</strong>
                    <span id="viewServiceDescription"></span>
                </div>
                <div class="detail-row">
                    <strong>Preço Base:</strong>
                    <span id="viewServicePrice" class="price-value"></span>
                </div>
                <div class="detail-row">
                    <strong>Status:</strong>
                    <span id="viewServiceStatus"></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="servicesManager.closeViewModal()">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Serviço (Criação/Edição) -->
<div class="modal" id="serviceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalServiceTitle">Novo Serviço</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="serviceForm">
                <input type="hidden" id="serviceId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="service_company_id">Empresa *</label>
                        <select id="service_company_id" name="company_id" required>
                            <option value="">Selecione a empresa</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>">
                                <?php echo htmlspecialchars($company['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="service_name">Nome do Serviço *</label>
                        <input type="text" id="service_name" name="name" required 
                               placeholder="Ex: Carregamento, Descarga, Seguro...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="service_description">Descrição</label>
                        <textarea id="service_description" name="description" rows="3" 
                                  placeholder="Descrição detalhada do serviço..."></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="service_base_price">Preço Base (R$) *</label>
                        <input type="number" id="service_base_price" name="base_price" 
                               step="0.01" min="0" required placeholder="0.00">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="service_is_active" name="is_active" value="1" checked>
                            <span class="checkmark"></span>
                            Serviço ativo
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelServiceButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveServiceButton">
                <span class="btn-text">Salvar Serviço</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<style>
.service-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.service-description {
    font-size: 0.9rem;
    color: var(--color-gray);
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.price-value {
    color: var(--color-success);
    font-weight: 600;
}

.service-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-gray-light);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row strong {
    min-width: 120px;
    color: var(--color-gray-dark);
}

/* Estilos para garantir que os modais funcionem */
.modal {
    z-index: 10000;
}

.modal-content {
    z-index: 10001;
}
</style>

<script>
// Debug helper
console.log('🔍 Services page loaded');
console.log('📋 Available services:', <?php echo json_encode($services ?? []); ?>);
console.log('🏢 Available companies:', <?php echo json_encode($companies ?? []); ?>);

// Verificar se os elementos existem
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Checking modal elements:');
    console.log('• serviceModal:', document.getElementById('serviceModal'));
    console.log('• newServiceBtn:', document.getElementById('newServiceBtn'));
    console.log('• emptyStateBtn:', document.getElementById('emptyStateBtn'));
});
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>