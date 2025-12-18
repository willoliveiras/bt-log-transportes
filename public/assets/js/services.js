// public/assets/js/services.js
(function() {
    'use strict';

    if (window.ServicesManagerLoaded) {
        return;
    }
    window.ServicesManagerLoaded = true;

    class ServicesManager {
        constructor() {
            this.currentServiceId = null;
            this.isInitialized = false;
            this.eventListeners = new Map();
            this.modal = null;
            this.viewModal = null;
            this.saving = false;
        }

        init() {
            if (this.isInitialized) return;

            console.log('🎯 Inicializando ServicesManager...');
            
            this.removeAllEventListeners();
            
            setTimeout(() => {
                this.setupAllEvents();
                this.isInitialized = true;
                console.log('✅ ServicesManager inicializado com sucesso!');
                
                // Debug: verificar se os botões estão funcionando
                this.debugButtons();
            }, 100);
        }

        debugButtons() {
            console.log('🔍 Debug dos botões:');
            
            // Verificar botões de ação na tabela
            const actionButtons = document.querySelectorAll('.btn-edit, .btn-view, .btn-delete');
            console.log(`• Botões de ação encontrados: ${actionButtons.length}`);
            
            actionButtons.forEach((btn, index) => {
                const serviceRow = btn.closest('tr[data-service-id]');
                const serviceId = serviceRow ? serviceRow.getAttribute('data-service-id') : 'N/A';
                console.log(`• Botão ${index + 1}:`, {
                    type: btn.className,
                    serviceId: serviceId,
                    element: btn
                });
            });
        }

        removeAllEventListeners() {
            this.eventListeners.forEach((listeners, elementId) => {
                listeners.forEach(({ type, handler }) => {
                    const element = document.getElementById(elementId);
                    if (element) {
                        element.removeEventListener(type, handler);
                    }
                });
            });
            this.eventListeners.clear();
        }

        setupAllEvents() {
            this.setupButtonEvents();
            this.setupModalEvents();
            this.setupTableEvents(); // NOVO: Eventos específicos da tabela
        }

        setupButtonEvents() {
            // Botão Novo Serviço
            const newServiceBtn = document.getElementById('newServiceBtn');
            if (newServiceBtn) {
                const handler = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📝 Abrindo formulário de novo serviço...');
                    this.openServiceForm();
                };
                
                newServiceBtn.addEventListener('click', handler);
                this.addEventListener('newServiceBtn', 'click', handler);
            }

            // Botão no empty state
            const emptyStateBtn = document.getElementById('emptyStateBtn');
            if (emptyStateBtn) {
                const handler = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📝 Abrindo formulário do empty state...');
                    this.openServiceForm();
                };
                
                emptyStateBtn.addEventListener('click', handler);
                this.addEventListener('emptyStateBtn', 'click', handler);
            }
        }

        // NOVO MÉTODO: Eventos específicos para a tabela
        setupTableEvents() {
            console.log('🔧 Configurando eventos da tabela...');
            
            // Delegation para ações da tabela - CORREÇÃO CRÍTICA
            const tableHandler = (e) => {
                console.log('🎯 Evento de clique na tabela:', e.target);
                
                // Encontrar o botão clicado
                const editBtn = e.target.closest('.btn-edit');
                const viewBtn = e.target.closest('.btn-view');
                const deleteBtn = e.target.closest('.btn-delete');
                
                // Encontrar a linha do serviço
                const serviceRow = e.target.closest('tr[data-service-id]');
                if (!serviceRow) {
                    console.log('❌ Linha de serviço não encontrada');
                    return;
                }
                
                const serviceId = serviceRow.getAttribute('data-service-id');
                const serviceName = serviceRow.querySelector('.service-info strong')?.textContent || 'Serviço';
                
                console.log('📋 Dados do serviço:', { serviceId, serviceName });

                if (editBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✏️ Editando serviço:', serviceId);
                    this.editService(serviceId);
                    return;
                }

                if (viewBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('👁️ Visualizando serviço:', serviceId);
                    this.viewService(serviceId);
                    return;
                }

                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🗑️ Excluindo serviço:', serviceId);
                    this.deleteService(serviceId, serviceName);
                    return;
                }
            };
            
            // Adicionar evento à tabela
            const servicesTable = document.getElementById('servicesTable');
            if (servicesTable) {
                servicesTable.addEventListener('click', tableHandler);
                this.eventListeners.set('servicesTable', [{ type: 'click', handler: tableHandler }]);
                console.log('✅ Eventos da tabela configurados');
            } else {
                console.error('❌ Tabela de serviços não encontrada');
                
                // Fallback: adicionar ao documento
                document.addEventListener('click', tableHandler);
                this.eventListeners.set('document', [{ type: 'click', handler: tableHandler }]);
                console.log('⚠️ Usando fallback para eventos da tabela');
            }
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos dos modais...');
            
            // Modal principal (edição/criação)
            this.modal = document.getElementById('serviceModal');
            if (this.modal) {
                this.setupServiceModalEventListeners();
            } else {
                console.error('❌ Modal principal não encontrado');
                setTimeout(() => {
                    this.modal = document.getElementById('serviceModal');
                    if (this.modal) {
                        this.setupServiceModalEventListeners();
                    }
                }, 500);
            }

            // Modal de visualização
            this.viewModal = document.getElementById('viewServiceModal');
            if (this.viewModal) {
                this.setupViewModalEventListeners();
            }
        }

        setupServiceModalEventListeners() {
            if (!this.modal) return;

            // Fechar com X
            const closeBtn = this.modal.querySelector('.modal-close');
            if (closeBtn && !this.eventListeners.has('modalClose')) {
                const handler = () => {
                    this.closeServiceModal();
                };
                closeBtn.addEventListener('click', handler);
                this.addEventListener('modalClose', 'click', handler);
            }

            // Fechar clicando fora
            if (!this.eventListeners.has('modalOutsideClick')) {
                const handler = (e) => {
                    if (e.target === this.modal) {
                        this.closeServiceModal();
                    }
                };
                this.modal.addEventListener('click', handler);
                this.eventListeners.set('modalOutsideClick', [{ type: 'click', handler }]);
            }

            // Botão Cancelar
            const cancelBtn = document.getElementById('cancelServiceButton');
            if (cancelBtn && !this.eventListeners.has('cancelButton')) {
                const handler = () => {
                    this.closeServiceModal();
                };
                cancelBtn.addEventListener('click', handler);
                this.addEventListener('cancelButton', 'click', handler);
            }

            // Botão Salvar
            const saveBtn = document.getElementById('saveServiceButton');
            if (saveBtn && !this.eventListeners.has('saveButton')) {
                const handler = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.saveService();
                };
                saveBtn.addEventListener('click', handler);
                this.addEventListener('saveButton', 'click', handler);
            }

            // Prevenir fechamento ao clicar dentro do conteúdo do modal
            const modalContent = this.modal.querySelector('.modal-content');
            if (modalContent && !this.eventListeners.has('modalContentClick')) {
                const handler = (e) => {
                    e.stopPropagation();
                };
                modalContent.addEventListener('click', handler);
                this.eventListeners.set('modalContentClick', [{ type: 'click', handler }]);
            }
        }

        setupViewModalEventListeners() {
            if (!this.viewModal) return;

            // Fechar com X
            const closeBtn = this.viewModal.querySelector('.modal-close');
            if (closeBtn && !this.eventListeners.has('viewModalClose')) {
                const handler = () => {
                    this.closeViewModal();
                };
                closeBtn.addEventListener('click', handler);
                this.addEventListener('viewModalClose', 'click', handler);
            }

            // Fechar clicando fora
            if (!this.eventListeners.has('viewModalOutsideClick')) {
                const handler = (e) => {
                    if (e.target === this.viewModal) {
                        this.closeViewModal();
                    }
                };
                this.viewModal.addEventListener('click', handler);
                this.eventListeners.set('viewModalOutsideClick', [{ type: 'click', handler }]);
            }

            // Prevenir fechamento ao clicar dentro do conteúdo do modal
            const modalContent = this.viewModal.querySelector('.modal-content');
            if (modalContent && !this.eventListeners.has('viewModalContentClick')) {
                const handler = (e) => {
                    e.stopPropagation();
                };
                modalContent.addEventListener('click', handler);
                this.eventListeners.set('viewModalContentClick', [{ type: 'click', handler }]);
            }
        }

        addEventListener(elementId, type, handler) {
            if (!this.eventListeners.has(elementId)) {
                this.eventListeners.set(elementId, []);
            }
            this.eventListeners.get(elementId).push({ type, handler });
        }

        // CORREÇÃO: Método editService separado
        editService(serviceId) {
            console.log('✏️ Iniciando edição do serviço:', serviceId);
            this.openServiceForm(serviceId);
        }

        openServiceForm(serviceId = null) {
            console.log('🚀 Abrindo modal de serviço...', { serviceId });
            
            this.currentServiceId = serviceId;
            this.modal = document.getElementById('serviceModal');
            
            if (!this.modal) {
                console.error('❌ Modal de serviços não encontrado');
                this.showAlert('Erro: Modal não encontrado', 'error');
                return;
            }

            // Atualizar título
            const title = document.getElementById('modalServiceTitle');
            if (title) {
                title.textContent = serviceId ? 'Editar Serviço' : 'Novo Serviço';
            }

            // Limpar ou carregar dados
            if (serviceId) {
                this.loadServiceData(serviceId);
            } else {
                this.resetForm();
            }

            // MOSTRAR MODAL
            this.modal.style.display = 'block';
            this.modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');

            console.log('✅ Modal aberto com sucesso!');
        }

        closeServiceModal() {
            console.log('🔒 Fechando modal de serviço...');
            
            if (this.modal) {
                this.modal.style.display = 'none';
                this.modal.classList.remove('show');
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            this.resetForm();
            this.currentServiceId = null;
        }

        resetForm() {
            const form = document.getElementById('serviceForm');
            if (form) {
                form.reset();
                const serviceIdField = document.getElementById('serviceId');
                if (serviceIdField) {
                    serviceIdField.value = '';
                }
                const isActiveCheckbox = document.getElementById('service_is_active');
                if (isActiveCheckbox) {
                    isActiveCheckbox.checked = true;
                }
            }
        }

        async loadServiceData(serviceId) {
            try {
                console.log('📥 Carregando dados do serviço:', serviceId);
                
                const apiUrl = `/bt-log-transportes/public/api/services.php?action=get&id=${serviceId}`;
                const response = await fetch(apiUrl);
                
                if (!response.ok) throw new Error('Erro na requisição');
                
                const result = await response.json();

                if (result.success && result.data) {
                    this.populateForm(result.data);
                    console.log('✅ Dados do serviço carregados com sucesso');
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados');
                }
            } catch (error) {
                console.error('❌ Erro ao carregar serviço:', error);
                this.showAlert('Erro ao carregar dados do serviço: ' + error.message, 'error');
            }
        }

        populateForm(service) {
            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                    console.log(`📝 Preenchendo ${id}:`, value);
                }
            };

            const setChecked = (id, checked) => {
                const element = document.getElementById(id);
                if (element) {
                    element.checked = !!checked;
                    console.log(`📝 Checkbox ${id}:`, checked);
                }
            };

            setValue('serviceId', service.id);
            setValue('service_company_id', service.company_id);
            setValue('service_name', service.name);
            setValue('service_description', service.description || '');
            setValue('service_base_price', service.base_price);
            setChecked('service_is_active', service.is_active);
        }

        async saveService() {
            if (this.saving) {
                console.log('⏳ Salvamento já em andamento...');
                return;
            }
            
            this.saving = true;
            console.log('💾 Iniciando salvamento do serviço...');
            
            if (!this.validateServiceForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveServiceButton');
            this.setLoadingState(saveBtn, true);

            try {
                const formData = new FormData(document.getElementById('serviceForm'));
                
                console.log('📤 Enviando dados:', Object.fromEntries(formData));
                
                const apiUrl = '/bt-log-transportes/public/api/services.php?action=save';
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('📥 Resposta da API:', result);

                if (result.success) {
                    this.showAlert('Serviço salvo com sucesso!', 'success');
                    this.closeServiceModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(result.message || 'Erro ao salvar serviço');
                }
                
            } catch (error) {
                console.error('💥 Erro ao salvar:', error);
                this.showAlert('Erro ao salvar serviço: ' + error.message, 'error');
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        async deleteService(serviceId, serviceName) {
            if (confirm(`Tem certeza que deseja excluir o serviço "${serviceName}"?`)) {
                try {
                    const formData = new FormData();
                    formData.append('id', serviceId);
                    
                    const apiUrl = '/bt-log-transportes/public/api/services.php?action=delete';
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Serviço excluído com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir serviço');
                    }
                    
                } catch (error) {
                    console.error('❌ Erro ao excluir:', error);
                    this.showAlert('Erro ao excluir serviço: ' + error.message, 'error');
                }
            }
        }

        async viewService(serviceId) {
            try {
                console.log('👁️ Visualizando serviço:', serviceId);
                
                const apiUrl = `/bt-log-transportes/public/api/services.php?action=get&id=${serviceId}`;
                const response = await fetch(apiUrl);
                
                if (!response.ok) throw new Error('Erro na requisição');
                
                const result = await response.json();

                if (result.success && result.data) {
                    this.showServiceDetails(result.data);
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados');
                }
            } catch (error) {
                console.error('❌ Erro ao visualizar serviço:', error);
                this.showAlert('Erro ao carregar dados do serviço: ' + error.message, 'error');
            }
        }

        showServiceDetails(service) {
            this.viewModal = document.getElementById('viewServiceModal');
            if (!this.viewModal) {
                console.error('❌ Modal de visualização não encontrado');
                return;
            }

            // Preencher os dados
            document.getElementById('modalViewServiceTitle').textContent = `Detalhes: ${service.name}`;
            document.getElementById('viewServiceName').textContent = service.name;
            document.getElementById('viewServiceCompany').textContent = service.company_name || 'N/A';
            document.getElementById('viewServiceDescription').textContent = service.description || 'Sem descrição';
            document.getElementById('viewServicePrice').textContent = `R$ ${parseFloat(service.base_price).toFixed(2).replace('.', ',')}`;
            
            const statusElement = document.getElementById('viewServiceStatus');
            statusElement.textContent = service.is_active ? 'Ativo' : 'Inativo';
            statusElement.className = service.is_active ? 'status-badge active' : 'status-badge inactive';

            // Mostrar modal
            this.viewModal.style.display = 'block';
            this.viewModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
        }

        closeViewModal() {
            if (this.viewModal) {
                this.viewModal.style.display = 'none';
                this.viewModal.classList.remove('show');
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
        }

        validateServiceForm() {
            const company = document.getElementById('service_company_id');
            const name = document.getElementById('service_name');
            const price = document.getElementById('service_base_price');
            
            if (!company || !company.value) {
                this.showAlert('A empresa é obrigatória', 'warning');
                company?.focus();
                return false;
            }
            
            if (!name || !name.value.trim()) {
                this.showAlert('O nome do serviço é obrigatório', 'warning');
                name?.focus();
                return false;
            }
            
            if (!price || !price.value || parseFloat(price.value) < 0) {
                this.showAlert('O preço base deve ser maior ou igual a zero', 'warning');
                price?.focus();
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
                animation: slideInRight 0.3s ease-out;
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

        filterByCompany(companyId) {
            const url = new URL(window.location);
            if (companyId) {
                url.searchParams.set('company', companyId);
            } else {
                url.searchParams.delete('company');
            }
            window.location.href = url.toString();
        }
    }

    // Inicialização melhorada
    if (!window.servicesManager) {
        window.servicesManager = new ServicesManager();
        
        const initServicesManager = () => {
            setTimeout(() => {
                if (window.servicesManager && !window.servicesManager.isInitialized) {
                    window.servicesManager.init();
                }
            }, 300);
        };

        // Inicializar quando o DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initServicesManager);
        } else {
            initServicesManager();
        }

        // Backup: inicializar após um tempo
        setTimeout(initServicesManager, 1000);
    }

    console.log('🔔 services.js carregado com sucesso!');

})();