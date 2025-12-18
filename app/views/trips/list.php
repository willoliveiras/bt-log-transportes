<?php
// app/views/trips/list.php

// Definir variáveis para o header
$pageTitle = 'Viagens';
$pageScript = 'trips.js';

// Obter enums para filtros
$tripStatuses = [
    'agendada' => 'Agendada',
    'em_andamento' => 'Em Andamento', 
    'concluida' => 'Concluída',
    'cancelada' => 'Cancelada'
];

$expenseTypes = [
    'combustivel' => 'Combustível',
    'pedagio' => 'Pedágio',
    'hospedagem' => 'Hospedagem',
    'alimentacao' => 'Alimentação',
    'manutencao' => 'Manutenção',
    'outros' => 'Outros'
];

// Buscar estatísticas
$tripStats = $this->tripModel->getTripStats($companyFilter);
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Viagens</h1>
        <p class="dashboard-subtitle">Gerencie as viagens e rotas da frota</p>
    </div>

    <!-- Filtros Modernos -->
    <div class="dashboard-filters">
        <div class="filter-group">
            <label class="filter-label">Empresa</label>
            <select id="companyFilter" class="header-select" onchange="tripsManager.filterByCompany(this.value)">
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
            <label class="filter-label">Status</label>
            <select id="statusFilter" class="header-select" onchange="tripsManager.filterByStatus(this.value)">
                <option value="">Todos os Status</option>
                <?php foreach ($tripStatuses as $key => $name): ?>
                <option value="<?php echo $key; ?>" 
                        <?php echo (isset($_GET['status']) && $_GET['status'] == $key) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Data</label>
            <input type="date" id="dateFilter" class="header-select" 
                   onchange="tripsManager.filterByDate(this.value)"
                   value="<?php echo $_GET['date'] ?? ''; ?>">
        </div>
        
        <div class="filter-group" style="flex: 1; max-width: 300px;">
            <label class="filter-label">Buscar</label>
            <div class="search-box">
                <input type="text" id="searchTrips" placeholder="Buscar viagens...">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <div class="filter-group" style="align-self: flex-end;">
            <button class="btn btn-secondary btn-sm" onclick="tripsManager.refreshTrips()">
                <i class="fas fa-redo"></i>
                Atualizar
            </button>
        </div>
    </div>

    <!-- Métricas Modernas -->
