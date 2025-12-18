<?php
// app/views/companies/list.php
$pageTitle = 'Empresas';
$currentPage = 'companies';
$pageScript = 'companies.js'; // ✅ ADICIONAR ISSO

// Carregar modelos
require_once __DIR__ . '/../../models/CompanyModel.php';

$companyModel = new CompanyModel();
$companies = $companyModel->getAll();


?>

<body>
<!-- Page Header -->
<div class="page-header">
    <div class="header-content">
        <h1>Empresas Cadastradas</h1>
        <p>Gerencie as empresas do sistema BT Log</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" id="newCompanyBtn">
            <i class="fas fa-plus"></i>
            Nova Empresa
        </button>
    </div>
</div>

<!-- Companies Table -->
<div class="content-card">
    <div class="card-header">
        <h2>Lista de Empresas</h2>
        <div class="card-actions">
            <div class="search-box">
                <input type="text" id="searchCompanies" placeholder="Buscar empresas...">
                <i class="fas fa-search"></i>
            </div>
            <button class="btn btn-secondary btn-sm" id="refreshBtn">
                <i class="fas fa-redo"></i>
                Atualizar
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table" id="companiesTable">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CNPJ</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-building"></i>
                                    <h3>Nenhuma empresa cadastrada</h3>
                                    <p>Comece cadastrando a primeira empresa no sistema.</p>
                                    <button class="btn btn-primary" id="emptyStateBtn">
                                        Cadastrar Empresa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($companies as $company): ?>
                        <tr data-company-id="<?php echo $company['id']; ?>">
                            <td>
								<div class="d-flex align-center gap-1">
									<?php 
									$logoPath = $company['logo'] ?? '';
									// Verificar se a logo existe no sistema de arquivos
									$hasLogo = !empty($logoPath) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/bt-log-transportes/' . $logoPath);
									?>
									
									<?php if ($hasLogo): ?>
										<!-- ✅ MOSTRAR IMAGEM DA LOGO -->
										<img src="/bt-log-transportes/<?php echo $logoPath; ?>?t=<?php echo time(); ?>" 
											 alt="<?php echo htmlspecialchars($company['name']); ?>" 
											 class="company-logo"
											 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
										<div class="company-logo-placeholder" style="background-color: <?php echo $company['color'] ?? '#FF6B00'; ?>; display: none;">
											<?php echo strtoupper(substr($company['name'], 0, 2)); ?>
										</div>
									<?php else: ?>
										<!-- ✅ MOSTRAR PLACEHOLDER (sem imagem) -->
										<div class="company-logo-placeholder" style="background-color: <?php echo $company['color'] ?? '#FF6B00'; ?>">
											<?php echo strtoupper(substr($company['name'], 0, 2)); ?>
										</div>
									<?php endif; ?>
									<div>
										<div style="font-weight: 600;"><?php echo htmlspecialchars($company['name']); ?></div>
										<div class="company-color" style="color: <?php echo $company['color'] ?? '#FF6B00'; ?>">
											● <?php echo $company['color'] ?? '#FF6B00'; ?>
										</div>
									</div>
								</div>
							</td>
														<td>
                                <?php echo $company['cnpj'] ?: 'Não informado'; ?>
                            </td>
                            <td>
                                <?php echo $company['email'] ?: 'Não informado'; ?>
                            </td>
                            <td>
                                <?php echo $company['phone'] ?: 'Não informado'; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $company['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $company['is_active'] ? 'Ativa' : 'Inativa'; ?>
                                </span>
                            </td>
                            <td>
								<div class="action-buttons">
									<!-- ✅ BOTÃO VISUALIZAR CORRIGIDO - SEM companiesManager -->
									<button class="btn-icon btn-view" 
											data-company-id="<?php echo $company['id']; ?>"
											title="Visualizar"
											onclick="if (typeof viewCompany === 'function') { viewCompany('<?php echo $company['id']; ?>'); } else { console.error('viewCompany não disponível'); } return false;">
										<i class="fas fa-eye"></i>
									</button>
									
									<!-- ✅ BOTÃO EDITAR CORRIGIDO - SEM companiesManager -->
									<button class="btn-icon btn-edit" 
											data-company-id="<?php echo $company['id']; ?>" 
											title="Editar"
											onclick="if (typeof editCompany === 'function') { editCompany('<?php echo $company['id']; ?>'); } else { console.error('editCompany não disponível'); } return false;">
										<i class="fas fa-edit"></i>
									</button>
									
									<!-- ✅ BOTÃO EXCLUIR CORRIGIDO - SEM companiesManager -->
									<button class="btn-icon btn-delete" 
											data-company-id="<?php echo $company['id']; ?>" 
											data-company-name="<?php echo htmlspecialchars($company['name']); ?>" 
											title="Excluir"
											onclick="if (typeof deleteCompany === 'function') { deleteCompany('<?php echo $company['id']; ?>', '<?php echo htmlspecialchars($company['name']); ?>'); } else { console.error('deleteCompany não disponível'); } return false;">
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

<!-- Modal de Empresa -->
<div class="modal" id="companyModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modalTitle">Nova Empresa</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="companyForm">
                <input type="hidden" id="companyId" name="id">
                
                <div class="form-section">
                    <h4>Informações Básicas</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome Fantasia *</label>
                            <input type="text" id="name" name="name" required placeholder="Nome comercial da empresa">
                            <div class="error-message" id="nameError"></div>
                        </div>
                        <div class="form-group">
                            <label for="razao_social">Razão Social *</label>
                            <input type="text" id="razao_social" name="razao_social" required placeholder="Razão social completa">
                            <div class="error-message" id="razao_socialError"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cnpj">CNPJ *</label>
                            <input type="text" id="cnpj" name="cnpj" required placeholder="00.000.000/0000-00" class="cnpj-mask">
                            <div class="error-message" id="cnpjError"></div>
                        </div>
                        <div class="form-group">
                            <label for="atuacao">Área de Atuação *</label>
                            <select id="atuacao" name="atuacao" required>
                                <option value="">Selecione...</option>
                                <option value="transportes">Transportes</option>
                                <option value="logistica">Logística</option>
                                <option value="cargas_gerais">Cargas Gerais</option>
                                <option value="cargas_perigosas">Cargas Perigosas</option>
                                <option value="mudancas">Mudanças</option>
                                <option value="entregas_rapidas">Entregas Rápidas</option>
                                <option value="outros">Outros</option>
                            </select>
                            <div class="error-message" id="atuacaoError"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Inscrição Estadual</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="isento_ie" name="isento_ie" value="1">
                                <span class="checkmark"></span>
                                Empresa isenta de Inscrição Estadual
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row" id="ie-field">
                        <div class="form-group">
                            <label for="inscricao_estadual">Inscrição Estadual</label>
                            <input type="text" id="inscricao_estadual" name="inscricao_estadual" placeholder="Número da IE">
                            <div class="error-message" id="inscricao_estadualError"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Contato</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="empresa@exemplo.com">
                            <div class="error-message" id="emailError"></div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefone Principal</label>
                            <input type="text" id="phone" name="phone" placeholder="(11) 99999-9999" class="phone-mask">
                            <div class="error-message" id="phoneError"></div>
                        </div>
                        <div class="form-group">
                            <label for="phone2">Telefone Secundário</label>
                            <input type="text" id="phone2" name="phone2" placeholder="(11) 99999-9999" class="phone-mask">
                            <div class="error-message" id="phone2Error"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Endereço</h4>
                    <div class="form-group">
                        <label for="address">Endereço Completo</label>
                        <textarea id="address" name="address" rows="3" placeholder="Rua, número, bairro, cidade, estado, CEP"></textarea>
                        <div class="error-message" id="addressError"></div>
                    </div>
                </div>

               <div class="form-section">
                    <h4>Personalização</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="color">Cor da Empresa</label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="color" id="color" name="color" value="#FF6B00">
                                <span id="colorValue" style="font-weight: 600; color: #FF6B00;">#FF6B00</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="logo">Logo</label>
                            <input type="file" id="logo" name="logo" accept="image/*">
                            <div id="fileInfo" style="font-size: 0.8rem; color: var(--color-gray); margin-top: 0.5rem;">
                                Nenhum arquivo selecionado
                            </div>
                            
                            <!-- Preview da Logo -->
                            <div class="logo-preview-container">
                                <div class="logo-preview" id="logoPreview">
                                    <div class="company-logo-large-placeholder" style="background-color: #FF6B00">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="logo-preview-text">Logo será exibida aqui</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <span class="checkmark"></span>
                            Empresa ativa
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelButton">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="saveButton">
                <span class="btn-text">Salvar Empresa</span>
                <div class="btn-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Salvando...</span>
                </div>
            </button>
        </div>
    </div>
</div>

<script>
// ✅ INICIALIZAÇÃO DE FALLBACK - GARANTE QUE O MODAL FUNCIONE
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Inicializando fallback para botões...');
    
    // Aguardar o companiesManager carregar
    const checkManager = setInterval(() => {
        if (window.companiesManager && window.companiesManager.viewCompany) {
            console.log('✅ CompaniesManager carregado - configurando fallbacks');
            clearInterval(checkManager);
            
            // Configurar fallbacks manuais
            setupButtonFallbacks();
        }
    }, 100);
    
    function setupButtonFallbacks() {
        // Botão Visualizar
        document.querySelectorAll('.view-company-btn').forEach(btn => {
            const originalOnClick = btn.onclick;
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🎯 BOTÃO VIEW CLICADO VIA FALLBACK');
                
                const companyId = btn.getAttribute('data-company-id');
                if (companyId && window.companiesManager) {
                    window.companiesManager.viewCompany(companyId);
                }
                
                if (originalOnClick) originalOnClick.call(this, e);
            };
        });
        
        // Botão Editar
        document.querySelectorAll('.edit-company-btn').forEach(btn => {
            const originalOnClick = btn.onclick;
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const companyId = btn.getAttribute('data-company-id');
                if (companyId && window.companiesManager) {
                    window.companiesManager.editCompany(companyId);
                }
                
                if (originalOnClick) originalOnClick.call(this, e);
            };
        });
        
        // Botão Excluir
        document.querySelectorAll('.delete-company-btn').forEach(btn => {
            const originalOnClick = btn.onclick;
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const companyId = btn.getAttribute('data-company-id');
                const companyName = btn.getAttribute('data-company-name');
                if (companyId && window.companiesManager) {
                    window.companiesManager.deleteCompany(companyId, companyName);
                }
                
                if (originalOnClick) originalOnClick.call(this, e);
            };
        });
        
        console.log('✅ Fallbacks configurados com sucesso!');
    }
});
</script>

</body>
<?php include __DIR__ . '/../layouts/footer.php'; ?>