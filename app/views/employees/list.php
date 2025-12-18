<?php
// app/views/employees/list.php
$pageTitle = 'Funcionários';
$pageScript = 'employees.js';

// Carregar modelos
require_once __DIR__ . '/../../models/EmployeeModel.php';
require_once __DIR__ . '/../../models/CompanyModel.php';

$employeeModel = new EmployeeModel();
$companyModel = new CompanyModel();

// Obter parâmetros de filtro
$companyFilter = $_GET['company'] ?? null;
$employees = $employeeModel->getAll($companyFilter);
$companies = $companyModel->getForDropdown();

include __DIR__ . '/../layouts/header.php';
?>
<body>
<div class="page-header">
    <div class="header-content">
        <h1>Funcionários</h1>
        <p>Gerencie os funcionários do sistema BT Log</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="employeesManager.openEmployeeForm()">
            <i class="fas fa-user-plus"></i>
            Novo Funcionário
        </button>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Lista de Funcionários</h2>
        <div class="card-actions">
            <div class="filter-group">
                <select id="companyFilter" class="header-select" onchange="employeesManager.filterByCompany(this.value)">
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
                <input type="text" id="searchEmployees" placeholder="Buscar funcionários...">
                <i class="fas fa-search"></i>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="employeesManager.refreshEmployees()">
                <i class="fas fa-redo"></i>
                Atualizar
            </button>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table" id="employeesTable">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Empresa</th>
                        <th>CPF</th>
                        <th>Cargo</th>
                        <th>Salário</th>
                        <th>Motorista</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
				<tbody>
					<?php if (empty($employees)): ?>
					<!-- ... empty state ... -->
					<?php else: ?>
						<?php foreach ($employees as $employee): ?>
						<?php 
						$age = $employeeModel->calculateAge($employee['birth_date']);
						$formattedCPF = $employeeModel->formatCPF($employee['cpf']);
						$formattedSalary = $employeeModel->formatSalary($employee['salary']);
						?>
						<tr data-employee-id="<?php echo $employee['id']; ?>">
							<td>
								<div class="employee-info">
									<!-- ✅ SOLUÇÃO DEFINITIVA: SEMPRE USAR AVATAR NA LISTA -->
									<!-- Fotos apenas no modal para melhor performance -->
									<div class="employee-avatar" style="background-color: <?php echo $employee['company_color'] ?? '#FF6B00'; ?>">
										<?php echo strtoupper(substr($employee['name'] ?? '', 0, 2)); ?>
									</div>
									<div>
										<strong><?php echo htmlspecialchars($employee['name'] ?? ''); ?></strong>
										<?php if ($age): ?>
										<div class="employee-position"><?php echo $age; ?> anos</div>
										<?php endif; ?>
									</div>
								</div>
							</td>
							<td>
								<span style="color: <?php echo $employee['company_color'] ?? '#FF6B00'; ?>">
									<?php echo htmlspecialchars($employee['company_name'] ?? ''); ?>
								</span>
							</td>
							<td><?php echo $formattedCPF ?: 'Não informado'; ?></td>
							<td><?php echo htmlspecialchars($employee['position'] ?? ''); ?></td>
							<td class="salary-value"><?php echo $formattedSalary; ?></td>
							<td>
								<span class="status-badge <?php echo $employee['is_driver'] ? 'driver' : 'non-driver'; ?>">
									<?php echo $employee['is_driver'] ? 'Sim' : 'Não'; ?>
								</span>
							</td>
							<td>
								<span class="status-badge <?php echo $employee['is_active'] ? 'active' : 'inactive'; ?>">
									<?php echo $employee['is_active'] ? 'Ativo' : 'Inativo'; ?>
								</span>
							</td>
							<td>
								<div class="action-buttons">
									<button class="btn-icon btn-edit employee-edit-btn" 
											data-employee-id="<?php echo $employee['id']; ?>"
											title="Editar">
										<i class="fas fa-edit"></i>
									</button>
									<button class="btn-icon btn-view employee-view-btn"
											data-employee-id="<?php echo $employee['id']; ?>"
											title="Visualizar">
										<i class="fas fa-eye"></i>
									</button>
									<button class="btn-icon btn-delete employee-delete-btn"
											data-employee-id="<?php echo $employee['id']; ?>"
											data-employee-name="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>"
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