<div class="metrics-grid">
    <?php
    // ✅ BUSCAR DADOS REAIS DO BANCO
    $tripModel = new TripModel();
    $realStats = $tripModel->getTripStats($companyFilter);
    
    // Calcular valores para o período atual
    $totalTrips = $realStats['total_trips'] ?? 0;
    $totalRevenue = $realStats['total_revenue'] ?? 0;
    $totalProfit = $realStats['total_profit'] ?? 0;
    $avgDistance = $realStats['avg_distance'] ?? 0;
    $completedTrips = $realStats['completed_trips'] ?? 0;
    $inProgressTrips = $realStats['in_progress_trips'] ?? 0;
    $scheduledTrips = $realStats['scheduled_trips'] ?? 0;
    
    // Calcular taxa de conclusão
    $completionRate = $totalTrips > 0 ? ($completedTrips / $totalTrips) * 100 : 0;
    ?>
    
    <div class="metric-card">
        <div class="metric-value"><?php echo number_format($totalTrips); ?></div>
        <div class="metric-label">Total de Viagens</div>
        <div class="metric-trend">
            <i class="fas fa-route"></i>
            <span>
                <?php echo $completedTrips; ?> concluídas • 
                <?php echo $inProgressTrips; ?> em andamento
            </span>
        </div>
    </div>
    
    <div class="metric-card success">
        <div class="metric-value">R$ <?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
        <div class="metric-label">Faturamento Total</div>
        <div class="metric-trend">
            <i class="fas fa-money-bill-wave"></i>
            <span>Receita real</span>
        </div>
    </div>
    
    <div class="metric-card info">
        <div class="metric-value">R$ <?php echo number_format($totalProfit, 0, ',', '.'); ?></div>
        <div class="metric-label">Lucro Líquido</div>
        <div class="metric-trend">
            <i class="fas fa-chart-line"></i>
            <span>Resultado real</span>
        </div>
    </div>
    
    <div class="metric-card warning">
        <div class="metric-value"><?php echo number_format($avgDistance, 0); ?> km</div>
        <div class="metric-label">Distância Média</div>
        <div class="metric-trend">
            <i class="fas fa-road"></i>
            <span>Por viagem</span>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Lista de Viagens</h2>
        <div class="card-actions">
            <button class="btn btn-primary" id="newTripBtn">
                <i class="fas fa-route"></i>
                Nova Viagem
            </button>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table" id="tripsTable">
                <thead>
                    <tr>
                        <th>Viagem</th>
                        <th>Cliente</th>
                        <th>Motorista</th>
                        <th>Veículo</th>
                        <th>Rota</th>
                        <th>Datas</th>
                        <th>Valores</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
					<?php if (empty($trips)): ?>
					<tr>
						<td colspan="9" class="text-center">
							<div class="empty-state">
								<i class="fas fa-route"></i>
								<h3>Nenhuma viagem cadastrada</h3>
								<p>Comece cadastrando a primeira viagem do sistema.</p>
								<button class="btn btn-primary" onclick="tripsManager.openTripForm()">
									Cadastrar Viagem
								</button>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($trips as $trip): ?>
						<tr data-trip-id="<?php echo $trip['id']; ?>">
							<td>
								<div class="trip-info">
									<div class="trip-avatar" style="background-color: <?php echo $trip['company_color'] ?? '#FF6B00'; ?>">
										<i class="fas fa-route"></i>
									</div>
									<div>
										<strong><?php echo htmlspecialchars($trip['trip_number'] ?? 'N/A'); ?></strong>
										<div class="trip-details">
											<?php echo htmlspecialchars($trip['company_name'] ?? 'Empresa não informada'); ?>
											<?php if (!empty($trip['distance_km'])): ?>
											 • <?php echo number_format($trip['distance_km'], 0); ?> km
											<?php endif; ?>
										</div>
									</div>
								</div>
							</td>
							<td>
								<div class="client-info">
									<strong><?php echo htmlspecialchars($trip['client_name'] ?? 'Cliente não informado'); ?></strong>
								</div>
							</td>
							<td>
								<div class="driver-info">
									<strong><?php echo htmlspecialchars($trip['driver_name']); ?></strong>
									
									<!-- DEBUG TEMPORÁRIO - REMOVER DEPOIS -->
									<?php 
									echo "<!-- DEBUG: ";
									echo "Driver Type: " . ($trip['driver_type'] ?? 'N/A') . " | ";
									echo "Custom Commission: " . ($trip['driver_commission_rate'] ?? 'N/A') . " | ";
									echo "Employee Commission: " . ($trip['employee_commission_rate'] ?? 'N/A') . " | ";
									echo "Final Commission Rate: " . ($trip['commission_rate'] ?? 'N/A');
									echo " -->";
									?>
									
									<?php 
									$commissionRate = $trip['commission_rate'] ?? 0.00;
									$driverType = $trip['driver_type'] ?? 'external';
									
									if ($driverType == 'employee'): 
									?>
										<div class="text-small" style="color: var(--color-success);">
											<i class="fas fa-user-tie"></i> 
											Funcionário 
											(<?php echo $commissionRate > 0 ? number_format($commissionRate, 1) . '%' : 'Sem comissão'; ?>)
										</div>
									<?php else: ?>
										<div class="text-small" style="color: var(--color-warning);">
											<i class="fas fa-user"></i> 
											Avulso 
											(<?php echo $commissionRate > 0 ? number_format($commissionRate, 1) . '%' : 'Sem comissão'; ?>)
										</div>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<div class="vehicle-info">
									<strong><?php echo htmlspecialchars($trip['vehicle_plate'] ?? 'Placa não informada'); ?></strong>
									<div class="text-small">
										<?php 
										$vehicleInfo = [];
										if (!empty($trip['vehicle_brand'])) $vehicleInfo[] = $trip['vehicle_brand'];
										if (!empty($trip['vehicle_model'])) $vehicleInfo[] = $trip['vehicle_model'];
										echo htmlspecialchars(implode(' ', $vehicleInfo) ?: 'Veículo não informado');
										?>
									</div>
								</div>
							</td>
							<td>
								<div class="route-info">
									<div class="route-block">
										<div class="route-label">Origem:</div>
										<div class="route-value">
											<?php 
											if (!empty($trip['origin_base_name'])) {
												$originText = $trip['origin_base_name'];
												if (!empty($trip['origin_base_city'])) {
													$originText .= ' - ' . $trip['origin_base_city'];
													if (!empty($trip['origin_base_state'])) {
														$originText .= '/' . $trip['origin_base_state'];
													}
												}
												echo htmlspecialchars($originText);
											} else {
												echo htmlspecialchars(substr($trip['origin_address'] ?? 'Endereço não informado', 0, 30) . '...');
											}
											?>
										</div>
									</div>
									<div class="route-block">
										<div class="route-label">Destino:</div>
										<div class="route-value">
											<?php 
											if (!empty($trip['destination_base_name'])) {
												$destinationText = $trip['destination_base_name'];
												if (!empty($trip['destination_base_city'])) {
													$destinationText .= ' - ' . $trip['destination_base_city'];
													if (!empty($trip['destination_base_state'])) {
														$destinationText .= '/' . $trip['destination_base_state'];
													}
												}
												echo htmlspecialchars($destinationText);
											} else {
												echo htmlspecialchars(substr($trip['destination_address'] ?? 'Endereço não informado', 0, 30) . '...');
											}
											?>
										</div>
									</div>
									<?php if (!empty($trip['actual_origin_address']) || !empty($trip['actual_destination_address'])): ?>
									<div class="route-alert">
										<i class="fas fa-exclamation-triangle"></i> Rota alterada
									</div>
									<?php endif; ?>
								</div>
							</td>
						
							<td>
								<div class="dates-info">
									<?php if (!empty($trip['scheduled_date'])): ?>
									<div class="date-block">
										<div class="date-label">Agendada:</div>
										<div class="date-value"><?php echo date('d/m/Y H:i', strtotime($trip['scheduled_date'])); ?></div>
									</div>
									<?php endif; ?>
									
									<?php if (!empty($trip['start_date'])): ?>
									<div class="date-block">
										<div class="date-label">Início:</div>
										<div class="date-value"><?php echo date('d/m/Y H:i', strtotime($trip['start_date'])); ?></div>
									</div>
									<?php endif; ?>
									
									<?php if (!empty($trip['end_date'])): ?>
									<div class="date-block">
										<div class="date-label">Fim:</div>
										<div class="date-value"><?php echo date('d/m/Y H:i', strtotime($trip['end_date'])); ?></div>
									</div>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<div class="financial-info compact">
									<?php
									// DEBUG DETALHADO - VERIFICAR COMISSÃO
									$freightValue = $trip['freight_value'] ?? 0;
									$servicesValue = $trip['total_services_value'] ?? 0;
									$expensesValue = $trip['total_expenses'] ?? 0;
									$commissionRate = $trip['commission_rate'] ?? 0;
									$commissionAmount = $trip['commission_amount'] ?? 0;
									
									$totalRevenue = $freightValue + $servicesValue;
									$totalCost = $commissionAmount + $expensesValue;
									$profit = $totalRevenue - $totalCost;
									
									echo "<!-- DEBUG DETALHADO: ";
									echo "Frete: $freightValue | ";
									echo "Serviços: $servicesValue | ";
									echo "Gastos: $expensesValue | ";
									echo "Comissão Rate: $commissionRate% | ";
									echo "Comissão Amount: $commissionAmount | ";
									echo "Receita: $totalRevenue | ";
									echo "Despesa: $totalCost | ";
									echo "Lucro: $profit";
									echo " -->";
									?>
									
									<div class="financial-line revenue">
										<span class="financial-label">Receita:</span>
										<span class="financial-value revenue">R$ <?php echo number_format($totalRevenue, 2, ',', '.'); ?></span>
									</div>
									
									<div class="financial-line cost">
										<span class="financial-label">Despesa:</span>
										<span class="financial-value cost">R$ <?php echo number_format($totalCost, 2, ',', '.'); ?></span>
									</div>
									
									<div class="financial-line profit <?php echo $profit >= 0 ? 'positive' : 'negative'; ?>">
										<span class="financial-label"><strong>Lucro:</strong></span>
										<span class="financial-value profit"><strong>R$ <?php echo number_format($profit, 2, ',', '.'); ?></strong></span>
									</div>
								</div>
							</td>
							<td>
								<span class="status-badge trip-status trip-status-<?php echo $trip['status'] ?? 'agendada'; ?>">
									<?php echo htmlspecialchars($tripStatuses[$trip['status'] ?? 'agendada'] ?? ($trip['status'] ?? 'Agendada')); ?>
								</span>
							</td>
							<td>
								<div class="action-buttons">
									<button class="btn-icon btn-edit" 
											onclick="tripsManager.editTrip(<?php echo $trip['id']; ?>)"
											title="Editar">
										<i class="fas fa-edit"></i>
									</button>
									<button class="btn-icon btn-view"
											onclick="tripsManager.viewTrip(<?php echo $trip['id']; ?>)"
											title="Visualizar">
										<i class="fas fa-eye"></i>
									</button>
									<button class="btn-icon btn-expenses"
											onclick="tripsManager.showExpenses(<?php echo $trip['id']; ?>)"
											title="Gastos">
										<i class="fas fa-receipt"></i>
									</button>
									<button class="btn-icon btn-delete"
											onclick="tripsManager.deleteTrip(<?php echo $trip['id']; ?>, '<?php echo htmlspecialchars($trip['trip_number'] ?? 'Viagem'); ?>')"
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
</div>

