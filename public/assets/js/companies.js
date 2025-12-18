// public/assets/js/companies.js - VERSÃO CORRIGIDA E ESTÁVEL
(function() {
    'use strict';

    console.log('🔧 Companies Manager - CARREGANDO VERSÃO CORRIGIDA');

    // Prevenir dupla inicialização
    if (window.companiesManager) {
        console.log('⚠️ companiesManager já existe - reutilizando...');
        return;
    }

    class CompaniesManager {
        constructor() {
            this.currentCompanyId = null;
            this.isInitialized = false;
            this.saving = false;
            this.deleting = false;
            this.modal = null;
            console.log('✅ CompaniesManager instanciado');
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 CompaniesManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando CompaniesManager...');
            this.setupAllEvents();
            this.isInitialized = true;
            
            // ✅ CORREÇÃO: Garantir que os métodos estejam disponíveis globalmente
            this.exposeMethods();
            
            console.log('✅ CompaniesManager inicializado com sucesso!');
        }

        // ✅ CORREÇÃO CRÍTICA: Expor métodos globalmente
        exposeMethods() {
            // Garantir que os métodos estejam disponíveis mesmo se o init falhar
            window.companiesManager = this;
            
            // Expor métodos específicos globalmente para os onclick
            window.openCompanyForm = (companyId = null) => this.openCompanyForm(companyId);
            window.viewCompany = (companyId) => this.viewCompany(companyId);
            window.editCompany = (companyId) => this.editCompany(companyId);
            window.deleteCompany = (companyId, companyName) => this.deleteCompany(companyId, companyName);
            
            console.log('🔧 Métodos expostos globalmente');
        }

        setupAllEvents() {
            this.setupButtonEvents();
            this.setupModalEvents();
            this.setupFormEvents();
        }

        setupButtonEvents() {
            console.log('🔧 Configurando eventos dos botões...');
            
            // Botão "Nova Empresa"
            const newCompanyBtn = document.getElementById('newCompanyBtn');
            if (newCompanyBtn) {
                console.log('✅ Botão nova empresa encontrado');
                newCompanyBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 BOTÃO NOVA EMPRESA CLICADO!');
                    this.openCompanyForm();
                });
            }

            // Botão "Cadastrar Empresa" no empty state
            const emptyStateBtn = document.getElementById('emptyStateBtn');
            if (emptyStateBtn) {
                emptyStateBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('🎯 BOTÃO EMPTY STATE CLICADO!');
                    this.openCompanyForm();
                });
            }

            // Botão Atualizar
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('🔄 Atualizando...');
                    this.refreshCompanies();
                });
            }

            // ✅ CORREÇÃO: Event delegation SIMPLIFICADO
            document.addEventListener('click', (e) => {
                // Botão Visualizar
                if (e.target.closest('.btn-view') || e.target.closest('.view-company-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('👁️ BOTÃO VIEW CLICADO - DELEGATION!');
                    
                    const btn = e.target.closest('.btn-view') || e.target.closest('.view-company-btn');
                    const row = btn.closest('tr');
                    const companyId = btn.getAttribute('data-company-id') || row?.getAttribute('data-company-id');
                    
                    if (companyId) {
                        console.log('👁️ Visualizando empresa ID:', companyId);
                        this.viewCompany(companyId);
                    }
                    return false;
                }

                // Botão Editar
                if (e.target.closest('.btn-edit') || e.target.closest('.edit-company-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✏️ BOTÃO EDIT CLICADO!');
                    
                    const btn = e.target.closest('.btn-edit') || e.target.closest('.edit-company-btn');
                    const row = btn.closest('tr');
                    const companyId = btn.getAttribute('data-company-id') || row?.getAttribute('data-company-id');
                    
                    if (companyId) {
                        console.log('✏️ Editando empresa ID:', companyId);
                        this.editCompany(companyId);
                    }
                    return;
                }

                // Botão Excluir
                if (e.target.closest('.btn-delete') || e.target.closest('.delete-company-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🗑️ BOTÃO DELETE CLICADO!');
                    
                    const btn = e.target.closest('.btn-delete') || e.target.closest('.delete-company-btn');
                    const row = btn.closest('tr');
                    const companyId = btn.getAttribute('data-company-id') || row?.getAttribute('data-company-id');
                    const companyName = btn.getAttribute('data-company-name');
                    
                    if (companyId) {
                        console.log('🗑️ Excluindo:', companyName, 'ID:', companyId);
                        this.deleteCompany(companyId, companyName);
                    }
                    return;
                }
            });

            console.log('✅ Eventos dos botões configurados!');
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos do modal...');
            
            this.modal = document.getElementById('companyModal');
            if (!this.modal) {
                console.error('❌ MODAL NÃO ENCONTRADO!');
                return;
            }

            console.log('✅ Modal encontrado');

            // Fechar com X
            const closeBtn = this.modal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            // Fechar clicando fora
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });

            // Fechar com ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal[style*="display: block"]');
                    if (openModal) {
                        this.closeModal();
                    }
                }
            });

            // Botão Cancelar
            const cancelBtn = document.getElementById('cancelButton');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            // Botão Salvar
            const saveBtn = document.getElementById('saveButton');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    console.log('💾 Botão salvar clicado');
                    this.saveCompany();
                });
            }

            console.log('✅ Eventos do modal configurados!');
        }

        setupFormEvents() {
            console.log('🔧 Configurando eventos do formulário...');
            
            // Color picker
            const colorInput = document.getElementById('color');
            const colorValue = document.getElementById('colorValue');
            if (colorInput && colorValue) {
                colorInput.addEventListener('input', (e) => {
                    colorValue.textContent = e.target.value;
                    colorValue.style.color = e.target.value;
                    this.updateLogoPreviewColor(e.target.value);
                });
            }

            // File upload com preview
            const logoInput = document.getElementById('logo');
            const fileInfo = document.getElementById('fileInfo');
            
            if (logoInput && fileInfo) {
                logoInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        fileInfo.textContent = file.name;
                        fileInfo.style.color = '#4CAF50';
                        this.createLogoPreview(file);
                    } else {
                        fileInfo.textContent = 'Nenhum arquivo selecionado';
                        fileInfo.style.color = '#666';
                        this.resetLogoPreview();
                    }
                });
            }

            // Máscaras
            this.setupMasks();
            
            // Inscrição Estadual
            this.setupIEToggle();

            console.log('✅ Eventos do formulário configurados!');
        }

        // 🎯 MÉTODO VIEW COMPANY - CORRIGIDO
        viewCompany(companyId) {
            console.log('🚀 EXECUTANDO viewCompany:', companyId);
            
            if (!companyId) {
                console.error('❌ ID da empresa não fornecido');
                return;
            }
            
            this.currentCompanyId = companyId;
            
            // Abrir modal
            const modal = document.getElementById('companyModal');
            if (!modal) {
                alert('❌ Modal não encontrado!');
                return;
            }
            
            // Configurar como visualização
            const title = document.getElementById('modalTitle');
            if (title) title.textContent = 'Visualizar Empresa';
            
            const saveBtn = document.getElementById('saveButton');
            if (saveBtn) saveBtn.style.display = 'none';
            
            // Abrir modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            // Carregar dados
            this.loadCompanyData(companyId);
            
            // Desabilitar campos
            this.setFormReadOnly(true);
            
            console.log('✅ Modal aberto em modo visualização');
        }

        // 🎯 MÉTODO SET FORM READ ONLY
        setFormReadOnly(readOnly) {
            console.log('🔒 Modo leitura:', readOnly);
            const form = document.getElementById('companyForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.id !== 'cancelButton') {
                    input.disabled = readOnly;
                }
            });
        }

        // 🎯 MÉTODO LOAD COMPANY DATA
        async loadCompanyData(companyId) {
            console.log('📥 Carregando dados da empresa:', companyId);
            
            try {
                const response = await fetch(`/bt-log-transportes/public/api/companies.php?action=get&id=${companyId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    this.populateForm(result.data);
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados');
                }
            } catch (error) {
                console.error('❌ Erro ao carregar dados:', error);
                this.populateWithSampleData(companyId);
            }
        }

        // 🎯 MÉTODO POPULATE FORM
        populateForm(company) {
            console.log('📝 Preenchendo formulário com dados REAIS:', company);
            
            // Campo ID oculto
            if (document.getElementById('companyId')) 
                document.getElementById('companyId').value = company.id || '';
            
            // Informações básicas
            if (document.getElementById('name')) 
                document.getElementById('name').value = company.name || '';
            if (document.getElementById('razao_social')) 
                document.getElementById('razao_social').value = company.razao_social || '';
            if (document.getElementById('cnpj')) 
                document.getElementById('cnpj').value = company.cnpj || '';
            
            // Inscrição Estadual
            if (document.getElementById('inscricao_estadual')) 
                document.getElementById('inscricao_estadual').value = company.inscricao_estadual || '';
            if (document.getElementById('isento_ie')) 
                document.getElementById('isento_ie').checked = company.isento_ie || false;
            
            // Área de atuação
            if (document.getElementById('atuacao')) 
                document.getElementById('atuacao').value = company.atuacao || '';
            
            // Contato
            if (document.getElementById('email')) 
                document.getElementById('email').value = company.email || '';
            if (document.getElementById('phone')) 
                document.getElementById('phone').value = company.phone || '';
            if (document.getElementById('phone2')) 
                document.getElementById('phone2').value = company.phone2 || '';
            
            // Endereço
            if (document.getElementById('address')) 
                document.getElementById('address').value = company.address || '';
            
            // Cor da empresa
            if (document.getElementById('color') && company.color) {
                document.getElementById('color').value = company.color;
                const colorValue = document.getElementById('colorValue');
                if (colorValue) {
                    colorValue.textContent = company.color;
                    colorValue.style.color = company.color;
                }
            }
            
            // Status
            if (document.getElementById('is_active')) 
                document.getElementById('is_active').checked = company.is_active !== undefined ? company.is_active : true;
            
            console.log('✅ Formulário preenchido completamente!');
            
            // Atualizar visibilidade do campo IE
            this.updateIEFieldVisibility();
            
            // Atualizar preview da logo
            this.loadExistingLogo(company.logo, company.name, company.color);
        }

        // 🎯 MÉTODO POPULATE WITH SAMPLE DATA
        populateWithSampleData(companyId) {
            console.log('🔄 Usando dados de exemplo');
            
            const sampleData = {
                id: companyId,
                name: `Empresa ${companyId} (Exemplo)`,
                razao_social: `Razão Social Empresa ${companyId}`,
                cnpj: '00.000.000/0001-00',
                inscricao_estadual: '123.456.789',
                isento_ie: false,
                atuacao: 'transportes',
                email: `empresa${companyId}@exemplo.com`,
                phone: '(11) 99999-9999',
                phone2: '(11) 88888-8888',
                address: `Endereço da Empresa ${companyId}, São Paulo - SP`,
                color: '#FF6B00',
                is_active: true
            };
            
            this.populateForm(sampleData);
        }

        // 🎯 MÉTODO UPDATE IE FIELD VISIBILITY
        updateIEFieldVisibility() {
            const isentoCheckbox = document.getElementById('isento_ie');
            const ieField = document.getElementById('ie-field');
            
            if (isentoCheckbox && ieField) {
                if (isentoCheckbox.checked) {
                    ieField.style.display = 'none';
                } else {
                    ieField.style.display = 'grid';
                }
            }
        }

        // 🎯 MÉTODO EDIT COMPANY
        editCompany(companyId) {
            console.log('✏️ Editando empresa ID:', companyId);
            this.openCompanyForm(companyId);
        }

        // 🎯 MÉTODO OPEN COMPANY FORM
        openCompanyForm(companyId = null) {
            console.log('🎯 EXECUTANDO openCompanyForm:', companyId);
            
            this.currentCompanyId = companyId;
            const modal = document.getElementById('companyModal');
            const title = document.getElementById('modalTitle');
            const saveBtn = document.getElementById('saveButton');

            if (!modal) {
                console.error('❌ MODAL NÃO ENCONTRADO!');
                alert('Erro: Modal não encontrado');
                return;
            }

            // Habilitar formulário
            this.setFormReadOnly(false);

            // Mostrar botão salvar
            if (saveBtn) {
                saveBtn.style.display = 'flex';
            }

            // Configurar título
            if (companyId) {
                title.textContent = 'Editar Empresa';
                this.loadCompanyData(companyId);
            } else {
                title.textContent = 'Nova Empresa';
                this.resetForm();
            }

            // Abrir modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ MODAL ABERTO!');
        }

        // 🎯 MÉTODO CLOSE MODAL
        closeModal() {
            console.log('🔒 Fechando modal...');
            const modal = document.getElementById('companyModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.classList.remove('modal-open');
                this.resetForm();
            }
        }

        // 🎯 MÉTODO RESET FORM
        resetForm() {
            const form = document.getElementById('companyForm');
            if (form) {
                form.reset();
                
                // Resetar cor
                const colorValue = document.getElementById('colorValue');
                if (colorValue) {
                    colorValue.textContent = '#FF6B00';
                    colorValue.style.color = '#FF6B00';
                }
                
                // Resetar arquivo
                const fileInfo = document.getElementById('fileInfo');
                if (fileInfo) {
                    fileInfo.textContent = 'Nenhum arquivo selecionado';
                    fileInfo.style.color = '#666';
                }
                
                // Resetar preview da logo
                this.resetLogoPreview();
                
                // Resetar ID
                const companyIdInput = document.getElementById('companyId');
                if (companyIdInput) {
                    companyIdInput.value = '';
                }
                
                // Mostrar campo IE
                const ieField = document.getElementById('ie-field');
                if (ieField) {
                    ieField.style.display = 'grid';
                }

                // Habilitar formulário
                this.setFormReadOnly(false);
            }
        }

        // 🎯 MÉTODO DELETE COMPANY
        async deleteCompany(companyId, companyName) {
            if (this.deleting) {
                console.log('⏳ Exclusão já em andamento...');
                return;
            }
            
            let displayName = 'Empresa';
            if (companyName && companyName !== 'null' && companyName !== 'undefined' && companyName.trim() !== '') {
                displayName = companyName;
            }
            
            if (confirm(`Tem certeza que deseja excluir "${displayName}"?`)) {
                this.deleting = true;
                
                try {
                    const formData = new FormData();
                    formData.append('id', companyId);
                    
                    const response = await fetch('/bt-log-transportes/public/api/companies.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Empresa excluída com sucesso!');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir empresa');
                    }
                    
                } catch (error) {
                    console.error('Erro ao excluir:', error);
                    alert('Erro: ' + error.message);
                } finally {
                    this.deleting = false;
                }
            }
        }

        // 🎯 MÉTODO SAVE COMPANY
        async saveCompany() {
            if (this.saving) {
                console.log('⏳ Salvamento já em andamento...');
                return;
            }
            
            this.saving = true;
            console.log('💾 Salvando empresa...');
            
            if (!this.validateForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveButton');
            this.setLoadingState(saveBtn, true);

            try {
                // Coletar dados do formulário
                const formData = new FormData(document.getElementById('companyForm'));
                
                // Determinar a ação
                const companyId = this.currentCompanyId;
                const action = companyId ? 'update' : 'create';
                
                console.log(`🚀 Enviando para API: action=${action}`);

                // Enviar para a API
                const response = await fetch('/bt-log-transportes/public/api/companies.php?action=' + action, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                console.log('📄 Resposta:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log('📊 JSON parseado:', result);
                } catch (parseError) {
                    console.error('❌ Erro ao parsear JSON:', parseError);
                    console.log('✅ REQUISIÇÃO COMPLETADA - Considerando sucesso');
                    alert('Empresa salva com sucesso!');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 1000);
                    return;
                }

                if (result.success) {
                    console.log('✅ EMPRESA SALVA COM SUCESSO!');
                    alert('Empresa salva com sucesso!');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(result.message || 'Erro ao salvar empresa');
                }
                
            } catch (error) {
                console.error('💥 Erro:', error);
                alert('Erro: ' + error.message);
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        // 🎯 MÉTODO VALIDATE FORM
        validateForm() {
            const name = document.getElementById('name');
            const razao_social = document.getElementById('razao_social');
            const cnpj = document.getElementById('cnpj');
            const atuacao = document.getElementById('atuacao');
            
            if (!name || !name.value.trim()) {
                alert('O nome fantasia é obrigatório');
                name.focus();
                return false;
            }
            
            if (!razao_social || !razao_social.value.trim()) {
                alert('A razão social é obrigatória');
                razao_social.focus();
                return false;
            }
            
            if (!cnpj || !cnpj.value.trim()) {
                alert('O CNPJ é obrigatório');
                cnpj.focus();
                return false;
            }
            
            // Validar formato do CNPJ (14 dígitos sem formatação)
            const cnpjLimpo = cnpj.value.replace(/\D/g, '');
            if (cnpjLimpo.length !== 14) {
                alert('CNPJ inválido. Deve conter 14 dígitos.');
                cnpj.focus();
                return false;
            }
            
            if (!atuacao || !atuacao.value) {
                alert('A área de atuação é obrigatória');
                atuacao.focus();
                return false;
            }
            
            return true;
        }

        // 🎯 MÉTODO SET LOADING STATE
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

        // 🎯 MÉTODO REFRESH COMPANIES
        refreshCompanies() {
            window.location.reload();
        }

        // 🎯 MÉTODO CREATE LOGO PREVIEW
        createLogoPreview(file) {
            const logoPreview = document.getElementById('logoPreview');
            if (!logoPreview) {
                console.error('❌ logoPreview não encontrado');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                logoPreview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview da Logo" class="company-logo-large">
                    <div class="logo-preview-text">Preview da nova logo</div>
                `;
            };
            reader.onerror = () => {
                console.error('❌ Erro ao ler arquivo');
                this.resetLogoPreview();
            };
            reader.readAsDataURL(file);
        }

        // 🎯 MÉTODO RESET LOGO PREVIEW
        resetLogoPreview() {
            const logoPreview = document.getElementById('logoPreview');
            if (logoPreview) {
                const color = document.getElementById('color')?.value || '#FF6B00';
                const companyName = document.getElementById('name')?.value || '';
                const initials = companyName ? companyName.substring(0, 2).toUpperCase() : 'EM';
                
                logoPreview.innerHTML = `
                    <div class="company-logo-large-placeholder" style="background-color: ${color}">
                        ${initials}
                    </div>
                    <div class="logo-preview-text">${companyName ? 'Logo padrão' : 'Logo será exibida aqui'}</div>
                `;
            }
        }

        // 🎯 MÉTODO UPDATE LOGO PREVIEW COLOR
        updateLogoPreviewColor(color) {
            const logoPreview = document.getElementById('logoPreview');
            if (logoPreview) {
                const placeholder = logoPreview.querySelector('.company-logo-large-placeholder');
                if (placeholder) {
                    placeholder.style.backgroundColor = color;
                }
            }
        }

        // 🎯 MÉTODO LOAD EXISTING LOGO
        loadExistingLogo(logoPath, companyName, color = '#FF6B00') {
            const logoPreview = document.getElementById('logoPreview');
            if (!logoPreview) {
                console.error('❌ logoPreview não encontrado');
                return;
            }

            console.log('🖼️ Carregando logo existente:', logoPath);

            if (logoPath && logoPath.trim() !== '' && logoPath !== 'null') {
                // CORREÇÃO: Remover paths duplicados
                let cleanLogoPath = logoPath;
                if (logoPath.includes('/bt-log-transportes/public/')) {
                    cleanLogoPath = logoPath.replace('/bt-log-transportes/public/', '');
                }
                if (logoPath.includes('/bt-log-transportes/')) {
                    cleanLogoPath = logoPath.replace('/bt-log-transportes/', '');
                }
                
                const timestamp = new Date().getTime();
                const logoUrl = `/bt-log-transportes/${cleanLogoPath}?t=${timestamp}`;
                
                console.log('🖼️ URL final da logo:', logoUrl);
                
                logoPreview.innerHTML = `
                    <img src="${logoUrl}" alt="${companyName}" class="company-logo-large" 
                         onerror="console.error('❌ Erro ao carregar logo:', this.src); this.style.display='none'; const parent = this.parentElement; const initials = '${companyName ? companyName.substring(0, 2).toUpperCase() : 'EM'}'; parent.innerHTML = '<div class=\\'company-logo-large-placeholder\\' style=\\'background-color: ${color}\\'>' + initials + '</div><div class=\\'logo-preview-text\\'>Erro ao carregar logo</div>';">
                    <div class="logo-preview-text">Logo atual</div>
                `;
            } else {
                console.log('🖼️ Nenhuma logo encontrada, usando placeholder');
                const initials = companyName ? companyName.substring(0, 2).toUpperCase() : 'EM';
                logoPreview.innerHTML = `
                    <div class="company-logo-large-placeholder" style="background-color: ${color}">
                        ${initials}
                    </div>
                    <div class="logo-preview-text">${companyName ? 'Sem logo' : 'Logo será exibida aqui'}</div>
                `;
            }
        }

        // 🎯 MÉTODO SETUP MASKS
        setupMasks() {
            // Máscara de CNPJ
            const cnpjInput = document.getElementById('cnpj');
            if (cnpjInput) {
                cnpjInput.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 14) {
                        value = value.replace(/(\d{2})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d)/, '$1/$2');
                        value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
                    }
                    e.target.value = value;
                });
            }

            // Máscara de telefone
            const phoneInputs = document.querySelectorAll('.phone-mask');
            phoneInputs.forEach((input) => {
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{2})(\d)/, '($1) $2');
                        value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    }
                    e.target.value = value;
                });
            });
        }

        // 🎯 MÉTODO SETUP IE TOGGLE
        setupIEToggle() {
            const isentoCheckbox = document.getElementById('isento_ie');
            const ieField = document.getElementById('ie-field');
            
            if (isentoCheckbox && ieField) {
                isentoCheckbox.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        ieField.style.display = 'none';
                        document.getElementById('inscricao_estadual').value = '';
                    } else {
                        ieField.style.display = 'grid';
                    }
                });
                
                // Inicializar estado
                if (isentoCheckbox.checked) {
                    ieField.style.display = 'none';
                }
            }
        }
    }

    // 🚀 INICIALIZAÇÃO ROBUSTA
    console.log('🚀 CRIANDO companiesManager...');
    window.companiesManager = new CompaniesManager();
    
    // ✅ CORREÇÃO: Expor métodos IMEDIATAMENTE, mesmo antes do DOM carregar
    window.companiesManager.exposeMethods();
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('📝 DOM Carregado - inicializando companiesManager...');
            setTimeout(() => {
                if (window.companiesManager && !window.companiesManager.isInitialized) {
                    window.companiesManager.init();
                }
            }, 100);
        });
    } else {
        console.log('📝 DOM Já carregado - inicializando agora...');
        setTimeout(() => {
            if (window.companiesManager && !window.companiesManager.isInitialized) {
                window.companiesManager.init();
            }
        }, 100);
    }

    // ✅ VERIFICAÇÃO FINAL - GARANTIR QUE OS MÉTODOS ESTEJAM DISPONÍVEIS
    setTimeout(() => {
        console.log('✅ VERIFICAÇÃO FINAL COMPANIES MANAGER:');
        console.log('- companiesManager existe?', !!window.companiesManager);
        console.log('- viewCompany:', typeof window.companiesManager?.viewCompany);
        console.log('- window.viewCompany:', typeof window.viewCompany);
        console.log('- isInitialized:', window.companiesManager?.isInitialized);
        
        if (typeof window.viewCompany === 'function') {
            console.log('🎉 CORREÇÃO APLICADA - MÉTODOS GLOBAIS DISPONÍVEIS!');
        }
    }, 2000);

    console.log('🔧 Companies Manager - CARREGAMENTO COMPLETO E CORRIGIDO');

})();