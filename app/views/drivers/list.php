<?php
// app/views/drivers/list.php - VERSÃO COMPLETAMENTE CORRIGIDA

// Definir variáveis para o header
$pageTitle = 'Motoristas';
$pageScript = 'drivers.js';

// Carregar modelos
require_once __DIR__ . '/../../models/DriverModel.php';
require_once __DIR__ . '/../../models/CompanyModel.php';

$driverModel = new DriverModel();
$companyModel = new CompanyModel();

// Obter parâmetros de filtro
$companyFilter = isset($_GET['company']) ? $_GET['company'] : null;

// ✅ CORREÇÃO: Buscar motoristas usando o model
$drivers = $driverModel->getAll($companyFilter);
$companies = $companyModel->getForDropdown();

// ✅ DEBUG: Log para verificar dados
error_log("🎯 [LIST VIEW] Motoristas carregados: " . count($drivers));
foreach ($drivers as $driver) {
    error_log("👤 [LIST VIEW] Motorista: ID " . $driver['id'] . " - " . $driver['display_name']);
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <div class="header-content">
        <h1>Motoristas</h1>
        <p>Gerencie os motoristas do sistema BT Log</p>
        <p><small><strong>Total encontrado: <?php echo count($drivers); ?> motorista(s)</strong></small></p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" id="newDriverBtn">
            <i class="fas fa-user-plus"></i>
            Novo Motorista
        </button>
        <button class="btn btn-secondary" onclick="location.reload()">
            <i class="fas fa-redo"></i>
            Recarregar
        </button>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Lista de Motoristas</h2>
        <div class="card-actions">
            <div class="filter-group">
                <select id="companyFilter" class="header-select" onchange="filterByCompany(this.value)">
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
                <input type="text" id="searchDrivers" placeholder="Buscar motoristas...">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($drivers)): ?>
            <div class="empty-state">
                <i class="fas fa-user-plus"></i>
                <h3>Nenhum motorista cadastrado</h3>
                <p>Comece cadastrando o primeiro motorista no sistema.</p>
                <button class="btn btn-primary" id="newDriverBtnEmpty">
                    Cadastrar Motorista
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="driversTable">
                    <thead>
                        <tr>
                            <th>Motorista</th>
                            <th>Tipo</th>
                            <th>CNH</th>
                            <th>Categoria</th>
                            <th>Validade CNH</th>
                            <th>Comissão</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drivers as $driver): ?>
                        <?php 
                        // ✅ CORREÇÃO: Calcular dados do motorista
                        $age = $driverModel->calculateAge($driver['birth_date']);
                        $cnhStatus = $driverModel->isCNHExpired($driver['cnh_expiration']);
                        $daysToExpire = $driverModel->daysUntilCNHExpiration($driver['cnh_expiration']);
                        
                        // ✅ CORREÇÃO: Determinar nome de exibição
                        $displayName = isset($driver['display_name']) ? $driver['display_name'] : 
                                      (isset($driver['name']) ? $driver['name'] : 'Motorista');
                        ?>
                        <tr data-driver-id="<?php echo $driver['id']; ?>">
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar" style="background-color: <?php echo isset($driver['company_color']) ? $driver['company_color'] : '#FF6B00'; ?>">
                                        <?php echo strtoupper(substr($displayName, 0, 2)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($displayName); ?></strong>
                                        <?php if ($age): ?>
                                        <div class="employee-position"><?php echo $age; ?> anos</div>
                                        <?php endif; ?>
                                        <?php if (isset($driver['position']) && !empty($driver['position'])): ?>
                                        <div class="employee-position"><?php echo htmlspecialchars($driver['position']); ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($driver['company_name'])): ?>
                                        <div class="employee-company" style="color: #666; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($driver['company_name']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (isset($driver['driver_type']) && $driver['driver_type'] === 'employee'): ?>
                                    <span class="status-badge active">Funcionário</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Avulso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars(isset($driver['cnh_number']) ? $driver['cnh_number'] : 'N/A'); ?></strong>
                            </td>
                            <td>
                                <span class="status-badge driver">
                                    <?php echo htmlspecialchars(isset($driver['cnh_category']) ? $driver['cnh_category'] : 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($driver['cnh_expiration']) && !empty($driver['cnh_expiration'])): ?>
                                    <?php
                                    $expirationClass = 'text-success';
                                    if ($cnhStatus) {
                                        $expirationClass = 'text-error';
                                    } elseif ($daysToExpire <= 30) {
                                        $expirationClass = 'text-warning';
                                    }
                                    ?>
                                    <div class="<?php echo $expirationClass; ?>">
                                        <?php echo date('d/m/Y', strtotime($driver['cnh_expiration'])); ?>
                                        <?php if ($cnhStatus): ?>
                                            <br><small class="text-error">(Expirada)</small>
                                        <?php elseif ($daysToExpire <= 30): ?>
                                            <br><small class="text-warning">(Expira em <?php echo $daysToExpire; ?> dias)</small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray">Não informada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($driver['custom_commission_rate']) && !empty($driver['custom_commission_rate'])): ?>
                                    <span class="salary-value"><?php echo number_format($driver['custom_commission_rate'], 2); ?>%</span>
                                <?php else: ?>
                                    <span class="text-gray">Padrão</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $isActive = isset($driver['is_active']) ? $driver['is_active'] : true;
                                $statusClass = $isActive ? 'active' : 'inactive';
                                $statusText = $isActive ? 'Ativo' : 'Inativo';
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon btn-edit" 
                                            onclick="window.driversManager.editDriver(<?php echo $driver['id']; ?>)"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon btn-view"
                                            onclick="window.driversManager.viewDriver(<?php echo $driver['id']; ?>)"
                                            title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon btn-delete"
                                            onclick="window.driversManager.deleteDriver(<?php echo $driver['id']; ?>, '<?php echo htmlspecialchars($displayName); ?>')"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Motorista -->
