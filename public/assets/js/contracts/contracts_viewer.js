// bt-log-transportes/public/assets/js/contracts/contracts_manager.js
// GERENCIADOR DE MODAL CRUD DE CONTRATOS - VERSÃO CORRIGIDA

(function() {
    'use strict';
    
    console.log('🔧 Contracts Manager (CRUD) carregando...');
    
    // Configurações
    const config = {
        apiUrl: '/bt-log-transportes/public/api/contracts.php'
    };
    
    // Estado global
    let state = {
        currentContract: null,
        currentFile: null,
        isInitialized: false,
        isSaving: false,
        isDeleting: false
    };
    
    // ✅ FUNÇÃO AUXILIAR PARA RESETAR INPUT DE ARQUIVO
    function resetFileInput(fileInput) {
        if (!fileInput) return;
        
        // Criar novo input para resetar completamente
        const newInput = fileInput.cloneNode(true);
        fileInput.parentNode.replaceChild(newInput, fileInput);
        
        // Reconfigurar evento
        newInput.addEventListener('change', handleFileSelect);
        newInput.id = 'modal_contract_file';
        newInput.name = 'contract_file';
        newInput.accept = '.pdf';
        
        // Resetar preview
        const filePreview = document.getElementById('filePreview');
        if (filePreview) {
            filePreview.innerHTML = '';
            filePreview.style.display = 'none';
        }
        
        const fileUploadArea = document.getElementById('fileUploadArea');
        if (fileUploadArea) {
            fileUploadArea.classList.remove('dragover', 'has-file');
        }
        
        state.currentFile = null;
        
        return newInput;
    }
    
    // ✅ FORMATAR TAMANHO DE ARQUIVO
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // ✅ VALIDAÇÃO DE ARQUIVO PDF (10MB)
    function validatePDFFile(file) {
        const errors = [];
        
        // Verificar se é PDF
        const allowedTypes = ['application/pdf', 'application/x-pdf'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && fileExtension !== 'pdf') {
            errors.push('Apenas arquivos PDF são permitidos');
        }
        
        // Verificar tamanho (10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            errors.push(`Arquivo muito grande. Tamanho máximo: 10MB. Tamanho atual: ${formatFileSize(file.size)}`);
        }
        
        return errors;
    }
    
    // ✅ HANDLE FILE SELECT - VERSÃO CORRIGIDA
    function handleFileSelect(event) {
        const fileInput = event.target;
        const file = fileInput.files[0];
        
        if (!file) {
            console.log('⚠️ Nenhum arquivo selecionado');
            return;
        }
        
        console.log('📁 Arquivo selecionado:', file.name, 'Tamanho:', formatFileSize(file.size));
        
        // Validar arquivo
        const errors = validatePDFFile(file);
        if (errors.length > 0) {
            showNotification(errors.join(', '), 'error');
            resetFileInput(fileInput);
            return;
        }
        
        // Verificar se é realmente um PDF pelos primeiros bytes
        const reader = new FileReader();
        reader.onload = function(e) {
            const arr = new Uint8Array(e.target.result).subarray(0, 4);
            let header = '';
            for (let i = 0; i < arr.length; i++) {
                header += arr[i].toString(16);
            }
            
            // Verificar assinatura do PDF (%PDF)
            if (header !== '25504446') {
                showNotification('Arquivo não é um PDF válido', 'error');
                resetFileInput(fileInput);
                return;
            }
            
            // Arquivo válido
            state.currentFile = file;
            showFilePreview(file);
            showNotification('Arquivo PDF válido selecionado', 'success');
        };
        
        reader.onerror = function() {
            showNotification('Erro ao ler arquivo', 'error');
            resetFileInput(fileInput);
        };
        
        // Ler apenas os primeiros 4 bytes para verificar assinatura
        reader.readAsArrayBuffer(file.slice(0, 4));
    }
    
    // ✅ Inicialização
    function init() {
        if (state.isInitialized) {
            console.log('📄 Contracts Manager já inicializado');
            return;
        }
        
        console.log('🚀 Contracts Manager inicializando...');
        
        try {
            setupEventListeners();
            setupFormValidation();
            setupDragAndDrop();
            setupModalListeners();
            
            state.isInitialized = true;
            console.log('✅ Contracts Manager (CRUD) inicializado com sucesso');
            
        } catch (error) {
            console.error('❌ Erro na inicialização:', error);
            showNotification('Erro ao inicializar gerenciador de contratos', 'error');
        }
    }
    
    // ✅ Configurar Event Listeners
    function setupEventListeners() {
        // Botão de novo contrato
        const newContractBtn = document.getElementById('newContractBtn');
        if (newContractBtn) {
            newContractBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🆕 Botão Novo Contrato clicado');
                openContractModal();
            });
        }
        
        // Botão para primeiro contrato
        const firstContractBtn = document.getElementById('firstContractBtn');
        if (firstContractBtn) {
            firstContractBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openContractModal();
            });
        }
    }
    
    // ✅ Configurar Listeners dos Modais
    function setupModalListeners() {
        // Botões de fechar modais
        const closeButtons = [
            { id: 'closeContractModal', handler: closeContractModal },
            { id: 'cancelContractModal', handler: closeContractModal },
            { id: 'closeRenewModal', handler: closeRenewModal },
            { id: 'cancelRenewModal', handler: closeRenewModal }
        ];
        
        closeButtons.forEach(btn => {
            const element = document.getElementById(btn.id);
            if (element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    btn.handler();
                });
            }
        });
        
        // Fechar modal ao clicar fora
        const contractModal = document.getElementById('contractModal');
        if (contractModal) {
            contractModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeContractModal();
                }
            });
        }
        
        const renewModal = document.getElementById('renewModal');
        if (renewModal) {
            renewModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeRenewModal();
                }
            });
        }
        
        // Formulário de contrato
        const contractForm = document.getElementById('contractForm');
        if (contractForm) {
            console.log('📝 Configurando listener para contractForm');
            contractForm.addEventListener('submit', handleSaveContract);
        }
        
        // Formulário de renovação
        const renewForm = document.getElementById('renewForm');
        if (renewForm) {
            renewForm.addEventListener('submit', handleRenewContract);
        }
        
        // Preencher selects de empresa
        const companySelect = document.getElementById('modal_company_id');
        if (companySelect) {
            companySelect.addEventListener('change', loadClientsAndSuppliers);
        }
        
        // Configurar validação de número de contrato
        const contractNumberInput = document.getElementById('modal_contract_number');
        if (contractNumberInput) {
            contractNumberInput.addEventListener('blur', validateContractNumber);
        }
        
        // Tipo de contrato
        const contractTypeRadios = document.querySelectorAll('input[name="contract_type"]');
        contractTypeRadios.forEach(radio => {
            radio.addEventListener('change', validateContractType);
        });
        
        // Datas
        const startDate = document.getElementById('modal_start_date');
        const endDate = document.getElementById('modal_end_date');
        if (startDate && endDate) {
            startDate.addEventListener('change', validateDates);
            endDate.addEventListener('change', validateDates);
        }
    }
    
    // ✅ Setup de validação de formulário
    function setupFormValidation() {
        // Validação é configurada em setupModalListeners
    }
    
    // ✅ Setup de drag and drop para arquivos
    function setupDragAndDrop() {
        const fileUploadArea = document.getElementById('fileUploadArea');
        let fileInput = document.getElementById('modal_contract_file');
        
        if (!fileUploadArea || !fileInput) return;
        
        // Drag over
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.classList.add('dragover');
        });
        
        // Drag leave
        fileUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.classList.remove('dragover');
        });
        
        // Drop
        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect({ target: fileInput });
            }
        });
        
        // Clique na área
        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Mudança no input
        fileInput.addEventListener('change', handleFileSelect);
    }
    
    // ✅ SHOW FILE PREVIEW
    function showFilePreview(file) {
        const filePreview = document.getElementById('filePreview');
        const fileUploadArea = document.getElementById('fileUploadArea');
        
        if (!filePreview || !fileUploadArea) return;
        
        filePreview.innerHTML = `
            <div class="file-preview-item">
                <i class="fas fa-file-pdf"></i>
                <div class="file-info">
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${formatFileSize(file.size)} • PDF</span>
                </div>
                <button type="button" class="btn-remove-file">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        filePreview.style.display = 'block';
        fileUploadArea.classList.add('has-file');
        
        // Adicionar evento ao botão de remover
        const removeBtn = filePreview.querySelector('.btn-remove-file');
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                removeFile();
            });
        }
    }
    
    // ✅ REMOVER ARQUIVO
    function removeFile() {
        const fileInput = document.getElementById('modal_contract_file');
        const filePreview = document.getElementById('filePreview');
        const fileUploadArea = document.getElementById('fileUploadArea');
        
        if (fileInput) resetFileInput(fileInput);
        if (filePreview) {
            filePreview.innerHTML = '';
            filePreview.style.display = 'none';
        }
        if (fileUploadArea) fileUploadArea.classList.remove('has-file');
        
        state.currentFile = null;
        showNotification('Arquivo removido', 'info');
    }
    
    // ✅ RESTANTE DO CÓDIGO (openContractModal, closeContractModal, etc.)
    // ... (mantenha o resto do código igual ao anterior, mas use as funções corrigidas acima)
    
    // ✅ DELETE CONTRACT - VERSÃO CORRIGIDA (SEM REPETIÇÃO)
    function deleteContract(contractId) {
        // Verificar se já está processando
        if (state.isDeleting) {
            console.log('⚠️ Delete já em andamento');
            return;
        }
        
        if (!contractId) {
            showNotification('ID do contrato não informado', 'error');
            return;
        }
        
        if (!confirm('Tem certeza que deseja cancelar este contrato?\n\nEsta ação marcará o contrato como cancelado, mas manterá os dados no sistema.')) {
            return;
        }
        
        console.log('🗑️  Cancelando contrato ID:', contractId);
        
        // Marcar como processando
        state.isDeleting = true;
        
        const formData = new FormData();
        formData.append('id', contractId);
        
        // Adicionar token CSRF para segurança
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        fetch(`${config.apiUrl}?action=delete`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📨 Resposta do delete:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('❌ Response text:', text);
                    throw new Error(`Erro HTTP ${response.status}: ${text.substring(0, 100)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados da resposta:', data);
            
            if (data.success) {
                showNotification(data.message, 'success');
                
                // Recarregar lista
                setTimeout(() => {
                    if (typeof window.contractsListManager !== 'undefined' && 
                        typeof window.contractsListManager.filterContracts === 'function') {
                        window.contractsListManager.filterContracts();
                    } else {
                        // Recarregar página após 1 segundo
                        setTimeout(() => window.location.reload(), 1000);
                    }
                }, 500);
            } else {
                throw new Error(data.message || 'Erro ao cancelar contrato');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao deletar:', error);
            
            let errorMessage = error.message || 'Erro ao cancelar contrato';
            
            if (errorMessage.includes('500')) {
                errorMessage = 'Erro interno do servidor ao cancelar contrato';
            } else if (errorMessage.includes('404')) {
                errorMessage = 'Contrato não encontrado';
            } else if (errorMessage.includes('403')) {
                errorMessage = 'Você não tem permissão para cancelar este contrato';
            }
            
            showNotification(errorMessage, 'error');
        })
        .finally(() => {
            // Liberar para próxima operação
            setTimeout(() => {
                state.isDeleting = false;
            }, 1000);
        });
    }
    
    // ✅ FUNÇÃO PARA INICIALIZAR A PÁGINA
    function initializePage() {
        console.log('🌐 Inicializando página de contratos (CRUD)...');
        
        // Verificar se os elementos necessários existem
        const hasContractModal = document.getElementById('contractModal') !== null;
        
        console.log('Elementos encontrados:', {
            contractModal: hasContractModal
        });
        
        // Inicializar manager
        init();
        
        console.log('✅ Página de contratos (CRUD) inicializada');
    }
    
    // API pública - Exportar para o escopo global
    window.contractsManager = {
        init,
        initializePage,
        openContractModal,
        closeContractModal,
        closeRenewModal,
        closeAllModals,
        renewContract,
        editContract,
        deleteContract,
        removeFile,
        toggleContractType,
        showNotification,
        // ✅ EXPORTAR FUNÇÕES AUXILIARES
        validatePDFFile,
        formatFileSize,
        resetFileInput
    };
    
    console.log('🔧 Contracts Manager (CRUD) exportado para window.contractsManager');
    
    // Inicializar automaticamente quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM carregado, inicializando Contracts Manager (CRUD)...');
            initializePage();
        });
    } else {
        console.log('📄 DOM já carregado, inicializando Contracts Manager (CRUD) agora...');
        setTimeout(initializePage, 100);
    }
    
})();