<!-- Modal de Viagem -->
<div class="modal" id="tripModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modalTripTitle">Nova Viagem</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="tripForm">
                <input type="hidden" id="tripId" name="id">
                
                <div class="form-section">
                    <h4>Informações Básicas</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_id">Empresa *</label>
                            <select id="company_id" name="company_id" required onchange="tripsManager.onCompanyChange(this.value)">
                                <option value="">Selecione a empresa</option>
                                <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>">
                                    <?php echo htmlspecialchars($company['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="trip_number">Número da Viagem</label>
                            <input type="text" id="trip_number" name="trip_number" readonly 
                                   placeholder="Gerado automaticamente">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Participantes</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="client_id">Cliente *</label>
                            <select id="client_id" name="client_id" required>
                                <option value="">Selecione o cliente</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="driver_id">Motorista *</label>
                            <select id="driver_id" name="driver_id" required>
                                <option value="">Selecione o motorista</option>
                                <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>">
                                    <?php echo htmlspecialchars($driver['display_name'] ?? $driver['name']); ?>
                                    <?php if ($driver['cnh_number']): ?> - <?php echo htmlspecialchars($driver['cnh_number']); ?><?php endif; ?>
                                    <?php if ($driver['driver_type'] == 'employee'): ?> (Funcionário)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="vehicle_id">Veículo *</label>
                            <select id="vehicle_id" name="vehicle_id" required>
                                <option value="">Selecione o veículo</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>">
                                    <?php echo htmlspecialchars($vehicle['plate']); ?> - 
                                    <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seção de Rota Dinâmica -->
                <div class="form-section">
                    <h4>Rota da Viagem</h4>
                    
                    <!-- Origem -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="origin_type">Tipo de Origem *</label>
                            <select id="origin_type" name="origin_type" required onchange="tripsManager.toggleOriginFields()">
                                <option value="base">Base Cadastrada</option>
                                <option value="custom">Endereço Personalizado</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="origin_base_field">
                            <label for="origin_base_id">Base de Origem</label>
                            <select id="origin_base_id" name="origin_base_id">
                                <option value="">Selecione a base</option>
                                <?php foreach ($bases as $base): ?>
                                <option value="<?php echo $base['id']; ?>">
                                    <?php echo htmlspecialchars($base['name']); ?> - 
                                    <?php echo htmlspecialchars($base['city'] . '/' . $base['state']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="origin_custom_field" style="display: none;">
                            <label for="origin_address">Endereço de Origem *</label>
                            <textarea id="origin_address" name="origin_address" rows="2" 
                                      placeholder="Endereço completo de origem"></textarea>
                        </div>
                    </div>

                    <!-- Destino -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="destination_type">Tipo de Destino *</label>
                            <select id="destination_type" name="destination_type" required onchange="tripsManager.toggleDestinationFields()">
                                <option value="base">Base Cadastrada</option>
                                <option value="custom">Endereço Personalizado</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="destination_base_field">
                            <label for="destination_base_id">Base de Destino</label>
                            <select id="destination_base_id" name="destination_base_id">
                                <option value="">Selecione a base</option>
                                <?php foreach ($bases as $base): ?>
                                <option value="<?php echo $base['id']; ?>">
                                    <?php echo htmlspecialchars($base['name']); ?> - 
                                    <?php echo htmlspecialchars($base['city'] . '/' . $base['state']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="destination_custom_field" style="display: none;">
                            <label for="destination_address">Endereço de Destino *</label>
                            <textarea id="destination_address" name="destination_address" rows="2" 
                                      placeholder="Endereço completo de destino"></textarea>
                        </div>
                    </div>

                    <!-- Mudança de Rota (Socorro) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="route_change" name="route_change" onchange="tripsManager.toggleRouteChange()">
                                <span class="checkmark"></span>
                                Houve mudança de rota por questões imprevistas
                            </label>
                        </div>
                    </div>

                    <div id="route_change_fields" style="display: none;">
                        <div class="route-change-section">
                            <h5 style="margin-bottom: 1rem; color: var(--color-warning);">
                                <i class="fas fa-exclamation-triangle"></i> Rota Real Percorrida
                            </h5>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="actual_origin_address">Endereço Real de Origem</label>
                                    <textarea id="actual_origin_address" name="actual_origin_address" rows="2" 
                                              placeholder="Endereço real de onde a viagem iniciou"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="actual_destination_address">Endereço Real de Destino</label>
                                    <textarea id="actual_destination_address" name="actual_destination_address" rows="2" 
                                              placeholder="Endereço real de onde a viagem terminou"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="route_change_reason">Motivo da Mudança</label>
                                    <textarea id="route_change_reason" name="route_change_reason" rows="2" 
                                              placeholder="Descreva o motivo da mudança de rota..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="distance_km">Distância (km)</label>
                            <input type="number" id="distance_km" name="distance_km" 
                                   step="0.1" min="0" placeholder="0.0">
                        </div>
                        <div class="form-group">
                            <label for="freight_value">Valor do Frete (R$) *</label>
                            <input type="number" id="freight_value" name="freight_value" 
                                   step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                </div>

                <!-- Serviços Vinculados -->
                <div class="form-section">
					<h4>Serviços Adicionais</h4>
					
					<div class="form-row">
						<div class="form-group">
							<label class="checkbox-label">
								<input type="checkbox" id="has_additional_services" name="has_additional_services" value="1">
								<span class="checkmark"></span>
								Esta viagem inclui serviços adicionais
							</label>
						</div>
					</div>

					<div id="services_section" style="display: none;">
						<div class="form-row">
							<div class="form-group" style="grid-column: 1 / -1;">
								<label for="trip_services">Serviços a Serem Realizados</label>
								<select id="trip_services" name="trip_services[]" multiple style="height: 120px;">
									<option value="">Selecione os serviços</option>
									<?php if (!empty($services)): ?>
										<?php foreach ($services as $service): ?>
											<option value="<?php echo $service['id']; ?>" 
													data-price="<?php echo $service['base_price']; ?>">
												<?php echo htmlspecialchars($service['name']); ?> - 
												R$ <?php echo number_format($service['base_price'], 2, ',', '.'); ?>
											</option>
										<?php endforeach; ?>
									<?php else: ?>
										<option value="" disabled>Nenhum serviço cadastrado</option>
									<?php endif; ?>
								</select>
								<small class="form-text">Segure CTRL para selecionar múltiplos serviços</small>
							</div>
						</div>
						
						<div id="selected_services_list">
							<p class="text-muted">Nenhum serviço selecionado</p>
						</div>
					</div>
				</div>

                <div class="form-section">
                    <h4>Datas e Status</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="scheduled_date">Data Agendada</label>
                            <input type="datetime-local" id="scheduled_date" name="scheduled_date">
                        </div>
                        <div class="form-group">
                            <label for="start_date">Data de Início</label>
                            <input type="datetime-local" id="start_date" name="start_date">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="end_date">Data de Término</label>
                            <input type="datetime-local" id="end_date" name="end_date">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <?php foreach ($tripStatuses as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Descrição</h4>
                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Descrição da Viagem</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Descrição detalhada da viagem, carga, observações..."></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelTripButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveTripButton">
                <span class="btn-text">Salvar Viagem</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- Modal de Gastos -->
<div class="modal" id="expensesModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalExpensesTitle">Gastos da Viagem</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="expensesList">
                <!-- Lista de gastos será carregada aqui -->
            </div>
            
            <div class="form-section" style="margin-top: 2rem;">
                <h4>Adicionar Gasto</h4>
                <form id="expenseForm">
                    <input type="hidden" id="expenseTripId" name="trip_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expense_type">Tipo de Gasto *</label>
                            <select id="expense_type" name="expense_type" required>
                                <option value="">Selecione o tipo</option>
                                <?php foreach ($expenseTypes as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="expense_amount">Valor (R$) *</label>
                            <input type="number" id="expense_amount" name="amount" 
                                   step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expense_date">Data</label>
                            <input type="date" id="expense_date" name="expense_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="expense_description">Descrição</label>
                            <textarea id="expense_description" name="description" rows="2" 
                                      placeholder="Descrição do gasto..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelExpenseButton">
                Fechar
            </button>
            <button type="button" class="btn btn-primary" id="saveExpenseButton">
                <span class="btn-text">Adicionar Gasto</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 2rem;
    
    margin: 0 auto;
}

.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-black);
    margin-bottom: 0.5rem;
}

.dashboard-subtitle {
    color: var(--color-gray);
    font-size: 1rem;
}

.dashboard-filters {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 200px;
}

.filter-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--color-gray-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid var(--color-primary);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.metric-card.success {
    border-left-color: var(--color-success);
}

.metric-card.info {
    border-left-color: var(--color-info);
}

.metric-card.warning {
    border-left-color: var(--color-warning);
}

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-black);
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.9rem;
    color: var(--color-gray);
    margin-bottom: 0.5rem;
}

.metric-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--color-gray);
}

