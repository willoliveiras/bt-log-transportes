// public/assets/js/clients.js
(function() {
    'use strict';

    if (window.ClientsManagerLoaded) {
        console.log('🔧 Clients Manager já carregado');
        return;
    }
    window.ClientsManagerLoaded = true;

    console.log('🏢 Clients Manager carregado');

    class ClientsManager {
        constructor() {
            this.currentClientId = null;
            this.isInitialized = false;
            this.eventListeners = new Set();
            this.saving = false;
            this.deleting = false;
            this.partnerCompanies = [];
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 ClientsManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando ClientsManager...');
            
            this.removeAllEventListeners();
            
            setTimeout(() => {
                this.setupAllEvents();
                this.loadPartnerCompanies();
                this.isInitialized = true;
                console.log('✅ ClientsManager inicializado com sucesso!');
            }, 100);
        }

        removeAllEventListeners() {
            console.log('🧹 Removendo event listeners antigos...');
            
            const elementsToClean = [
                'newClientBtn',
                'cancelClientButton',
                'saveClientButton',
                'type',
                'client_category',
                'cpf_cnpj'
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
            console.log('🔧 Configurando eventos dos botões...');
            
            // Botão "Novo Cliente"
            const newClientBtn = document.getElementById('newClientBtn');
            if (newClientBtn && !this.eventListeners.has('newClientBtn')) {
                newClientBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 Botão novo cliente clicado');
                    this.openModal();
                });
                this.eventListeners.add('newClientBtn');
            }

            // Delegation handler para ações da tabela
            if (!this.eventListeners.has('delegation')) {
                this.delegationHandler = (e) => {
                    const clientRow = e.target.closest('tr[data-client-id]');
                    if (!clientRow) return;

                    // Botão Editar
                    const editBtn = e.target.closest('.btn-edit');
                    if (editBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const clientId = clientRow.getAttribute('data-client-id');
                        console.log('✏️ Editando cliente:', clientId);
                        this.editClient(clientId);
                        return;
                    }

                    // Botão Excluir
                    const deleteBtn = e.target.closest('.btn-delete');
                    if (deleteBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const clientId = clientRow.getAttribute('data-client-id');
                        const clientName = clientRow.querySelector('.client-name')?.textContent || 'Cliente';
                        console.log('🗑️ Excluindo cliente:', clientId);
                        this.deleteClient(clientId, clientName);
                        return;
                    }
                };

                document.addEventListener('click', this.delegationHandler);
                this.eventListeners.add('delegation');
            }
        }

        setupModalEvents() {
            const modal = document.getElementById('clientModal');
            if (!modal) return;

            // Botão Cancelar
            const cancelBtn = document.getElementById('cancelClientButton');
            if (cancelBtn && !this.eventListeners.has('cancelBtn')) {
                cancelBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('❌ Cancelando operação');
                    this.closeModal();
                });
                this.eventListeners.add('cancelBtn');
            }

            // Botão Salvar
            const saveBtn = document.getElementById('saveClientButton');
            if (saveBtn && !this.eventListeners.has('saveBtn')) {
                saveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('💾 Salvando cliente');
                    this.saveClient();
                });
                this.eventListeners.add('saveBtn');
            }

            // Fechar modal ao clicar no X
            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn && !this.eventListeners.has('closeBtn')) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal();
                });
                this.eventListeners.add('closeBtn');
            }

            // Fechar modal ao clicar fora
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }

        setupFormEvents() {
            // Evento para mudança de tipo de pessoa
            const typeSelect = document.getElementById('type');
            if (typeSelect && !this.eventListeners.has('type')) {
                typeSelect.addEventListener('change', (e) => {
                    this.handleTypeChange(e.target.value);
                });
                this.eventListeners.add('type');
            }

            // Evento para mudança de categoria
            const categorySelect = document.getElementById('client_category');
            if (categorySelect && !this.eventListeners.has('category')) {
                categorySelect.addEventListener('change', (e) => {
                    this.handleCategoryChange(e.target.value);
                });
                this.eventListeners.add('category');
            }

            // Formatação automática de CPF/CNPJ
            const cpfCnpjField = document.getElementById('cpf_cnpj');
            if (cpfCnpjField && !this.eventListeners.has('cpfCnpj')) {
                cpfCnpjField.addEventListener('input', (e) => {
                    this.formatDocument(e.target);
                });
                this.eventListeners.add('cpfCnpj');
            }
        }

        // Carregar empresas parceiras
        async loadPartnerCompanies() {
            try {
                const response = await fetch('/bt-log-transportes/public/api/clients.php?action=get_partner_companies');
                const result = await response.json();
                
                if (result.success) {
                    this.partnerCompanies = result.data;
                    console.log('✅ Empresas parceiras carregadas:', this.partnerCompanies.length);
                }
            } catch (error) {
                console.error('❌ Erro ao carregar empresas parceiras:', error);
            }
        }

        // Mudança no tipo de pessoa
        handleTypeChange(type) {
			console.log(`🔄 Tipo selecionado: ${type}`);
			
			const cpfCnpjField = document.getElementById('cpf_cnpj');
			const categorySelect = document.getElementById('client_category');
			
			if (!cpfCnpjField || !categorySelect) return;

			// Atualizar placeholder do CPF/CNPJ
			if (type === 'pessoa_fisica') {
				cpfCnpjField.placeholder = '000.000.000-00';
				cpfCnpjField.maxLength = 14;
				
				// Pessoa física só pode ser cliente comum ou cliente de empresa parceira
				const partnerOption = categorySelect.querySelector('option[value="empresa_parceira"]');
				if (partnerOption) {
					partnerOption.disabled = true;
					// Se estiver selecionada, mudar para cliente comum
					if (categorySelect.value === 'empresa_parceira') {
						categorySelect.value = 'cliente_comum';
						this.handleCategoryChange('cliente_comum');
					}
				}
				
				// Habilitar opções permitidas
				const commonOption = categorySelect.querySelector('option[value="cliente_comum"]');
				const referredOption = categorySelect.querySelector('option[value="cliente_empresa_parceira"]');
				if (commonOption) commonOption.disabled = false;
				if (referredOption) referredOption.disabled = false;
				
			} else {
				cpfCnpjField.placeholder = '00.000.000/0000-00';
				cpfCnpjField.maxLength = 18;
				
				// Pessoa jurídica pode ser qualquer categoria
				const options = categorySelect.querySelectorAll('option');
				options.forEach(option => {
					option.disabled = false;
				});
			}

			// Atualizar campos de nome dinamicamente
			this.updateNameFields(type);

			// Disparar evento de change na categoria para atualizar campos
			if (categorySelect.value) {
				this.handleCategoryChange(categorySelect.value);
			}
		}
		
		updateNameFields(type) {
			const nameField = document.getElementById('name');
			const fantasyNameField = document.getElementById('fantasy_name');
			const nameLabel = nameField ? nameField.previousElementSibling : null;
			const fantasyNameLabel = fantasyNameField ? fantasyNameField.previousElementSibling : null;
			
			if (type === 'pessoa_fisica') {
				// Pessoa Física
				if (nameLabel) nameLabel.textContent = 'Nome Completo *';
				if (nameField) {
					nameField.placeholder = 'Nome completo da pessoa';
					nameField.type = 'text';
				}
				
				// Ocultar nome fantasia para PF
				if (fantasyNameField) {
					fantasyNameField.style.display = 'none';
					fantasyNameField.value = ''; // Limpar valor
					fantasyNameField.required = false;
				}
				if (fantasyNameLabel) {
					fantasyNameLabel.style.display = 'none';
				}
				
			} else {
				// Pessoa Jurídica
				if (nameLabel) nameLabel.textContent = 'Razão Social *';
				if (nameField) {
					nameField.placeholder = 'Razão social completa';
					nameField.type = 'text';
				}
				
				// Mostrar nome fantasia para PJ
				if (fantasyNameField) {
					fantasyNameField.style.display = 'block';
					fantasyNameField.placeholder = 'Nome comercial (opcional)';
					fantasyNameField.required = false;
				}
				if (fantasyNameLabel) {
					fantasyNameLabel.style.display = 'block';
					fantasyNameLabel.textContent = 'Nome Fantasia';
				}
			}
		}

        // Mudança na categoria do cliente
        handleCategoryChange(category) {
			console.log(`🏷️ Categoria selecionada: ${category}`);
			
			this.toggleCategoryFields(category);
		}

        // Mostrar/ocultar campos baseado na categoria
        toggleCategoryFields(category) {
			// REMOVIDO: Campos de comissão para empresa parceira
			
			// Campos para Cliente de Empresa Parceira
			const referredClientFields = document.getElementById('referredClientFields');
			if (referredClientFields) {
				referredClientFields.style.display = category === 'cliente_empresa_parceira' ? 'block' : 'none';
			}

			// Atualizar obrigatoriedade dos campos
			this.updateFieldRequirements(category);
		}

        // Atualizar campos obrigatórios baseado na categoria
        updateFieldRequirements(category) {
			const partnerCompanyField = document.getElementById('partner_company_id');
			
			// REMOVIDO: Campo de comissão

			if (partnerCompanyField) {
				partnerCompanyField.required = category === 'cliente_empresa_parceira';
			}
		}

        formatDocument(input) {
            let value = input.value.replace(/\D/g, '');
            
            const typeSelect = document.getElementById('type');
            const type = typeSelect ? typeSelect.value : 'pessoa_fisica';
            
            if (type === 'pessoa_fisica') {
                if (value.length > 11) value = value.substring(0, 11);
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                if (value.length > 14) value = value.substring(0, 14);
                value = value.replace(/(\d{2})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1/$2');
                value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }
            
            input.value = value;
        }

        openModal(clientId = null) {
            console.log('📝 Abrindo modal para cliente:', clientId);
            
            this.currentClientId = clientId;
            this.showModal();
            
            if (clientId) {
                this.loadClientData(clientId);
            } else {
                this.resetForm();
            }
        }

        showModal() {
            const modal = document.getElementById('clientModal');
            if (!modal) {
                console.error('❌ Modal não encontrado');
                return;
            }

            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);

            // Prevenir scroll do body
            document.body.style.overflow = 'hidden';
        }

        closeModal() {
            const modal = document.getElementById('clientModal');
            if (!modal) return;

            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                this.resetForm();
                
                // Restaurar scroll do body
                document.body.style.overflow = '';
            }, 300);
        }

        resetForm() {
			const form = document.getElementById('clientForm');
			if (!form) return;

			form.reset();
			this.currentClientId = null;
			
			// Resetar título
			const modalTitle = document.getElementById('clientModalLabel');
			if (modalTitle) {
				modalTitle.textContent = 'Novo Cliente';
			}

			// Resetar campos dinâmicos
			this.handleTypeChange('pessoa_fisica');
			this.handleCategoryChange('cliente_comum');
			
			// Resetar validação
			this.clearValidation();
		}

        

        clearValidation() {
            const form = document.getElementById('clientForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.classList.remove('is-invalid', 'is-valid');
            });

            const feedbacks = form.querySelectorAll('.invalid-feedback');
            feedbacks.forEach(feedback => {
                feedback.style.display = 'none';
            });
        }

        async loadClientData(clientId) {
            try {
                console.log('📥 Carregando dados do cliente:', clientId);
                
                const response = await fetch(`/bt-log-transportes/public/api/clients.php?action=get&id=${clientId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Erro ao carregar cliente');
                }

                this.populateForm(result.data);
                
            } catch (error) {
                console.error('❌ Erro ao carregar cliente:', error);
                this.showAlert('Erro ao carregar dados do cliente: ' + error.message, 'error');
            }
        }

        populateForm(clientData) {
			console.log('📋 Preenchendo formulário com dados:', clientData);
			
			const form = document.getElementById('clientForm');
			if (!form) return;

			// Atualizar título
			const modalTitle = document.getElementById('clientModalLabel');
			if (modalTitle) {
				modalTitle.textContent = `Editando: ${clientData.fantasy_name || clientData.name}`;
			}

			// Preencher campos básicos
			const fields = [
				'company_id', 'name', 'fantasy_name', 'type', 'client_category', 'cpf_cnpj', 'email', 'phone', 
				'address', 'contact_name', 'contact_phone', 'contact_email',
				'client_segment', 'client_size', 'payment_terms', 'credit_limit',
				'registration_date', 'partner_company_id', 'notes', 'is_active'
			];

			fields.forEach(field => {
				const element = document.getElementById(field);
				if (element && clientData[field] !== undefined && clientData[field] !== null) {
					if (element.type === 'checkbox') {
						element.checked = Boolean(clientData[field]);
					} else {
						element.value = clientData[field];
					}
				}
			});

			// Atualizar campos de nome baseado no tipo
			if (clientData.type) {
				this.updateNameFields(clientData.type);
			}

			// Disparar eventos para atualizar campos dinâmicos
			const typeSelect = document.getElementById('type');
			if (typeSelect && clientData.type) {
				typeSelect.dispatchEvent(new Event('change'));
			}

			const categorySelect = document.getElementById('client_category');
			if (categorySelect && clientData.client_category) {
				setTimeout(() => {
					categorySelect.dispatchEvent(new Event('change'));
				}, 100);
			}
		}

        async saveClient() {
			if (this.saving) {
				console.log('⏳ Salvamento já em andamento...');
				return;
			}

			try {
				this.saving = true;
				console.log('💾 Iniciando salvamento do cliente...');

				// Validar formulário
				if (!this.validateForm()) {
					this.saving = false;
					return;
				}

				// Preparar dados CORRIGIDOS
				const formData = new FormData(document.getElementById('clientForm'));
				
				// Garantir que o limite de crédito seja positivo
				const creditLimit = formData.get('credit_limit');
				if (creditLimit && parseFloat(creditLimit) < 0) {
					formData.set('credit_limit', '0');
				}
				
				// Garantir que campos vazios sejam null
				const fieldsToCheck = ['cpf_cnpj', 'email', 'phone', 'address', 'contact_name', 
									 'contact_phone', 'contact_email', 'payment_terms', 'notes'];
				
				fieldsToCheck.forEach(field => {
					const value = formData.get(field);
					if (value === '') {
						formData.set(field, '');
					}
				});

				if (this.currentClientId) {
					formData.append('id', this.currentClientId);
				}

				// Mostrar loading
				const saveBtn = document.getElementById('saveClientButton');
				const originalText = saveBtn.innerHTML;
				saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
				saveBtn.disabled = true;

				// Enviar requisição
				const response = await fetch('/bt-log-transportes/public/api/clients.php?action=save', {
					method: 'POST',
					body: formData
				});

				const result = await response.json();

				// Restaurar botão
				saveBtn.innerHTML = originalText;
				saveBtn.disabled = false;

				if (!result.success) {
					throw new Error(result.message || 'Erro ao salvar cliente');
				}

				console.log('✅ Cliente salvo com sucesso!');
				this.showAlert(result.message, 'success');
				this.closeModal();
				
				// Recarregar a página para atualizar a lista
				setTimeout(() => {
					window.location.reload();
				}, 1500);

			} catch (error) {
				console.error('❌ Erro ao salvar cliente:', error);
				this.showAlert('Erro ao salvar cliente: ' + error.message, 'error');
			} finally {
				this.saving = false;
			}
		}

        validateForm() {
			const form = document.getElementById('clientForm');
			if (!form) return false;

			let isValid = true;
			this.clearValidation();

			// Validar campos obrigatórios
			const requiredFields = ['company_id', 'name', 'type', 'client_category'];
			
			requiredFields.forEach(field => {
				const element = document.getElementById(field);
				if (!element) return;

				if (!element.value.trim()) {
					this.showFieldError(element, 'Este campo é obrigatório');
					isValid = false;
				} else {
					this.showFieldSuccess(element);
				}
			});

			// Validar regras específicas por categoria
			const category = document.getElementById('client_category').value;
			const type = document.getElementById('type').value;

			// Validar se PF pode ser empresa parceira
			if (category === 'empresa_parceira' && type !== 'pessoa_juridica') {
				this.showFieldError(document.getElementById('client_category'), 'Apenas Pessoa Jurídica pode ser Empresa Parceira');
				isValid = false;
			}

			// Validar empresa parceira para cliente de empresa parceira
			if (category === 'cliente_empresa_parceira') {
				const partnerCompany = document.getElementById('partner_company_id');
				if (!partnerCompany.value) {
					this.showFieldError(partnerCompany, 'É necessário selecionar uma empresa parceira');
					isValid = false;
				}
			}

			// Validar CPF/CNPJ
			const cpfCnpjField = document.getElementById('cpf_cnpj');
			if (cpfCnpjField && cpfCnpjField.value.trim()) {
				const documentValue = cpfCnpjField.value.replace(/\D/g, '');
				
				if (type === 'pessoa_fisica' && documentValue.length !== 11) {
					this.showFieldError(cpfCnpjField, 'CPF deve ter 11 dígitos');
					isValid = false;
				} else if (type === 'pessoa_juridica' && documentValue.length !== 14) {
					this.showFieldError(cpfCnpjField, 'CNPJ deve ter 14 dígitos');
					isValid = false;
				} else {
					this.showFieldSuccess(cpfCnpjField);
				}
			}

			// Validar emails
			const emailFields = ['email', 'contact_email'];
			emailFields.forEach(field => {
				const element = document.getElementById(field);
				if (!element || !element.value.trim()) return;

				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (!emailRegex.test(element.value.trim())) {
					this.showFieldError(element, 'Email inválido');
					isValid = false;
				} else {
					this.showFieldSuccess(element);
				}
			});

			// Validar limite de crédito CORRIGIDO
			const creditLimit = document.getElementById('credit_limit');
			if (creditLimit && creditLimit.value) {
				const limitValue = parseFloat(creditLimit.value);
				if (isNaN(limitValue) || limitValue < 0) {
					this.showFieldError(creditLimit, 'O limite de crédito não pode ser negativo');
					isValid = false;
					
					// Corrigir automaticamente para 0
					creditLimit.value = '0';
					this.showFieldSuccess(creditLimit);
				} else {
					this.showFieldSuccess(creditLimit);
				}
			}

			return isValid;
		}

        showFieldError(element, message) {
            element.classList.add('is-invalid');
            element.classList.remove('is-valid');
            
            let feedback = element.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                element.parentNode.appendChild(feedback);
            }
            
            feedback.textContent = message;
            feedback.style.display = 'block';
        }

        showFieldSuccess(element) {
            element.classList.add('is-valid');
            element.classList.remove('is-invalid');
            
            const feedback = element.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'none';
            }
        }

        async editClient(clientId) {
            console.log('✏️ Editando cliente:', clientId);
            this.openModal(clientId);
        }

        async deleteClient(clientId, clientName) {
            if (this.deleting) {
                console.log('⏳ Exclusão já em andamento...');
                return;
            }

            if (!confirm(`Tem certeza que deseja excluir o cliente "${clientName}"? Esta ação não pode ser desfeita.`)) {
                return;
            }

            try {
                this.deleting = true;
                console.log('🗑️ Excluindo cliente:', clientId);

                const formData = new FormData();
                formData.append('id', clientId);

                const response = await fetch('/bt-log-transportes/public/api/clients.php?action=delete', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erro ao excluir cliente');
                }

                console.log('✅ Cliente excluído com sucesso!');
                this.showAlert(result.message, 'success');
                
                // Recarregar a página para atualizar a lista
                setTimeout(() => {
                    window.location.reload();
                }, 1500);

            } catch (error) {
                console.error('❌ Erro ao excluir cliente:', error);
                this.showAlert('Erro ao excluir cliente: ' + error.message, 'error');
            } finally {
                this.deleting = false;
            }
        }

        showAlert(message, type = 'info') {
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <strong>${type === 'success' ? 'Sucesso!' : type === 'error' ? 'Erro!' : 'Atenção!'}</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            // Encontrar ou criar container de alertas
            let alertContainer = document.getElementById('alertContainer');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.id = 'alertContainer';
                alertContainer.className = 'position-fixed top-0 end-0 p-3';
                alertContainer.style.zIndex = '9999';
                document.body.appendChild(alertContainer);
            }

            // Adicionar alerta
            const alertElement = document.createElement('div');
            alertElement.innerHTML = alertHtml;
            alertContainer.appendChild(alertElement);

            // Remover alerta automaticamente após 5 segundos
            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.remove();
                }
            }, 5000);
        }
    }

    // Inicializar o sistema de clientes
    window.clientsManager = new ClientsManager();
    
    // Aguardar o DOM carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.clientsManager.init();
            }, 500);
        });
    } else {
        setTimeout(() => {
            window.clientsManager.init();
        }, 500);
    }

})();