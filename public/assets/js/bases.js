// public/assets/js/bases.js - VERSÃO CORRIGIDA

// ✅ VERIFICAÇÃO PARA EVITAR DECLARAÇÃO DUPLA
if (typeof window.BasesManager === 'undefined') {
    
    class BasesManager {
        constructor() {
            this.currentBases = [];
            this.filteredBases = [];
            this.currentFilters = {
                status: 'all',
                company: 'all',
                capacity: 'all'
            };
            
            this.selectedEmployees = [];
            this.selectedVehicles = [];
            this.currentBaseId = null;
            this.isNewBase = false;
            
            console.log('🚀 Criando nova instância do BasesManager');
            this.init();
        }

        init() {
            console.log('🎯 Inicializando Sistema de Bases');
            this.loadBases();
            this.setupEventListeners();
            this.setupModalHandlers();
        }

        // ✅ CARREGAR BASES
        async loadBases() {
			try {
				this.showLoading();
				
				// ✅ CORREÇÃO: Adicionar timestamp para evitar cache
				const timestamp = new Date().getTime();
				const response = await fetch(`/bt-log-transportes/public/api/bases.php?action=getAll&t=${timestamp}`);
				const data = await response.json();
				
				console.log('📦 Dados recebidos da API:', data);
				
				if (data.success) {
					this.currentBases = data.bases || [];
					this.filteredBases = [...this.currentBases];
					this.renderBases();
					this.updateStats();
					this.hideLoading();
					console.log(`✅ ${this.currentBases.length} bases carregadas`);
				} else {
					throw new Error(data.message || 'Erro ao carregar bases');
				}
			} catch (error) {
				console.error('❌ Erro ao carregar bases:', error);
				this.showError('Erro ao carregar bases: ' + error.message);
				this.hideLoading();
			}
		}

        // ✅ CONFIGURAR EVENT LISTENERS
        setupEventListeners() {
            // Filtros
            document.getElementById('filterStatus')?.addEventListener('change', (e) => {
                this.currentFilters.status = e.target.value;
                this.applyFilters();
            });

            document.getElementById('filterCompany')?.addEventListener('change', (e) => {
                this.currentFilters.company = e.target.value;
                this.applyFilters();
            });

            // Busca
            const searchInput = document.getElementById('searchBases');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.searchBases(e.target.value);
                });
            }

            // ✅ BOTÃO NOVA BASE
            const newBaseBtn = document.getElementById('newBaseBtn');
            if (newBaseBtn) {
                newBaseBtn.addEventListener('click', () => {
                    console.log('🔄 Clicou no botão Nova Base');
                    this.openBaseModal();
                });
            } else {
                console.error('❌ Botão newBaseBtn não encontrado');
            }

            // Limpar filtros
            document.getElementById('clearFilters')?.addEventListener('click', () => {
                this.clearFilters();
            });
        }

        // ✅ CONFIGURAR MODAL
        setupModalHandlers() {
            console.log('🔧 Configurando handlers do modal');
            
            // Fechar modal com botão X
            const closeBtn = document.querySelector('#baseModal .btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeBaseModal();
                });
            }

            // Fechar modal com botão Cancelar
            const cancelBtn = document.querySelector('#baseModal .btn-secondary');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    this.closeBaseModal();
                });
            }

            // ✅ SUBMIT DO FORMULÁRIO - CORREÇÃO CRÍTICA
            const baseForm = document.getElementById('baseForm');
            if (baseForm) {
                baseForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    console.log('📝 Submetendo formulário');
                    this.saveBase();
                });
            }

            // Fechar modal clicando fora
            const baseModal = document.getElementById('baseModal');
            if (baseModal) {
                baseModal.addEventListener('click', (e) => {
                    if (e.target === baseModal) {
                        this.closeBaseModal();
                    }
                });
            }
        }

        // ✅ ABRIR MODAL
        openBaseModal(baseId = null) {
            console.log('📝 Abrindo modal de base, ID:', baseId);
            
            const modalElement = document.getElementById('baseModal');
            if (!modalElement) {
                console.error('❌ Elemento modal não encontrado');
                return;
            }

            this.currentBaseId = baseId;
            this.isNewBase = !baseId;

            if (baseId) {
                this.loadBaseData(baseId);
            } else {
                this.resetBaseForm();
                // Para nova base, inicializar listas vazias
                this.renderEmployeesList([]);
                this.renderVehiclesList([]);
            }
            
            // ✅ MOSTRAR MODAL
            modalElement.style.display = 'block';
            modalElement.style.opacity = '1';
            modalElement.style.visibility = 'visible';
            
            // Adicionar classe para backdrop
            document.body.classList.add('modal-open');
            
            console.log('✅ Modal aberto com sucesso');
        }

        // ✅ FECHAR MODAL
        closeBaseModal() {
            console.log('🔒 Fechando modal');
            
            const modalElement = document.getElementById('baseModal');
            if (modalElement) {
                modalElement.style.display = 'none';
                modalElement.style.opacity = '0';
                modalElement.style.visibility = 'hidden';
                
                // Remover classe do backdrop
                document.body.classList.remove('modal-open');
                
                this.resetBaseForm();
                this.clearResourceSelections();
            }
        }

        // ✅ CARREGAR DADOS DA BASE
        async loadBaseData(baseId) {
            try {
                console.log('📥 Carregando dados da base:', baseId);
                const response = await fetch(`/bt-log-transportes/public/api/bases.php?action=get&id=${baseId}`);
                const data = await response.json();
                
                if (data.success) {
                    this.populateBaseForm(data.data || data.base);
                    this.loadBaseResources(baseId);
                    console.log('✅ Dados da base carregados');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('❌ Erro ao carregar base:', error);
                this.showError('Erro ao carregar dados da base');
            }
        }

        // ✅ CARREGAR RECURSOS DA BASE
        async loadBaseResources(baseId) {
            try {
                // Carregar funcionários
                const employeesResponse = await fetch(`/bt-log-transportes/public/api/bases.php?action=get_employees&base_id=${baseId}`);
                const employeesData = await employeesResponse.json();
                
                if (employeesData.success) {
                    this.renderEmployeesList(employeesData.data);
                }

                // Carregar veículos
                const vehiclesResponse = await fetch(`/bt-log-transportes/public/api/bases.php?action=get_vehicles&base_id=${baseId}`);
                const vehiclesData = await vehiclesResponse.json();
                
                if (vehiclesData.success) {
                    this.renderVehiclesList(vehiclesData.data);
                }
            } catch (error) {
                console.error('❌ Erro ao carregar recursos:', error);
            }
        }

        // ✅ SALVAR BASE - CORREÇÃO CRÍTICA
        async saveBase() {
			try {
				console.log('💾 Salvando base...');
				
				const formData = new FormData(document.getElementById('baseForm'));
				
				// ✅ CORREÇÃO: Adicionar funcionários e veículos selecionados
				formData.append('selected_employees', JSON.stringify(this.selectedEmployees));
				formData.append('selected_vehicles', JSON.stringify(this.selectedVehicles));
				
				console.log('👥 Funcionários selecionados:', this.selectedEmployees);
				console.log('🚚 Veículos selecionados:', this.selectedVehicles);

				const response = await fetch('/bt-log-transportes/public/api/bases.php?action=save', {
					method: 'POST',
					body: formData
				});

				const data = await response.json();
				
				if (data.success) {
					this.showSuccess('Base salva com sucesso!');
					
					// Limpar seleções após salvar
					this.selectedEmployees = [];
					this.selectedVehicles = [];
					
					await this.loadBases();
					this.closeBaseModal();
					
				} else {
					throw new Error(data.message || 'Erro desconhecido ao salvar base');
				}
			} catch (error) {
				console.error('❌ Erro ao salvar base:', error);
				this.showError('Erro ao salvar base: ' + error.message);
			}
		}
		
		
		// ✅ NOVO MÉTODO: Visualizar base
		viewBase(baseId) {
			console.log('👁️ Visualizando base:', baseId);
			this.openBaseModal(baseId, true); // true = modo visualização
		}

		// ✅ ATUALIZAR: openBaseModal para suportar modo visualização
		openBaseModal(baseId = null, viewMode = false) {
			console.log('📝 Abrindo modal de base, ID:', baseId, 'Modo visualização:', viewMode);
			
			const modalElement = document.getElementById('baseModal');
			if (!modalElement) {
				console.error('❌ Elemento modal não encontrado');
				return;
			}

			this.currentBaseId = baseId;
			this.isNewBase = !baseId;
			this.viewMode = viewMode;

			if (baseId) {
				this.loadBaseData(baseId);
			} else {
				this.resetBaseForm();
				this.renderEmployeesList([]);
				this.renderVehiclesList([]);
			}
			
			// Configurar modo visualização
			this.setViewMode(viewMode);
			
			modalElement.style.display = 'block';
			modalElement.style.opacity = '1';
			modalElement.style.visibility = 'visible';
			
			document.body.classList.add('modal-open');
		}

		// ✅ NOVO MÉTODO: Configurar modo visualização
		setViewMode(viewMode) {
			const form = document.getElementById('baseForm');
			const inputs = form.querySelectorAll('input, select, textarea');
			const submitBtn = form.querySelector('button[type="submit"]');
			const modalTitle = document.getElementById('baseModalLabel');
			
			if (viewMode) {
				// Modo visualização - desabilitar todos os inputs
				inputs.forEach(input => input.disabled = true);
				if (submitBtn) submitBtn.style.display = 'none';
				if (modalTitle) modalTitle.textContent = 'Visualizar Base';
				
				// Esconder botões de ação nos recursos
				document.querySelectorAll('.btn-add-resource, .btn-remove-resource').forEach(btn => {
					btn.style.display = 'none';
				});
			} else {
				// Modo edição - habilitar todos os inputs
				inputs.forEach(input => input.disabled = false);
				if (submitBtn) submitBtn.style.display = 'flex';
				if (modalTitle) modalTitle.textContent = this.isNewBase ? 'Nova Base' : 'Editar Base';
				
				// Mostrar botões de ação nos recursos
				document.querySelectorAll('.btn-add-resource, .btn-remove-resource').forEach(btn => {
					btn.style.display = 'flex';
				});
			}
		}

        // ✅ VINCULAR RECURSOS SELECIONADOS APÓS SALVAR BASE
        async linkSelectedResources() {
            // Vincular funcionários selecionados
            if (this.selectedEmployees.length > 0) {
                for (const employeeId of this.selectedEmployees) {
                    await this.assignEmployeeToBase(employeeId, this.currentBaseId);
                }
                this.clearEmployeeSelections();
            }

            // Vincular veículos selecionados
            if (this.selectedVehicles.length > 0) {
                for (const vehicleId of this.selectedVehicles) {
                    await this.assignVehicleToBase(vehicleId, this.currentBaseId);
                }
                this.clearVehicleSelections();
            }

            // Recarregar recursos após vincular
            if (this.currentBaseId) {
                this.loadBaseResources(this.currentBaseId);
            }
        }

        // ✅ ABRIR MODAL DE SELEÇÃO DE FUNCIONÁRIOS
        openEmployeeSelector() {
            console.log('👥 Abrindo seletor de funcionários...');
            
            const modal = document.getElementById('employeeModal');
            if (modal) {
                modal.style.display = 'block';
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                this.loadAvailableEmployees();
            } else {
                console.error('❌ Modal de funcionários não encontrado');
            }
        }

        // ✅ ABRIR MODAL DE SELEÇÃO DE VEÍCULOS
        openVehicleSelector() {
            console.log('🚚 Abrindo seletor de veículos...');
            
            const modal = document.getElementById('vehicleModal');
            if (modal) {
                modal.style.display = 'block';
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                this.loadAvailableVehicles();
            } else {
                console.error('❌ Modal de veículos não encontrado');
            }
        }

        // ✅ CARREGAR FUNCIONÁRIOS DISPONÍVEIS
        async loadAvailableEmployees() {
			try {
				const companyId = document.querySelector('[name="company_id"]').value;
				if (!companyId) {
					this.showError('Selecione uma empresa primeiro');
					return;
				}

				const response = await fetch(`/bt-log-transportes/public/api/bases.php?action=get_available_employees&company_id=${companyId}`);
				const data = await response.json();
				
				if (data.success) {
					this.renderEmployeeSelectionList(data.data);
				} else {
					this.showError('Erro ao carregar funcionários disponíveis');
				}
			} catch (error) {
				console.error('❌ Erro ao carregar funcionários:', error);
				this.showError('Erro ao carregar funcionários disponíveis');
			}
		}

        // ✅ CARREGAR VEÍCULOS DISPONÍVEIS
        async loadAvailableVehicles() {
			try {
				const companyId = document.querySelector('[name="company_id"]').value;
				if (!companyId) {
					this.showError('Selecione uma empresa primeiro');
					return;
				}

				const response = await fetch(`/bt-log-transportes/public/api/bases.php?action=get_available_vehicles&company_id=${companyId}`);
				const data = await response.json();
				
				if (data.success) {
					this.renderVehicleSelectionList(data.data);
				} else {
					this.showError('Erro ao carregar veículos disponíveis');
				}
			} catch (error) {
				console.error('❌ Erro ao carregar veículos:', error);
				this.showError('Erro ao carregar veículos disponíveis');
			}
		}

        // ✅ RENDERIZAR LISTA DE SELEÇÃO DE FUNCIONÁRIOS
        renderEmployeeSelectionList(employees) {
            const container = document.getElementById('employeeSelectionList');
            if (!container) return;

            if (!employees || employees.length === 0) {
                container.innerHTML = '<div class="empty-state">Nenhum funcionário disponível</div>';
                return;
            }

            container.innerHTML = employees.map(employee => `
                <div class="resource-item" data-employee-id="${employee.id}">
                    <div class="resource-info">
                        <div class="resource-avatar">
                            ${employee.name ? employee.name.charAt(0).toUpperCase() : 'F'}
                        </div>
                        <div class="resource-details">
                            <h6>${this.escapeHtml(employee.name)}</h6>
                            <p>${this.escapeHtml(employee.position || 'Funcionário')}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-add-resource" onclick="window.basesManager.toggleEmployeeSelection(${employee.id})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `).join('');
        }

        // ✅ RENDERIZAR LISTA DE SELEÇÃO DE VEÍCULOS
        renderVehicleSelectionList(vehicles) {
            const container = document.getElementById('vehicleSelectionList');
            if (!container) return;

            if (!vehicles || vehicles.length === 0) {
                container.innerHTML = '<div class="empty-state">Nenhum veículo disponível</div>';
                return;
            }

            container.innerHTML = vehicles.map(vehicle => `
                <div class="resource-item" data-vehicle-id="${vehicle.id}">
                    <div class="resource-info">
                        <div class="resource-avatar">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="resource-details">
                            <h6>${this.escapeHtml(vehicle.plate)}</h6>
                            <p>${this.escapeHtml(vehicle.brand || '')} ${this.escapeHtml(vehicle.model || '')}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-add-resource" onclick="window.basesManager.toggleVehicleSelection(${vehicle.id})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `).join('');
        }

        // ✅ TOGGLE SELEÇÃO DE FUNCIONÁRIO
        toggleEmployeeSelection(employeeId) {
            const index = this.selectedEmployees.indexOf(employeeId);
            const button = document.querySelector(`[data-employee-id="${employeeId}"] .btn-add-resource`);
            
            if (index === -1) {
                this.selectedEmployees.push(employeeId);
                if (button) {
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    button.style.background = '#4CAF50';
                    button.style.color = 'white';
                }
                this.showSuccess(`Funcionário selecionado! Será vinculado após salvar a base.`);
            } else {
                this.selectedEmployees.splice(index, 1);
                if (button) {
                    button.innerHTML = '<i class="fas fa-plus"></i>';
                    button.style.background = '';
                    button.style.color = '';
                }
            }
        }

        // ✅ TOGGLE SELEÇÃO DE VEÍCULO
        toggleVehicleSelection(vehicleId) {
            const index = this.selectedVehicles.indexOf(vehicleId);
            const button = document.querySelector(`[data-vehicle-id="${vehicleId}"] .btn-add-resource`);
            
            if (index === -1) {
                this.selectedVehicles.push(vehicleId);
                if (button) {
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    button.style.background = '#4CAF50';
                    button.style.color = 'white';
                }
                this.showSuccess(`Veículo selecionado! Será vinculado após salvar a base.`);
            } else {
                this.selectedVehicles.splice(index, 1);
                if (button) {
                    button.innerHTML = '<i class="fas fa-plus"></i>';
                    button.style.background = '';
                    button.style.color = '';
                }
            }
        }

        // ✅ CONFIRMAR SELEÇÃO DE FUNCIONÁRIOS
        async confirmEmployeeSelection() {
            if (this.selectedEmployees.length === 0) {
                this.showInfo('Nenhum funcionário selecionado. Os funcionários serão vinculados quando você salvar a base.');
                this.closeEmployeeModal();
                return;
            }

            this.showSuccess(`${this.selectedEmployees.length} funcionário(s) selecionado(s). Eles serão vinculados quando você salvar a base.`);
            this.closeEmployeeModal();
        }

        // ✅ CONFIRMAR SELEÇÃO DE VEÍCULOS
        async confirmVehicleSelection() {
            if (this.selectedVehicles.length === 0) {
                this.showInfo('Nenhum veículo selecionado. Os veículos serão vinculados quando você salvar a base.');
                this.closeVehicleModal();
                return;
            }

            this.showSuccess(`${this.selectedVehicles.length} veículo(s) selecionado(s). Eles serão vinculados quando você salvar a base.`);
            this.closeVehicleModal();
        }

        // ✅ VINCULAR FUNCIONÁRIO À BASE
        async assignEmployeeToBase(employeeId, baseId) {
            try {
                const response = await fetch('/bt-log-transportes/public/api/bases.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=assign_employee&employee_id=${employeeId}&base_id=${baseId}`
                });

                const data = await response.json();
                return data.success;
            } catch (error) {
                console.error('❌ Erro ao vincular funcionário:', error);
                return false;
            }
        }

        // ✅ VINCULAR VEÍCULO À BASE
        async assignVehicleToBase(vehicleId, baseId) {
            try {
                const response = await fetch('/bt-log-transportes/public/api/bases.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=assign_vehicle&vehicle_id=${vehicleId}&base_id=${baseId}`
                });

                const data = await response.json();
                return data.success;
            } catch (error) {
                console.error('❌ Erro ao vincular veículo:', error);
                return false;
            }
        }

        // ✅ REMOVER FUNCIONÁRIO DA BASE
        async removeEmployeeFromBase(employeeId) {
            if (!confirm('Tem certeza que deseja remover este funcionário da base?')) {
                return;
            }

            try {
                const response = await fetch('/bt-log-transportes/public/api/bases.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_assignment&entity_type=employee&entity_id=${employeeId}`
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess('Funcionário removido da base com sucesso!');
                    this.loadBaseResources(this.currentBaseId);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('❌ Erro ao remover funcionário:', error);
                this.showError('Erro ao remover funcionário da base');
            }
        }

        // ✅ REMOVER VEÍCULO DA BASE
        async removeVehicleFromBase(vehicleId) {
            if (!confirm('Tem certeza que deseja remover este veículo da base?')) {
                return;
            }

            try {
                const response = await fetch('/bt-log-transportes/public/api/bases.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_assignment&entity_type=vehicle&entity_id=${vehicleId}`
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess('Veículo removido da base com sucesso!');
                    this.loadBaseResources(this.currentBaseId);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('❌ Erro ao remover veículo:', error);
                this.showError('Erro ao remover veículo da base');
            }
        }

        // ✅ FECHAR MODAL DE FUNCIONÁRIOS
        closeEmployeeModal() {
            const modal = document.getElementById('employeeModal');
            if (modal) {
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.visibility = 'hidden';
            }
        }

        // ✅ FECHAR MODAL DE VEÍCULOS
        closeVehicleModal() {
            const modal = document.getElementById('vehicleModal');
            if (modal) {
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.visibility = 'hidden';
            }
        }

        // ✅ LIMPAR SELEÇÕES
        clearEmployeeSelections() {
            this.selectedEmployees = [];
            const buttons = document.querySelectorAll('#employeeSelectionList .btn-add-resource');
            buttons.forEach(button => {
                button.innerHTML = '<i class="fas fa-plus"></i>';
                button.style.background = '';
                button.style.color = '';
            });
        }

        clearVehicleSelections() {
            this.selectedVehicles = [];
            const buttons = document.querySelectorAll('#vehicleSelectionList .btn-add-resource');
            buttons.forEach(button => {
                button.innerHTML = '<i class="fas fa-plus"></i>';
                button.style.background = '';
                button.style.color = '';
            });
        }

        clearResourceSelections() {
            this.clearEmployeeSelections();
            this.clearVehicleSelections();
            this.currentBaseId = null;
            this.isNewBase = false;
        }
		


        // ✅ APLICAR FILTROS
        applyFilters() {
            this.filteredBases = this.currentBases.filter(base => {
                // Filtro por status
                if (this.currentFilters.status !== 'all') {
                    if (this.currentFilters.status === 'active' && !base.is_active) return false;
                    if (this.currentFilters.status === 'inactive' && base.is_active) return false;
                }

                // Filtro por empresa
                if (this.currentFilters.company !== 'all' && base.company_id != this.currentFilters.company) {
                    return false;
                }

                return true;
            });

            this.renderBases();
            this.updateStats();
        }

        // ✅ BUSCAR BASES
        searchBases(searchTerm) {
            if (!searchTerm.trim()) {
                this.filteredBases = [...this.currentBases];
            } else {
                const term = searchTerm.toLowerCase();
                this.filteredBases = this.currentBases.filter(base => 
                    base.name.toLowerCase().includes(term) ||
                    base.city?.toLowerCase().includes(term) ||
                    base.email?.toLowerCase().includes(term)
                );
            }
            
            this.renderBases();
            this.updateStats();
        }

        // ✅ LIMPAR FILTROS
        clearFilters() {
            const statusFilter = document.getElementById('filterStatus');
            const companyFilter = document.getElementById('filterCompany');
            const searchInput = document.getElementById('searchBases');
            
            if (statusFilter) statusFilter.value = 'all';
            if (companyFilter) companyFilter.value = 'all';
            if (searchInput) searchInput.value = '';
            
            this.currentFilters = {
                status: 'all',
                company: 'all',
                capacity: 'all'
            };
            
            this.filteredBases = [...this.currentBases];
            this.renderBases();
            this.updateStats();
            
            console.log('✅ Filtros limpos');
        }

        // ✅ RENDERIZAR LISTA
        renderBases() {
            const tbody = document.getElementById('basesTableBody');
            if (!tbody) {
                console.error('❌ Tbody não encontrado');
                return;
            }

            if (this.filteredBases.length === 0) {
                tbody.innerHTML = this.getEmptyStateHTML();
                return;
            }

            tbody.innerHTML = this.filteredBases.map(base => this.getBaseRowHTML(base)).join('');
            console.log(`✅ ${this.filteredBases.length} bases renderizadas`);
        }

        // ✅ HTML DA LINHA
        getBaseRowHTML(base) {
			// Calcular utilizações
			const currentVehicles = base.total_vehicles || 0;
			const capacityVehicles = base.capacity_vehicles || 1;
			const currentDrivers = base.total_drivers || 0;
			const capacityDrivers = base.capacity_drivers || 1;
			
			const vehicleUtilization = capacityVehicles > 0 ? 
				Math.min(100, Math.round((currentVehicles / capacityVehicles) * 100)) : 0;
			const driverUtilization = capacityDrivers > 0 ? 
				Math.min(100, Math.round((currentDrivers / capacityDrivers) * 100)) : 0;
			
			const vehicleClass = vehicleUtilization >= 90 ? 'critical' : 
								vehicleUtilization >= 75 ? 'high' : 
								vehicleUtilization >= 50 ? 'medium' : 'low';
			
			const driverClass = driverUtilization >= 90 ? 'critical' : 
							   driverUtilization >= 75 ? 'high' : 
							   driverUtilization >= 50 ? 'medium' : 'low';

			return `
				<tr data-base-id="${base.id}">
					<!-- Coluna Base -->
					<td>
						<div class="base-card-modern">
							<div class="base-avatar-modern" style="background: linear-gradient(135deg, ${base.company_color || '#FF6B00'}, ${base.company_color || '#E55A00'});">
								${(base.name || '').substring(0, 2)}
								<div class="avatar-status ${base.is_active ? '' : 'inactive'}"></div>
							</div>
							<div class="base-info-modern">
								<div class="base-name-modern">${this.escapeHtml(base.name)}</div>
								<div class="base-company-modern">
									<i class="fas fa-building"></i>
									${this.escapeHtml(base.company_name || 'N/A')}
								</div>
							</div>
						</div>
					</td>
					
					<!-- Coluna Localização -->
					<td>
						<div class="location-card-modern">
							<div class="location-city-modern">
								<i class="fas fa-map-marker-alt"></i>
								${this.escapeHtml(base.city || 'N/A')} - ${base.state || 'N/A'}
							</div>
							${base.address ? `
								<div class="location-address-modern">
									${this.escapeHtml(base.address)}
								</div>
							` : ''}
						</div>
					</td>
					
					<!-- ✅ CORREÇÃO: Coluna Contato - Telefone e Email VERTICAL -->
					<td>
						<div class="contact-list-modern">
							${base.phone ? `
								<div class="contact-item-modern">
									<div class="contact-icon-modern">
										<i class="fas fa-phone"></i>
									</div>
									<div class="contact-info-modern">
										<div class="contact-type-modern">Telefone</div>
										<div class="contact-value-modern">${this.formatPhone(base.phone)}</div>
									</div>
								</div>
							` : ''}
							${base.email ? `
								<div class="contact-item-modern">
									<div class="contact-icon-modern">
										<i class="fas fa-envelope"></i>
									</div>
									<div class="contact-info-modern">
										<div class="contact-type-modern">Email</div>
										<div class="contact-value-modern">${this.escapeHtml(base.email)}</div>
									</div>
								</div>
							` : ''}
						</div>
					</td>
					<!-- ✅ CORREÇÃO: Coluna Capacidade com BARRAS DE PROGRESSO -->
					<td>
						<div class="capacity-dashboard-modern">
							<!-- Capacidade de Veículos -->
							<div class="capacity-item-modern">
								<div class="capacity-header-modern">
									<div class="capacity-label-modern">Veículos</div>
									<div class="capacity-stats-modern">${currentVehicles}/${capacityVehicles}</div>
								</div>
								<div class="capacity-progress-modern">
									<div class="capacity-fill-modern ${vehicleClass}" style="width: ${vehicleUtilization}%"></div>
								</div>
								<div class="capacity-percentage-modern">${vehicleUtilization}%</div>
							</div>
							
							<!-- Capacidade de Motoristas -->
							<div class="capacity-item-modern">
								<div class="capacity-header-modern">
									<div class="capacity-label-modern">Motoristas</div>
									<div class="capacity-stats-modern">${currentDrivers}/${capacityDrivers}</div>
								</div>
								<div class="capacity-progress-modern">
									<div class="capacity-fill-modern ${driverClass}" style="width: ${driverUtilization}%"></div>
								</div>
								<div class="capacity-percentage-modern">${driverUtilization}%</div>
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
									<div class="resource-content-modern">
										<div class="resource-count-modern">${currentVehicles}</div>
										<div class="resource-label-modern">Veículos</div>
									</div>
								</div>
								<div class="resource-badge-modern drivers">
									<div class="resource-icon-modern">
										<i class="fas fa-user-tie"></i>
									</div>
									<div class="resource-content-modern">
										<div class="resource-count-modern">${currentDrivers}</div>
										<div class="resource-label-modern">Motoristas</div>
									</div>
								</div>
							</div>
						</div>
					</td>
					
					<!-- Coluna Gerente -->
					<td>
						${base.manager_name ? `
							<div class="manager-card-modern">
								<div class="manager-avatar-modern">
									${(base.manager_name || '').substring(0, 2)}
								</div>
								<div class="manager-info-modern">
									<div class="manager-name-modern">${this.escapeHtml(base.manager_name)}</div>
									${base.manager_position ? `
										<div class="manager-position-modern">${this.escapeHtml(base.manager_position)}</div>
									` : ''}
								</div>
							</div>
						` : `
							<div class="empty-manager">
								<i class="fas fa-user-times"></i>
								<span>Sem gerente</span>
							</div>
						`}
					</td>
					
					<!-- Coluna Status -->
					<td>
						<span class="status-pill-modern ${base.is_active ? 'active' : 'inactive'}">
							<i class="fas fa-${base.is_active ? 'check' : 'times'}"></i>
							${base.is_active ? 'Ativa' : 'Inativa'}
						</span>
					</td>
					
					<!-- ✅ CORREÇÃO: Coluna Ações com Ícone VISUALIZAR -->
					<td>
						<div class="actions-toolbar-modern">
							<!-- Botão Visualizar -->
							<button class="action-btn-modern btn-view-modern" 
									onclick="window.basesManager.viewBase(${base.id})"
									title="Visualizar Base">
								<i class="fas fa-eye"></i>
							</button>
							
							<!-- Botão Editar -->
							<button class="action-btn-modern btn-edit-modern" 
									onclick="window.basesManager.editBase(${base.id})"
									title="Editar Base">
								<i class="fas fa-edit"></i>
							</button>
							
							<!-- Botão Ativar/Desativar -->
							<button class="action-btn-modern btn-delete-modern" 
									onclick="window.basesManager.deleteBase(${base.id})"
									title="${base.is_active ? 'Desativar' : 'Ativar'} Base">
								<i class="fas ${base.is_active ? 'fa-times' : 'fa-check'}"></i>
							</button>
						</div>
					</td>
				</tr>
			`;
		}

        // ✅ ESTADO VAZIO - CORRIGIDO
        getEmptyStateHTML() {
            return `
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
            `;
        }

        // ✅ EDITAR BASE
        editBase(baseId) {
            console.log('✏️ Editando base:', baseId);
            this.openBaseModal(baseId);
        }

        // ✅ DELETAR/ATIVAR BASE
        async deleteBase(baseId) {
            const base = this.currentBases.find(b => b.id == baseId);
            if (!base) return;

            const action = base.is_active ? 'desativar' : 'ativar';
            const confirmMessage = `Tem certeza que deseja ${action} a base "${base.name}"?`;

            if (!confirm(confirmMessage)) return;

            try {
                const endpoint = base.is_active ? 'delete' : 'activate';
                const response = await fetch('/bt-log-transportes/public/api/bases.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${endpoint}&id=${baseId}`
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess(`Base ${action === 'desativar' ? 'desativada' : 'ativada'} com sucesso!`);
                    this.loadBases();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('❌ Erro ao alterar status:', error);
                this.showError(`Erro ao ${action} base`);
            }
        }

        // ✅ ATUALIZAR ESTATÍSTICAS
        updateStats() {
            const totalBases = this.filteredBases.length;
            const activeBases = this.filteredBases.filter(b => b.is_active).length;
            
            this.updateStatCard('statTotalBases', totalBases, 'Bases Totais');
            this.updateStatCard('statActiveBases', activeBases, 'Bases Ativas');
        }

        updateStatCard(elementId, value, label) {
            const element = document.getElementById(elementId);
            if (element) {
                const valueElement = element.querySelector('.stat-value-discreet');
                const labelElement = element.querySelector('.stat-label-discreet');
                
                if (valueElement) valueElement.textContent = value;
                if (labelElement) labelElement.textContent = label;
            }
        }

        // ✅ POPULAR FORMULÁRIO
        populateBaseForm(base) {
            const form = document.getElementById('baseForm');
            if (!form) {
                console.error('❌ Formulário não encontrado');
                return;
            }

            // Preencher campos
            form.querySelector('[name="base_id"]').value = base.id || '';
            form.querySelector('[name="name"]').value = base.name || '';
            form.querySelector('[name="company_id"]').value = base.company_id || '';
            form.querySelector('[name="address"]').value = base.address || '';
            form.querySelector('[name="city"]').value = base.city || '';
            form.querySelector('[name="state"]').value = base.state || '';
            form.querySelector('[name="phone"]').value = base.phone || '';
            form.querySelector('[name="email"]').value = base.email || '';
            form.querySelector('[name="manager_id"]').value = base.manager_id || '';
            form.querySelector('[name="capacity_vehicles"]').value = base.capacity_vehicles || '';
            form.querySelector('[name="capacity_drivers"]').value = base.capacity_drivers || '';
            form.querySelector('[name="operating_hours"]').value = base.operating_hours || '';
            form.querySelector('[name="opening_date"]').value = base.opening_date || '';
            
            const isActiveCheckbox = form.querySelector('[name="is_active"]');
            if (isActiveCheckbox) {
                isActiveCheckbox.checked = base.is_active !== false;
            }

            // Atualizar título do modal
            const modalTitle = document.getElementById('baseModalLabel');
            if (modalTitle) {
                modalTitle.textContent = base.id ? 'Editar Base' : 'Nova Base';
            }

            console.log('✅ Formulário preenchido');
        }

        // ✅ RESETAR FORMULÁRIO
        resetBaseForm() {
            const form = document.getElementById('baseForm');
            if (form) {
                form.reset();
                form.querySelector('[name="base_id"]').value = '';
                
                // Resetar título do modal
                const modalTitle = document.getElementById('baseModalLabel');
                if (modalTitle) {
                    modalTitle.textContent = 'Nova Base';
                }
                
                // Limpar listas de recursos
                this.renderEmployeesList([]);
                this.renderVehiclesList([]);
                
                console.log('✅ Formulário resetado');
            }
        }

        // ✅ RENDERIZAR LISTA DE FUNCIONÁRIOS
        renderEmployeesList(employees) {
            const container = document.getElementById('employeesListContainer');
            if (!container) return;

            if (!employees || employees.length === 0) {
                container.innerHTML = `
                    <div class="empty-resource">
                        <i class="fas fa-users"></i>
                        <span>Nenhum funcionário vinculado</span>
                    </div>
                `;
                return;
            }

            container.innerHTML = employees.map(employee => `
                <div class="resource-item" data-employee-id="${employee.id}">
                    <div class="resource-info">
                        <div class="resource-avatar">
                            ${employee.name ? employee.name.charAt(0).toUpperCase() : 'F'}
                        </div>
                        <div class="resource-details">
                            <h6>${this.escapeHtml(employee.name)}</h6>
                            <p>${this.escapeHtml(employee.position || 'Funcionário')}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-resource" onclick="window.basesManager.removeEmployeeFromBase(${employee.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }

        // ✅ RENDERIZAR LISTA DE VEÍCULOS
        renderVehiclesList(vehicles) {
            const container = document.getElementById('vehiclesListContainer');
            if (!container) return;

            if (!vehicles || vehicles.length === 0) {
                container.innerHTML = `
                    <div class="empty-resource">
                        <i class="fas fa-truck"></i>
                        <span>Nenhum veículo vinculado</span>
                    </div>
                `;
                return;
            }

            container.innerHTML = vehicles.map(vehicle => `
                <div class="resource-item" data-vehicle-id="${vehicle.id}">
                    <div class="resource-info">
                        <div class="resource-avatar">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="resource-details">
                            <h6>${this.escapeHtml(vehicle.plate)}</h6>
                            <p>${this.escapeHtml(vehicle.brand || '')} ${this.escapeHtml(vehicle.model || '')}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-resource" onclick="window.basesManager.removeVehicleFromBase(${vehicle.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }

        // ✅ UTILITÁRIOS
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        formatPhone(phone) {
            if (!phone) return 'N/A';
            return phone.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3');
        }

        showLoading() {
            console.log('⏳ Carregando bases...');
        }

        hideLoading() {
            console.log('✅ Bases carregadas!');
        }

        showSuccess(message) {
            this.showToast(message, 'success');
        }

        showError(message) {
            this.showToast(message, 'error');
        }

        showInfo(message) {
            this.showToast(message, 'info');
        }

        showToast(message, type = 'info') {
            // Criar toast notification
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle"></i>
                    <span>${message}</span>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
            `;
            
            document.body.appendChild(toast);
            
            // Remover automaticamente após 5 segundos
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }
    }

    // ✅ DEFINIR NO GLOBAL SCOPE
    window.BasesManager = BasesManager;
}

// ✅ INICIALIZAÇÃO SEGURA
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏁 DOM Carregado - Inicializando Bases...');
    
    // Inicializar apenas se não existir
    if (typeof window.basesManager === 'undefined') {
        window.basesManager = new BasesManager();
        console.log('🎉 Sistema de Bases inicializado com sucesso!');
    } else {
        console.log('ℹ️  BasesManager já inicializado');
    }
});

// ✅ FUNÇÕES GLOBAIS SEGURAS
function editBase(baseId) {
    if (window.basesManager) {
        window.basesManager.editBase(baseId);
    } else {
        console.error('❌ BasesManager não disponível');
    }
}

function deleteBase(baseId) {
    if (window.basesManager) {
        window.basesManager.deleteBase(baseId);
    } else {
        console.error('❌ BasesManager não disponível');
    }
}

function clearAllFilters() {
    if (window.basesManager) {
        window.basesManager.clearFilters();
    } else {
        console.error('❌ BasesManager não disponível');
    }
}

// ✅ FUNÇÕES GLOBAIS PARA OS BOTÕES
function openEmployeeSelector() {
    console.log('👥 Abrindo seletor de funcionários...');
    if (window.basesManager) {
        window.basesManager.openEmployeeSelector();
    } else {
        console.error('❌ BasesManager não disponível');
    }
}

function openVehicleSelector() {
    console.log('🚚 Abrindo seletor de veículos...');
    if (window.basesManager) {
        window.basesManager.openVehicleSelector();
    } else {
        console.error('❌ BasesManager não disponível');
    }
}

function confirmEmployeeSelection() {
    if (window.basesManager) {
        window.basesManager.confirmEmployeeSelection();
    }
}

function confirmVehicleSelection() {
    if (window.basesManager) {
        window.basesManager.confirmVehicleSelection();
    }
}

function closeEmployeeModal() {
    if (window.basesManager) {
        window.basesManager.closeEmployeeModal();
    }
}

function closeVehicleModal() {
    if (window.basesManager) {
        window.basesManager.closeVehicleModal();
    }
}

function toggleEmployeeSelection(employeeId) {
    if (window.basesManager) {
        window.basesManager.toggleEmployeeSelection(employeeId);
    }
}

function toggleVehicleSelection(vehicleId) {
    if (window.basesManager) {
        window.basesManager.toggleVehicleSelection(vehicleId);
    }
}