.trip-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.trip-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.trip-details {
    font-size: 0.8rem;
    color: var(--color-gray);
    margin-top: 0.25rem;
}

.route-info, .dates-info, .financial-info {
    line-height: 1.4;
}

.financial-item {
    margin-bottom: 0.25rem;
}

.freight-value {
    color: var(--color-success);
    font-weight: 600;
}

.expenses-value, .commission-value {
    color: var(--color-error);
    font-size: 0.9rem;
}

.profit-value.positive {
    color: var(--color-success);
}

.profit-value.negative {
    color: var(--color-error);
}

.trip-status {
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}

.trip-status-agendada {
    background: #E3F2FD;
    color: var(--color-info);
}

.trip-status-em_andamento {
    background: #FFF3E0;
    color: var(--color-warning);
}

.trip-status-concluida {
    background: #E8F5E8;
    color: var(--color-success);
}

.trip-status-cancelada {
    background: #FFEBEE;
    color: var(--color-error);
}

.btn-expenses {
    color: var(--color-warning);
}

.btn-expenses:hover {
    background: #FFF3E0;
}

.selected-services {
    background: var(--color-background);
    padding: 1rem;
    border-radius: var(--border-radius);
    border-left: 3px solid var(--color-primary);
}

.selected-services h5 {
    margin: 0 0 0.5rem 0;
    color: var(--color-gray-dark);
    font-size: 0.9rem;
}