<!-- Modal de Funcionário -->
<div class="modal" id="employeeModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modalEmployeeTitle">Novo Funcionário</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="employeeForm" enctype="multipart/form-data">
                <input type="hidden" id="employeeId" name="id">
                
                <div class="form-section">
                    <h4>Informações Básicas</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_id">Empresa *</label>
                            <select id="company_id" name="company_id" required>
                                <option value="">Selecione uma empresa</option>
                                <?php
                                // ✅ CARREGAMENTO DIRETO VIA PHP - FUNCIONANDO
                                $companyModel = new CompanyModel();
                                $companies = $companyModel->getForDropdown();
                                foreach ($companies as $company): 
                                ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Nome Completo *</label>
                            <input type="text" id="name" name="name" required placeholder="Nome completo do funcionário">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="position">Cargo *</label>
                            <input type="text" id="position" name="position" required placeholder="Cargo/função">
                        </div>
                        
                        <div class="form-group">
                            <label for="birth_date">Data de Nascimento</label>
                            <input type="date" id="birth_date" name="birth_date">
                        </div>
                    </div>
                </div>

                <!-- Seção Foto -->
                <div class="form-section">
					<h4>Foto do Funcionário</h4>
					<div class="form-row">
						<div class="form-group">
							<label for="employee_photo">Foto</label>
							<input type="file" id="employee_photo" name="employee_photo" accept="image/*">
							<div id="photoFileInfo" style="font-size: 0.8rem; color: var(--color-gray); margin-top: 0.5rem;">
								Nenhum arquivo selecionado
							</div>
							
							<div class="photo-preview-container">
								<div class="photo-preview" id="employeePhotoPreview">
									<!-- ✅ CORREÇÃO: Placeholder para quando não há foto -->
									<div class="employee-photo-placeholder-large">
										<i class="fas fa-user"></i>
									</div>
									<!-- ✅ CORREÇÃO: Imagem da foto (inicialmente oculta) -->
									<img id="photoPreview" style="display: none; width: 120px; height: 120px; border-radius: 8px; object-fit: cover; border: 2px solid var(--color-gray-light);">
									<div class="photo-preview-text">Foto será exibida aqui</div>
								</div>
							</div>
						</div>
					</div>
				</div>

                <!-- Seção Contato -->
                <div class="form-section">
                    <h4>Contato</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="funcionario@empresa.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefone</label>
                            <input type="tel" id="phone" name="phone" placeholder="(11) 99999-9999" class="phone-mask">
                        </div>
                    </div>
                </div>

                <!-- Seção Endereço -->
                <div class="form-section">
                    <h4>Endereço</h4>
                    <div class="form-group">
                        <label for="address">Endereço Completo</label>
                        <textarea id="address" name="address" rows="3" placeholder="Rua, número, bairro, cidade, estado, CEP"></textarea>
                    </div>
                </div>

                <!-- Seção Documentação Pessoal -->
                <div class="form-section clt-info">
                    <h4>Documentação Pessoal</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" class="cpf-mask">
                        </div>
                        
                        <div class="form-group">
                            <label for="rg">RG</label>
                            <input type="text" id="rg" name="rg" placeholder="00.000.000-0" class="rg-mask">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ctps">CTPS</label>
                            <input type="text" id="ctps" name="ctps" placeholder="Número da Carteira de Trabalho">
                        </div>
                        
                        <div class="form-group">
                            <label for="pis_pasep">PIS/PASEP</label>
                            <input type="text" id="pis_pasep" name="pis_pasep" placeholder="000.00000.00-0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="titulo_eleitor">Título de Eleitor</label>
                            <input type="text" id="titulo_eleitor" name="titulo_eleitor" placeholder="Número do título">
                        </div>
                        
                        <div class="form-group">
                            <label for="reservista">Certificado de Reservista</label>
                            <input type="text" id="reservista" name="reservista" placeholder="Número da reservista">
                        </div>
                    </div>
                </div>

                <!-- Seção Informações Pessoais -->
                <div class="form-section">
                    <h4>Informações Pessoais</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_mae">Nome da Mãe</label>
                            <input type="text" id="nome_mae" name="nome_mae" placeholder="Nome completo da mãe">
                        </div>
                        
                        <div class="form-group">
                            <label for="nome_pai">Nome do Pai</label>
                            <input type="text" id="nome_pai" name="nome_pai" placeholder="Nome completo do pai">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="naturalidade">Naturalidade</label>
                            <input type="text" id="naturalidade" name="naturalidade" placeholder="Cidade de nascimento">
                        </div>
                        
                        <div class="form-group">
                            <label for="nacionalidade">Nacionalidade</label>
                            <input type="text" id="nacionalidade" name="nacionalidade" placeholder="Nacionalidade" value="Brasileira">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="estado_civil">Estado Civil</label>
                            <select id="estado_civil" name="estado_civil">
                                <option value="">Selecione...</option>
                                <option value="solteiro">Solteiro(a)</option>
                                <option value="casado">Casado(a)</option>
                                <option value="divorciado">Divorciado(a)</option>
                                <option value="viuvo">Viúvo(a)</option>
                                <option value="uniao_estavel">União Estável</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="grau_instrucao">Grau de Instrução</label>
                            <select id="grau_instrucao" name="grau_instrucao">
                                <option value="">Selecione...</option>
                                <option value="fundamental">Fundamental</option>
                                <option value="medio">Médio</option>
                                <option value="superior">Superior</option>
                                <option value="pos_graduacao">Pós-Graduação</option>
                                <option value="mestrado">Mestrado</option>
                                <option value="doutorado">Doutorado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_sanguineo">Tipo Sanguíneo</label>
                            <select id="tipo_sanguineo" name="tipo_sanguineo">
                                <option value="">Selecione...</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seção Remuneração -->
                <div class="form-section">
                    <h4>Remuneração</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary">Salário Base *</label>
                            <div class="currency-input">
                                
                                <input type="number" id="salary" name="salary" required step="0.01" min="0" placeholder="0,00">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="commission_rate">Comissão (%)</label>
                            <div class="percentage-input">
                                <input type="number" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100" placeholder="0,00" value="0">
                                <span>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Descontos Obrigatórios -->
                <div class="form-section">
                    <h4>Descontos Obrigatórios</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inss">INSS</label>
                            <div class="currency-input">
                                
                                <input type="number" id="inss" name="inss" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="irrf">IRRF</label>
                            <div class="currency-input">
                                
                                <input type="number" id="irrf" name="irrf" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="fgts">FGTS</label>
                            <div class="currency-input">
                                
                                <input type="number" id="fgts" name="fgts" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="vale_transporte">Vale Transporte</label>
                            <div class="currency-input">
                                
                                <input type="number" id="vale_transporte" name="vale_transporte" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="vale_refeicao">Vale Refeição</label>
                            <div class="currency-input">
                                
                                <input type="number" id="vale_refeicao" name="vale_refeicao" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="plano_saude">Plano de Saúde</label>
                            <div class="currency-input">
                                
                                <input type="number" id="plano_saude" name="plano_saude" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="outros_descontos">Outros Descontos</label>
                            <div class="currency-input">
                                
                                <input type="number" id="outros_descontos" name="outros_descontos" step="0.01" min="0" placeholder="0,00" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Resumo Financeiro -->
                    <div class="salary-summary">
                        <h5>Resumo Financeiro</h5>
                        <div class="summary-item">
                            <span>Total de Descontos:</span>
                            <span id="totalDescontos">R$ 0,00</span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>Salário Líquido:</span>
                            <span id="salarioLiquido">R$ 0,00</span>
                        </div>
                    </div>
                </div>

                <!-- Seção Configurações -->
                <div class="form-section">
                    <h4>Configurações</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is_driver" name="is_driver" value="1">
                                <span class="checkmark"></span>
                                É motorista?
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <span class="checkmark"></span>
                                Funcionário ativo
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelEmployeeButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveEmployeeButton">
                <span class="btn-text">Salvar Funcionário</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>


</body>
<?php
include __DIR__ . '/../layouts/footer.php';
?>