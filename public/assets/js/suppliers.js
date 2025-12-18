// public/assets/js/suppliers.js

if (!window.suppliersLoaded) {
    window.suppliersLoaded = true;

    console.log('🏭 Suppliers JS carregando...');

    // Variáveis globais
    let currentSupplierId = null;

    // ✅ FUNÇÕES DE MODAL 
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
            
            setTimeout(() => {
                modal.classList.add('show');
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.style.transform = 'translateY(0)';
                    modalContent.style.opacity = '1';
                }
            }, 10);
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.transform = 'translateY(-50px)';
                modalContent.style.opacity = '0';
            }
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }, 300);
        }
    };

    // Fechar modal ao clicar no backdrop
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });

    // Fechar modal com ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModal(modal.id);
                }
            });
        }
    });

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 DOM Carregado - Inicializando Suppliers');
        initializeSuppliers();
    });

    function initializeSuppliers() {
        console.log('🔧 Inicializando sistema de fornecedores...');
        
        // Busca em tempo real
        const searchSupplier = document.getElementById('searchSupplier');
        if (searchSupplier) {
            searchSupplier.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.data-table tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }

        // Máscaras
        const cpfCnpjInput = document.getElementById('cpf_cnpj');
        const phoneInput = document.getElementById('phone');
        const editCpfCnpjInput = document.getElementById('edit_cpf_cnpj');
        const editPhoneInput = document.getElementById('edit_phone');
        
        if (cpfCnpjInput) cpfCnpjInput.addEventListener('input', maskCpfCnpj);
        if (phoneInput) phoneInput.addEventListener('input', maskPhone);
        if (editCpfCnpjInput) editCpfCnpjInput.addEventListener('input', maskCpfCnpj);
        if (editPhoneInput) editPhoneInput.addEventListener('input', maskPhone);

        // Formulário de criação
        const supplierForm = document.getElementById('supplierForm');
        if (supplierForm) {
            supplierForm.addEventListener('submit', handleSupplierFormSubmit);
        }

        // Formulário de edição
        const editSupplierForm = document.getElementById('editSupplierForm');
        if (editSupplierForm) {
            editSupplierForm.addEventListener('submit', handleEditSupplierFormSubmit);
        }

        console.log('✅ Suppliers inicializado com sucesso!');
    }

    // Funções de máscara
    function maskCpfCnpj(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length <= 11) {
            // CPF
            value = value.replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            // CNPJ
            value = value.replace(/(\d{2})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d)/, '$1/$2')
                        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        }
        e.target.value = value;
    }

    function maskPhone(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2')
                        .replace(/(\d{5})(\d{4})$/, '$1-$2');
        }
        e.target.value = value;
    }

    // ✅ VISUALIZAR FORNECEDOR
    window.viewSupplier = async function(id) {
        console.log('👁️ Visualizando fornecedor:', id);
        currentSupplierId = id;
        
        try {
            // Mostrar loading
            document.getElementById('supplierDetails').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Carregando...</p>
                </div>
            `;
            
            openModal('viewSupplierModal');
            
            const response = await fetch(`index.php?page=suppliers&action=view&id=${id}`);
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                populateViewModal(result.data);
            } else {
                throw new Error(result.message || 'Erro ao carregar fornecedor');
            }
            
        } catch (error) {
            console.error('❌ Erro ao carregar fornecedor:', error);
            document.getElementById('supplierDetails').innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Erro ao carregar dados: ${error.message}
                </div>
            `;
        }
    };

    function populateViewModal(supplier) {
        const modal = document.getElementById('supplierDetails');
        modal.innerHTML = `
            <div class="content-card">
                <div class="card-body">
                    <div class="form-section">
                        <h4><i class="fas fa-id-card"></i> Dados do Fornecedor</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome/Razão Social</label>
                                <div class="form-control-static"><strong>${supplier.name || '-'}</strong></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome Fantasia</label>
                                <div class="form-control-static">${supplier.fantasy_name || '-'}</div>
                            </div>
                            <div class="form-group">
                                <label>CPF/CNPJ</label>
                                <div class="form-control-static"><code>${supplier.cpf_cnpj || '-'}</code></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-address-book"></i> Contato</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>E-mail</label>
                                <div class="form-control-static">${supplier.email || '-'}</div>
                            </div>
                            <div class="form-group">
                                <label>Telefone</label>
                                <div class="form-control-static">${supplier.phone || '-'}</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Endereço</h4>
                        <div class="form-group">
                            <label>Endereço</label>
                            <div class="form-control-static">${supplier.address || '-'}</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-info-circle"></i> Informações do Sistema</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Data de Cadastro</label>
                                <div class="form-control-static">${formatDate(supplier.created_at)}</div>
                            </div>
                            <div class="form-group">
                                <label>Última Atualização</label>
                                <div class="form-control-static">${supplier.updated_at ? formatDate(supplier.updated_at) : '-'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
        } catch (e) {
            return dateString;
        }
    }

    // ✅ EDITAR FORNECEDOR
    window.editSupplier = async function(id) {
        console.log('✏️ Editando fornecedor:', id);
        currentSupplierId = id;
        
        try {
            const response = await fetch(`index.php?page=suppliers&action=get&id=${id}`);
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                populateEditForm(result.data);
                openModal('editSupplierModal');
            } else {
                throw new Error(result.message || 'Erro ao carregar fornecedor');
            }
            
        } catch (error) {
            console.error('❌ Erro ao carregar fornecedor para edição:', error);
            alert('Erro ao carregar dados do fornecedor para edição: ' + error.message);
        }
    };

    function populateEditForm(supplier) {
        document.getElementById('edit_name').value = supplier.name || '';
        document.getElementById('edit_fantasy_name').value = supplier.fantasy_name || '';
        document.getElementById('edit_cpf_cnpj').value = supplier.cpf_cnpj || '';
        document.getElementById('edit_email').value = supplier.email || '';
        document.getElementById('edit_phone').value = supplier.phone || '';
        document.getElementById('edit_address').value = supplier.address || '';
        
        // Atualizar action do formulário
        document.getElementById('editSupplierForm').action = `index.php?page=suppliers&action=update&id=${supplier.id}`;
    }

    // ✅ EDITAR FORNECEDOR ATUAL (do modal de visualização)
    window.editCurrentSupplier = function() {
        if (currentSupplierId) {
            closeModal('viewSupplierModal');
            editSupplier(currentSupplierId);
        }
    };

    // ✅ EXCLUIR FORNECEDOR
    window.deleteSupplier = function(id) {
        if (confirm('Tem certeza que deseja excluir este fornecedor?\n\nEsta ação não pode ser desfeita.')) {
            console.log('🗑️ Excluindo fornecedor:', id);
            
            fetch(`index.php?page=suppliers&action=delete&id=${id}`)
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Erro ao excluir fornecedor.');
                    }
                })
                .catch(error => {
                    console.error('❌ Erro:', error);
                    alert('Erro ao excluir fornecedor.');
                });
        }
    };

    // ✅ SUBMIT DO FORMULÁRIO DE CRIAÇÃO
    async function handleSupplierFormSubmit(e) {
        e.preventDefault();
        console.log('💾 Salvando novo fornecedor...');
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Validar campos obrigatórios
        const name = document.getElementById('name').value;
        if (!name.trim()) {
            alert('Por favor, informe o nome do fornecedor.');
            return;
        }
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                console.log('✅ Fornecedor salvo com sucesso!');
                closeModal('supplierModal');
                window.location.reload();
            } else {
                throw new Error('Erro na resposta do servidor');
            }
            
        } catch (error) {
            console.error('❌ Erro ao salvar fornecedor:', error);
            alert('Erro ao salvar fornecedor. Tente novamente.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    // ✅ SUBMIT DO FORMULÁRIO DE EDIÇÃO
    async function handleEditSupplierFormSubmit(e) {
        e.preventDefault();
        console.log('💾 Atualizando fornecedor...');
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Validar campos obrigatórios
        const name = document.getElementById('edit_name').value;
        if (!name.trim()) {
            alert('Por favor, informe o nome do fornecedor.');
            return;
        }
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                console.log('✅ Fornecedor atualizado com sucesso!');
                closeModal('editSupplierModal');
                window.location.reload();
            } else {
                throw new Error('Erro na resposta do servidor');
            }
            
        } catch (error) {
            console.error('❌ Erro ao atualizar fornecedor:', error);
            alert('Erro ao atualizar fornecedor. Tente novamente.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    // Fechar alertas automaticamente
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);

    // Inicialização imediata se DOM já estiver carregado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSuppliers);
    } else {
        initializeSuppliers();
    }

    console.log('✅ Suppliers JS carregado com sucesso!');
}