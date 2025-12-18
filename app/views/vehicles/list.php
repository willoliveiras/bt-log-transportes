<?php
// Definir variáveis para o header
$pageTitle = 'Veículos';
$pageScript = 'vehicles.js';

// Carregar modelos
require_once __DIR__ . '/../../models/VehicleModel.php';
require_once __DIR__ . '/../../models/CompanyModel.php';

$vehicleModel = new VehicleModel();
$companyModel = new CompanyModel();

?>

<div class="page-header">
    <div class="header-content">
        <h1>Veículos</h1>
        <p>Gerencie a frota de veículos do sistema BT Log</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="vehiclesManager.openVehicleForm()">
            <i class="fas fa-truck"></i>
            Novo Veículo
        </button>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Frota de Veículos</h2>
        <div class="card-actions">
            <div class="filter-group">
                <select id="companyFilter" class="header-select" onchange="vehiclesManager.filterByCompany(this.value)">
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
                <select id="typeFilter" class="header-select" onchange="vehiclesManager.filterByType(this.value)">
                    <option value="">Todos os Tipos</option>
                    <?php foreach ($vehicleTypes as $key => $name): ?>
                    <option value="<?php echo $key; ?>" 
                            <?php echo (isset($_GET['type']) && $_GET['type'] == $key) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="statusFilter" class="header-select" onchange="vehiclesManager.filterByStatus(this.value)">
                    <option value="">Todos os Status</option>
                    <?php foreach ($statusTypes as $key => $name): ?>
                    <option value="<?php echo $key; ?>" 
                            <?php echo (isset($_GET['status']) && $_GET['status'] == $key) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-box">
                <input type="text" id="searchVehicles" placeholder="Buscar veículos...">
                <i class="fas fa-search"></i>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="vehiclesManager.refreshVehicles()">
                <i class="fas fa-redo"></i>
                Atualizar
            </button>
        </div>
    </div>

    <div class="card-body">
    <div class="table-responsive">
        <table class="data-table" id="vehiclesTable">
            <thead>
                <tr>
                    <th>Veículo</th>
                    <th>Placa</th>
                    <th>Tipo</th>
                    <th>Capacidade</th>
                    <th>Combustível</th>
                    <th>KM Atual</th>
                    <th>Status</th>
                    <th>Empresa</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // DEBUG: Verificar se há veículos
                error_log("📊 [VEHICLES LIST] Total de veículos para exibir: " . count($vehicles));
                
                if (empty($vehicles)): 
                ?>
                <tr>
                    <td colspan="9" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <h3>Nenhum veículo cadastrado</h3>
                            <p>Comece cadastrando o primeiro veículo da frota.</p>
                            <button class="btn btn-primary" onclick="vehiclesManager.openVehicleForm()">
                                Cadastrar Veículo
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): 
                    // DEBUG: Log de cada veículo
                    error_log("🚗 [VEHICLES LIST] Exibindo veículo: ID {$vehicle['id']} - {$vehicle['brand']} {$vehicle['model']}");
                    ?>
                    <tr data-vehicle-id="<?php echo $vehicle['id']; ?>">
                        <td>
                            <div class="vehicle-info">
                                <div class="vehicle-avatar" style="background-color: <?php echo $vehicle['company_color'] ?? '#FF6B00'; ?>">
                                    <i class="fas fa-<?php echo $this->getVehicleIcon($vehicle['type']); ?>"></i>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></strong>
                                    <div class="vehicle-details">
                                        <?php echo $vehicle['year']; ?> • 
                                        <?php echo htmlspecialchars($vehicle['color']); ?>
                                        <?php if ($vehicle['vehicle_subtype']): ?>
                                         • <?php echo htmlspecialchars($vehicle['vehicle_subtype']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong class="vehicle-plate"><?php echo htmlspecialchars($vehicle['plate']); ?></strong>
                        </td>
                        <td>
                            <span class="vehicle-type-badge" data-type="<?php echo $vehicle['type']; ?>">
                                <?php echo htmlspecialchars($vehicleTypes[$vehicle['type']] ?? $vehicle['type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($vehicle['capacity']): ?>
                                <span class="capacity-value">
                                    <?php echo number_format($vehicle['capacity'], 0); ?> 
                                    <?php echo htmlspecialchars($vehicle['capacity_unit']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fuel-info">
                                <span class="fuel-type"><?php echo htmlspecialchars($fuelTypes[$vehicle['fuel_type']] ?? $vehicle['fuel_type']); ?></span>
                                <?php if ($vehicle['average_consumption']): ?>
                                    <br>
                                    <small class="text-gray"><?php echo number_format($vehicle['average_consumption'], 1); ?> km/L</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="mileage-value">
                                <?php echo number_format($vehicle['current_km'], 0); ?> km
                            </span>
                        </td>
                        <td>
                            <span class="status-badge vehicle-status vehicle-status-<?php echo $vehicle['status']; ?>">
                                <?php echo htmlspecialchars($statusTypes[$vehicle['status']] ?? $vehicle['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="color: <?php echo $vehicle['company_color'] ?? '#FF6B00'; ?>">
                                <?php echo htmlspecialchars($vehicle['company_name'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-edit" 
                                        onclick="vehiclesManager.editVehicle(<?php echo $vehicle['id']; ?>)"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-view"
                                        onclick="vehiclesManager.viewVehicle(<?php echo $vehicle['id']; ?>)"
                                        title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon btn-delete"
                                        onclick="vehiclesManager.deleteVehicle(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' - ' . $vehicle['plate']); ?>')"
                                        title="Excluir">
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

<!-- Modal de Veículo -->
<div class="modal" id="vehicleModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modalVehicleTitle">Novo Veículo</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="vehicleForm">
                <input type="hidden" id="vehicleId" name="id">
                
                <div class="form-section">
                    <h4>Informações Básicas</h4>
					<div class="form-group">
						<label for="company_id">Empresa *</label>
						<select id="company_id" name="company_id" required>
							<option value="">Selecione a empresa</option>
							<?php 
							// Garantir que $companies está disponível
							$companies = $companyModel->getForDropdown();
							foreach ($companies as $company): ?>
							<option value="<?php echo $company['id']; ?>">
								<?php echo htmlspecialchars($company['name']); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					
                    <div class="form-row">
                        <div class="form-group">
							<label for="fuel_type">Tipo de Combustível *</label>
							<select id="fuel_type" name="fuel_type" required>
								<option value="">Selecione o combustível</option>
								<?php 
								$fuelTypes = $vehicleModel->getFuelTypes();
								foreach ($fuelTypes as $key => $name): ?>
								<option value="<?php echo $key; ?>">
									<?php echo htmlspecialchars($name); ?>
								</option>
								<?php endforeach; ?>
							</select>
						</div>
                        <div class="form-group">
                            <label for="plate">Placa *</label>
                            <input type="text" id="plate" name="plate" required 
                                   placeholder="ABC1D23" class="plate-mask" maxlength="7">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="brand">Marca *</label>
                            <input type="text" id="brand" name="brand" required 
                                   placeholder="Ex: Volkswagen, Mercedes-Benz">
                        </div>
                        <div class="form-group">
                            <label for="model">Modelo *</label>
                            <input type="text" id="model" name="model" required 
                                   placeholder="Ex: Golf, Actros, Hilux">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="year">Ano *</label>
                            <input type="number" id="year" name="year" required 
                                   min="1900" max="<?php echo date('Y') + 1; ?>" 
                                   placeholder="<?php echo date('Y'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="color">Cor</label>
                            <input type="text" id="color" name="color" placeholder="Ex: Preto, Branco, Prata">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Classificação e Capacidade</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Tipo de Veículo *</label>
                            <select id="type" name="type" required onchange="vehiclesManager.onTypeChange(this.value)">
                                <option value="">Selecione o tipo</option>
                                <?php foreach ($vehicleTypes as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="vehicle_subtype">Subtipo</label>
                            <select id="vehicle_subtype" name="vehicle_subtype">
                                <option value="">Selecione o subtipo</option>
                                <!-- Preenchido via JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacity">Capacidade</label>
                            <input type="number" id="capacity" name="capacity" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="capacity_unit">Unidade de Capacidade</label>
                            <select id="capacity_unit" name="capacity_unit">
                                <?php foreach ($capacityUnits as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Combustível e Consumo</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuel_type">Tipo de Combustível *</label>
                            <select id="fuel_type" name="fuel_type" required>
                                <option value="">Selecione o combustível</option>
                                <?php foreach ($fuelTypes as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fuel_capacity">Capacidade do Tanque (L)</label>
                            <input type="number" id="fuel_capacity" name="fuel_capacity" 
                                   step="0.1" min="0" placeholder="0.0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="average_consumption">Consumo Médio (km/L)</label>
                            <input type="number" id="average_consumption" name="average_consumption" 
                                   step="0.1" min="0" placeholder="0.0">
                        </div>
                        <div class="form-group">
                            <label for="current_km">Quilometragem Atual</label>
                            <input type="number" id="current_km" name="current_km" 
                                   min="0" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Documentos e Seguro</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="chassis_number">Número do Chassi</label>
                            <input type="text" id="chassis_number" name="chassis_number" 
                                   placeholder="Número completo do chassi">
                        </div>
                        <div class="form-group">
                            <label for="registration_number">Número do CRLV</label>
                            <input type="text" id="registration_number" name="registration_number" 
                                   placeholder="Número do documento">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="registration_expiry">Vencimento do CRLV</label>
                            <input type="date" id="registration_expiry" name="registration_expiry">
                        </div>
                        <div class="form-group">
                            <label for="insurance_company">Seguradora</label>
                            <input type="text" id="insurance_company" name="insurance_company" 
                                   placeholder="Nome da seguradora">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="insurance_number">Número da Apólice</label>
                            <input type="text" id="insurance_number" name="insurance_number" 
                                   placeholder="Número da apólice">
                        </div>
                        <div class="form-group">
                            <label for="insurance_expiry">Vencimento do Seguro</label>
                            <input type="date" id="insurance_expiry" name="insurance_expiry">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Status e Observações</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status do Veículo</label>
                            <select id="status" name="status">
                                <?php foreach ($statusTypes as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <span class="checkmark"></span>
                                Veículo ativo
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="notes">Observações</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      placeholder="Observações adicionais sobre o veículo..."></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelVehicleButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveVehicleButton">
                <span class="btn-text">Salvar Veículo</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<style>
.vehicle-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.vehicle-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.vehicle-details {
    font-size: 0.8rem;
    color: var(--color-gray);
    margin-top: 0.25rem;
}

.vehicle-plate {
    background: var(--color-background);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-weight: bold;
}

.vehicle-type-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--color-primary-light);
    color: var(--color-primary);
}

.vehicle-status {
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}

.vehicle-status-disponivel {
    background: #E8F5E8;
    color: var(--color-success);
}

.vehicle-status-em_viagem {
    background: #E3F2FD;
    color: var(--color-info);
}

.vehicle-status-manutencao {
    background: #FFF3E0;
    color: var(--color-warning);
}

.vehicle-status-inativo {
    background: #FFEBEE;
    color: var(--color-error);
}

.capacity-value, .mileage-value {
    font-weight: 600;
    color: var(--color-gray-dark);
}

.fuel-info {
    line-height: 1.3;
}

.fuel-type {
    font-weight: 600;
    text-transform: capitalize;
}

/* Ícones para tipos de veículos */
.fa-car:before { content: "🚗"; }
.fa-motorcycle:before { content: "🏍️"; }
.fa-truck:before { content: "🚚"; }
.fa-truck-pickup:before { content: "🛻"; }
.fa-van:before { content: "🚐"; }
.fa-bus:before { content: "🚌"; }
.fa-trailer:before { content: "🚛"; }
</style>

<?php
include __DIR__ . '/../layouts/footer.php';
?>