.selected-services ul {
    margin: 0;
    padding-left: 1rem;
}

.selected-services li {
    margin-bottom: 0.25rem;
    color: var(--color-gray-dark);
}

.route-change-section {
    background: #FFF3E0;
    padding: 1rem;
    border-radius: var(--border-radius);
    border-left: 3px solid var(--color-warning);
}

.text-muted {
    color: var(--color-gray) !important;
    font-style: italic;
}

/* Estilos para a lista de gastos */
.expense-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--color-gray-light);
}

.expense-item:last-child {
    border-bottom: none;
}

.expense-info {
    flex: 1;
}

.expense-type {
    font-weight: 600;
    color: var(--color-gray-dark);
}

.expense-description {
    font-size: 0.9rem;
    color: var(--color-gray);
    margin-top: 0.25rem;
}

.expense-date {
    font-size: 0.8rem;
    color: var(--color-gray);
}

.expense-amount {
    font-weight: 600;
    color: var(--color-error);
}

.expense-total {
    background: var(--color-background);
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
    font-weight: 600;
    text-align: center;
}

.expense-total .amount {
    color: var(--color-error);
    font-size: 1.2rem;
}

/* Estilos para a seção de serviços dinâmica */
#services_section {
    background: var(--color-background);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--color-gray-light);
    margin-top: 1rem;
    transition: all 0.3s ease;
}