<div class="modal" id="driverModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modalDriverTitle">Novo Motorista</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="driverForm">
                <input type="hidden" id="driverId" name="id">
                <input type="hidden" id="company_id" name="company_id" value="1">
                <input type="hidden" id="driver_type_field" name="driver_type" value="external">
                
                <!-- Opção para motorista funcionário -->
                <div class="form-section">
                    <h4>Vincular a Funcionário</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is_employee_driver" name="is_employee_driver" value="1">
                                <span class="checkmark"></span>
                                Este motorista é um funcionário da empresa
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Seção para selecionar funcionário -->
                <div id="employeeSelectionSection" class="form-section" style="display: none;">
                    <h4>Selecionar Funcionário</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employee_id">Funcionário *</label>
                            <select id="employee_id" name="employee_id">
                                <option value="">Selecione um funcionário</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seção de informações pessoais -->
                <div class="form-section">
                    <h4>Informações Pessoais</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome Completo *</label>
                            <input type="text" id="name" name="name" required placeholder="Nome completo do motorista">
                        </div>
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="rg">RG</label>
                            <input type="text" id="rg" name="rg" placeholder="Número do RG">
                        </div>
                        <div class="form-group">
                            <label for="birth_date">Data de Nascimento</label>
                            <input type="date" id="birth_date" name="birth_date">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Telefone *</label>
                            <input type="text" id="phone" name="phone" required placeholder="(00) 00000-0000">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="email@exemplo.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="address">Endereço</label>
                            <textarea id="address" name="address" placeholder="Endereço completo" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Seção de documentação da CNH -->
                <div class="form-section">
                    <h4>Documentação da CNH</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cnh_number">Número da CNH *</label>
                            <input type="text" id="cnh_number" name="cnh_number" required placeholder="00000000000">
                        </div>
                        
                        <div class="form-group">
                            <label for="cnh_category">Categoria *</label>
                            <select id="cnh_category" name="cnh_category" required>
                                <option value="">Selecione...</option>
                                <option value="A">A - Motocicleta</option>
                                <option value="B">B - Automóvel</option>
                                <option value="C">C - Caminhão</option>
                                <option value="D">D - Ônibus</option>
                                <option value="E">E - Reboque</option>
                                <option value="AB">AB</option>
                                <option value="AC">AC</option>
                                <option value="AD">AD</option>
                                <option value="AE">AE</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cnh_expiration">Data de Validade *</label>
                            <input type="date" id="cnh_expiration" name="cnh_expiration" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Configurações de Comissão</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="custom_commission_rate">Comissão Personalizada (%)</label>
                            <input type="number" id="custom_commission_rate" name="custom_commission_rate" 
                                   step="0.01" min="0" max="100" placeholder="0,00">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Status</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <span class="checkmark"></span>
                                Motorista ativo
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelDriverButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveDriverButton">
                <span class="btn-text">Salvar Motorista</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<style>
.employee-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--color-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.employee-position {
    font-size: 0.8rem;
    color: var(--color-gray);
    margin-top: 0.25rem;
}

.employee-company {
    font-size: 0.75rem;
    color: var(--color-gray);
    margin-top: 0.1rem;
}

.status-badge.driver {
    background: #E3F2FD;
    color: #1976D2;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.text-error { color: var(--color-error); }
.text-warning { color: var(--color-warning); }
.text-success { color: var(--color-success); }
.text-gray { color: var(--color-gray); }

.salary-value {
    font-weight: 600;
    color: var(--color-success);
}
</style>

<script>
// ✅ CORREÇÃO: Função simples para filtro
function filterByCompany(companyId) {
    const url = new URL(window.location);
    if (companyId) {
        url.searchParams.set('company', companyId);
    } else {
        url.searchParams.delete('company');
    }
    window.location.href = url.toString();
}

// ✅ CORREÇÃO: Inicialização simples dos botões
document.addEventListener('DOMContentLoaded', function() {
    const newDriverBtn = document.getElementById('newDriverBtn');
    const newDriverBtnEmpty = document.getElementById('newDriverBtnEmpty');
    
    if (newDriverBtn) {
        newDriverBtn.addEventListener('click', function() {
            if (window.driversManager) {
                window.driversManager.openDriverForm();
            } else {
                alert('Sistema de motoristas carregando...');
            }
        });
    }
    
    if (newDriverBtnEmpty) {
        newDriverBtnEmpty.addEventListener('click', function() {
            if (window.driversManager) {
                window.driversManager.openDriverForm();
            } else {
                alert('Sistema de motoristas carregando...');
            }
        });
    }
    
    console.log('🎯 [LIST] Total de motoristas na tabela: ' + document.querySelectorAll('#driversTable tbody tr').length);
});
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>