// public/assets/js/maintenance.js - SISTEMA COMPLETO DE MANUTENÇÕES - VERSÃO CORRIGIDA
(function() {
    'use strict';

    if (window.MaintenanceManagerLoaded) {
        console.log('🔧 Maintenance Manager já carregado');
        return;
    }
    window.MaintenanceManagerLoaded = true;

    console.log('🛠️ Maintenance Manager carregado');

    class MaintenanceManager {
        constructor() {
            this.currentMaintenanceId = null;
            this.currentVehicleId = null;
            this.isInitialized = false;
            this.eventListeners = new Set();
            this.modal = null;
            this.saving = false;
            this.deleting = false;
            this.completing = false;
            this.filters = {
                company: '',
                vehicle: '',
                type: '',
                status: '',
                search: ''
            };
            
            // Intervalos padrão de manutenção (em KM)
            this.defaultIntervals = {
                'troca_oleo': 10000,
                'filtro_ar': 15000,
                'filtro_combustivel': 20000,
                'pastilhas_freio': 25000,
                'discos_freio': 50000,
                'pneus': 50000,
                'alinhamento': 10000,
                'suspensao': 30000,
                'transmissao': 60000,
                'diferencial': 60000,
                'bateria': 0,
                'correia': 80000,
                'velas': 30000,
                'injetores': 40000,
                'ar_condicionado': 20000,
                'freios': 25000,
                'motor': 10000,
                'eletrica': 20000,
                'outros': 0
            };
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 MaintenanceManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando MaintenanceManager...');
            
            this.removeAllEventListeners();
            
            setTimeout(() => {
                this.setupAllEvents();
                this.setupFilters();
                this.setupSearch();
                this.isInitialized = true;
                console.log('✅ MaintenanceManager inicializado com sucesso!');
            }, 100);
        }

        removeAllEventListeners() {
            console.log('🧹 Removendo event listeners antigos do MaintenanceManager...');
            
            const elementsToClean = [
                'newMaintenanceBtn',
                'cancelMaintenanceButton',
                'saveMaintenanceButton',
                'completeMaintenanceButton',
                'confirmCompleteButton',
                'vehicle_id',
                'service_type',
                'maintenance_interval'
            ];
            
            elementsToClean.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    const newElement = element.cloneNode(true);
                    element.parentNode.replaceChild(newElement, element);
                }
            });

            if (this.delegationHandler) {
                document.removeEventListener('click', this.delegationHandler);
                this.delegationHandler = null;
            }
        }

        setupAllEvents() {
            this.setupButtonEvents();
            this.setupModalEvents();
            this.setupFormEvents();
            this.setupTableActions();
        }

        setupButtonEvents() {
            console.log('🔧 Configurando eventos dos botões do MaintenanceManager...');
            
            // Botão "Nova Manutenção"
            const newMaintenanceBtn = document.getElementById('newMaintenanceBtn');
            if (newMaintenanceBtn && !this.eventListeners.has('newMaintenanceBtn')) {
                newMaintenanceBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 [MAINTENANCE] Botão nova manutenção clicado');
                    this.openMaintenanceForm();
                });
                this.eventListeners.add('newMaintenanceBtn');
            }

            // Botão Atualizar
            const refreshBtn = document.getElementById('refreshMaintenancesBtn');
            if (refreshBtn && !this.eventListeners.has('refreshBtn')) {
                refreshBtn.addEventListener('click', () => {
                    this.refreshMaintenances();
                });
                this.eventListeners.add('refreshBtn');
            }

            console.log('✅ Eventos dos botões do MaintenanceManager configurados!');
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos do modal de manutenções...');
            
            this.modal = document.getElementById('maintenanceModal');
            this.completeModal = document.getElementById('completeMaintenanceModal');
            
            if (!this.modal) {
                console.log('ℹ️ Modal de manutenções ainda não carregado, aguardando...');
                setTimeout(() => {
                    this.modal = document.getElementById('maintenanceModal');
                    if (this.modal) {
                        console.log('✅ Modal de manutenções encontrado após delay');
                        this.setupModalEventListeners();
                    } else {
                        console.error('❌ Modal de manutenções não encontrado após múltiplas tentativas');
                    }
                }, 500);
                return;
            }

            console.log('✅ Modal de manutenções encontrado');
            this.setupModalEventListeners();
        }

        setupModalEventListeners() {
            if (!this.modal) {
                console.error('❌ Modal não disponível para configurar eventos');
                return;
            }

            // Fechar com X
            const closeBtn = this.modal.querySelector('.modal-close');
            if (closeBtn && !this.eventListeners.has('modalClose')) {
                closeBtn.addEventListener('click', () => {
                    this.closeMaintenanceModal();
                });
                this.eventListeners.add('modalClose');
            }

            // Fechar clicando fora
            if (!this.eventListeners.has('modalOutsideClick')) {
                this.modal.addEventListener('click', (e) => {
                    if (e.target === this.modal) {
                        this.closeMaintenanceModal();
                    }
                });
                this.eventListeners.add('modalOutsideClick');
            }

            // Botão Cancelar
            const cancelBtn = document.getElementById('cancelMaintenanceButton');
            if (cancelBtn && !this.eventListeners.has('cancelButton')) {
                cancelBtn.addEventListener('click', () => {
                    this.closeMaintenanceModal();
                });
                this.eventListeners.add('cancelButton');
            }

            // Botão Salvar
            const saveBtn = document.getElementById('saveMaintenanceButton');
            if (saveBtn && !this.eventListeners.has('saveButton')) {
                saveBtn.addEventListener('click', () => {
                    console.log('💾 [MAINTENANCE] Botão salvar manutenção clicado');
                    this.saveMaintenance();
                });
                this.eventListeners.add('saveButton');
            }

            // Botão Concluir Manutenção
            const completeBtn = document.getElementById('completeMaintenanceButton');
            if (completeBtn && !this.eventListeners.has('completeButton')) {
                completeBtn.addEventListener('click', () => {
                    console.log('✅ [MAINTENANCE] Botão concluir manutenção clicado');
                    this.completeMaintenance();
                });
                this.eventListeners.add('completeButton');
            }

            // Modal de Conclusão
            if (this.completeModal) {
                const closeCompleteBtn = this.completeModal.querySelector('.modal-close');
                if (closeCompleteBtn) {
                    closeCompleteBtn.addEventListener('click', () => {
                        this.closeCompleteModal();
                    });
                }

                const confirmCompleteBtn = document.getElementById('confirmCompleteButton');
                if (confirmCompleteBtn) {
                    confirmCompleteBtn.addEventListener('click', () => {
                        this.confirmCompleteMaintenance();
                    });
                }

                this.completeModal.addEventListener('click', (e) => {
                    if (e.target === this.completeModal) {
                        this.closeCompleteModal();
                    }
                });
            }

            console.log('✅ Eventos do modal de manutenções configurados!');
        }

        setupFormEvents() {
            // Toggle fornecedor
            const useSupplierCheckbox = document.getElementById('use_supplier');
            if (useSupplierCheckbox) {
                useSupplierCheckbox.addEventListener('change', () => {
                    this.toggleSupplierFields(useSupplierCheckbox.checked);
                });
            }

            // Toggle status
            const statusSelect = document.getElementById('status');
            if (statusSelect) {
                statusSelect.addEventListener('change', () => {
                    this.toggleCompletionFields(statusSelect.value === 'concluida');
                });
            }

            // Calcular próximo KM
            const intervalInput = document.getElementById('maintenance_interval');
            if (intervalInput) {
                intervalInput.addEventListener('change', () => {
                    this.calculateNextMaintenanceKm();
                });
            }

            // Formatação de valores monetários
            const costInput = document.getElementById('cost');
            if (costInput) {
                costInput.addEventListener('blur', (e) => {
                    this.formatCurrencyInput(e.target);
                });
            }

            console.log('✅ Eventos do formulário de manutenções configurados!');
        }

        setupTableActions() {
            // Delegation handler para manutenções
            document.addEventListener('click', (e) => {
                const maintenanceRow = e.target.closest('tr[data-maintenance-id]');
                if (!maintenanceRow) return;

                const maintenanceId = maintenanceRow.getAttribute('data-maintenance-id');
                
                // Botão Editar
                if (e.target.closest('.btn-edit')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✏️ [MAINTENANCE] Editando manutenção:', maintenanceId);
                    this.editMaintenance(maintenanceId);
                    return;
                }

                // Botão Visualizar
                if (e.target.closest('.btn-view')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('👁️ [MAINTENANCE] Visualizando manutenção:', maintenanceId);
                    this.viewMaintenance(maintenanceId);
                    return;
                }

                // Botão Concluir
                if (e.target.closest('.btn-complete')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✅ [MAINTENANCE] Concluindo manutenção:', maintenanceId);
                    this.openCompleteModal(maintenanceId);
                    return;
                }

                // Botão Repetir
                if (e.target.closest('.btn-repeat')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🔁 [MAINTENANCE] Repetindo manutenção:', maintenanceId);
                    this.repeatMaintenance(maintenanceId);
                    return;
                }

                // Botão Excluir
                if (e.target.closest('.btn-delete')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const maintenanceName = maintenanceRow.querySelector('.vehicle-plate')?.textContent || 
                                          maintenanceRow.querySelector('.description')?.textContent || 
                                          'Manutenção';
                    console.log('🗑️ [MAINTENANCE] Excluindo manutenção:', maintenanceName);
                    this.deleteMaintenance(maintenanceId, maintenanceName);
                    return;
                }
            });
        }

        setupFilters() {
            const companyFilter = document.getElementById('companyFilter');
            const vehicleFilter = document.getElementById('vehicleFilter');
            const typeFilter = document.getElementById('typeFilter');
            const statusFilter = document.getElementById('statusFilter');

            if (companyFilter) {
                companyFilter.addEventListener('change', (e) => {
                    this.filters.company = e.target.value;
                    this.applyFilters();
                });
            }

            if (vehicleFilter) {
                vehicleFilter.addEventListener('change', (e) => {
                    this.filters.vehicle = e.target.value;
                    this.applyFilters();
                });
            }

            if (typeFilter) {
                typeFilter.addEventListener('change', (e) => {
                    this.filters.type = e.target.value;
                    this.applyFilters();
                });
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', (e) => {
                    this.filters.status = e.target.value;
                    this.applyFilters();
                });
            }

            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', () => {
                    this.clearFilters();
                });
            }
        }

        setupSearch() {
            const searchInput = document.getElementById('searchMaintenance');
            let searchTimeout;

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.filters.search = e.target.value.toLowerCase();
                        this.applyFilters();
                    }, 300);
                });
            }
        }

        applyFilters() {
            const rows = document.querySelectorAll('#maintenancesTable tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                let show = true;
                
                // Filtro por empresa
                if (this.filters.company && row.dataset.companyId != this.filters.company) {
                    show = false;
                }
                
                // Filtro por veículo
                if (this.filters.vehicle && row.dataset.vehicleId != this.filters.vehicle) {
                    show = false;
                }
                
                // Filtro por tipo
                if (this.filters.type && row.dataset.type !== this.filters.type) {
                    show = false;
                }
                
                // Filtro por status
                if (this.filters.status && row.dataset.status !== this.filters.status) {
                    show = false;
                }
                
                // Filtro por busca
                if (this.filters.search) {
                    const rowText = row.textContent.toLowerCase();
                    if (!rowText.includes(this.filters.search)) {
                        show = false;
                    }
                }
                
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            // Atualizar contador
            this.updateResultsCount(visibleCount);
        }

        clearFilters() {
            this.filters = {
                company: '',
                vehicle: '',
                type: '',
                status: '',
                search: ''
            };
            
            // Resetar inputs
            document.getElementById('companyFilter').value = '';
            document.getElementById('vehicleFilter').value = '';
            document.getElementById('typeFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchMaintenance').value = '';
            
            this.applyFilters();
        }

        updateResultsCount(count) {
            const countElement = document.querySelector('.results-count');
            if (countElement) {
                countElement.textContent = `${count} manutenção${count !== 1 ? 'ões' : ''} encontrada${count !== 1 ? 's' : ''}`;
            }
        }

        toggleSupplierFields(useRegistered) {
            const customField = document.getElementById('custom_supplier_field');
            const registeredField = document.getElementById('registered_supplier_field');
            
            if (useRegistered) {
                customField.style.display = 'none';
                registeredField.style.display = 'block';
                this.loadSuppliers();
            } else {
                customField.style.display = 'block';
                registeredField.style.display = 'none';
            }
        }

        toggleCompletionFields(isComplete) {
            const completionSection = document.getElementById('completion_section');
            const saveBtn = document.getElementById('saveMaintenanceButton');
            const completeBtn = document.getElementById('completeMaintenanceButton');
            const costField = document.querySelector('.cost-field');
            
            if (completionSection) {
                completionSection.style.display = isComplete ? 'block' : 'none';
            }
            if (saveBtn) {
                saveBtn.style.display = isComplete ? 'none' : 'block';
            }
            if (completeBtn) {
                completeBtn.style.display = isComplete ? 'block' : 'none';
            }
            if (costField) {
                costField.style.display = isComplete ? 'block' : 'none';
            }
        }

        async loadSuppliers() {
            try {
                const response = await fetch('/bt-log-transportes/public/api/accounts_payable.php?action=get_suppliers');
                const result = await response.json();
                
                if (result.success && result.data) {
                    const supplierSelect = document.getElementById('supplier_selection');
                    if (supplierSelect) {
                        // Limpar opções existentes (exceto a primeira)
                        while (supplierSelect.options.length > 1) {
                            supplierSelect.removeChild(supplierSelect.lastChild);
                        }
                        
                        // Adicionar fornecedores
                        result.data.forEach(supplier => {
                            const option = document.createElement('option');
                            option.value = supplier.id;
                            option.textContent = supplier.name + (supplier.phone ? ' - ' + supplier.phone : '');
                            supplierSelect.appendChild(option);
                        });
                    }
                }
            } catch (error) {
                console.error('❌ Erro ao carregar fornecedores:', error);
            }
        }

        // ✅ MÉTODO PRINCIPAL: Abrir modal de manutenção
        openMaintenanceForm(maintenanceId = null, vehicleId = null) {
            console.log('🎯 [MAINTENANCE] ABRINDO MODAL! MaintenanceId:', maintenanceId, 'VehicleId:', vehicleId);
            
            this.currentMaintenanceId = maintenanceId;
            this.currentVehicleId = vehicleId;

            // Buscar o modal
            this.modal = document.getElementById('maintenanceModal');
            
            if (!this.modal) {
                console.error('❌ MODAL MANUTENÇÕES NÃO ENCONTRADO!');
                this.showAlert('Erro: Modal não encontrado. Verifique se o HTML do modal está correto.', 'error');
                return;
            }

            const title = document.getElementById('modalMaintenanceTitle');

            if (maintenanceId) {
                if (title) title.textContent = 'Editar Manutenção';
                this.loadMaintenanceData(maintenanceId);
            } else {
                if (title) title.textContent = 'Nova Manutenção';
                this.resetForm();
                
                // Se veículo foi especificado, preencher automaticamente
                if (vehicleId) {
                    document.getElementById('vehicle_id').value = vehicleId;
                    this.onVehicleChange(vehicleId);
                }
            }

            // Abrir modal
            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ [MAINTENANCE] MODAL MANUTENÇÕES ABERTO COM SUCESSO!');
        }

        // ✅ MÉTODO: Fechar modal de manutenção
        closeMaintenanceModal() {
            console.log('🔒 [MAINTENANCE] Fechando modal...');
            if (this.modal) {
                this.modal.style.display = 'none';
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            this.resetForm();
            this.setFormReadOnly(false);
        }

        // ✅ MÉTODO: Abrir modal de conclusão
        openCompleteModal(maintenanceId) {
            this.currentMaintenanceId = maintenanceId;
            const modal = document.getElementById('completeMaintenanceModal');
            if (modal) {
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
                document.getElementById('complete_maintenance_id').value = maintenanceId;
            }
        }

        // ✅ MÉTODO: Fechar modal de conclusão
        closeCompleteModal() {
            const modal = document.getElementById('completeMaintenanceModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.getElementById('completeMaintenanceForm').reset();
            }
        }

        // ✅ MÉTODO: Editar manutenção
        editMaintenance(maintenanceId) {
            console.log('✏️ [MAINTENANCE] Editando manutenção:', maintenanceId);
            this.setFormReadOnly(false);
            this.openMaintenanceForm(maintenanceId);
        }

        // ✅ MÉTODO: Visualizar manutenção
        viewMaintenance(maintenanceId) {
            console.log('👁️ [MAINTENANCE] Visualizando manutenção:', maintenanceId);
            this.openMaintenanceForm(maintenanceId);
            this.setFormReadOnly(true);
        }

        // ✅ MÉTODO: Repetir manutenção
        async repeatMaintenance(maintenanceId) {
            console.log('🔁 [MAINTENANCE] Repetindo manutenção:', maintenanceId);
            
            try {
                const apiUrl = `/bt-log-transportes/public/api/maintenance.php?action=get&id=${maintenanceId}`;
                const response = await fetch(apiUrl);
                
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                
                const result = await response.json();

                if (result.success && result.data) {
                    const maintenance = result.data;
                    this.openMaintenanceForm();
                    
                    // Preencher com dados da manutenção anterior
                    setTimeout(() => {
                        document.getElementById('vehicle_id').value = maintenance.vehicle_id;
                        document.getElementById('type').value = maintenance.type;
                        document.getElementById('service_type').value = '';
                        document.getElementById('description').value = 'Refazer: ' + maintenance.description;
                        document.getElementById('cost').value = '';
                        document.getElementById('service_provider').value = maintenance.service_provider || '';
                        
                        // Calcular próxima manutenção baseada na atual
                        this.onVehicleChange(maintenance.vehicle_id);
                        
                    }, 300);
                    
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados da manutenção');
                }
            } catch (error) {
                console.error('❌ [MAINTENANCE] Erro ao repetir manutenção:', error);
                this.showAlert('Erro ao repetir manutenção: ' + error.message, 'error');
            }
        }

        // ✅ MÉTODO: Registrar manutenção rápida para veículo
        registerMaintenance(vehicleId) {
            console.log('⚡ [MAINTENANCE] Registrando manutenção rápida para veículo:', vehicleId);
            this.openMaintenanceForm(null, vehicleId);
        }

        // ✅ MÉTODO: Definir formulário como somente leitura
        setFormReadOnly(readOnly) {
            const form = document.getElementById('maintenanceForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.id !== 'cancelMaintenanceButton') {
                    input.disabled = readOnly;
                }
            });

            const saveBtn = document.getElementById('saveMaintenanceButton');
            const completeBtn = document.getElementById('completeMaintenanceButton');
            if (saveBtn) {
                saveBtn.style.display = readOnly ? 'none' : 'block';
            }
            if (completeBtn) {
                completeBtn.style.display = 'none';
            }

            const title = document.getElementById('modalMaintenanceTitle');
            if (title && readOnly) {
                title.textContent = 'Visualizar Manutenção';
            }
        }

        // ✅ MÉTODO: Resetar formulário
        resetForm() {
            const form = document.getElementById('maintenanceForm');
            if (form) {
                form.reset();
                
                const maintenanceIdField = document.getElementById('maintenanceId');
                if (maintenanceIdField) {
                    maintenanceIdField.value = '';
                }
                
                // Data padrão para hoje
                document.getElementById('maintenance_date').value = new Date().toISOString().split('T')[0];
                
                // Limpar campos calculados
                document.getElementById('maintenance_interval').value = '';
                document.getElementById('next_maintenance_km').value = '';
                
                // Resetar toggles
                document.getElementById('use_supplier').checked = false;
                document.getElementById('status').value = 'agendada';
                this.toggleSupplierFields(false);
                this.toggleCompletionFields(false);
                
            } else {
                console.warn('⚠️ [MAINTENANCE] Formulário não encontrado para reset');
            }
        }

        // ✅ MÉTODO: Carregar dados da manutenção
        async loadMaintenanceData(maintenanceId) {
            console.log(`📥 [MAINTENANCE] Carregando manutenção ${maintenanceId}`);
            
            try {
                const apiUrl = `/bt-log-transportes/public/api/maintenance.php?action=get&id=${maintenanceId}`;
                const response = await fetch(apiUrl);
                
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                
                const result = await response.json();

                if (result.success && result.data) {
                    this.populateForm(result.data);
                    console.log('✅ [MAINTENANCE] Dados da manutenção carregados com sucesso');
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados da manutenção');
                }
            } catch (error) {
                console.error('❌ [MAINTENANCE] Erro ao carregar dados:', error);
                this.showAlert('Erro ao carregar dados da manutenção: ' + error.message, 'error');
                // Carregar dados mock para desenvolvimento
                this.loadMockData(maintenanceId);
            }
        }
        
        // ✅ MÉTODO: Preencher formulário com dados
        populateForm(maintenance) {
            console.log('📝 [MAINTENANCE] Preenchendo formulário com dados:', maintenance);
            
            const maintenanceIdField = document.getElementById('maintenanceId');
            if (maintenanceIdField) {
                maintenanceIdField.value = maintenance.id;
            }

            // Preencher campos básicos
            document.getElementById('vehicle_id').value = maintenance.vehicle_id || '';
            document.getElementById('type').value = maintenance.type || '';
            document.getElementById('maintenance_date').value = maintenance.maintenance_date || '';
            document.getElementById('current_km').value = maintenance.current_km || '';
            document.getElementById('cost').value = maintenance.cost || '';
            document.getElementById('description').value = maintenance.description || '';
            document.getElementById('service_provider').value = maintenance.service_provider || '';
            document.getElementById('notes').value = maintenance.notes || '';
            document.getElementById('next_maintenance_date').value = maintenance.next_maintenance_date || '';
            document.getElementById('next_maintenance_km').value = maintenance.next_maintenance_km || '';
            document.getElementById('status').value = maintenance.status || 'agendada';

            // Configurar fornecedor
            const useRegisteredSupplier = maintenance.supplier_id && maintenance.supplier_id > 0;
            document.getElementById('use_supplier').checked = useRegisteredSupplier;
            this.toggleSupplierFields(useRegisteredSupplier);
            
            if (useRegisteredSupplier) {
                document.getElementById('supplier_selection').value = maintenance.supplier_id || '';
            }

            // Configurar campos de conclusão
            this.toggleCompletionFields(maintenance.status === 'concluida');

            // Tentar identificar o tipo de serviço pela descrição
            this.identifyServiceType(maintenance.description);
        }

        // ✅ MÉTODO: Identificar tipo de serviço pela descrição
        identifyServiceType(description) {
            const commonServices = {
                'troca_oleo': ['óleo', 'oleo', 'lubrificante'],
                'filtro_ar': ['filtro de ar', 'filtro ar'],
                'filtro_combustivel': ['filtro de combustível', 'filtro combustivel'],
                'pastilhas_freio': ['pastilha', 'freio dianteiro', 'freio traseiro'],
                'pneus': ['pneu', 'calibragem', 'balanceamento'],
                'alinhamento': ['alinhamento', 'geometria'],
            };

            const descLower = description.toLowerCase();
            for (const [serviceKey, keywords] of Object.entries(commonServices)) {
                for (const keyword of keywords) {
                    if (descLower.includes(keyword)) {
                        document.getElementById('service_type').value = serviceKey;
                        this.onServiceTypeChange(serviceKey);
                        return;
                    }
                }
            }
        }

        // ✅ MÉTODO: Carregar dados mock para desenvolvimento
        loadMockData(maintenanceId) {
            console.log('🎭 [MAINTENANCE] Carregando dados mock');
            
            const mockData = {
                id: maintenanceId,
                vehicle_id: 1,
                type: 'preventiva',
                maintenance_date: '2024-01-15',
                current_km: 75000,
                cost: 450.00,
                description: 'Troca de óleo e filtros - Manutenção preventiva',
                service_provider: 'Oficina Central',
                next_maintenance_date: '2024-04-15',
                next_maintenance_km: 85000,
                notes: 'Veículo em bom estado, próxima revisão em 10.000 km',
                status: 'agendada'
            };

            this.populateForm(mockData);
        }

        // ✅ MÉTODO: Mudança de veículo
        onVehicleChange(vehicleId) {
            console.log(`🚗 [MAINTENANCE] Veículo selecionado: ${vehicleId}`);
            
            if (!vehicleId) return;
            
            // Buscar informações do veículo selecionado
            const vehicleSelect = document.getElementById('vehicle_id');
            const selectedOption = vehicleSelect.querySelector(`option[value="${vehicleId}"]`);
            
            if (selectedOption) {
                const currentKm = selectedOption.getAttribute('data-current-km');
                const nextMaintenanceKm = selectedOption.getAttribute('data-next-maintenance-km');
                
                // Preencher KM atual se estiver vazio
                const currentKmInput = document.getElementById('current_km');
                if (currentKm && currentKm > 0 && !currentKmInput.value) {
                    currentKmInput.value = currentKm;
                }
                
                // Sugerir próxima manutenção baseada na atual do veículo
                if (nextMaintenanceKm && nextMaintenanceKm > 0) {
                    document.getElementById('next_maintenance_km').value = nextMaintenanceKm;
                }
            }
        }

        // ✅ MÉTODO: Mudança de tipo de manutenção
        onTypeChange(type) {
            console.log(`🔧 [MAINTENANCE] Tipo selecionado: ${type}`);
            // Aqui você pode adicionar lógica específica para cada tipo
        }

        // ✅ MÉTODO: Mudança de tipo de serviço
        onServiceTypeChange(serviceType) {
            console.log(`🛠️ [MAINTENANCE] Serviço selecionado: ${serviceType}`);
            
            // Preencher intervalo padrão
            const interval = this.defaultIntervals[serviceType] || 0;
            document.getElementById('maintenance_interval').value = interval;
            
            // Calcular próxima manutenção automaticamente
            if (interval > 0) {
                this.calculateNextMaintenanceKm();
            }
        }

        // ✅ MÉTODO: Calcular próxima manutenção por KM
        calculateNextMaintenanceKm() {
            const currentKmInput = document.getElementById('current_km');
            const intervalInput = document.getElementById('maintenance_interval');
            const nextKmInput = document.getElementById('next_maintenance_km');
            
            if (!currentKmInput.value || !intervalInput.value) return;
            
            const currentKm = parseFloat(currentKmInput.value);
            const interval = parseFloat(intervalInput.value);
            
            if (isNaN(currentKm) || isNaN(interval) || interval <= 0) return;
            
            const nextKm = currentKm + interval;
            nextKmInput.value = nextKm;
            
            console.log(`📊 [MAINTENANCE] Próxima manutenção calculada: ${nextKm} km`);
        }

        // ✅ MÉTODO: Salvar manutenção
        async saveMaintenance() {
            if (this.saving) return;
            
            this.saving = true;
            console.log('💾 [MAINTENANCE] Salvando manutenção...');
            
            if (!this.validateMaintenanceForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveMaintenanceButton');
            this.setLoadingState(saveBtn, true);

            try {
                const formData = new FormData(document.getElementById('maintenanceForm'));

                const maintenanceId = this.currentMaintenanceId;
                
                const apiUrl = '/bt-log-transportes/public/api/maintenance.php?action=save';
                
                console.log(`🚀 [MAINTENANCE] Enviando para API: id=${maintenanceId}`);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                console.log('📡 [MAINTENANCE] Resposta bruta:', responseText.substring(0, 200));

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('❌ [MAINTENANCE] Erro ao parsear JSON:', parseError);
                    throw new Error('Resposta inválida do servidor');
                }

                console.log('📊 [MAINTENANCE] Resposta parseada:', result);

                if (result.success) {
                    console.log('✅ [MAINTENANCE] MANUTENÇÃO SALVA COM SUCESSO!');
                    
                    let successMessage = result.message || 'Manutenção salva com sucesso!';
                    
                    // ✅ MOSTRAR MENSAGEM ESPECIAL SE GEROU CONTA A PAGAR
                    if (result.payableGenerated) {
                        successMessage = '✅ Manutenção salva com sucesso!\n💰 Conta a pagar gerada automaticamente na seção financeira.';
                    }
                    
                    this.showAlert(successMessage, 'success');
                    this.closeMaintenanceModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Erro ao salvar manutenção');
                }
                
            } catch (error) {
                console.error('💥 [MAINTENANCE] Erro:', error);
                this.showAlert('Erro ao salvar manutenção: ' + error.message, 'error');
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        // ✅ MÉTODO: Concluir manutenção
        async completeMaintenance() {
            const status = document.getElementById('status').value;
            if (status !== 'concluida') {
                this.showAlert('Para concluir a manutenção, altere o status para "Concluída"', 'warning');
                return;
            }

            const cost = document.getElementById('cost').value;
            if (!cost || parseFloat(cost) <= 0) {
                this.showAlert('Informe o custo da manutenção para concluí-la', 'warning');
                document.getElementById('cost').focus();
                return;
            }

            await this.saveMaintenance();
        }

        // ✅ MÉTODO: Confirmar conclusão da manutenção (modal separado)
        async confirmCompleteMaintenance() {
            if (this.completing) return;
            
            this.completing = true;
            const button = document.getElementById('confirmCompleteButton');
            this.setLoadingState(button, true);

            try {
                const formData = new FormData(document.getElementById('completeMaintenanceForm'));
                formData.append('id', this.currentMaintenanceId);
                formData.append('status', 'concluida');
                
                // Verificar se deve gerar conta a pagar
                const generatePayable = document.getElementById('complete_generate_payable').checked;
                if (generatePayable) {
                    formData.append('generate_payable', '1');
                }

                console.log('✅ [MAINTENANCE] Concluindo manutenção:', this.currentMaintenanceId);

                const response = await fetch('/bt-log-transportes/public/api/maintenance.php?action=save', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    let successMessage = 'Manutenção concluída com sucesso!';
                    if (result.payableGenerated) {
                        successMessage += '\nConta a pagar gerada automaticamente.';
                    }
                    
                    this.showAlert(successMessage, 'success');
                    this.closeCompleteModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Erro ao concluir manutenção');
                }
                
            } catch (error) {
                console.error('❌ [MAINTENANCE] Erro ao concluir:', error);
                this.showAlert('Erro ao concluir manutenção: ' + error.message, 'error');
            } finally {
                this.completing = false;
                this.setLoadingState(button, false);
            }
        }

        // ✅ MÉTODO: Excluir manutenção
        async deleteMaintenance(maintenanceId, maintenanceName) {
            if (this.deleting) return;
            
            let displayName = 'Manutenção';
            if (maintenanceName && maintenanceName !== 'null' && maintenanceName !== 'undefined' && maintenanceName.trim() !== '') {
                displayName = maintenanceName;
            }
            
            if (confirm(`Tem certeza que deseja excluir a manutenção "${displayName}"?\n\n⚠️ Esta ação não pode ser desfeita.`)) {
                this.deleting = true;
                
                try {
                    const formData = new FormData();
                    formData.append('id', maintenanceId);
                    
                    console.log(`🗑️ [MAINTENANCE] Excluindo manutenção: ${displayName}`);
                    
                    const apiUrl = '/bt-log-transportes/public/api/maintenance.php?action=delete';
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Manutenção excluída com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir manutenção');
                    }
                    
                } catch (error) {
                    console.error('❌ [MAINTENANCE] Erro ao excluir:', error);
                    this.showAlert('Erro ao excluir manutenção: ' + error.message, 'error');
                } finally {
                    this.deleting = false;
                }
            }
        }

        // ✅ MÉTODO: Validar formulário de manutenção
        validateMaintenanceForm() {
            const vehicle = document.getElementById('vehicle_id');
            const type = document.getElementById('type');
            const maintenanceDate = document.getElementById('maintenance_date');
            const description = document.getElementById('description');
            const status = document.getElementById('status').value;
            
            const errors = [];
            
            if (!vehicle || !vehicle.value) {
                errors.push('O veículo é obrigatório');
                vehicle?.focus();
            }
            
            if (!type || !type.value) {
                errors.push('O tipo de manutenção é obrigatório');
                type?.focus();
            }
            
            if (!maintenanceDate || !maintenanceDate.value) {
                errors.push('A data da manutenção é obrigatória');
                maintenanceDate?.focus();
            }
            
            if (!description || !description.value.trim()) {
                errors.push('A descrição do serviço é obrigatória');
                description?.focus();
            }
            
            // Validar custo apenas se status for "concluida"
            if (status === 'concluida') {
                const cost = document.getElementById('cost');
                if (!cost || !cost.value || parseFloat(cost.value) <= 0) {
                    errors.push('O custo deve ser maior que zero para manutenções concluídas');
                    cost?.focus();
                }
            }
            
            // Validar fornecedor
            const useSupplier = document.getElementById('use_supplier')?.checked;
            if (useSupplier) {
                const supplierSelection = document.getElementById('supplier_selection');
                if (!supplierSelection || !supplierSelection.value) {
                    errors.push('Selecione um fornecedor cadastrado');
                }
            } else {
                const serviceProvider = document.getElementById('service_provider');
                if (!serviceProvider || !serviceProvider.value.trim()) {
                    errors.push('O nome do prestador de serviço é obrigatório');
                }
            }
            
            // Validar datas
            const nextMaintenanceDate = document.getElementById('next_maintenance_date').value;
            if (nextMaintenanceDate && nextMaintenanceDate <= maintenanceDate.value) {
                errors.push('A data da próxima manutenção deve ser depois da data atual');
            }
            
            // Validar KM
            const currentKm = document.getElementById('current_km').value;
            const nextMaintenanceKm = document.getElementById('next_maintenance_km').value;
            if (nextMaintenanceKm && currentKm && parseFloat(nextMaintenanceKm) <= parseFloat(currentKm)) {
                errors.push('O KM da próxima manutenção deve ser maior que o KM atual');
            }
            
            if (errors.length > 0) {
                this.showAlert(errors.join('\n'), 'error');
                return false;
            }
            
            return true;
        }

        // ✅ MÉTODO: Definir estado de loading
        setLoadingState(button, isLoading) {
            if (!button) return;
            
            const btnText = button.querySelector('.btn-text');
            const btnLoading = button.querySelector('.btn-loading');
            
            if (isLoading) {
                if (btnText) btnText.style.display = 'none';
                if (btnLoading) btnLoading.style.display = 'flex';
                button.disabled = true;
            } else {
                if (btnText) btnText.style.display = 'block';
                if (btnLoading) btnLoading.style.display = 'none';
                button.disabled = false;
            }
        }

        // ✅ MÉTODO: Formatar input de currency
        formatCurrencyInput(input) {
            if (!input.value) return;
            
            let value = parseFloat(input.value);
            if (isNaN(value)) {
                input.value = '';
                return;
            }
            
            input.value = value.toFixed(2);
        }

        // ✅ MÉTODO: Mostrar alerta
        showAlert(message, type = 'info', duration = 5000) {
            // Criar alerta temporário
            const alertDiv = document.createElement('div');
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
                white-space: pre-line;
            `;
            
            const bgColors = {
                'warning': '#FF9800',
                'error': '#F44336',
                'success': '#4CAF50',
                'info': '#2196F3'
            };
            
            alertDiv.style.background = bgColors[type] || '#666';
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, duration);
        }

        // ✅ MÉTODO: Filtrar por empresa
        filterByCompany(companyId) {
            this.filters.company = companyId;
            this.applyFilters();
        }

        // ✅ MÉTODO: Filtrar por veículo
        filterByVehicle(vehicleId) {
            this.filters.vehicle = vehicleId;
            this.applyFilters();
        }

        // ✅ MÉTODO: Filtrar por tipo
        filterByType(type) {
            this.filters.type = type;
            this.applyFilters();
        }

        // ✅ MÉTODO: Filtrar por status
        filterByStatus(status) {
            this.filters.status = status;
            this.applyFilters();
        }

        // ✅ MÉTODO: Atualizar lista
        refreshMaintenances() {
            window.location.reload();
        }
    }

    // Inicialização
    if (!window.maintenanceManager) {
        window.maintenanceManager = new MaintenanceManager();
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.maintenanceManager.init();
            }, 500);
        });

        if (document.readyState !== 'loading') {
            setTimeout(() => {
                if (window.maintenanceManager && !window.maintenanceManager.isInitialized) {
                    window.maintenanceManager.init();
                }
            }, 800);
        }
    }

    // ✅ DEBUG: Verificar se o script carregou
    console.log('🛠️ maintenance.js carregado com sucesso!');

})();