#services_section[style*="display: block"] {
    animation: fadeIn 0.3s ease-out;
}

.checkbox-label {
    font-weight: 600;
    color: var(--color-gray-dark);
}

/* Novos estilos para informações financeiras */
.revenue-value {
    color: var(--color-success);
    font-size: 1rem;
}

.cost-value {
    color: var(--color-error);
    font-size: 0.9rem;
}

.total-cost {
    border-top: 1px dashed var(--color-gray-light);
    padding-top: 0.25rem;
    margin-top: 0.25rem;
}

.financial-info .financial-item {
    margin-bottom: 0.4rem;
    line-height: 1.3;
}

.financial-info .financial-item:last-child {
    margin-bottom: 0;
}

.financial-info .profit {
    border-top: 2px solid var(--color-gray-light);
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

/* Destaque para a comissão do funcionário */
.driver-info .text-small {
    font-size: 0.75rem;
    margin-top: 0.1rem;
}


/* Estilos para ações de despesas */
.expense-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-delete-expense {
    color: var(--color-error);
    background: none;
    border: none;
    padding: 0.25rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-delete-expense:hover {
    background: #FFEBEE;
}

.expense-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--color-gray-light);
    transition: background 0.3s ease;
}

.expense-item:hover {
    background: var(--color-background);
}


