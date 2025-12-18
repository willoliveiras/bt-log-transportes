// public/assets/js/drivers.js - VERSÃO COMPLETA E CORRIGIDA (SEM DADOS MOCK)
(function() {
    'use strict';

    if (window.DriversManagerLoaded) {
        console.log('🔧 Drivers Manager já carregado');
        return;
    }
    window.DriversManagerLoaded = true;

    console.log('🚚 Drivers Manager carregado');

    class DriversManager {
        constructor() {
            this.currentDriverId = null;
            this.isInitialized = false;
            this.eventListeners = new Set();
            this.modal = null;
            this.saving = false;
            this.deleting = false;
            this.managerId = 'drivers';
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 DriversManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando DriversManager...');
            
            this.removeAllEventListeners();
            
            setTimeout(() => {
                this.setupAllEvents();
                this.isInitialized = true;
                console.log('✅ DriversManager inicializado com sucesso!');
                
                // Debug: Verificar motoristas na tabela
                this.debugCheckTable();
            }, 100);
        }

        debugCheckTable() {
            const driverRows = document.querySelectorAll('tr[data-driver-id]');
            console.log(`📊 [INIT] Linhas de motoristas encontradas: ${driverRows.length}`);
            
            if (driverRows.length === 0) {
                console.warn('⚠️ [INIT] Nenhum motorista encontrado na tabela');
            } else {
                driverRows.forEach(row => {
                    const driverId = row.getAttribute('data-driver-id');
                    console.log(`👤 [INIT] Motorista ID: ${driverId}`);
                });
            }
        }

        removeAllEventListeners() {
            console.log('🧹 Removendo event listeners antigos do DriversManager...');
            
            const elementsToClean = [
                'newDriverBtn',
                'cancelDriverButton',
                'saveDriverButton'
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
        }

        setupButtonEvents() {
            console.log('🔧 Configurando eventos dos botões do DriversManager...');
            
            // Botão "Novo Motorista"
            const newDriverBtn = document.getElementById('newDriverBtn');
            if (newDriverBtn && !this.eventListeners.has('newDriverBtn')) {
                newDriverBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 [DRIVERS] Botão novo motorista clicado');
                    this.openDriverForm();
                });
                this.eventListeners.add('newDriverBtn');
            }

            // Delegation handler para motoristas
            if (!this.eventListeners.has('delegation')) {
                this.delegationHandler = (e) => {
                    const driverRow = e.target.closest('tr[data-driver-id]');
                    if (!driverRow) return;

                    // Botão Editar
                    const editBtn = e.target.closest('.btn-edit');
                    if (editBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const driverId = driverRow.getAttribute('data-driver-id');
                        console.log('✏️ [DRIVERS] Editando motorista:', driverId);
                        this.editDriver(driverId);
                        return;
                    }

                    // Botão Excluir
                    const deleteBtn = e.target.closest('.btn-delete');
                    if (deleteBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const driverId = driverRow.getAttribute('data-driver-id');
                        
                        let driverName = 'Motorista';
                        const nameElement = driverRow.querySelector('.employee-info strong');
                        if (nameElement && nameElement.textContent) {
                            driverName = nameElement.textContent.trim();
                        }
                        
                        console.log('🗑️ [DRIVERS] Excluindo motorista:', driverName);
                        this.deleteDriver(driverId, driverName);
                        return;
                    }

                    // Botão Visualizar
                    const viewBtn = e.target.closest('.btn-view');
                    if (viewBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const driverId = driverRow.getAttribute('data-driver-id');
                        console.log('👁️ [DRIVERS] Visualizando motorista:', driverId);
                        this.viewDriver(driverId);
                        return;
                    }
                };
                
                document.addEventListener('click', this.delegationHandler);
                this.eventListeners.add('delegation');
            }

            console.log('✅ Eventos dos botões do DriversManager configurados!');
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos do modal de motoristas...');
            
            this.modal = document.getElementById('driverModal');
            
            if (!this.modal) {
                console.error('❌ MODAL MOTORISTAS NÃO ENCONTRADO!');
                setTimeout(() => {
                    this.modal = document.getElementById('driverModal');
                    if (this.modal) {
                        console.log('✅ Modal de motoristas encontrado após delay');
                        this.setupModalEventListeners();
                    } else {
                        console.error('❌ Modal de motoristas não encontrado após múltiplas tentativas');
                    }
                }, 500);
                return;
            }

            console.log('✅ Modal de motoristas encontrado');
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
                    this.closeDriverModal();
                });
                this.eventListeners.add('modalClose');
            }

            // Fechar clicando fora
            if (!this.eventListeners.has('modalOutsideClick')) {
                this.modal.addEventListener('click', (e) => {
                    if (e.target === this.modal) {
                        this.closeDriverModal();
                    }
                });
                this.eventListeners.add('modalOutsideClick');
            }

            // Botão Cancelar
            const cancelBtn = document.getElementById('cancelDriverButton');
            if (cancelBtn && !this.eventListeners.has('cancelButton')) {
                cancelBtn.addEventListener('click', () => {
                    this.closeDriverModal();
                });
                this.eventListeners.add('cancelButton');
            }

            // Botão Salvar
            const saveBtn = document.getElementById('saveDriverButton');
            if (saveBtn && !this.eventListeners.has('saveButton')) {
                saveBtn.addEventListener('click', () => {
                    console.log('💾 [DRIVERS] Botão salvar motorista clicado');
                    this.saveDriver();
                });
                this.eventListeners.add('saveButton');
            }

            console.log('✅ Eventos do modal de motoristas configurados!');
        }

        setupFormEvents() {
            // Aguardar um pouco para garantir que os elementos do formulário estejam carregados
            setTimeout(() => {
                // Máscara para CNH
                const cnhInput = document.getElementById('cnh_number');
                if (cnhInput && !this.eventListeners.has('cnhMask')) {
                    cnhInput.addEventListener('input', (e) => {
                        e.target.value = e.target.value.replace(/\D/g, '');
                    });
                    this.eventListeners.add('cnhMask');
                }

                // Máscara para CPF
                const cpfInput = document.getElementById('cpf');
                if (cpfInput && !this.eventListeners.has('cpfMask')) {
                    cpfInput.addEventListener('input', (e) => {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length <= 11) {
                            value = value.replace(/(\d{3})(\d)/, '$1.$2');
                            value = value.replace(/(\d{3})(\d)/, '$1.$2');
                            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                        }
                        e.target.value = value;
                    });
                    this.eventListeners.add('cpfMask');
                }

                // Máscara para telefone
                const phoneInput = document.getElementById('phone');
                if (phoneInput && !this.eventListeners.has('phoneMask')) {
                    phoneInput.addEventListener('input', (e) => {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length <= 11) {
                            value = value.replace(/(\d{2})(\d)/, '($1) $2');
                            value = value.replace(/(\d{5})(\d)/, '$1-$2');
                        }
                        e.target.value = value;
                    });
                    this.eventListeners.add('phoneMask');
                }

                // Validação de data da CNH (não bloqueadora)
                const cnhExpiration = document.getElementById('cnh_expiration');
                if (cnhExpiration && !this.eventListeners.has('cnhValidation')) {
                    cnhExpiration.addEventListener('change', (e) => {
                        this.validateCNHExpiration(e.target.value);
                    });
                    this.eventListeners.add('cnhValidation');
                }

                // Evento para checkbox de motorista funcionário
                const employeeCheckbox = document.getElementById('is_employee_driver');
                if (employeeCheckbox && !this.eventListeners.has('employeeCheckbox')) {
                    employeeCheckbox.addEventListener('change', (e) => {
                        this.toggleEmployeeDriver(e.target.checked);
                    });
                    this.eventListeners.add('employeeCheckbox');
                }

                // Evento para seleção de funcionário
                const employeeSelect = document.getElementById('employee_id');
                if (employeeSelect && !this.eventListeners.has('employeeSelect')) {
                    employeeSelect.addEventListener('change', (e) => {
                        this.fillFromEmployee(e.target.value);
                    });
                    this.eventListeners.add('employeeSelect');
                }
            }, 200);
        }

        validateCNHExpiration(date) {
            if (!date) return true;
            
            const expiration = new Date(date);
            const today = new Date();
            
            if (expiration <= today) {
                console.log('⚠️ [DRIVERS] CNH expirada informada');
                this.showAlert('⚠️ Atenção: A CNH informada está expirada!', 'warning');
                return false;
            } else {
                const daysUntilExpiration = Math.ceil((expiration - today) / (1000 * 60 * 60 * 24));
                if (daysUntilExpiration <= 30) {
                    console.log(`⚠️ [DRIVERS] CNH expira em ${daysUntilExpiration} dias`);
                    this.showAlert(`⚠️ Atenção: A CNH expira em ${daysUntilExpiration} dias!`, 'warning');
                }
            }
            
            return true;
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

        // ✅ MÉTODO CORRIGIDO: Preencher dados do funcionário COM ENDEREÇO
        async fillFromEmployee(employeeId) {
			if (!employeeId) {
				this.clearEmployeeFields();
				return;
			}

			try {
				console.log('🔄 [DRIVERS] Buscando dados do funcionário ID:', employeeId);
				
				// ✅ CORREÇÃO: Usar a API do drivers.php que funciona
				const response = await fetch(`/bt-log-transportes/public/api/drivers.php?action=get_employee_data&id=${employeeId}`);
				
				if (!response.ok) {
					throw new Error('Erro na conexão com o servidor');
				}
				
				const result = await response.json();
				console.log('📦 [DRIVERS] Resposta da API:', result);

				if (result.success && result.data) {
					const employee = result.data;
					
					// ✅ CORREÇÃO: MAPEAMENTO COMPLETO DOS CAMPOS INCLUINDO ENDEREÇO
					const fieldsToFill = {
						'name': employee.name || '',
						'cpf': employee.cpf || '',
						'rg': employee.rg || '',
						'birth_date': employee.birth_date || '',
						'phone': employee.phone || '',
						'email': employee.email || '',
						'address': employee.address || '' // ✅ AGORA INCLUI ENDEREÇO
					};

					// Preencher cada campo
					Object.keys(fieldsToFill).forEach(fieldId => {
						const fieldElement = document.getElementById(fieldId);
						if (fieldElement) {
							fieldElement.value = fieldsToFill[fieldId];
							console.log(`✅ [DRIVERS] Campo ${fieldId} preenchido: "${fieldsToFill[fieldId]}"`);
						}
					});

					console.log('✅ [DRIVERS] Todos os campos preenchidos do funcionário:', employee.name);
					
					// Mostrar alerta de sucesso
					this.showAlert(`Dados de "${employee.name}" carregados com sucesso!`, 'success');
					
				} else {
					throw new Error(result.message || 'Dados do funcionário não encontrados');
				}
			} catch (error) {
				console.error('❌ [DRIVERS] Erro ao carregar informações do funcionário:', error);
				this.showAlert('Erro ao carregar dados do funcionário: ' + error.message, 'error');
			}
		}

        toggleEmployeeDriver(isEmployee) {
            console.log(`🔄 [DRIVERS] Alternando para: ${isEmployee ? 'motorista funcionário' : 'motorista avulso'}`);
            
            const employeeSection = document.getElementById('employeeSelectionSection');
            const driverTypeField = document.getElementById('driver_type_field');
            
            if (!employeeSection || !driverTypeField) {
                console.error('❌ [DRIVERS] Elementos do formulário não encontrados');
                return;
            }

            if (isEmployee) {
                employeeSection.style.display = 'block';
                driverTypeField.value = 'employee';
                this.loadAvailableEmployeesWithFallback();
                this.setPersonalFieldsReadOnly(true);
            } else {
                employeeSection.style.display = 'none';
                driverTypeField.value = 'external';
                this.setPersonalFieldsReadOnly(false);
                this.clearEmployeeFields();
            }
        }

        setPersonalFieldsReadOnly(readOnly) {
            const personalFields = ['name', 'cpf', 'rg', 'birth_date', 'phone', 'email', 'address'];
            
            personalFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    element.readOnly = readOnly;
                    if (readOnly) {
                        element.classList.add('auto-filled-field');
                        element.placeholder = 'Preenchido automaticamente';
                    } else {
                        element.classList.remove('auto-filled-field');
                        element.placeholder = '';
                    }
                }
            });
        }

        clearEmployeeFields() {
            const employeeSelect = document.getElementById('employee_id');
            const nameField = document.getElementById('name');
            const cpfField = document.getElementById('cpf');
            const rgField = document.getElementById('rg');
            const birthDateField = document.getElementById('birth_date');
            const phoneField = document.getElementById('phone');
            const emailField = document.getElementById('email');
            const addressField = document.getElementById('address');
            
            if (employeeSelect) employeeSelect.value = '';
            if (nameField) nameField.value = '';
            if (cpfField) cpfField.value = '';
            if (rgField) rgField.value = '';
            if (birthDateField) birthDateField.value = '';
            if (phoneField) phoneField.value = '';
            if (emailField) emailField.value = '';
            if (addressField) addressField.value = '';
        }

        async loadAvailableEmployeesWithFallback() {
			console.log('🔄 [DRIVERS] Carregando funcionários disponíveis...');
			
			try {
				const apiUrl = '/bt-log-transportes/public/api/drivers.php?action=available_employees';
				console.log('📡 [DRIVERS] Fazendo requisição para:', apiUrl);
				
				const response = await fetch(apiUrl);
				
				if (!response.ok) {
					throw new Error(`HTTP ${response.status}: ${response.statusText}`);
				}
				
				const result = await response.json();
				console.log('📊 [DRIVERS] Resposta da API:', result);
				
				if (result.success) {
					if (result.data && result.data.length > 0) {
						this.populateEmployeeSelect(result.data);
						console.log(`✅ [DRIVERS] ${result.data.length} funcionários carregados`);
					} else {
						console.warn('⚠️ [DRIVERS] Nenhum funcionário encontrado');
						this.showAlert(
							'ℹ️ Nenhum funcionário disponível para ser motorista. Verifique se existem funcionários ativos marcados como "motorista" no sistema.', 
							'info'
						);
						this.populateEmployeeSelect([]);
					}
				} else {
					throw new Error(result.message || 'Erro na resposta da API');
				}
				
			} catch (error) {
				console.error('💥 [DRIVERS] Erro ao carregar funcionários:', error);
				this.showAlert(
					'❌ Erro ao carregar lista de funcionários: ' + error.message, 
					'error'
				);
				this.populateEmployeeSelect([]);
			}
		}
        
        async tryAlternativeEmployeeLoad() {
            console.log('🔄 [DRIVERS] Tentando método alternativo...');
            
            try {
                const allEmployeesUrl = '/bt-log-transportes/public/api/employees.php?action=list&active=1';
                const response = await fetch(allEmployeesUrl);
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        const potentialDrivers = result.data.filter(emp => 
                            emp.is_active && !emp.is_already_driver
                        );
                        
                        if (potentialDrivers.length > 0) {
                            this.populateEmployeeSelect(potentialDrivers);
                            console.log(`✅ [DRIVERS] ${potentialDrivers.length} funcionários carregados via método alternativo`);
                            return;
                        }
                    }
                }
                
                // ✅ CORREÇÃO: Mensagem informativa em vez de dados mock
                this.showAlert(
                    '⚠️ Não foi possível carregar a lista de funcionários. Verifique a conexão com o servidor.', 
                    'warning'
                );
                
            } catch (error) {
                console.error('💥 [DRIVERS] Método alternativo também falhou:', error);
                this.showAlert(
                    '❌ Erro ao carregar funcionários: ' + error.message, 
                    'error'
                );
            }
        }
        
        loadMockEmployees() {
			console.log('🔍 [DRIVERS] Nenhum funcionário disponível');
			
			const employeeSelect = document.getElementById('employee_id');
			if (employeeSelect) {
				employeeSelect.innerHTML = '<option value="">Selecione um funcionário</option>';
				employeeSelect.innerHTML += '<option value="" disabled>Nenhum funcionário disponível</option>';
			}
			
			console.log('✅ [DRIVERS] Sistema operando sem dados de demonstração');
		}

        populateEmployeeSelect(employees) {
            const employeeSelect = document.getElementById('employee_id');
            if (!employeeSelect) {
                console.error('❌ [DRIVERS] Elemento employee_id não encontrado');
                return;
            }
            
            employeeSelect.innerHTML = '<option value="">Selecione um funcionário</option>';
            
            if (employees.length === 0) {
                employeeSelect.innerHTML += '<option value="" disabled>Nenhum funcionário disponível</option>';
                console.warn('⚠️ [DRIVERS] Nenhum funcionário disponível para seleção');
                return;
            }
            
            employees.forEach(employee => {
                const option = document.createElement('option');
                option.value = employee.id;
                option.textContent = `${employee.name}${employee.position ? ' - ' + employee.position : ''}`;
                employeeSelect.appendChild(option);
            });
            
            console.log(`✅ [DRIVERS] Select populado com ${employees.length} funcionários`);
        }

        openDriverForm(driverId = null) {
            console.log('🎯 [DRIVERS] ABRINDO MODAL! DriverId:', driverId);
            
            this.currentDriverId = driverId;
            this.modal = document.getElementById('driverModal');
            
            if (!this.modal) {
                console.error('❌ MODAL MOTORISTAS NÃO ENCONTRADO!');
                alert('Erro: Modal não encontrado. Verifique se o HTML do modal está correto.');
                return;
            }

            const title = document.getElementById('modalDriverTitle');

            if (driverId) {
                if (title) title.textContent = 'Editar Motorista';
                this.loadDriverData(driverId);
            } else {
                if (title) title.textContent = 'Novo Motorista';
                this.resetForm();
            }

            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ [DRIVERS] MODAL MOTORISTAS ABERTO COM SUCESSO!');
        }

        closeDriverModal() {
            console.log('🔒 [DRIVERS] Fechando modal...');
            if (this.modal) {
                this.modal.style.display = 'none';
            } else {
                const anyModal = document.getElementById('driverModal');
                if (anyModal) {
                    anyModal.style.display = 'none';
                }
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            this.resetForm();
            this.setFormReadOnly(false);
        }

        editDriver(driverId) {
            console.log('✏️ [DRIVERS] Editando motorista:', driverId);
            this.setFormReadOnly(false);
            this.openDriverForm(driverId);
        }

        viewDriver(driverId) {
            console.log('👁️ [DRIVERS] Visualizando motorista:', driverId);
            this.openDriverForm(driverId);
            this.setFormReadOnly(true);
        }

        setFormReadOnly(readOnly) {
            const form = document.getElementById('driverForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.id !== 'cancelDriverButton') {
                    input.disabled = readOnly;
                }
            });

            const saveBtn = document.getElementById('saveDriverButton');
            if (saveBtn) {
                saveBtn.style.display = readOnly ? 'none' : 'block';
            }

            const title = document.getElementById('modalDriverTitle');
            if (title && readOnly) {
                title.textContent = 'Visualizar Motorista';
            }
        }

        resetForm() {
            const form = document.getElementById('driverForm');
            if (form) {
                form.reset();
                
                const driverIdField = document.getElementById('driverId');
                if (driverIdField) {
                    driverIdField.value = '';
                }
                
                const employeeCheckbox = document.getElementById('is_employee_driver');
                const employeeSection = document.getElementById('employeeSelectionSection');
                const driverTypeField = document.getElementById('driver_type_field');
                
                if (employeeCheckbox) {
                    employeeCheckbox.checked = false;
                }
                
                if (employeeSection) {
                    employeeSection.style.display = 'none';
                }
                
                if (driverTypeField) {
                    driverTypeField.value = 'external';
                }
                
                this.setPersonalFieldsReadOnly(false);

                const employeeSelect = document.getElementById('employee_id');
                if (employeeSelect) {
                    employeeSelect.innerHTML = '<option value="">Selecione um funcionário</option>';
                }
            } else {
                console.warn('⚠️ [DRIVERS] Formulário não encontrado para reset');
            }
        }

        async loadDriverData(driverId) {
            console.log(`📥 [DRIVERS] Carregando motorista ${driverId}`);
            
            try {
                const apiUrl = `/bt-log-transportes/public/api/drivers.php?action=get&id=${driverId}`;
                const response = await fetch(apiUrl);
                
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                
                const result = await response.json();

                if (result.success && result.data) {
                    this.populateForm(result.data);
                    console.log('✅ [DRIVERS] Dados do motorista carregados com sucesso');
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados do motorista');
                }
            } catch (error) {
                console.error('❌ [DRIVERS] Erro ao carregar dados:', error);
                this.showAlert('Erro ao carregar dados do motorista: ' + error.message, 'error');
                this.loadMockData(driverId);
            }
        }
        
        populateForm(driver) {
			console.log('📝 [DRIVERS] Preenchendo formulário com dados:', driver);
			
			const driverIdField = document.getElementById('driverId');
			if (driverIdField) {
				driverIdField.value = driver.id;
			}

			const isEmployeeDriver = driver.driver_type === 'employee' && driver.employee_id;
			
			if (isEmployeeDriver) {
				const employeeCheckbox = document.getElementById('is_employee_driver');
				const driverTypeField = document.getElementById('driver_type_field');
				
				if (employeeCheckbox) {
					employeeCheckbox.checked = true;
					this.toggleEmployeeDriver(true);
				}
				
				if (driverTypeField) {
					driverTypeField.value = 'employee';
				}
				
				// ✅ CORREÇÃO: Não carregar lista de funcionários disponíveis ao editar
				// Preencher diretamente os campos com os dados do motorista funcionário
				setTimeout(() => {
					const employeeSelect = document.getElementById('employee_id');
					if (employeeSelect) {
						// ✅ CORREÇÃO: Criar option para o funcionário vinculado
						employeeSelect.innerHTML = `<option value="${driver.employee_id}">${driver.employee_name || 'Funcionário #' + driver.employee_id}</option>`;
						
						// ✅ CORREÇÃO: Preencher os campos automaticamente
						this.fillFromEmployee(driver.employee_id);
					}
				}, 300);
				
			} else {
				const employeeCheckbox = document.getElementById('is_employee_driver');
				const driverTypeField = document.getElementById('driver_type_field');
				
				if (employeeCheckbox) {
					employeeCheckbox.checked = false;
					this.toggleEmployeeDriver(false);
				}
				
				if (driverTypeField) {
					driverTypeField.value = 'external';
				}
				
				document.getElementById('name').value = driver.name || '';
				document.getElementById('cpf').value = driver.cpf || '';
				document.getElementById('rg').value = driver.rg || '';
				document.getElementById('birth_date').value = driver.birth_date || '';
				document.getElementById('phone').value = driver.phone || '';
				document.getElementById('email').value = driver.email || '';
				document.getElementById('address').value = driver.address || '';
			}

			document.getElementById('cnh_number').value = driver.cnh_number || '';
			document.getElementById('cnh_category').value = driver.cnh_category || '';
			document.getElementById('cnh_expiration').value = driver.cnh_expiration || '';
			document.getElementById('custom_commission_rate').value = driver.custom_commission_rate || '';
			
			const isActiveCheckbox = document.getElementById('is_active');
			if (isActiveCheckbox) {
				isActiveCheckbox.checked = driver.is_active !== undefined ? driver.is_active : true;
			}
		}

        loadMockData(driverId) {
            console.log('❌ [DRIVERS] Não foi possível carregar dados do motorista');
            
            this.showAlert(
                'Erro: Não foi possível carregar os dados do motorista. Verifique a conexão com o servidor.', 
                'error'
            );
            
            // Limpar o formulário em caso de erro
            this.resetForm();
            
            const driverIdField = document.getElementById('driverId');
            if (driverIdField && driverId) {
                driverIdField.value = driverId;
            }
        }

        async saveDriver() {
            if (this.saving) return;
            
            this.saving = true;
            console.log('💾 [DRIVERS] Salvando motorista...');
            
            if (!this.validateForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveDriverButton');
            this.setLoadingState(saveBtn, true);

            try {
                const formData = new FormData(document.getElementById('driverForm'));

                const isEmployeeDriver = document.getElementById('is_employee_driver').checked;
                const driverType = isEmployeeDriver ? 'employee' : 'external';
                formData.append('driver_type', driverType);
                formData.append('is_employee_driver', isEmployeeDriver ? '1' : '0');

                if (!isEmployeeDriver) {
                    formData.set('employee_id', '');
                }

                const driverId = this.currentDriverId;
                
                const apiUrl = '/bt-log-transportes/public/api/drivers.php?action=save';
                
                console.log(`🚀 [DRIVERS] Enviando para API: type=${driverType}, id=${driverId}`);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                console.log('📡 [DRIVERS] Resposta bruta:', responseText.substring(0, 200));

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('❌ [DRIVERS] Erro ao parsear JSON:', parseError);
                    
                    if (responseText.includes('<b>') || responseText.includes('<br')) {
                        const errorMatch = responseText.match(/<b>(.*?)<\/b>/);
                        const errorMessage = errorMatch ? errorMatch[1] : 'Erro no servidor PHP';
                        throw new Error(`Erro PHP: ${errorMessage}`);
                    } else {
                        throw new Error('Resposta inválida do servidor (não é JSON)');
                    }
                }

                console.log('📊 [DRIVERS] Resposta parseada:', result);

                if (result.success) {
                    console.log('✅ [DRIVERS] MOTORISTA SALVO COM SUCESSO!');
                    this.showAlert('Motorista salvo com sucesso!', 'success');
                    this.closeDriverModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Erro ao salvar motorista');
                }
                
            } catch (error) {
                console.error('💥 [DRIVERS] Erro:', error);
                this.showAlert('Erro ao salvar motorista: ' + error.message, 'error');
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        async deleteDriver(driverId, driverName) {
            if (this.deleting) return;
            
            let displayName = 'Motorista';
            if (driverName && driverName !== 'null' && driverName !== 'undefined' && driverName.trim() !== '') {
                displayName = driverName;
            }
            
            if (confirm(`Tem certeza que deseja excluir o motorista "${displayName}"?`)) {
                this.deleting = true;
                
                try {
                    const formData = new FormData();
                    formData.append('id', driverId);
                    
                    console.log(`🗑️ [DRIVERS] Excluindo motorista: ${displayName}`);
                    
                    const apiUrl = '/bt-log-transportes/public/api/drivers.php?action=delete';
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Motorista excluído com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir motorista');
                    }
                    
                } catch (error) {
                    console.error('❌ [DRIVERS] Erro ao excluir:', error);
                    this.showAlert('Erro ao excluir motorista: ' + error.message, 'error');
                } finally {
                    this.deleting = false;
                }
            }
        }

        validateForm() {
            const isEmployeeDriver = document.getElementById('is_employee_driver').checked;
            
            if (isEmployeeDriver) {
                const employeeId = document.getElementById('employee_id');
                if (!employeeId || !employeeId.value) {
                    this.showAlert('Por favor, selecione um funcionário', 'warning');
                    employeeId.focus();
                    return false;
                }
            }

            const name = document.getElementById('name');
            const cpf = document.getElementById('cpf');
            const phone = document.getElementById('phone');
            const cnhNumber = document.getElementById('cnh_number');
            const cnhCategory = document.getElementById('cnh_category');
            const cnhExpiration = document.getElementById('cnh_expiration');
            
            if (!name || !name.value.trim()) {
                this.showAlert('O nome do motorista é obrigatório', 'warning');
                name.focus();
                return false;
            }
            
            if (!cpf || !cpf.value.trim()) {
                this.showAlert('O CPF do motorista é obrigatório', 'warning');
                cpf.focus();
                return false;
            }

            if (!phone || !phone.value.trim()) {
                this.showAlert('O telefone do motorista é obrigatório', 'warning');
                phone.focus();
                return false;
            }
            
            if (!cnhNumber || !cnhNumber.value.trim()) {
                this.showAlert('O número da CNH é obrigatório', 'warning');
                cnhNumber.focus();
                return false;
            }
            
            if (!cnhCategory || !cnhCategory.value) {
                this.showAlert('A categoria da CNH é obrigatória', 'warning');
                cnhCategory.focus();
                return false;
            }
            
            if (!cnhExpiration || !cnhExpiration.value) {
                this.showAlert('A data de validade da CNH é obrigatória', 'warning');
                cnhExpiration.focus();
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

        debugDrivers() {
            console.log('🐛 [DRIVERS DEBUG] Iniciando debug...');
            
            const driverRows = document.querySelectorAll('tr[data-driver-id]');
            console.log(`📊 [DEBUG] Linhas de motoristas na tabela: ${driverRows.length}`);
            
            driverRows.forEach(row => {
                const driverId = row.getAttribute('data-driver-id');
                const name = row.querySelector('.employee-info strong')?.textContent;
                console.log(`👤 [DEBUG] Motorista: ${name} (ID: ${driverId})`);
            });
            
            fetch('/bt-log-transportes/public/api/drivers.php?action=get&id=1')
                .then(response => response.json())
                .then(data => {
                    console.log('📡 [DEBUG] Resposta da API get:', data);
                })
                .catch(error => {
                    console.error('❌ [DEBUG] Erro na API:', error);
                });
            
            this.loadAvailableEmployeesWithFallback();
            
            alert(`Debug iniciado. Verifique o console.\nMotoristas na tabela: ${driverRows.length}`);
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

        refreshDrivers() {
            window.location.reload();
        }

        getApiUrl(module, action) {
            const basePath = '/bt-log-transportes/public';
            return `${basePath}/api/${module}.php?action=${action}`;
        }
    }

    // Inicialização
    if (!window.driversManager) {
        window.driversManager = new DriversManager();
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.driversManager.init();
            }, 500);
        });

        if (document.readyState !== 'loading') {
            setTimeout(() => {
                if (window.driversManager && !window.driversManager.isInitialized) {
                    window.driversManager.init();
                }
            }, 800);
        }
    }

})();