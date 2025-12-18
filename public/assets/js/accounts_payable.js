// public/assets/js/accounts_payable.js - VERSÃO COMPLETA E CORRIGIDA

// ✅ VERIFICAR SE JÁ EXISTE ANTES DE DECLARAR
if (typeof AccountsPayableManager === 'undefined') {

class AccountsPayableManager {
    constructor() {
        this.currentSupplierType = 'custom';
        this.suppliers = [];
        this.isInitialized = false;
        this.saving = false;
        this.deleting = false;
        this.editingId = null;
        this.filters = {
            status: '',
            period: 'month',
            supplier: '',
            search: ''
        };
    }

    init() {
        if (this.isInitialized) return;
        
        console.log('🎯 Inicializando Accounts Payable Manager...');
        
        this.setupEventListeners();
        this.loadSuppliers();
        this.setupFilters();
        this.setupSearch();
        this.isInitialized = true;
        
        console.log('✅ Accounts Payable Manager pronto!');
    }

    setupEventListeners() {
        console.log('🔧 Configurando event listeners...');
        
        // Toggle fornecedor
        const supplierCheckbox = document.getElementById('is_supplier');
        if (supplierCheckbox) {
            supplierCheckbox.addEventListener('change', (e) => {
                this.toggleSupplierFields(e.target.checked);
            });
        }

        // Botão novo fornecedor rápido
        const newSupplierBtn = document.getElementById('newSupplierQuickBtn');
        if (newSupplierBtn) {
            newSupplierBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openQuickSupplierModal();
            });
        }

        // Formatação monetária
        const amountInput = document.getElementById('amount');
        if (amountInput) {
            amountInput.addEventListener('blur', (e) => {
                this.formatCurrency(e.target);
            });
            amountInput.addEventListener('focus', (e) => {
                this.clearCurrencyFormat(e.target);
            });
        }

        // Recorrência
        const recurringCheckbox = document.getElementById('is_recurring');
        if (recurringCheckbox) {
            recurringCheckbox.addEventListener('change', (e) => {
                this.toggleRecurrenceFields(e.target.checked);
            });
        }

        // Salvar
        const saveButton = document.getElementById('savePayableButton');
        if (saveButton) {
            saveButton.addEventListener('click', () => {
                this.savePayable();
            });
        }

        // Busca fornecedores
        const searchSupplier = document.getElementById('searchSupplier');
        if (searchSupplier) {
            searchSupplier.addEventListener('input', (e) => {
                this.filterSuppliers(e.target.value);
            });
        }

        // Botões de ação na tabela
        this.setupTableActions();
    }

    setupTableActions() {
        // Delegation handler para ações na tabela
        document.addEventListener('click', (e) => {
            // Marcar como pago
            if (e.target.closest('.btn-mark-paid')) {
                e.preventDefault();
                const row = e.target.closest('tr');
                const id = row?.dataset?.id;
                if (id) {
                    this.markAsPaid(id);
                }
                return;
            }

            // Editar
            if (e.target.closest('.btn-edit-payable')) {
                e.preventDefault();
                const row = e.target.closest('tr');
                const id = row?.dataset?.id;
                if (id) {
                    this.editPayable(id);
                }
                return;
            }

            // Reabrir
            if (e.target.closest('.btn-reopen-payable')) {
                e.preventDefault();
                const row = e.target.closest('tr');
                const id = row?.dataset?.id;
                if (id) {
                    this.reopenPayable(id);
                }
                return;
            }

            // Excluir
            if (e.target.closest('.btn-delete-payable')) {
                e.preventDefault();
                const row = e.target.closest('tr');
                const id = row?.dataset?.id;
                const description = row?.querySelector('.description')?.textContent || 'Conta';
                if (id) {
                    this.deletePayable(id, description);
                }
                return;
            }
        });
    }

    setupFilters() {
        const statusFilter = document.getElementById('statusFilter');
        const periodSelect = document.getElementById('periodSelect');
        const supplierFilter = document.getElementById('supplierFilter');

        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.applyFilters();
            });
        }

        if (periodSelect) {
            periodSelect.addEventListener('change', (e) => {
                this.filters.period = e.target.value;
                this.applyFilters();
            });
        }

        if (supplierFilter) {
            supplierFilter.addEventListener('change', (e) => {
                this.filters.supplier = e.target.value;
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
        const searchInput = document.getElementById('searchPayable');
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

    toggleSupplierFields(isRegistered) {
        const customField = document.getElementById('custom_supplier_field');
        const registeredField = document.getElementById('registered_supplier_field');

        if (!customField || !registeredField) return;

        if (isRegistered) {
            customField.style.display = 'none';
            registeredField.style.display = 'block';
        } else {
            customField.style.display = 'block';
            registeredField.style.display = 'none';
        }

        this.currentSupplierType = isRegistered ? 'registered' : 'custom';
    }

    toggleRecurrenceFields(isRecurring) {
        const recurrenceFields = document.getElementById('recurrence_fields');
        if (!recurrenceFields) return;

        recurrenceFields.style.display = isRecurring ? 'block' : 'none';
    }

    formatCurrency(input) {
        if (!input || !input.value) return;
        
        let value = input.value.replace(/\D/g, '');
        if (!value) {
            input.value = '';
            return;
        }
        
        value = (parseInt(value) / 100).toFixed(2);
        value = value.replace('.', ',');
        value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = 'R$ ' + value;
    }

    clearCurrencyFormat(input) {
        if (!input || !input.value) return;
        
        let value = input.value.replace('R$ ', '').replace(/\./g, '').replace(',', '.');
        input.value = value;
    }

    async loadSuppliers() {
        try {
            console.log('🔄 Carregando fornecedores...');
            
            const response = await fetch('/bt-log-transportes/public/api/accounts_payable.php?action=get_suppliers');
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.suppliers = result.data || [];
                this.populateSuppliersDropdown(this.suppliers);
                this.populateSupplierFilter(this.suppliers);
                console.log(`✅ ${this.suppliers.length} fornecedores carregados`);
            } else {
                throw new Error(result.message || 'Erro ao carregar fornecedores');
            }
        } catch (error) {
            console.error('❌ Erro ao carregar fornecedores:', error);
            this.suppliers = [];
            this.populateSuppliersDropdown(this.suppliers);
            this.populateSupplierFilter(this.suppliers);
        }
    }

    populateSuppliersDropdown(suppliers) {
        const dropdown = document.getElementById('supplier_selection');
        if (!dropdown) return;

        // Limpar opções existentes (exceto a primeira)
        while (dropdown.children.length > 1) {
            dropdown.removeChild(dropdown.lastChild);
        }

        // Adicionar fornecedores
        suppliers.forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier.id;
            option.textContent = supplier.name;
            if (supplier.phone) {
                option.textContent += ` - ${supplier.phone}`;
            }
            option.setAttribute('data-supplier-name', supplier.name);
            dropdown.appendChild(option);
        });
    }

    populateSupplierFilter(suppliers) {
        const filter = document.getElementById('supplierFilter');
        if (!filter) return;

        // Limpar opções existentes (exceto a primeira)
        while (filter.children.length > 1) {
            filter.removeChild(filter.lastChild);
        }

        // Adicionar fornecedores
        suppliers.forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier.id;
            option.textContent = supplier.name;
            filter.appendChild(option);
        });
    }

    filterSuppliers(searchTerm) {
        const dropdown = document.getElementById('supplier_selection');
        if (!dropdown) return;

        const options = dropdown.querySelectorAll('option');
        let hasVisibleOptions = false;

        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
                return;
            }

            const supplierName = option.getAttribute('data-supplier-name') || option.textContent;
            if (supplierName.toLowerCase().includes(searchTerm.toLowerCase())) {
                option.style.display = 'block';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
            }
        });
    }

    applyFilters() {
        const rows = document.querySelectorAll('#accountsTable tbody tr');
        let visibleCount = 0;
        const today = new Date();
        
        rows.forEach(row => {
            let show = true;
            
            // Filtro por status
            if (this.filters.status && row.dataset.status !== this.filters.status) {
                show = false;
            }
            
            // Filtro por fornecedor
            if (this.filters.supplier && row.dataset.supplierId != this.filters.supplier) {
                show = false;
            }
            
            // Filtro por busca
            if (this.filters.search) {
                const rowText = row.textContent.toLowerCase();
                if (!rowText.includes(this.filters.search)) {
                    show = false;
                }
            }
            
            // Filtro por período
            if (this.filters.period) {
                const dueDateStr = row.dataset.dueDate;
                if (dueDateStr) {
                    const dueDate = new Date(dueDateStr);
                    const isOverdue = dueDate < today && row.dataset.status === 'pendente';
                    
                    switch (this.filters.period) {
                        case 'overdue':
                            if (!isOverdue) show = false;
                            break;
                        case 'week':
                            const weekAgo = new Date();
                            weekAgo.setDate(today.getDate() - 7);
                            if (dueDate < weekAgo || dueDate > today) show = false;
                            break;
                        case 'month':
                            const monthAgo = new Date();
                            monthAgo.setMonth(today.getMonth() - 1);
                            if (dueDate < monthAgo || dueDate > today) show = false;
                            break;
                        case 'quarter':
                            const quarterAgo = new Date();
                            quarterAgo.setMonth(today.getMonth() - 3);
                            if (dueDate < quarterAgo || dueDate > today) show = false;
                            break;
                        case 'year':
                            const yearAgo = new Date();
                            yearAgo.setFullYear(today.getFullYear() - 1);
                            if (dueDate < yearAgo || dueDate > today) show = false;
                            break;
                    }
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
            status: '',
            period: 'month',
            supplier: '',
            search: ''
        };
        
        // Resetar inputs
        document.getElementById('statusFilter').value = '';
        document.getElementById('periodSelect').value = 'month';
        document.getElementById('supplierFilter').value = '';
        document.getElementById('searchPayable').value = '';
        
        this.applyFilters();
    }

    updateResultsCount(count) {
        const countElement = document.querySelector('.results-count');
        if (countElement) {
            countElement.textContent = `${count} conta${count !== 1 ? 's' : ''} encontrada${count !== 1 ? 's' : ''}`;
        }
    }

    openQuickSupplierModal() {
        console.log('🏭 Redirecionando para cadastro de fornecedores...');
        
        // Salvar estado atual da página
        this.saveCurrentState();
        
        // Preparar URL de retorno
        const currentUrl = window.location.href.split('?')[0];
        const returnUrl = encodeURIComponent(currentUrl);
        
        // Redirecionar para fornecedores com flags
        window.location.href = `index.php?page=suppliers&return_to=accounts_payable&open_modal=true&return_url=${returnUrl}`;
    }

    saveCurrentState() {
        const formData = {
            description: document.getElementById('description')?.value || '',
            amount: document.getElementById('amount')?.value || '',
            due_date: document.getElementById('due_date')?.value || '',
            chart_account_id: document.getElementById('chart_account_id')?.value || '',
            notes: document.getElementById('notes')?.value || '',
            is_supplier: document.getElementById('is_supplier')?.checked || false,
            supplier_custom: document.getElementById('supplier_custom')?.value || '',
            supplier_selection: document.getElementById('supplier_selection')?.value || '',
            is_recurring: document.getElementById('is_recurring')?.checked || false,
            recurrence_frequency: document.getElementById('recurrence_frequency')?.value || ''
        };
        
        sessionStorage.setItem('ap_form_data', JSON.stringify(formData));
    }

    restoreSavedState() {
        const savedData = sessionStorage.getItem('ap_form_data');
        if (savedData) {
            const formData = JSON.parse(savedData);
            
            // Restaurar valores do formulário
            if (document.getElementById('description')) 
                document.getElementById('description').value = formData.description;
            if (document.getElementById('amount')) 
                document.getElementById('amount').value = formData.amount;
            if (document.getElementById('due_date')) 
                document.getElementById('due_date').value = formData.due_date;
            if (document.getElementById('chart_account_id')) 
                document.getElementById('chart_account_id').value = formData.chart_account_id;
            if (document.getElementById('notes')) 
                document.getElementById('notes').value = formData.notes;
            if (document.getElementById('is_supplier')) {
                document.getElementById('is_supplier').checked = formData.is_supplier;
                this.toggleSupplierFields(formData.is_supplier);
            }
            if (document.getElementById('supplier_custom')) 
                document.getElementById('supplier_custom').value = formData.supplier_custom;
            if (document.getElementById('supplier_selection')) 
                document.getElementById('supplier_selection').value = formData.supplier_selection;
            if (document.getElementById('is_recurring')) {
                document.getElementById('is_recurring').checked = formData.is_recurring;
                this.toggleRecurrenceFields(formData.is_recurring);
            }
            if (document.getElementById('recurrence_frequency')) 
                document.getElementById('recurrence_frequency').value = formData.recurrence_frequency;
            
            // Limpar dados salvos
            sessionStorage.removeItem('ap_form_data');
        }
    }

    checkReturnFromSuppliers() {
        const urlParams = new URLSearchParams(window.location.search);
        const returnedFrom = urlParams.get('returned_from');
        const supplierSaved = urlParams.get('supplier_saved');
        
        if (returnedFrom === 'suppliers') {
            console.log('🔄 Retornando de fornecedores...');
            
            // Restaurar estado salvo
            this.restoreSavedState();
            
            // Mostrar mensagem de sucesso se o fornecedor foi salvo
            if (supplierSaved === 'true') {
                this.showAlert('Fornecedor cadastrado com sucesso!', 'success');
                
                // Recarregar lista de fornecedores
                setTimeout(() => {
                    this.loadSuppliers();
                }, 1000);
            }
            
            // Limpar parâmetros da URL
            this.cleanUrl();
        }
    }

    cleanUrl() {
        const url = new URL(window.location);
        url.searchParams.delete('returned_from');
        url.searchParams.delete('supplier_saved');
        url.searchParams.delete('open_modal');
        window.history.replaceState({}, '', url.toString());
    }

    validateForm() {
        const fields = {
            'description': 'Descrição da conta',
            'amount': 'Valor',
            'due_date': 'Data de vencimento',
            'chart_account_id': 'Conta contábil'
        };

        const errors = [];

        // Validar campos básicos
        for (const [field, name] of Object.entries(fields)) {
            const element = document.getElementById(field);
            if (!element || !element.value) {
                errors.push(`${name} é obrigatório`);
                element?.focus();
            }
        }

        // Validar valor
        const amount = document.getElementById('amount');
        if (amount && amount.value && this.parseCurrency(amount.value) <= 0) {
            errors.push('Valor deve ser maior que zero');
        }

        // Validar fornecedor
        const isSupplierChecked = document.getElementById('is_supplier')?.checked;
        
        if (isSupplierChecked) {
            const supplierSelection = document.getElementById('supplier_selection');
            if (!supplierSelection || !supplierSelection.value) {
                errors.push('Selecione um fornecedor cadastrado');
            }
        } else {
            const supplierCustom = document.getElementById('supplier_custom');
            if (!supplierCustom || !supplierCustom.value.trim()) {
                errors.push('Nome do fornecedor é obrigatório');
            }
        }

        // Validar recorrência
        const isRecurring = document.getElementById('is_recurring')?.checked;
        if (isRecurring) {
            const frequency = document.getElementById('recurrence_frequency')?.value;
            if (!frequency) {
                errors.push('Frequência da recorrência é obrigatória');
            }
        }

        if (errors.length > 0) {
            this.showAlert(errors.join(', '), 'error');
            return false;
        }

        return true;
    }

    async savePayable() {
        if (this.saving) {
            console.log('⏳ Salvamento já em andamento...');
            return;
        }
        
        console.log('💾 Iniciando salvamento da conta...');
        
        if (!this.validateForm()) {
            return;
        }

        this.saving = true;
        const saveBtn = document.getElementById('savePayableButton');
        
        try {
            this.setLoading(saveBtn, true);

            // Coletar dados do formulário
            const formData = new FormData();
            
            // Se estiver editando, adicionar ID
            if (this.editingId) {
                formData.append('id', this.editingId);
            }
            
            // Dados básicos
            formData.append('description', document.getElementById('description').value);
            
            // Converter valor corretamente
            const amountValue = this.parseCurrency(document.getElementById('amount').value);
            formData.append('amount', amountValue.toString());
            
            formData.append('due_date', document.getElementById('due_date').value);
            formData.append('chart_account_id', document.getElementById('chart_account_id').value);
            formData.append('notes', document.getElementById('notes').value || '');
            
            // Fornecedor
            const isSupplier = document.getElementById('is_supplier').checked;
            formData.append('supplier_type', isSupplier ? 'registered' : 'custom');
            
            if (isSupplier) {
                formData.append('supplier_selection', document.getElementById('supplier_selection').value);
            } else {
                formData.append('supplier_custom', document.getElementById('supplier_custom').value);
            }
            
            // Recorrência
            const isRecurring = document.getElementById('is_recurring').checked;
            formData.append('is_recurring', isRecurring ? '1' : '0');
            
            if (isRecurring) {
                formData.append('recurrence_frequency', document.getElementById('recurrence_frequency').value);
            }

            console.log('📤 Dados enviados:', Object.fromEntries(formData));

            const response = await fetch('/bt-log-transportes/public/api/accounts_payable.php?action=save', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            console.log('✅ Resposta da API:', result);

            if (result.success) {
                this.showAlert(
                    this.editingId ? 'Conta atualizada com sucesso!' : 'Conta salva com sucesso!', 
                    'success'
                );
                this.closeModal();
                
                // Recarregar página após sucesso
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erro ao salvar conta');
            }

        } catch (error) {
            console.error('❌ Erro ao salvar conta:', error);
            this.showAlert('Erro: ' + error.message, 'error');
        } finally {
            this.setLoading(saveBtn, false);
            this.saving = false;
            this.editingId = null;
        }
    }

    async markAsPaid(id) {
        if (!confirm('Deseja marcar esta conta como paga?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('/bt-log-transportes/public/api/accounts_payable.php?action=mark_paid', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Conta marcada como paga!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            this.showAlert('Erro: ' + error.message, 'error');
        }
    }

    async editPayable(id) {
        try {
            const response = await fetch(`/bt-log-transportes/public/api/accounts_payable.php?action=get&id=${id}`);
            const result = await response.json();

            if (result.success && result.data) {
                const account = result.data;
                this.editingId = id;
                
                // Preencher modal com dados
                document.getElementById('description').value = account.description || '';
                document.getElementById('amount').value = account.amount ? 'R$ ' + parseFloat(account.amount).toFixed(2).replace('.', ',') : '';
                document.getElementById('due_date').value = account.due_date || '';
                document.getElementById('chart_account_id').value = account.chart_account_id || '';
                document.getElementById('notes').value = account.notes || '';
                
                // Configurar fornecedor
                const isRegisteredSupplier = account.supplier_id && account.supplier_id > 0;
                document.getElementById('is_supplier').checked = isRegisteredSupplier;
                this.toggleSupplierFields(isRegisteredSupplier);
                
                if (isRegisteredSupplier) {
                    document.getElementById('supplier_selection').value = account.supplier_id || '';
                } else {
                    document.getElementById('supplier_custom').value = account.supplier || '';
                }
                
                // Configurar recorrência
                document.getElementById('is_recurring').checked = account.is_recurring == 1;
                this.toggleRecurrenceFields(account.is_recurring == 1);
                document.getElementById('recurrence_frequency').value = account.recurrence_frequency || '';
                
                // Atualizar título do modal
                document.querySelector('#payableModal h3').innerHTML = 
                    '<i class="fas fa-edit"></i> Editar Conta a Pagar';
                
                // Abrir modal
                this.openModal();
            } else {
                throw new Error(result.message || 'Erro ao carregar dados da conta');
            }
        } catch (error) {
            console.error('❌ Erro ao editar conta:', error);
            this.showAlert('Erro ao carregar dados da conta: ' + error.message, 'error');
        }
    }

    async reopenPayable(id) {
        if (!confirm('Deseja reabrir esta conta para pagamento?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', 'pendente');

            const response = await fetch('/bt-log-transportes/public/api/accounts_payable.php?action=update_status', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Conta reaberta com sucesso!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            this.showAlert('Erro: ' + error.message, 'error');
        }
    }

    async deletePayable(id, description = 'Conta') {
        if (!confirm(`Tem certeza que deseja excluir a conta "${description}"?\n\nEsta ação não pode ser desfeita.`)) return;

        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('/bt-log-transportes/public/api/accounts_payable.php?action=delete', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Conta excluída com sucesso!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            this.showAlert('Erro: ' + error.message, 'error');
        }
    }

    parseCurrency(value) {
        if (!value) return 0;
        try {
            let cleanedValue = value.toString().replace('R$ ', '').replace(/\./g, '').replace(',', '.');
            return parseFloat(cleanedValue) || 0;
        } catch {
            return 0;
        }
    }

    setLoading(button, isLoading) {
        if (!button) return;
        
        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (btnText && btnLoading) {
            btnText.style.display = isLoading ? 'none' : 'block';
            btnLoading.style.display = isLoading ? 'flex' : 'none';
        }
        
        button.disabled = isLoading;
    }

    showAlert(message, type = 'info', duration = 5000) {
        // Remover alertas existentes
        document.querySelectorAll('.global-alert').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert-ap alert-${type}-ap fade-in`;
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
        alertDiv.innerHTML = `
            <div style="display: flex; align-items: center;">
                <span style="flex: 1;">${message}</span>
                <button style="background: none; border: none; color: white; margin-left: 10px; cursor: pointer;" 
                        onclick="this.parentElement.parentElement.remove()">✕</button>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, duration);
    }

    openModal() {
        const modal = document.getElementById('payableModal');
        if (modal) {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    closeModal() {
        const modal = document.getElementById('payableModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            this.resetForm();
            this.editingId = null;
        }
    }

    resetForm() {
        const form = document.getElementById('payableForm');
        if (form) {
            form.reset();
            document.getElementById('is_supplier').checked = false;
            document.getElementById('is_recurring').checked = false;
            this.toggleSupplierFields(false);
            this.toggleRecurrenceFields(false);
            
            // Resetar título do modal
            document.querySelector('#payableModal h3').innerHTML = 
                '<i class="fas fa-plus-circle"></i> Nova Conta a Pagar';
            
            const dueDate = document.getElementById('due_date');
            if (dueDate) {
                dueDate.value = new Date().toISOString().split('T')[0];
            }
        }
    }
}

} // Fim do if typeof

// ✅ FUNÇÕES GLOBAIS PARA COMPATIBILIDADE
if (typeof window.openModal !== 'function') {
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    };
}

if (typeof window.closeModal !== 'function') {
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    };
}

if (typeof window.openPayableModal !== 'function') {
    window.openPayableModal = function() {
        if (window.accountsPayableManager) {
            window.accountsPayableManager.resetForm();
            window.accountsPayableManager.openModal();
        } else {
            const modal = document.getElementById('payableModal');
            if (modal) {
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        }
    };
}

if (typeof window.closePayableModal !== 'function') {
    window.closePayableModal = function() {
        if (window.accountsPayableManager) {
            window.accountsPayableManager.closeModal();
        } else {
            const modal = document.getElementById('payableModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        }
    };
}

if (typeof window.markAsPaid !== 'function') {
    window.markAsPaid = function(id) {
        if (window.accountsPayableManager) {
            window.accountsPayableManager.markAsPaid(id);
        }
    };
}

if (typeof window.editPayable !== 'function') {
    window.editPayable = function(id) {
        if (window.accountsPayableManager) {
            window.accountsPayableManager.editPayable(id);
        }
    };
}

if (typeof window.deletePayable !== 'function') {
    window.deletePayable = function(id) {
        if (window.accountsPayableManager) {
            window.accountsPayableManager.deletePayable(id);
        }
    };
}

// ✅ INICIALIZAÇÃO AUTOMÁTICA
function initializeAccountsPayable() {
    // Verificar se estamos na página de contas a pagar
    const isPayablePage = window.location.href.includes('accounts_payable') || 
                         document.getElementById('payableModal');
    
    if (isPayablePage) {
        // ✅ VERIFICAR SE JÁ EXISTE ANTES DE CRIAR NOVA INSTÂNCIA
        if (!window.accountsPayableManager) {
            window.accountsPayableManager = new AccountsPayableManager();
        }
        window.accountsPayableManager.init();
        window.accountsPayableManager.checkReturnFromSuppliers();
    }
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAccountsPayable);
} else {
    initializeAccountsPayable();
}

console.log('✅ accounts_payable.js carregado - Módulo seguro');