/* ✅ ESTILOS MELHORADOS PARA LAYOUT FINANCEIRO */
.financial-info.compact {
    font-size: 12px;
    line-height: 1.3;
    min-width: 140px;
}

.financial-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3px;
    padding: 2px 0;
}

.financial-line:last-child {
    margin-bottom: 0;
    border-top: 1px solid var(--color-gray-light);
    padding-top: 4px;
    margin-top: 4px;
}

.financial-label {
    font-weight: 500;
    color: var(--color-gray-dark);
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.financial-value {
    font-weight: 600;
    margin-left: 8px;
    white-space: nowrap;
    text-align: right;
    min-width: 70px;
}

/* Cores para os valores */
.financial-value.revenue {
    color: var(--color-success);
}

.financial-value.expenses {
    color: var(--color-error);
    font-size: 11px;
}

.financial-value.commission {
    color: var(--color-warning);
    font-size: 11px;
}

.financial-value.profit.positive {
    color: var(--color-success);
}



.financial-value.profit.negative {
    color: var(--color-error);
}

/* ✅ MELHORIAS GERAIS DE LAYOUT */
.data-table {
    font-size: 13px;
}

.data-table th {
    font-size: 12px;
    font-weight: 700;
    padding: 12px 8px;
    background: var(--color-background);
    border-bottom: 2px solid var(--color-gray-light);
}

.data-table td {
    padding: 10px 8px;
    vertical-align: top;
}

/* Ajuste específico para coluna de valores */
.data-table td:nth-child(7) {
    min-width: 150px;
}

/* Ajuste para coluna de motorista */
.data-table td:nth-child(3) {
    min-width: 180px;
}

/* ✅ ESTILOS PARA INFORMAÇÕES COMPACTAS */
.trip-info {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    min-width: 200px;
}

.trip-avatar {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.trip-info > div {
    flex: 1;
    min-width: 0;
}

.trip-info strong {
    font-size: 13px;
    font-weight: 700;
    display: block;
    margin-bottom: 2px;
}

.trip-details {
    font-size: 11px;
    color: var(--color-gray);
    line-height: 1.2;
}

/* ✅ ESTILOS PARA INFORMAÇÕES DE MOTORISTA */
.driver-info {
    min-width: 180px;
}

.driver-info strong {
    font-size: 13px;
    font-weight: 700;
    display: block;
    margin-bottom: 3px;
}

.driver-info .text-small {
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ✅ ESTILOS PARA INFORMAÇÕES DE VEÍCULO */
.vehicle-info {
    min-width: 150px;
}

.vehicle-info strong {
    font-size: 13px;
    font-weight: 700;
    display: block;
    margin-bottom: 3px;
}

.vehicle-info .text-small {
    font-size: 11px;
    color: var(--color-gray);
}

/* ✅ ESTILOS PARA ROTA */
.route-info {
    min-width: 220px;
}

.route-label {
    font-weight: 600;
    color: var(--color-gray-dark);
    font-size: 11px;
    margin-bottom: 2px;
    line-height: 1.2;
}

.route-value {
    font-size: 12px;
    color: var(--color-gray-dark);
    line-height: 1.3;
    word-break: break-word;
}

.route-alert {
    font-size: 10px;
    color: var(--color-warning);
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 3px;
}

/* ✅ LAYOUT EM BLOCO PARA DATAS */
.dates-info {
    min-width: 150px;
}

.date-block {
    margin-bottom: 6px;
}

.date-block:last-child {
    margin-bottom: 0;
}

.date-label {
    font-weight: 600;
    color: var(--color-gray-dark);
    font-size: 11px;
    margin-bottom: 2px;
    line-height: 1.2;
}

.date-value {
    font-size: 12px;
    color: var(--color-gray-dark);
    line-height: 1.3;
}

.route-block {
    margin-bottom: 6px;
}

.route-block:last-child {
    margin-bottom: 0;
}

.route-info .text-small {
    font-size: 11px;
    line-height: 1.3;
    margin-bottom: 4px;
}

.route-info .text-small:last-child {
    margin-bottom: 0;
}

/* ✅ ESTILOS PARA DATAS */
.dates-info {
    min-width: 150px;
}

.dates-info .text-small {
    font-size: 11px;
    line-height: 1.3;
    margin-bottom: 3px;
}

.dates-info .text-small:last-child {
    margin-bottom: 0;
}

/* ✅ ESTILOS PARA STATUS */
.status-badge {
    font-size: 11px;
    padding: 4px 8px;
    font-weight: 700;
}

/* ✅ ESTILOS PARA BOTÕES DE AÇÃO */
.action-buttons {
    display: flex;
    gap: 4px;
    justify-content: center;
}

.btn-icon {
    width: 28px;
    height: 28px;
    font-size: 12px;
}

/* ✅ MELHORIAS RESPONSIVAS */

    .financial-info.compact {
        min-width: 130px;
    }
    
    .financial-value {
        min-width: 65px;
    }
}

/* ✅ DESTAQUE PARA LUCRO NEGATIVO */
.financial-line.profit.negative {
    background: #FFEBEE;
    margin: 2px -4px;
    padding: 3px 4px;
    border-radius: 4px;
}

.financial-line.profit.positive {
    background: #E8F5E8;
    margin: 2px -4px;
    padding: 3px 4px;
    border-radius: 4px;
}
</style>

<script>
// Inicialização dos eventos de serviços
document.addEventListener('DOMContentLoaded', function() {
    const servicesSelect = document.getElementById('trip_services');
    if (servicesSelect) {
        servicesSelect.addEventListener('change', function() {
            updateSelectedServicesList();
        });
    }
});

function updateSelectedServicesList() {
    const servicesSelect = document.getElementById('trip_services');
    const selectedServicesList = document.getElementById('selected_services_list');
    const selectedOptions = Array.from(servicesSelect.selectedOptions);
    
    if (selectedOptions.length === 0) {
        selectedServicesList.innerHTML = '<p class="text-muted">Nenhum serviço selecionado</p>';
        return;
    }
    
    let html = '<div class="selected-services"><h5>Serviços Selecionados:</h5><ul>';
    
    selectedOptions.forEach(option => {
        html += `<li>${option.text}</li>`;
    });
    
    html += '</ul></div>';
    selectedServicesList.innerHTML = html;
}
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>