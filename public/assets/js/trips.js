// public/assets/js/trips.js 
(function() {
    'use strict';

    if (window.TripsManagerLoaded) {
        console.log('🔧 Trips Manager já carregado');
        return;
    }
    window.TripsManagerLoaded = true;

    console.log('🚛 Trips Manager carregado');

    class TripsManager {
        constructor() {
            this.currentTripId = null;
            this.currentExpenses = [];
            this.isInitialized = false;
            this.eventListeners = new Set();
            this.modal = null;
            this.expensesModal = null;
            this.saving = false;
            this.deleting = false;
            this.availableServices = [];
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 TripsManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando TripsManager...');
            
            this.debugFormElements();
            
            this.removeAllEventListeners();
            
            setTimeout(() => {
                this.setupAllEvents();
                
                const companyId = document.getElementById('company_id')?.value;
                this.loadServices(companyId);
                
                this.isInitialized = true;
                console.log('✅ TripsManager inicializado com sucesso!');
            }, 100);
        }

        debugFormElements() {
            const elementsToCheck = [
                'tripForm', 'tripId', 'trip_number', 'status', 'origin_type',
                'destination_type', 'route_change', 'trip_services',
                'freight_value', 'company_id', 'client_id', 'driver_id', 'vehicle_id',
                'saveTripButton', 'cancelTripButton', 'has_additional_services',
                'services_section', 'financialSummary'
            ];
            
            console.group('🔍 DEBUG - Elementos do Formulário');
            elementsToCheck.forEach(id => {
                const element = document.getElementById(id);
                console.log(`${id}:`, element ? '✅ ENCONTRADO' : '❌ NÃO ENCONTRADO');
                if (element) {
                    console.log(`   - display: ${window.getComputedStyle(element).display}`);
                    console.log(`   - visibility: ${window.getComputedStyle(element).visibility}`);
                }
            });
            console.groupEnd();
        }

        removeAllEventListeners() {
            console.log('🧹 Removendo event listeners antigos do TripsManager...');
            
            const elementsToClean = [
                'newTripBtn',
                'cancelTripButton',
                'saveTripButton',
                'cancelExpenseButton',
                'saveExpenseButton',
                'company_id',
                'has_additional_services'
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
            this.setupServicesEvents();
        }

        setupButtonEvents() {
            console.log('🔧 Configurando eventos dos botões do TripsManager...');
            
            const newTripBtn = document.getElementById('newTripBtn');
            if (newTripBtn && !this.eventListeners.has('newTripBtn')) {
                newTripBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 [TRIPS] Botão nova viagem clicado');
                    this.openTripForm();
                });
                this.eventListeners.add('newTripBtn');
            }

            if (!this.eventListeners.has('delegation')) {
                this.delegationHandler = (e) => {
                    const tripRow = e.target.closest('tr[data-trip-id]');
                    if (!tripRow) return;

                    const editBtn = e.target.closest('.btn-edit');
                    if (editBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const tripId = tripRow.getAttribute('data-trip-id');
                        console.log('✏️ [TRIPS] Editando viagem:', tripId);
                        this.editTrip(tripId);
                        return;
                    }

                    const viewBtn = e.target.closest('.btn-view');
                    if (viewBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const tripId = tripRow.getAttribute('data-trip-id');
                        console.log('👁️ [TRIPS] Visualizando viagem:', tripId);
                        this.viewTrip(tripId);
                        return;
                    }

                    const expensesBtn = e.target.closest('.btn-expenses');
                    if (expensesBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const tripId = tripRow.getAttribute('data-trip-id');
                        console.log('💰 [TRIPS] Mostrando gastos da viagem:', tripId);
                        this.showExpenses(tripId);
                        return;
                    }

                    const deleteBtn = e.target.closest('.btn-delete');
                    if (deleteBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const tripId = tripRow.getAttribute('data-trip-id');
                        
                        let tripName = 'Viagem';
                        const tripNumberElement = tripRow.querySelector('.trip-info strong');
                        if (tripNumberElement) {
                            tripName = tripNumberElement.textContent;
                        }
                        
                        console.log('🗑️ [TRIPS] Excluindo viagem:', tripName);
                        this.deleteTrip(tripId, tripName);
                        return;
                    }
                };
                
                document.addEventListener('click', this.delegationHandler);
                this.eventListeners.add('delegation');
            }

            console.log('✅ Eventos dos botões do TripsManager configurados!');
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos dos modais de viagens...');
            
            this.modal = document.getElementById('tripModal');
            this.expensesModal = document.getElementById('expensesModal');
            
            if (!this.modal) {
                console.log('ℹ️ Modal de viagens ainda não carregado, aguardando...');
                setTimeout(() => {
                    this.modal = document.getElementById('tripModal');
                    if (this.modal) {
                        console.log('✅ Modal de viagens encontrado após delay');
                        this.setupTripModalEventListeners();
                    } else {
                        console.error('❌ Modal de viagens não encontrado após múltiplas tentativas');
                    }
                }, 500);
            } else {
                this.setupTripModalEventListeners();
            }

            if (!this.expensesModal) {
                setTimeout(() => {
                    this.expensesModal = document.getElementById('expensesModal');
                    if (this.expensesModal) {
                        console.log('✅ Modal de gastos encontrado após delay');
                        this.setupExpensesModalEventListeners();
                    }
                }, 500);
            } else {
                this.setupExpensesModalEventListeners();
            }
        }

        setupTripModalEventListeners() {
            if (!this.modal) {
                console.error('❌ Modal não disponível para configurar eventos');
                return;
            }

            const closeBtn = this.modal.querySelector('.modal-close');
            if (closeBtn && !this.eventListeners.has('modalClose')) {
                closeBtn.addEventListener('click', () => {
                    this.closeTripModal();
                });
                this.eventListeners.add('modalClose');
            }

            if (!this.eventListeners.has('modalOutsideClick')) {
                this.modal.addEventListener('click', (e) => {
                    if (e.target === this.modal) {
                        this.closeTripModal();
                    }
                });
                this.eventListeners.add('modalOutsideClick');
            }

            const cancelBtn = document.getElementById('cancelTripButton');
            if (cancelBtn && !this.eventListeners.has('cancelButton')) {
                cancelBtn.addEventListener('click', () => {
                    this.closeTripModal();
                });
                this.eventListeners.add('cancelButton');
            }

            const saveBtn = document.getElementById('saveTripButton');
            if (saveBtn && !this.eventListeners.has('saveButton')) {
                saveBtn.addEventListener('click', () => {
                    console.log('💾 [TRIPS] Botão salvar viagem clicado');
                    this.saveTrip();
                });
                this.eventListeners.add('saveButton');
            }

            console.log('✅ Eventos do modal de viagens configurados!');
        }

        setupExpensesModalEventListeners() {
            if (!this.expensesModal) return;

            const closeBtn = this.expensesModal.querySelector('.modal-close');
            if (closeBtn && !this.eventListeners.has('expensesModalClose')) {
                closeBtn.addEventListener('click', () => {
                    this.closeExpensesModal();
                });
                this.eventListeners.add('expensesModalClose');
            }

            if (!this.eventListeners.has('expensesModalOutsideClick')) {
                this.expensesModal.addEventListener('click', (e) => {
                    if (e.target === this.expensesModal) {
                        this.closeExpensesModal();
                    }
                });
                this.eventListeners.add('expensesModalOutsideClick');
            }

            const cancelBtn = document.getElementById('cancelExpenseButton');
            if (cancelBtn && !this.eventListeners.has('cancelExpenseButton')) {
                cancelBtn.addEventListener('click', () => {
                    this.closeExpensesModal();
                });
                this.eventListeners.add('cancelExpenseButton');
            }

            const saveBtn = document.getElementById('saveExpenseButton');
            if (saveBtn && !this.eventListeners.has('saveExpenseButton')) {
                saveBtn.addEventListener('click', () => {
                    console.log('💰 [TRIPS] Botão salvar gasto clicado');
                    this.saveExpense();
                });
                this.eventListeners.add('saveExpenseButton');
            }

            console.log('✅ Eventos do modal de gastos configurados!');
        }

        setupFormEvents() {
            setTimeout(() => {
                const freightInput = document.getElementById('freight_value');
                if (freightInput && !this.eventListeners.has('freightFormat')) {
                    freightInput.addEventListener('blur', (e) => {
                        this.formatCurrencyInput(e.target);
                        this.updateFinancialSummary();
                    });
                    freightInput.addEventListener('input', () => {
                        this.updateFinancialSummary();
                    });
                    this.eventListeners.add('freightFormat');
                }

                const expenseAmountInput = document.getElementById('expense_amount');
                if (expenseAmountInput && !this.eventListeners.has('expenseAmountFormat')) {
                    expenseAmountInput.addEventListener('blur', (e) => {
                        this.formatCurrencyInput(e.target);
                    });
                    this.eventListeners.add('expenseAmountFormat');
                }

                console.log('✅ Eventos do formulário de viagens configurados!');
            }, 200);
        }

        setupServicesEvents() {
            const hasServicesCheckbox = document.getElementById('has_additional_services');
            if (hasServicesCheckbox && !this.eventListeners.has('hasServicesCheckbox')) {
                hasServicesCheckbox.addEventListener('change', () => {
                    this.toggleServicesSection();
                });
                this.eventListeners.add('hasServicesCheckbox');
            }
            
            const servicesSelect = document.getElementById('trip_services');
            if (servicesSelect && !this.eventListeners.has('servicesSelect')) {
                servicesSelect.addEventListener('change', () => {
                    this.updateSelectedServicesList();
                    this.updateFinancialSummary();
                });
                this.eventListeners.add('servicesSelect');
            }
            
            const freightInput = document.getElementById('freight_value');
            if (freightInput && !this.eventListeners.has('freightChange')) {
                freightInput.addEventListener('input', () => {
                    this.updateFinancialSummary();
                });
                this.eventListeners.add('freightChange');
            }
            
            const driverSelect = document.getElementById('driver_id');
            if (driverSelect && !this.eventListeners.has('driverChange')) {
                driverSelect.addEventListener('change', () => {
                    this.updateFinancialSummary();
                });
                this.eventListeners.add('driverChange');
            }
        }

        toggleServicesSection() {
            const hasServices = document.getElementById('has_additional_services').checked;
            const servicesSection = document.getElementById('services_section');
            
            if (servicesSection) {
                if (hasServices) {
                    servicesSection.style.display = 'block';
                    const companyId = document.getElementById('company_id')?.value;
                    this.loadServices(companyId);
                } else {
                    servicesSection.style.display = 'none';
                    this.resetServices();
                }
                this.updateFinancialSummary();
            }
        }

        formatCurrencyInput(input) {
            if (!input.value) return;
            
            let value = parseFloat(input.value);
            if (isNaN(value)) {
                input.value = '';
                return;
            }
            
            input.value = value.toFixed(2);
        }

        showAlert(message, type = 'info') {
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
            }, 5000);
        }

        openTripForm(tripId = null) {
            console.log('🎯 [TRIPS] ABRINDO MODAL! TripId:', tripId);
            
            this.currentTripId = tripId;

            this.modal = document.getElementById('tripModal');
            
            if (!this.modal) {
                console.error('❌ MODAL VIAGENS NÃO ENCONTRADO!');
                this.showAlert('Erro: Modal não encontrado. Verifique se o HTML do modal está correto.', 'error');
                return;
            }

            const title = document.getElementById('modalTripTitle');

            if (tripId) {
                if (title) title.textContent = 'Editar Viagem';
                this.loadTripData(tripId);
            } else {
                if (title) title.textContent = 'Nova Viagem';
                this.resetForm();
            }

            console.log('🚀 Forçando abertura do modal...');
            this.modal.style.display = 'block';
            this.modal.classList.add('show');
            
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ [TRIPS] MODAL VIAGENS ABERTO COM SUCESSO!');
        }

        closeTripModal() {
            console.log('🔒 [TRIPS] Fechando modal...');
            
            if (this.modal) {
                this.modal.style.display = 'none';
                this.modal.classList.remove('show');
            } else {
                const anyModal = document.getElementById('tripModal');
                if (anyModal) {
                    anyModal.style.display = 'none';
                    anyModal.classList.remove('show');
                }
            }
            
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            this.setFormReadOnly(false);
            
            console.log('✅ Modal fechado com sucesso');
        }

        closeExpensesModal() {
            console.log('🔒 [TRIPS] Fechando modal de gastos...');
            if (this.expensesModal) {
                this.expensesModal.style.display = 'none';
                this.expensesModal.classList.remove('show');
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            this.resetExpenseForm();
        }

        editTrip(tripId) {
            console.log('✏️ [TRIPS] Editando viagem:', tripId);
            this.setFormReadOnly(false);
            this.openTripForm(tripId);
        }

        viewTrip(tripId) {
            console.log('👁️ [TRIPS] Visualizando viagem:', tripId);
            this.openTripForm(tripId);
            this.setFormReadOnly(true);
        }

        async showExpenses(tripId) {
            console.log('💰 [TRIPS] Mostrando gastos da viagem:', tripId);
            
            this.currentTripId = tripId;
            
            this.expensesModal = document.getElementById('expensesModal');
            
            if (!this.expensesModal) {
                console.error('❌ Modal de gastos não encontrado no DOM');
                this.showAlert('Erro: Modal de gastos não carregado', 'error');
                return;
            }

            const title = document.getElementById('modalExpensesTitle');
            if (title) {
                title.textContent = `Gastos da Viagem #${tripId}`;
            }

            await this.loadTripExpenses(tripId);

            const expenseTripIdField = document.getElementById('expenseTripId');
            if (expenseTripIdField) {
                expenseTripIdField.value = tripId;
            }

            console.log('🎯 Abrindo modal de gastos...');
            this.expensesModal.style.display = 'block';
            this.expensesModal.classList.add('show');
            
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ Modal de gastos aberto com sucesso');
        }

        setFormReadOnly(readOnly) {
            const form = document.getElementById('tripForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.id !== 'cancelTripButton') {
                    input.disabled = readOnly;
                }
            });

            const saveBtn = document.getElementById('saveTripButton');
            if (saveBtn) {
                saveBtn.style.display = readOnly ? 'none' : 'flex';
            }

            const title = document.getElementById('modalTripTitle');
            if (title && readOnly) {
                title.textContent = 'Visualizar Viagem';
            }
        }

        resetForm() {
            const form = document.getElementById('tripForm');
            if (form) {
                form.reset();
                
                this.safeSetValue('tripId', '');
                this.safeSetValue('trip_number', '');
                this.safeSetValue('status', 'agendada');
                
                this.safeSetValue('origin_type', 'base');
                this.safeSetValue('destination_type', 'base');
                
                this.safeSetChecked('route_change', false);
                this.safeSetChecked('has_additional_services', false);
                
                this.resetServices();
                
                this.toggleServicesSection();
                
                this.toggleOriginFields();
                this.toggleDestinationFields();
                this.toggleRouteChange();
                
            } else {
                console.warn('⚠️ [TRIPS] Formulário não encontrado para reset');
            }
        }

        safeSetValue(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.value = value;
            }
        }

        safeSetChecked(elementId, checked) {
            const element = document.getElementById(elementId);
            if (element) {
                element.checked = checked;
            }
        }

        resetServices() {
            const servicesSelect = document.getElementById('trip_services');
            if (servicesSelect) {
                Array.from(servicesSelect.options).forEach(option => {
                    option.selected = false;
                });
                this.updateSelectedServicesList();
            }
            
            this.updateFinancialSummary();
        }

        resetExpenseForm() {
            const form = document.getElementById('expenseForm');
            if (form) {
                form.reset();
                document.getElementById('expense_date').value = new Date().toISOString().split('T')[0];
            }
        }

        async loadTripData(tripId) {
			console.log(`📥 [TRIPS] Carregando viagem ${tripId}`);
			
			try {
				const apiUrl = `/bt-log-transportes/public/api/trips.php?action=get&id=${tripId}`;
				const response = await fetch(apiUrl);
				
				if (!response.ok) {
					throw new Error('Erro na requisição: ' + response.status);
				}
				
				const result = await response.json();

				if (result.success && result.data) {
					this.populateForm(result.data);
					
					// ✅ CORREÇÃO: Carregar despesas também para o resumo financeiro
					await this.loadTripExpensesForSummary(tripId);
					
					console.log('✅ [TRIPS] Dados da viagem carregados com sucesso');
				} else {
					throw new Error(result.message || 'Erro ao carregar dados da viagem');
				}
			} catch (error) {
				console.error('❌ [TRIPS] Erro ao carregar dados:', error);
				this.showAlert('Erro ao carregar dados da viagem: ' + error.message, 'error');
			}
		}
		
		async loadTripExpensesForSummary(tripId) {
			try {
				const expensesUrl = `/bt-log-transportes/public/api/trips.php?action=get_expenses&trip_id=${tripId}`;
				const expensesResponse = await fetch(expensesUrl);
				
				if (expensesResponse.ok) {
					const expensesResult = await expensesResponse.json();
					if (expensesResult.success) {
						this.currentExpenses = expensesResult.data || [];
						console.log('✅ Despesas carregadas para resumo:', this.currentExpenses.length);
					}
				}
			} catch (error) {
				console.error('❌ Erro ao carregar despesas para resumo:', error);
				this.currentExpenses = [];
			}
		}
        
        populateForm(trip) {
			console.log('📝 [TRIPS] Preenchendo formulário com dados completos:', trip);
			
			// ✅ CORREÇÃO: Preencher todos os campos
			this.safeSetValue('tripId', trip.id || '');
			this.safeSetValue('company_id', trip.company_id || '');
			this.safeSetValue('client_id', trip.client_id || '');
			this.safeSetValue('driver_id', trip.driver_id || '');
			this.safeSetValue('vehicle_id', trip.vehicle_id || '');
			this.safeSetValue('origin_base_id', trip.origin_base_id || '');
			this.safeSetValue('destination_base_id', trip.destination_base_id || '');
			this.safeSetValue('origin_address', trip.origin_address || '');
			this.safeSetValue('destination_address', trip.destination_address || '');
			this.safeSetValue('distance_km', trip.distance_km || '');
			this.safeSetValue('freight_value', trip.freight_value || '');
			this.safeSetValue('description', trip.description || '');
			this.safeSetValue('status', trip.status || 'agendada');

			// ✅ CORREÇÃO: Preencher datas corretamente
			if (trip.scheduled_date) {
				const scheduledDate = new Date(trip.scheduled_date);
				this.safeSetValue('scheduled_date', scheduledDate.toISOString().slice(0, 16));
			}
			if (trip.start_date) {
				const startDate = new Date(trip.start_date);
				this.safeSetValue('start_date', startDate.toISOString().slice(0, 16));
			}
			if (trip.end_date) {
				const endDate = new Date(trip.end_date);
				this.safeSetValue('end_date', endDate.toISOString().slice(0, 16));
			}

			// ✅ CORREÇÃO: Preencher tipo de origem/destino
			if (trip.origin_base_id) {
				this.safeSetValue('origin_type', 'base');
			} else {
				this.safeSetValue('origin_type', 'custom');
			}
			
			if (trip.destination_base_id) {
				this.safeSetValue('destination_type', 'base');
			} else {
				this.safeSetValue('destination_type', 'custom');
			}
			
			this.toggleOriginFields();
			this.toggleDestinationFields();
			
			// ✅ CORREÇÃO: Preencher mudança de rota
			if (trip.route_change || trip.actual_origin_address || trip.actual_destination_address) {
				this.safeSetChecked('route_change', true);
				this.safeSetValue('actual_origin_address', trip.actual_origin_address || '');
				this.safeSetValue('actual_destination_address', trip.actual_destination_address || '');
				this.safeSetValue('route_change_reason', trip.route_change_reason || '');
				this.toggleRouteChange();
			}

			// ✅ CORREÇÃO: Preencher serviços
			if (trip.services && trip.services.length > 0) {
				console.log('🛠️ Serviços encontrados:', trip.services);
				this.safeSetChecked('has_additional_services', true);
				this.toggleServicesSection();
				
				// Carregar serviços primeiro, depois preencher
				setTimeout(() => {
					const servicesSelect = document.getElementById('trip_services');
					if (servicesSelect) {
						// Selecionar serviços da viagem
						trip.services.forEach(service => {
							const option = Array.from(servicesSelect.options).find(
								opt => opt.value == service.service_id
							);
							if (option) {
								option.selected = true;
							}
						});
						this.updateSelectedServicesList();
					}
				}, 300);
			}

			// ✅ CORREÇÃO: Atualizar resumo financeiro com todos os dados
			setTimeout(() => {
				this.updateFinancialSummary();
			}, 500);

			console.log('✅ Formulário preenchido com todos os dados');
		}

        loadMockData(tripId) {
            console.log('🎭 [TRIPS] Carregando dados mock');
            
            const mockData = {
                id: tripId,
                company_id: 1,
                trip_number: 'TRP-2024-0001',
                client_id: 1,
                driver_id: 1,
                vehicle_id: 1,
                origin_base_id: 1,
                destination_base_id: 2,
                origin_address: 'Av. Paulista, 1000 - São Paulo, SP',
                destination_address: 'Rua XV de Novembro, 200 - Curitiba, PR',
                distance_km: 400,
                scheduled_date: '2024-01-15 08:00:00',
                start_date: '2024-01-15 08:30:00',
                end_date: '2024-01-15 18:00:00',
                freight_value: 2500.00,
                description: 'Transporte de equipamentos eletrônicos',
                status: 'concluida'
            };

            this.populateForm(mockData);
        }

        async loadTripExpenses(tripId) {
			try {
				console.log(`💰 [TRIPS] Carregando despesas da viagem ${tripId}`);
				
				// ✅ CORREÇÃO: Usar API específica para buscar despesas
				const apiUrl = `/bt-log-transportes/public/api/trips.php?action=get&id=${tripId}`;
				const response = await fetch(apiUrl);
				
				if (!response.ok) {
					throw new Error('Erro na requisição: ' + response.status);
				}
				
				const result = await response.json();
				console.log('📊 Dados da viagem recebidos:', result);

				if (result.success && result.data) {
					// ✅ CORREÇÃO: Buscar despesas da viagem
					const expensesUrl = `/bt-log-transportes/public/api/trips.php?action=get_expenses&trip_id=${tripId}`;
					const expensesResponse = await fetch(expensesUrl);
					
					if (expensesResponse.ok) {
						const expensesResult = await expensesResponse.json();
						if (expensesResult.success) {
							this.currentExpenses = expensesResult.data || [];
							console.log('✅ Despesas carregadas:', this.currentExpenses);
						} else {
							// Fallback: buscar despesas dos dados da viagem
							this.currentExpenses = result.data.expenses || [];
							console.log('⚠️ Usando fallback para despesas:', this.currentExpenses);
						}
					} else {
						// Fallback alternativo
						this.currentExpenses = result.data.trip_expenses || [];
						console.log('⚠️ Fallback alternativo para despesas:', this.currentExpenses);
					}
					
					this.renderExpensesList();
				} else {
					throw new Error(result.message || 'Erro ao carregar dados da viagem');
				}
			} catch (error) {
				console.error('❌ [TRIPS] Erro ao carregar despesas:', error);
				
				// ✅ CORREÇÃO: Tentar carregar despesas diretamente
				try {
					const directExpensesUrl = `/bt-log-transportes/public/api/trips.php?action=get_expenses&trip_id=${tripId}`;
					const directResponse = await fetch(directExpensesUrl);
					
					if (directResponse.ok) {
						const directResult = await directResponse.json();
						if (directResult.success) {
							this.currentExpenses = directResult.data || [];
							console.log('✅ Despesas carregadas via API direta:', this.currentExpenses);
							this.renderExpensesList();
							return;
						}
					}
				} catch (fallbackError) {
					console.error('❌ Fallback também falhou:', fallbackError);
				}
				
				this.showAlert('Erro ao carregar despesas: ' + error.message, 'error');
				this.currentExpenses = [];
				this.renderExpensesList();
			}
		}

        renderExpensesList() {
			const expensesList = document.getElementById('expensesList');
			if (!expensesList) return;

			if (this.currentExpenses.length === 0) {
				expensesList.innerHTML = `
					<div class="empty-state" style="padding: 2rem;">
						<i class="fas fa-receipt"></i>
						<h3>Nenhum gasto registrado</h3>
						<p>Adicione o primeiro gasto desta viagem.</p>
					</div>
				`;
				return;
			}

			let html = '';
			let total = 0;

			this.currentExpenses.forEach(expense => {
				total += parseFloat(expense.amount);
				
				html += `
					<div class="expense-item" data-expense-id="${expense.id}">
						<div class="expense-info">
							<div class="expense-type">${this.getExpenseTypeName(expense.expense_type)}</div>
							${expense.description ? `<div class="expense-description">${expense.description}</div>` : ''}
							<div class="expense-date">${this.formatDate(expense.expense_date)}</div>
						</div>
						<div class="expense-actions">
							<span class="expense-amount">
								R$ ${parseFloat(expense.amount).toFixed(2)}
							</span>
							<button class="btn-icon btn-delete-expense" 
									onclick="tripsManager.deleteExpense(${expense.id}, ${this.currentTripId})"
									title="Excluir despesa">
								<i class="fas fa-trash"></i>
							</button>
						</div>
					</div>
				`;
			});

			html += `
				<div class="expense-total">
					<div>Total de Gastos:</div>
					<div class="amount">R$ ${total.toFixed(2)}</div>
				</div>
			`;

			expensesList.innerHTML = html;
		}
		
		async deleteExpense(expenseId, tripId) {
			if (!confirm('Tem certeza que deseja excluir esta despesa?')) {
				return;
			}

			try {
				console.log(`🗑️ Excluindo despesa ${expenseId} da viagem ${tripId}`);
				
				const formData = new FormData();
				formData.append('expense_id', expenseId);
				formData.append('trip_id', tripId);
				
				const apiUrl = '/bt-log-transportes/public/api/trips.php?action=delete_expense';
				const response = await fetch(apiUrl, {
					method: 'POST',
					body: formData
				});
				
				const result = await response.json();
				
				if (result.success) {
					this.showAlert('Despesa excluída com sucesso!', 'success');
					// Remover do array local
					this.currentExpenses = this.currentExpenses.filter(expense => expense.id != expenseId);
					this.renderExpensesList();
					// Atualizar resumo financeiro no modal principal
					this.updateFinancialSummary();
				} else {
					throw new Error(result.message || 'Erro ao excluir despesa');
				}
				
			} catch (error) {
				console.error('❌ Erro ao excluir despesa:', error);
				this.showAlert('Erro ao excluir despesa: ' + error.message, 'error');
			}
		}

        getExpenseTypeName(type) {
            const types = {
                'combustivel': 'Combustível',
                'pedagio': 'Pedágio',
                'hospedagem': 'Hospedagem',
                'alimentacao': 'Alimentação',
                'manutencao': 'Manutenção',
                'outros': 'Outros'
            };
            return types[type] || type;
        }

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        async saveTrip() {
            if (this.saving) return;
            
            this.saving = true;
            console.log('💾 [TRIPS] Salvando viagem...');
            
            if (!this.validateTripForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveTripButton');
            this.setLoadingState(saveBtn, true);

            try {
                const formData = new FormData(document.getElementById('tripForm'));

                console.group('📋 Dados do Formulário:');
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}:`, value);
                }
                console.groupEnd();

                const tripId = this.currentTripId;
                
                const apiUrl = '/bt-log-transportes/public/api/trips.php?action=save';
                
                console.log(`🚀 [TRIPS] Enviando para API: id=${tripId}`);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                console.log('📡 [TRIPS] Resposta bruta:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('❌ [TRIPS] Erro ao parsear JSON:', parseError);
                    console.error('📄 Resposta do servidor:', responseText);
                    throw new Error('Resposta inválida do servidor - não é JSON');
                }

                console.log('📊 [TRIPS] Resposta parseada:', result);

                if (result.success) {
                    console.log('✅ [TRIPS] VIAGEM SALVA COM SUCESSO!');
                    this.showAlert('Viagem salva com sucesso!', 'success');
                    this.closeTripModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Erro ao salvar viagem');
                }
                
            } catch (error) {
                console.error('💥 [TRIPS] Erro:', error);
                this.showAlert('Erro ao salvar viagem: ' + error.message, 'error');
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        async saveExpense() {
			if (this.saving) return;
			
			this.saving = true;
			console.log('💰 [TRIPS] Salvando gasto...');
			
			if (!this.validateExpenseForm()) {
				this.saving = false;
				return;
			}

			const saveBtn = document.getElementById('saveExpenseButton');
			this.setLoadingState(saveBtn, true);

			try {
				const formData = new FormData(document.getElementById('expenseForm'));

				const apiUrl = '/bt-log-transportes/public/api/trips.php?action=add_expense';
				
				console.log('🚀 [TRIPS] Enviando gasto para API');

				const response = await fetch(apiUrl, {
					method: 'POST',
					body: formData
				});

				const result = await response.json();

				if (result.success) {
					console.log('✅ [TRIPS] GASTO SALVO COM SUCESSO!');
					this.showAlert('Gasto adicionado com sucesso!', 'success');
					this.resetExpenseForm();
					await this.loadTripExpenses(this.currentTripId);
					
					// ✅ CORREÇÃO: Atualizar resumo financeiro no modal principal
					this.updateFinancialSummary();
				} else {
					throw new Error(result.message || 'Erro ao salvar gasto');
				}
				
			} catch (error) {
				console.error('💥 [TRIPS] Erro:', error);
				this.showAlert('Erro ao salvar gasto: ' + error.message, 'error');
			} finally {
				this.saving = false;
				this.setLoadingState(saveBtn, false);
			}
		}

        async deleteTrip(tripId, tripName) {
            if (this.deleting) return;
            
            let displayName = 'Viagem';
            if (tripName && tripName !== 'null' && tripName !== 'undefined' && tripName.trim() !== '') {
                displayName = tripName;
            }
            
            if (confirm(`Tem certeza que deseja excluir a viagem "${displayName}"?\n\n⚠️ Todos os gastos e comissões relacionados também serão excluídos.`)) {
                this.deleting = true;
                
                try {
                    const formData = new FormData();
                    formData.append('id', tripId);
                    
                    console.log(`🗑️ [TRIPS] Excluindo viagem: ${displayName}`);
                    
                    const apiUrl = '/bt-log-transportes/public/api/trips.php?action=delete';
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Viagem excluída com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir viagem');
                    }
                    
                } catch (error) {
                    console.error('❌ [TRIPS] Erro ao excluir:', error);
                    this.showAlert('Erro ao excluir viagem: ' + error.message, 'error');
                } finally {
                    this.deleting = false;
                }
            }
        }

        validateTripForm() {
            const company = document.getElementById('company_id');
            const client = document.getElementById('client_id');
            const driver = document.getElementById('driver_id');
            const vehicle = document.getElementById('vehicle_id');
            const originType = document.getElementById('origin_type');
            const destinationType = document.getElementById('destination_type');
            const originBase = document.getElementById('origin_base_id');
            const destinationBase = document.getElementById('destination_base_id');
            const originAddress = document.getElementById('origin_address');
            const destinationAddress = document.getElementById('destination_address');
            const freightValue = document.getElementById('freight_value');
            
            console.log('🔍 Validando formulário...');
            console.log('Origin Type:', originType?.value);
            console.log('Origin Base:', originBase?.value);
            console.log('Origin Address:', originAddress?.value);
            
            if (!company || !company.value) {
                this.showAlert('A empresa é obrigatória', 'warning');
                company.focus();
                return false;
            }
            
            if (!client || !client.value) {
                this.showAlert('O cliente é obrigatório', 'warning');
                client.focus();
                return false;
            }
            
            if (!driver || !driver.value) {
                this.showAlert('O motorista é obrigatório', 'warning');
                driver.focus();
                return false;
            }
            
            if (!vehicle || !vehicle.value) {
                this.showAlert('O veículo é obrigatório', 'warning');
                vehicle.focus();
                return false;
            }
            
            if (originType && originType.value === 'base') {
                if (!originBase || !originBase.value) {
                    this.showAlert('A base de origem é obrigatória', 'warning');
                    originBase.focus();
                    return false;
                }
            } else {
                if (!originAddress || !originAddress.value.trim()) {
                    this.showAlert('O endereço de origem é obrigatório', 'warning');
                    originAddress.focus();
                    return false;
                }
            }
            
            if (destinationType && destinationType.value === 'base') {
                if (!destinationBase || !destinationBase.value) {
                    this.showAlert('A base de destino é obrigatória', 'warning');
                    destinationBase.focus();
                    return false;
                }
            } else {
                if (!destinationAddress || !destinationAddress.value.trim()) {
                    this.showAlert('O endereço de destino é obrigatório', 'warning');
                    destinationAddress.focus();
                    return false;
                }
            }
            
            if (!freightValue || !freightValue.value || parseFloat(freightValue.value) <= 0) {
                this.showAlert('O valor do frete deve ser maior que zero', 'warning');
                freightValue.focus();
                return false;
            }
            
            console.log('✅ Validação do formulário concluída com sucesso');
            return true;
        }

        validateExpenseForm() {
            const expenseType = document.getElementById('expense_type');
            const expenseAmount = document.getElementById('expense_amount');
            
            if (!expenseType || !expenseType.value) {
                this.showAlert('O tipo de gasto é obrigatório', 'warning');
                expenseType.focus();
                return false;
            }
            
            if (!expenseAmount || !expenseAmount.value || parseFloat(expenseAmount.value) <= 0) {
                this.showAlert('O valor do gasto deve ser maior que zero', 'warning');
                expenseAmount.focus();
                return false;
            }
            
            return true;
        }

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

        toggleOriginFields() {
            const originType = document.getElementById('origin_type');
            const baseField = document.getElementById('origin_base_field');
            const customField = document.getElementById('origin_custom_field');
            
            if (originType && baseField && customField) {
                if (originType.value === 'custom') {
                    baseField.style.display = 'none';
                    customField.style.display = 'flex';
                    if (document.getElementById('origin_address')) {
                        document.getElementById('origin_address').required = true;
                    }
                    if (document.getElementById('origin_base_id')) {
                        document.getElementById('origin_base_id').required = false;
                    }
                } else {
                    baseField.style.display = 'flex';
                    customField.style.display = 'none';
                    if (document.getElementById('origin_address')) {
                        document.getElementById('origin_address').required = false;
                    }
                    if (document.getElementById('origin_base_id')) {
                        document.getElementById('origin_base_id').required = true;
                    }
                }
            }
        }

        toggleDestinationFields() {
            const destinationType = document.getElementById('destination_type');
            const baseField = document.getElementById('destination_base_field');
            const customField = document.getElementById('destination_custom_field');
            
            if (destinationType && baseField && customField) {
                if (destinationType.value === 'custom') {
                    baseField.style.display = 'none';
                    customField.style.display = 'flex';
                    if (document.getElementById('destination_address')) {
                        document.getElementById('destination_address').required = true;
                    }
                    if (document.getElementById('destination_base_id')) {
                        document.getElementById('destination_base_id').required = false;
                    }
                } else {
                    baseField.style.display = 'flex';
                    customField.style.display = 'none';
                    if (document.getElementById('destination_address')) {
                        document.getElementById('destination_address').required = false;
                    }
                    if (document.getElementById('destination_base_id')) {
                        document.getElementById('destination_base_id').required = true;
                    }
                }
            }
        }

        toggleRouteChange() {
            const routeChange = document.getElementById('route_change');
            const routeChangeFields = document.getElementById('route_change_fields');
            
            if (routeChange && routeChangeFields) {
                if (routeChange.checked) {
                    routeChangeFields.style.display = 'block';
                } else {
                    routeChangeFields.style.display = 'none';
                }
            }
        }

        async loadServices(companyId = null) {
			try {
				console.log('📡 Buscando serviços do banco de dados...');
				
				let apiUrl = '/bt-log-transportes/public/api/trips.php?action=get_services';
				if (companyId) {
					apiUrl += `&company_id=${companyId}`;
				}
				
				console.log('🔗 URL da API:', apiUrl);
				
				const response = await fetch(apiUrl);
				
				// ✅ CORREÇÃO: Verificar se a resposta é JSON válido
				const responseText = await response.text();
				console.log('📄 Resposta bruta:', responseText.substring(0, 200)); // Mostra apenas os primeiros 200 chars
				
				let result;
				try {
					result = JSON.parse(responseText);
				} catch (parseError) {
					console.error('❌ Resposta não é JSON válido:', responseText);
					
					// ✅ CORREÇÃO: Tentar carregar serviços via PHP diretamente
					const success = this.loadServicesViaPHP();
					if (success) {
						console.log('✅ Serviços carregados via PHP como fallback');
						return this.availableServices;
					}
					
					throw new Error('Resposta da API não é JSON válido. Verifique se há erros PHP.');
				}
				
				if (!response.ok) {
					throw new Error('Erro na requisição: ' + response.status);
				}
				
				if (result.success) {
					this.availableServices = result.data || [];
					console.log('✅ Serviços carregados do banco:', this.availableServices.length, 'serviços');
				} else {
					console.warn('⚠️ API retornou sucesso=false:', result.message);
					this.availableServices = [];
					
					// ✅ CORREÇÃO: Tentar carregar via PHP como fallback
					this.loadServicesViaPHP();
				}
				
				this.populateServicesDropdown();
				
				return this.availableServices;
				
			} catch (error) {
				console.error('❌ Erro ao carregar serviços:', error);
				
				// ✅ CORREÇÃO: Tentar carregar via PHP como último recurso
				const success = this.loadServicesViaPHP();
				
				if (!success) {
					this.showAlert('Aviso: Não foi possível carregar a lista de serviços. Verifique se há serviços cadastrados.', 'warning');
				}
				
				return this.availableServices;
			}
		}

        populateServicesDropdown() {
			const servicesSelect = document.getElementById('trip_services');
			if (!servicesSelect) {
				console.warn('⚠️ Select de serviços não encontrado');
				return;
			}
			
			// ✅ CORREÇÃO: Manter opções selecionadas ao recarregar
			const currentlySelected = Array.from(servicesSelect.selectedOptions).map(opt => opt.value);
			
			// Limpar apenas as opções dinâmicas, manter a primeira
			while (servicesSelect.options.length > 1) {
				servicesSelect.remove(1);
			}
			
			if (this.availableServices.length === 0) {
				console.warn('⚠️ Nenhum serviço disponível no banco');
				const option = document.createElement('option');
				option.value = '';
				option.textContent = 'Nenhum serviço cadastrado';
				option.disabled = true;
				servicesSelect.appendChild(option);
			} else {
				this.availableServices.forEach(service => {
					const option = document.createElement('option');
					option.value = service.id;
					option.textContent = `${service.name} - R$ ${parseFloat(service.base_price).toFixed(2)}`;
					option.dataset.price = service.base_price;
					
					// ✅ CORREÇÃO: Manter seleção anterior
					if (currentlySelected.includes(service.id.toString())) {
						option.selected = true;
					}
					
					servicesSelect.appendChild(option);
				});
				
				console.log('✅ Dropdown de serviços preenchido:', this.availableServices.length, 'serviços');
			}
			
			// Atualizar lista de serviços selecionados
			this.updateSelectedServicesList();
		}

        loadServicesViaPHP() {
            console.log('🔄 Tentando carregar serviços via PHP...');
            
            const servicesSelect = document.getElementById('trip_services');
            if (servicesSelect && servicesSelect.options.length > 1) {
                this.availableServices = [];
                
                Array.from(servicesSelect.options).forEach((option, index) => {
                    if (index > 0 && option.value && option.value !== '') {
                        const textParts = option.text.split(' - R$ ');
                        const service = {
                            id: option.value,
                            name: textParts[0] || option.text,
                            base_price: option.dataset.price || (textParts[1] ? parseFloat(textParts[1].replace('.', '').replace(',', '.')) : 0)
                        };
                        this.availableServices.push(service);
                    }
                });
                
                console.log('✅ Serviços carregados via PHP:', this.availableServices.length);
                return true;
            } else {
                console.warn('⚠️ Nenhum serviço encontrado no HTML via PHP');
                this.availableServices = [];
                return false;
            }
        }

        updateSelectedServicesList() {
            const servicesSelect = document.getElementById('trip_services');
            const selectedServicesList = document.getElementById('selected_services_list');
            
            if (!servicesSelect || !selectedServicesList) {
                console.warn('⚠️ Elementos de serviços não encontrados');
                return;
            }
            
            const selectedOptions = Array.from(servicesSelect.selectedOptions);
            
            if (selectedOptions.length === 0) {
                selectedServicesList.innerHTML = '<p class="text-muted">Nenhum serviço selecionado</p>';
            } else {
                let html = '<div class="selected-services"><h5>Serviços Selecionados:</h5><ul>';
                
                selectedOptions.forEach(option => {
                    const price = option.dataset.price ? parseFloat(option.dataset.price).toFixed(2) : '0.00';
                    html += `<li>${option.text}</li>`;
                });
                
                html += '</ul></div>';
                selectedServicesList.innerHTML = html;
            }
        }

        async getDriverCommission(driverId) {
			if (!driverId) {
				console.log('⚠️ Nenhum motorista selecionado, usando comissão padrão 0%');
				return 0.00; // ✅ CORREÇÃO: 0% se não tem motorista
			}
			
			try {
				console.log(`💰 Buscando comissão do motorista: ${driverId}`);
				
				const apiUrl = `/bt-log-transportes/public/api/trips.php?action=get_driver_commission&driver_id=${driverId}`;
				const response = await fetch(apiUrl);
				
				const contentType = response.headers.get('content-type');
				if (!contentType || !contentType.includes('application/json')) {
					console.warn('⚠️ Resposta da comissão não é JSON, usando valor padrão 0%');
					return 0.00; // ✅ CORREÇÃO: 0% em caso de erro
				}
				
				if (!response.ok) {
					throw new Error('Erro na requisição: ' + response.status);
				}
				
				const result = await response.json();
				
				if (result.success) {
					// ✅ CORREÇÃO: A API já deve retornar a comissão correta baseada no tipo de motorista
					const commissionRate = result.data.commission_rate || 0.00;
					console.log(`✅ Comissão do motorista: ${commissionRate}%`);
					return commissionRate;
				} else {
					console.warn('⚠️ Erro ao buscar comissão:', result.message);
					return 0.00; // ✅ CORREÇÃO: 0% em caso de erro
				}
				
			} catch (error) {
				console.error('❌ Erro ao buscar comissão do motorista:', error);
				return 0.00; // ✅ CORREÇÃO: 0% em caso de erro
			}
		}

        async updateFinancialSummary() {
			const freightValue = parseFloat(document.getElementById('freight_value')?.value) || 0;
			const servicesSelect = document.getElementById('trip_services');
			const hasServices = document.getElementById('has_additional_services')?.checked;
			const driverId = document.getElementById('driver_id')?.value;
			
			let servicesTotal = 0;
			if (hasServices && servicesSelect) {
				const selectedOptions = Array.from(servicesSelect.selectedOptions);
				selectedOptions.forEach(option => {
					const price = parseFloat(option.dataset.price) || 0;
					servicesTotal += price;
				});
			}
			
			// ✅ CORREÇÃO: Buscar comissão correta
			let commissionRate = 0.00;
			if (driverId) {
				commissionRate = await this.getDriverCommission(driverId);
			}
			
			const commissionAmount = (freightValue * commissionRate) / 100;
			
			// ✅ CORREÇÃO: Incluir despesas no cálculo
			let totalExpenses = 0;
			if (this.currentExpenses && this.currentExpenses.length > 0) {
				totalExpenses = this.currentExpenses.reduce((sum, expense) => {
					return sum + parseFloat(expense.amount || 0);
				}, 0);
			}
			
			// ✅ CORREÇÃO: Calcular totais incluindo despesas
			const totalRevenue = freightValue + servicesTotal;
			const totalCost = commissionAmount + totalExpenses; // Comissão + Despesas
			const profit = totalRevenue - totalCost;
			
			this.updateSummaryDisplay({
				freightValue,
				servicesTotal,
				commissionAmount,
				commissionRate,
				totalExpenses, // ✅ NOVO: Incluir despesas
				totalRevenue,
				totalCost,  
				profit
			});
		}

        updateSummaryDisplay(totals) {
			let summaryContainer = document.getElementById('financialSummary');
			
			if (!summaryContainer) {
				summaryContainer = document.createElement('div');
				summaryContainer.id = 'financialSummary';
				summaryContainer.className = 'financial-summary';
				
				const servicesSection = document.getElementById('services_section');
				if (servicesSection) {
					servicesSection.parentNode.insertBefore(summaryContainer, servicesSection.nextSibling);
				} else {
					console.warn('⚠️ Seção de serviços não encontrada para inserir resumo');
					return;
				}
			}
			
			// ✅ CORREÇÃO: Exibir comissão apenas se for maior que 0%
			const commissionDisplay = totals.commissionRate > 0 ? 
				`<div class="summary-item">
					<span class="summary-label">Comissão Motorista (${totals.commissionRate}%):</span>
					<span class="summary-value commission">- R$ ${totals.commissionAmount.toFixed(2)}</span>
				</div>` : 
				`<div class="summary-item">
					<span class="summary-label">Comissão Motorista:</span>
					<span class="summary-value commission">Sem comissão</span>
				</div>`;
			
			// ✅ CORREÇÃO: Exibir despesas se existirem
			const expensesDisplay = totals.totalExpenses > 0 ?
				`<div class="summary-item">
					<span class="summary-label">Despesas da Viagem:</span>
					<span class="summary-value expenses">- R$ ${totals.totalExpenses.toFixed(2)}</span>
				</div>` : '';
			
			summaryContainer.innerHTML = `
				<div class="form-section">
					<h4>Resumo Financeiro</h4>
					<div class="financial-summary-grid">
						<div class="summary-item">
							<span class="summary-label">Valor do Frete:</span>
							<span class="summary-value freight">R$ ${totals.freightValue.toFixed(2)}</span>
						</div>
						${totals.servicesTotal > 0 ? `
						<div class="summary-item">
							<span class="summary-label">Serviços Adicionais:</span>
							<span class="summary-value services">+ R$ ${totals.servicesTotal.toFixed(2)}</span>
						</div>
						` : ''}
						<div class="summary-item revenue-total">
							<span class="summary-label"><strong>Receita Total:</strong></span>
							<span class="summary-value revenue"><strong>R$ ${totals.totalRevenue.toFixed(2)}</strong></span>
						</div>
						${commissionDisplay}
						${expensesDisplay}
						<div class="summary-item cost-total">
							<span class="summary-label"><strong>Despesa Total:</strong></span>
							<span class="summary-value cost"><strong>R$ ${totals.totalCost.toFixed(2)}</strong></span>
						</div>
						<div class="summary-item total">
							<span class="summary-label"><strong>Lucro Líquido:</strong></span>
							<span class="summary-value total ${totals.profit >= 0 ? 'positive' : 'negative'}">
								<strong>R$ ${totals.profit.toFixed(2)}</strong>
							</span>
						</div>
					</div>
				</div>
			`;
		}

        onCompanyChange(companyId) {
            console.log(`🔄 [TRIPS] Empresa selecionada: ${companyId}`);
            this.loadServices(companyId);
        }

        filterByCompany(companyId) {
            const url = new URL(window.location);
            if (companyId) {
                url.searchParams.set('company', companyId);
            } else {
                url.searchParams.delete('company');
            }
            window.location.href = url.toString();
        }

        filterByStatus(status) {
            const url = new URL(window.location);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }

        filterByDate(date) {
            const url = new URL(window.location);
            if (date) {
                url.searchParams.set('date', date);
            } else {
                url.searchParams.delete('date');
            }
            window.location.href = url.toString();
        }

        refreshTrips() {
            window.location.reload();
        }
    }

    if (!window.tripsManager) {
        window.tripsManager = new TripsManager();
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.tripsManager.init();
            }, 500);
        });

        if (document.readyState !== 'loading') {
            setTimeout(() => {
                if (window.tripsManager && !window.tripsManager.isInitialized) {
                    window.tripsManager.init();
                }
            }, 800);
        }
    }

    console.log('🚛 trips.js carregado com sucesso!');

})();