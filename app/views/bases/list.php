<?php
// app/views/bases/list.php - DESIGN DISCRETO COM DASHBOARD E RECURSOS
$pageTitle = 'Bases';
?>

<div class="bases-dashboard">
    <!-- Cabeçalho da Página -->
    <div class="page-header">
        <div class="header-content">
            <h1>Bases BT Log</h1>
            <p>Gerencie as bases/unidades da empresa</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" id="newBaseBtn">
                <i class="fas fa-building"></i>
                Nova Base
            </button>
        </div>
    </div>
</div>

    <!-- Dashboard Discreto -->
    <div class="stats-section">
        <h3>Visão Geral</h3>
        <div class="stats-grid-discreet">
            <!-- Total de Bases -->
            <div class="stat-card-discreet primary" id="statTotalBases">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['total_bases']; ?></div>
                        <div class="stat-label-discreet">Total de Bases</div>
                        <div class="stat-description-discreet">
                            <?php echo $stats['active_bases']; ?> ativas
                            <?php if($stats['inactive_bases'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <?php echo $stats['inactive_bases']; ?> inativas
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Todas ativas
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>

            <!-- Capacidade de Veículos -->
            <div class="stat-card-discreet info" id="statTotalCapacity">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo number_format($stats['total_capacity_vehicles']); ?></div>
                        <div class="stat-label-discreet">Capacidade Veículos</div>
                        <div class="stat-description-discreet">
                            <?php echo $stats['total_current_vehicles']; ?> ativos
                            <div class="stat-trend-discreet <?php echo $stats['utilization_vehicles'] > 80 ? 'trend-down-discreet' : 'trend-up-discreet'; ?>">
                                <i class="fas fa-<?php echo $stats['utilization_vehicles'] > 80 ? 'exclamation-triangle' : 'check'; ?>"></i>
                                <?php echo $stats['utilization_vehicles']; ?>% utilizado
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>

            <!-- Capacidade de Motoristas -->
            <div class="stat-card-discreet success" id="statActiveBases">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo number_format($stats['total_capacity_drivers']); ?></div>
                        <div class="stat-label-discreet">Capacidade Motoristas</div>
                        <div class="stat-description-discreet">
                            <?php echo $stats['total_current_drivers']; ?> ativos
                            <div class="stat-trend-discreet <?php echo $stats['utilization_drivers'] > 80 ? 'trend-down-discreet' : 'trend-up-discreet'; ?>">
                                <i class="fas fa-<?php echo $stats['utilization_drivers'] > 80 ? 'exclamation-triangle' : 'check'; ?>"></i>
                                <?php echo $stats['utilization_drivers']; ?>% utilizado
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>

            <!-- Cobertura Geográfica -->
            <div class="stat-card-discreet warning" id="statUsageRate">
                <div class="stat-content-discreet">
                    <div class="stat-text-discreet">
                        <div class="stat-value-discreet"><?php echo $stats['states_count']; ?></div>
                        <div class="stat-label-discreet">Estados Atendidos</div>
                        <div class="stat-description-discreet">
                            Cobertura nacional
                            <?php if($stats['bases_without_manager'] > 0): ?>
                            <div class="stat-trend-discreet trend-down-discreet">
                                <i class="fas fa-user-times"></i>
                                <?php echo $stats['bases_without_manager']; ?> sem gerente
                            </div>
                            <?php else: ?>
                            <div class="stat-trend-discreet trend-up-discreet">
                                <i class="fas fa-check"></i>
                                Todas com gerente
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon-discreet">
                        <i class="fas fa-map-marker-alt"></i>
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
                    <option value="active">Ativas</option>
                    <option value="inactive">Inativas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterCapacity">
                    <i class="fas fa-chart-bar"></i>
                    Capacidade
                </label>
                <select id="filterCapacity" class="form-select">
                    <option value="all">Todas as Capacidades</option>
                    <option value="low">Baixa (&lt;50%)</option>
                    <option value="medium">Média (50-80%)</option>
                    <option value="high">Alta (80-95%)</option>
                    <option value="critical">Crítica (&gt;95%)</option>
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

    <!-- Tabela de Bases -->
    <div class="bases-table-container">
		<div class="bases-table-header">
			<h2><i class="fas fa-list"></i> Lista de Bases</h2>
		</div>

		<div class="table-responsive">
			<table class="bases-table" id="basesTable">
				<thead>
					<tr>
						<th>Base</th>
						<th>Localização</th>
						<th>Contato</th>
						<th>Capacidade</th>
						<th>Recursos</th>
						<th>Gerente</th>
						<th>Status</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody id="basesTableBody">
					<?php if (empty($bases)): ?>
						<tr>
							<td colspan="8">
								<div class="empty-state-modern">
									<div class="empty-icon-modern">
										<i class="fas fa-warehouse"></i>
									</div>
									<h3>Nenhuma Base Cadastrada</h3>
									<p>Comece cadastrando a primeira base do sistema.</p>
									<button class="btn btn-primary" onclick="window.basesManager.openBaseModal()">
										<i class="fas fa-plus"></i>
										Cadastrar Primeira Base
									</button>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($bases as $base): ?>
						<tr data-base-id="<?php echo $base['id']; ?>" style="<?php echo !$base['is_active'] ? 'opacity: 0.6;' : ''; ?>">
							
							<!-- Coluna Base -->
							<td>
								<div class="base-card-modern">
									<div class="base-avatar-modern" style="background: linear-gradient(135deg, <?php echo $base['company_color'] ?? '#FF6B00'; ?>, <?php echo $base['company_color'] ?? '#E55A00'; ?>);">
										<?php echo substr($base['name'], 0, 2); ?>
										<div class="avatar-status <?php echo $base['is_active'] ? '' : 'inactive'; ?>"></div>
									</div>
									<div class="base-info-modern">
										<div class="base-name-modern">
											<?php echo htmlspecialchars($base['name']); ?>
											<?php if(!$base['is_active']): ?>
												<small style="color: #dc2626;">(Inativa)</small>
											<?php endif; ?>
										</div>
										<div class="base-company-modern">
											<i class="fas fa-building"></i>
											<?php echo htmlspecialchars($base['company_name']); ?>
										</div>
									</div>
								</div>
							</td>
							
							<!-- Coluna Localização -->
							<td>
								<div class="location-card-modern">
									<?php if ($base['city'] && $base['state']): ?>
										<div class="location-city-modern">
											<i class="fas fa-map-marker-alt"></i>
											<?php echo htmlspecialchars($base['city']); ?> - <?php echo htmlspecialchars($base['state']); ?>
										</div>
									<?php endif; ?>
									<?php if ($base['address']): ?>
										<div class="location-address-modern">
											<?php echo htmlspecialchars($base['address']); ?>
										</div>
									<?php endif; ?>
								</div>
							</td>
							
							<!-- ✅ CORREÇÃO CRÍTICA: Coluna Contato -->
							<td>
								<div class="contact-list-modern">
									<?php if ($base['phone']): ?>
										<div class="contact-item-modern">
											<div class="contact-icon-modern">
												<i class="fas fa-phone"></i>
											</div>
											<div class="contact-info-modern">
												<div class="contact-type-modern">Telefone</div>
												<div class="contact-value-modern"><?php echo htmlspecialchars($base['phone']); ?></div>
											</div>
										</div>
									<?php endif; ?>
									
									<?php if ($base['email']): ?>
										<div class="contact-item-modern">
											<div class="contact-icon-modern">
												<i class="fas fa-envelope"></i>
											</div>
											<div class="contact-info-modern">
												<div class="contact-type-modern">Email</div>
												<div class="contact-value-modern"><?php echo htmlspecialchars($base['email']); ?></div>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</td>
							
							<!-- ✅ CORREÇÃO CRÍTICA: Coluna Capacidade -->
							<td>
								<div class="capacity-dashboard-modern">
									<!-- Capacidade de Veículos -->
									<div class="capacity-item-modern">
										<div class="capacity-header-modern">
											<div class="capacity-label-modern">Veículos</div>
											<div class="capacity-stats-modern">
												<?php echo $base['total_vehicles'] ?? 0; ?>/<?php echo $base['capacity_vehicles'] ?? 0; ?>
											</div>
										</div>
										<div class="capacity-progress-modern">
											<?php
											$vehicleUtilization = ($base['capacity_vehicles'] > 0) ? 
												min(100, round((($base['total_vehicles'] ?? 0) / $base['capacity_vehicles']) * 100)) : 0;
											$vehicleClass = $vehicleUtilization >= 90 ? 'critical' : 
														  ($vehicleUtilization >= 75 ? 'high' : 
														  ($vehicleUtilization >= 50 ? 'medium' : 'low'));
											?>
											<div class="capacity-fill-modern <?php echo $vehicleClass; ?>" 
												 style="width: <?php echo $vehicleUtilization; ?>%">
											</div>
										</div>
										<div class="capacity-percentage-modern"><?php echo $vehicleUtilization; ?>%</div>
									</div>
									
									<!-- Capacidade de Motoristas -->
									<div class="capacity-item-modern">
										<div class="capacity-header-modern">
											<div class="capacity-label-modern">Motoristas</div>
											<div class="capacity-stats-modern">
												<?php echo $base['total_drivers'] ?? 0; ?>/<?php echo $base['capacity_drivers'] ?? 0; ?>
											</div>
										</div>
										<div class="capacity-progress-modern">
											<?php
											$driverUtilization = ($base['capacity_drivers'] > 0) ? 
												min(100, round((($base['total_drivers'] ?? 0) / $base['capacity_drivers']) * 100)) : 0;
											$driverClass = $driverUtilization >= 90 ? 'critical' : 
														 ($driverUtilization >= 75 ? 'high' : 
														 ($driverUtilization >= 50 ? 'medium' : 'low'));
											?>
											<div class="capacity-fill-modern <?php echo $driverClass; ?>" 
												 style="width: <?php echo $driverUtilization; ?>%">
											</div>
										</div>
										<div class="capacity-percentage-modern"><?php echo $driverUtilization; ?>%</div>
									</div>
								</div>
							</td>
							
							<!-- Coluna Recursos -->
							<td>
								<div class="resources-showcase-modern">
									<div class="resource-badges-modern">
										<div class="resource-badge-modern vehicles">
											<div class="resource-icon-modern">
												<i class="fas fa-truck"></i>
											</div>
											<div class="resource-count-modern"><?php echo $base['total_vehicles'] ?? 0; ?></div>
										</div>
										<div class="resource-badge-modern drivers">
											<div class="resource-icon-modern">
												<i class="fas fa-user-tie"></i>
											</div>
											<div class="resource-count-modern"><?php echo $base['total_drivers'] ?? 0; ?></div>
										</div>
									</div>
								</div>
							</td>
							
							<!-- Coluna Gerente -->
							<td>
								<?php if ($base['manager_name']): ?>
									<div class="manager-card-modern">
										<div class="manager-avatar-modern">
											<?php echo substr($base['manager_name'], 0, 2); ?>
										</div>
										<div class="manager-info-modern">
											<div class="manager-name-modern"><?php echo htmlspecialchars($base['manager_name']); ?></div>
											<?php if ($base['manager_position']): ?>
												<div class="manager-position-modern"><?php echo htmlspecialchars($base['manager_position']); ?></div>
											<?php endif; ?>
										</div>
									</div>
								<?php else: ?>
									<div class="empty-manager">
										<i class="fas fa-user-times"></i>
										<span>Sem gerente</span>
									</div>
								<?php endif; ?>
							</td>
							
							<!-- Coluna Status -->
							<td>
								<span class="status-pill-modern <?php echo $base['is_active'] ? 'active' : 'inactive'; ?>">
									<i class="fas fa-<?php echo $base['is_active'] ? 'check' : 'times'; ?>"></i>
									<?php echo $base['is_active'] ? 'Ativa' : 'Inativa'; ?>
								</span>
							</td>
							
							<!-- ✅ CORREÇÃO CRÍTICA: Coluna Ações -->
							<td>
								<div class="actions-toolbar-modern">
									<button class="action-btn-modern btn-view-modern" 
											onclick="window.basesManager.viewBase(<?php echo $base['id']; ?>)"
											title="Visualizar Base">
										<i class="fas fa-eye"></i>
									</button>
									<button class="action-btn-modern btn-edit-modern" 
											onclick="window.basesManager.editBase(<?php echo $base['id']; ?>)"
											title="Editar Base">
										<i class="fas fa-edit"></i>
									</button>
									<button class="action-btn-modern btn-delete-modern" 
											onclick="window.basesManager.deleteBase(<?php echo $base['id']; ?>)"
											title="<?php echo $base['is_active'] ? 'Desativar' : 'Ativar'; ?> Base">
										<i class="fas <?php echo $base['is_active'] ? 'fa-times' : 'fa-check'; ?>"></i>
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
<!-- Modal para Cadastro/Edição de Base -->
<div class="modal" id="baseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="baseModalLabel">Nova Base</h5>
                <button type="button" class="btn-close" onclick="window.basesManager.closeBaseModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="baseForm">
                <input type="hidden" name="base_id" value="">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_id" class="form-label">Empresa *</label>
                            <select id="company_id" name="company_id" class="form-control" required>
                                <option value="">Selecione a Empresa</option>
                                <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>">
                                    <?php echo htmlspecialchars($company['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="name" class="form-label">Nome da Base *</label>
                            <input type="text" id="name" name="name" class="form-control" required 
                                   placeholder="Ex: Matriz São Paulo">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="address" class="form-label">Endereço</label>
                            <textarea id="address" name="address" class="form-control" rows="2" 
                                      placeholder="Endereço completo"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city" class="form-label">Cidade</label>
                            <input type="text" id="city" name="city" class="form-control" placeholder="Cidade">
                        </div>
                        <div class="form-group">
                            <label for="state" class="form-label">Estado</label>
                            <select id="state" name="state" class="form-control">
                                <option value="">Selecione</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" id="phone" name="phone" class="form-control" 
                                   placeholder="(11) 99999-9999">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="base@empresa.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="manager_id" class="form-label">Gerente</label>
                            <select id="manager_id" name="manager_id" class="form-control">
                                <option value="">Selecione o Gerente</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?> 
                                    - <?php echo htmlspecialchars($employee['position'] ?? 'Funcionário'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="opening_date" class="form-label">Data de Abertura</label>
                            <input type="date" id="opening_date" name="opening_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacity_vehicles" class="form-label">Capacidade Veículos</label>
                            <input type="number" id="capacity_vehicles" name="capacity_vehicles" 
                                   class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="capacity_drivers" class="form-label">Capacidade Motoristas</label>
                            <input type="number" id="capacity_drivers" name="capacity_drivers" 
                                   class="form-control" min="0" value="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="operating_hours" class="form-label">Horário de Funcionamento</label>
                            <input type="text" id="operating_hours" name="operating_hours" class="form-control" 
                                   placeholder="Ex: 06:00-22:00">
                        </div>
                        <div class="form-group">
                            <div class="form-check" style="margin-top: 2rem;">
                                <input type="checkbox" id="is_active" name="is_active" 
                                       class="form-check-input" value="1" checked>
                                <label for="is_active" class="form-check-label">Base Ativa</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                  placeholder="Observações adicionais..."></textarea>
                    </div>

                    <!-- Seção de Recursos Vinculados -->
                    <div class="resources-section">
                        <h6><i class="fas fa-link"></i> Recursos Vinculados</h6>
                        
                        <div class="resources-list">
                            <!-- Funcionários -->
                            <div class="resource-card">
                                <h6>Funcionários</h6>
                                <div class="resource-list" id="employeesListContainer">
                                    <div class="empty-resource">
                                        <i class="fas fa-users"></i>
                                        <span>Nenhum funcionário vinculado</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-add-resource" onclick="openEmployeeSelector()">
                                    <i class="fas fa-plus"></i> Vincular Funcionários
                                </button>
                            </div>

                            <!-- Veículos -->
                            <div class="resource-card">
                                <h6>Veículos</h6>
                                <div class="resource-list" id="vehiclesListContainer">
                                    <div class="empty-resource">
                                        <i class="fas fa-truck"></i>
                                        <span>Nenhum veículo vinculado</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-add-resource" onclick="openVehicleSelector()">
                                    <i class="fas fa-plus"></i> Vincular Veículos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.basesManager.closeBaseModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Base
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Seleção de Funcionários -->
<div class="modal" id="employeeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Vincular Funcionários</h5>
                <button type="button" class="btn-close" onclick="closeEmployeeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="resource-list" id="employeeSelectionList">
                    <?php foreach ($employees as $employee): ?>
                    <div class="resource-item" data-employee-id="<?php echo $employee['id']; ?>">
                        <div class="resource-info">
                            <div class="resource-avatar">
                                <?php echo strtoupper(substr($employee['name'], 0, 1)); ?>
                            </div>
                            <div class="resource-details">
                                <h6><?php echo htmlspecialchars($employee['name']); ?></h6>
                                <p><?php echo htmlspecialchars($employee['position'] ?? 'Funcionário'); ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-add-resource" onclick="toggleEmployeeSelection(<?php echo $employee['id']; ?>)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEmployeeModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmEmployeeSelection()">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Seleção de Veículos -->
<div class="modal" id="vehicleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Vincular Veículos</h5>
                <button type="button" class="btn-close" onclick="closeVehicleModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="resource-list" id="vehicleSelectionList">
                    <?php foreach ($vehicles as $vehicle): ?>
                    <div class="resource-item" data-vehicle-id="<?php echo $vehicle['id']; ?>">
                        <div class="resource-info">
                            <div class="resource-avatar">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="resource-details">
                                <h6><?php echo htmlspecialchars($vehicle['plate']); ?></h6>
                                <p><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-add-resource" onclick="toggleVehicleSelection(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeVehicleModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmVehicleSelection()">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<style>
/* public/assets/css/bases-emergency.css - CORREÇÃO DO ALINHAMENTO */

/* ✅ BASE - CENTRALIZADO VERTICALMENTE */
.base-card-modern {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    min-height: 60px !important;
}

.base-avatar-modern {
    width: 48px !important;
    height: 48px !important;
    border-radius: 12px !important;
    background: linear-gradient(135deg, #FF6B00, #E55A00) !important;
    color: white !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: bold !important;
    font-size: 16px !important;
    position: relative !important;
}

.avatar-status {
    position: absolute !important;
    bottom: -2px !important;
    right: -2px !important;
    width: 12px !important;
    height: 12px !important;
    border-radius: 50% !important;
    border: 2px solid white !important;
    background: #4CAF50 !important;
}

.avatar-status.inactive {
    background: #F44336 !important;
}

.base-info-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 4px !important;
}

.base-name-modern {
    font-weight: 600 !important;
    font-size: 14px !important;
    color: #000 !important;
}

.base-company-modern {
    font-size: 12px !important;
    color: #666 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

/* ✅ LOCALIZAÇÃO - CENTRALIZADO VERTICALMENTE */
.location-card-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    min-height: 60px !important;
    justify-content: center !important;
}

.location-city-modern {
    font-weight: 500 !important;
    font-size: 14px !important;
    color: #000 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.location-address-modern {
    font-size: 12px !important;
    color: #666 !important;
}

/* ✅ CONTATO - MANTIDO ALINHAMENTO ORIGINAL (NÃO CENTRALIZAR) */
.contact-list-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    min-width: 180px !important;
    height: auto !important;
}

.contact-item-modern {
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px !important;
    padding: 8px 0 !important;
}

.contact-icon-modern {
    width: 32px !important;
    height: 32px !important;
    border-radius: 8px !important;
    background: #FFE0CC !important;
    color: #FF6B00 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 14px !important;
    flex-shrink: 0 !important;
    margin-top: 2px !important;
}

.contact-info-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
    flex: 1 !important;
}

.contact-type-modern {
    font-size: 10px !important;
    color: #666 !important;
    text-transform: uppercase !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px !important;
}

.contact-value-modern {
    font-size: 13px !important;
    color: #000 !important;
    font-weight: 500 !important;
}

/* ✅ CAPACIDADE - MANTIDO ALINHAMENTO ORIGINAL (NÃO CENTRALIZAR) */
.capacity-dashboard-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 16px !important;
    min-width: 200px !important;
    height: auto !important;
}

.capacity-item-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    padding: 4px 0 !important;
}

.capacity-header-modern {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    width: 100% !important;
}

.capacity-label-modern {
    font-size: 12px !important;
    color: #666 !important;
    font-weight: 500 !important;
}

.capacity-stats-modern {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #000 !important;
}

.capacity-progress-modern {
    width: 100% !important;
    height: 8px !important;
    background: #E0E0E0 !important;
    border-radius: 4px !important;
    overflow: hidden !important;
}

.capacity-fill-modern {
    height: 100% !important;
    border-radius: 4px !important;
    transition: width 0.3s ease !important;
}

.capacity-fill-modern.low { background: #4CAF50 !important; }
.capacity-fill-modern.medium { background: #FF9800 !important; }
.capacity-fill-modern.high { background: #FF6B00 !important; }
.capacity-fill-modern.critical { background: #F44336 !important; }

.capacity-percentage-modern {
    font-size: 11px !important;
    color: #666 !important;
    text-align: right !important;
    font-weight: 600 !important;
}

/* ✅ RECURSOS - MANTIDO ALINHAMENTO ORIGINAL (NÃO CENTRALIZAR) */
.resources-showcase-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    min-width: 120px !important;
    height: auto !important;
}

.resource-badges-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 10px !important;
    width: 100% !important;
}

.resource-badge-modern {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 10px 12px !important;
    background: #FFE0CC !important;
    border-radius: 12px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.resource-badge-modern.vehicles {
    background: #E3F2FD !important;
    color: #2196F3 !important;
}

.resource-badge-modern.drivers {
    background: #E8F5E8 !important;
    color: #4CAF50 !important;
}

.resource-icon-modern {
    width: 24px !important;
    height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 14px !important;
    flex-shrink: 0 !important;
}

.resource-content-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
    flex: 1 !important;
}

.resource-count-modern {
    font-weight: 700 !important;
    font-size: 14px !important;
    color: inherit !important;
}

.resource-label-modern {
    font-size: 10px !important;
    color: #666 !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

/* ✅ GERENTE - CENTRALIZADO VERTICALMENTE */
.manager-card-modern {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    min-height: 60px !important;
}

.manager-avatar-modern {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    background: #FF6B00 !important;
    color: white !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: bold !important;
    font-size: 14px !important;
}

.manager-info-modern {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
}

.manager-name-modern {
    font-weight: 600 !important;
    font-size: 13px !important;
    color: #000 !important;
}

.manager-position-modern {
    font-size: 11px !important;
    color: #666 !important;
}

.empty-manager {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    color: #666 !important;
    font-size: 13px !important;
    min-height: 60px !important;
}

/* ✅ STATUS - CENTRALIZADO VERTICAL E HORIZONTALMENTE */
.status-pill-modern {
    padding: 8px 14px !important;
    border-radius: 20px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    white-space: nowrap !important;
    min-height: 36px !important;
    justify-content: center !important;
}

.status-pill-modern.active {
    background: #E8F5E8 !important;
    color: #4CAF50 !important;
}

.status-pill-modern.inactive {
    background: #FFEBEE !important;
    color: #F44336 !important;
}

/* ✅ AÇÕES - CENTRALIZADO VERTICAL E HORIZONTALMENTE */
.actions-toolbar-modern {
    display: flex !important;
    gap: 6px !important;
    justify-content: center !important;
    align-items: center !important;
    min-height: 60px !important;
}

.action-btn-modern {
    background: none !important;
    border: none !important;
    padding: 8px !important;
    border-radius: 6px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 32px !important;
    height: 32px !important;
    font-size: 14px !important;
    text-decoration: none !important;
}

.btn-view-modern {
    color: #4CAF50 !important;
    background: #E8F5E8 !important;
}

.btn-view-modern:hover {
    background: #4CAF50 !important;
    color: white !important;
}

.btn-edit-modern {
    color: #2196F3 !important;
    background: #E3F2FD !important;
}

.btn-edit-modern:hover {
    background: #2196F3 !important;
    color: white !important;
}

.btn-delete-modern {
    color: #F44336 !important;
    background: #FFEBEE !important;
}

.btn-delete-modern:hover {
    background: #F44336 !important;
    color: white !important;
}

/* ✅ CORREÇÃO CRÍTICA: ALINHAMENTO DAS CÉLULAS DA TABELA */
.bases-table-container .bases-table td {
    padding: 16px 12px !important;
}

/* ✅ APENAS STATUS E AÇÕES CENTRALIZADOS VERTICAL E HORIZONTALMENTE */
.bases-table-container .bases-table td:nth-child(7), /* Status */
.bases-table-container .bases-table td:nth-child(8) { /* Ações */
    vertical-align: middle !important;
    text-align: center !important;
}

/* ✅ BASE, LOCALIZAÇÃO E GERENTE - APENAS CENTRALIZADOS VERTICALMENTE */
.bases-table-container .bases-table td:nth-child(1), /* Base */
.bases-table-container .bases-table td:nth-child(2), /* Localização */
.bases-table-container .bases-table td:nth-child(6) { /* Gerente */
    vertical-align: middle !important;
    text-align: left !important;
}

/* ✅ CONTATO, CAPACIDADE E RECURSOS - MANTIDO ALINHAMENTO ORIGINAL (NÃO CENTRALIZAR) */
.bases-table-container .bases-table td:nth-child(3), /* Contatos */
.bases-table-container .bases-table td:nth-child(4), /* Capacidade */
.bases-table-container .bases-table td:nth-child(5) { /* Recursos */
    vertical-align: top !important;
    text-align: left !important;
}
</style>