// bt-log-transportes/public/assets/js/contracts/contracts_manager.js
// GERENCIADOR DE MODAL CRUD DE CONTRATOS

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
        isInitialized: false
    };
    
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
			setupFileExplorerCancelHandler(); // ✅ NOVO
            
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
                console.log('🆕 Botão Novo Contrato clicado');
                openContractModal();
            });
        }
        
        // Botão para primeiro contrato
        const firstContractBtn = document.getElementById('firstContractBtn');
        if (firstContractBtn) {
            firstContractBtn.addEventListener('click', function(e) {
                e.preventDefault();
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
					e.stopPropagation(); // ✅ IMPEDIR PROPAGAÇÃO
					
					// ✅ CONFIRMAÇÃO SE HÁ DADOS NO FORMULÁRIO
					if (btn.id === 'closeContractModal' || btn.id === 'cancelContractModal') {
						const form = document.getElementById('contractForm');
						const hasData = formHasData(form);
						
						if (hasData) {
							if (!confirm('Tem certeza que deseja cancelar? Os dados não salvos serão perdidos.')) {
								return;
							}
						}
					}
					
					btn.handler();
				});
			}
		});
		
		// ✅ PREVINIR FECHAMENTO AO CLICAR FORA DO MODAL
		const contractModal = document.getElementById('contractModal');
		if (contractModal) {
			contractModal.addEventListener('click', function(e) {
				if (e.target === contractModal) {
					e.preventDefault();
					e.stopPropagation();
					
					const form = document.getElementById('contractForm');
					const hasData = formHasData(form);
					
					if (hasData) {
						if (!confirm('Tem certeza que deseja fechar? Os dados não salvos serão perdidos.')) {
							return;
						}
					}
					
					closeContractModal();
				}
			});
		}
	}

	// ✅ VERIFICAR SE FORMULÁRIO TEM DADOS
	function formHasData(form) {
		if (!form) return false;
		
		const inputs = form.querySelectorAll('input, select, textarea');
		for (let input of inputs) {
			if (input.type === 'text' && input.value.trim() !== '') return true;
			if (input.type === 'number' && input.value !== '') return true;
			if (input.type === 'date' && input.value !== '') return true;
			if (input.type === 'select-one' && input.value !== '') return true;
			if (input.type === 'textarea' && input.value.trim() !== '') return true;
			if (input.type === 'file' && input.files.length > 0) return true;
		}
		
		return false;
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
		
		// ✅ RESETAR COMPLETAMENTE O INPUT AO INICIAR
		function resetFileInputElement() {
			const newInput = document.createElement('input');
			newInput.type = 'file';
			newInput.id = 'modal_contract_file';
			newInput.name = 'contract_file';
			newInput.accept = '.pdf';
			newInput.style.display = 'none';
			newInput.classList.add('file-upload-input');
			
			// Substituir o input antigo
			if (fileInput && fileInput.parentNode) {
				fileInput.parentNode.replaceChild(newInput, fileInput);
			}
			
			fileInput = newInput;
			
			// Adicionar evento
			fileInput.addEventListener('change', handleFileSelect);
			
			return fileInput;
		}
		
		// ✅ INICIALIZAR INPUT CORRETAMENTE
		fileInput = resetFileInputElement();
		
		// ✅ DRAG OVER
		fileUploadArea.addEventListener('dragover', (e) => {
			e.preventDefault();
			e.stopPropagation();
			fileUploadArea.classList.add('dragover');
		});
		
		// ✅ DRAG LEAVE
		fileUploadArea.addEventListener('dragleave', (e) => {
			e.preventDefault();
			e.stopPropagation();
			fileUploadArea.classList.remove('dragover');
		});
		
		// ✅ DROP
		fileUploadArea.addEventListener('drop', (e) => {
			e.preventDefault();
			e.stopPropagation();
			fileUploadArea.classList.remove('dragover');
			
			if (e.dataTransfer.files.length) {
				console.log('📁 Arquivo arrastado:', e.dataTransfer.files[0].name);
				
				// Atribuir arquivo ao input
				const dataTransfer = new DataTransfer();
				dataTransfer.items.add(e.dataTransfer.files[0]);
				fileInput.files = dataTransfer.files;
				
				// Disparar evento change manualmente
				const changeEvent = new Event('change', { bubbles: true });
				fileInput.dispatchEvent(changeEvent);
			}
		});
		
		// ✅ CLIQUE NA ÁREA
		fileUploadArea.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			
			console.log('📁 Clicou na área de upload');
			
			// Garantir que o input existe
			if (!fileInput || !fileInput.parentNode) {
				fileInput = resetFileInputElement();
			}
			
			// Clonar o evento para evitar problemas
			const clickEvent = new MouseEvent('click', {
				view: window,
				bubbles: true,
				cancelable: true
			});
			
			fileInput.dispatchEvent(clickEvent);
		});
		
		// ✅ MUDANÇA NO INPUT
		fileInput.addEventListener('change', handleFileSelect);
		
		console.log('✅ Drag and Drop configurado');
	}
    
    // ✅ ABRIR MODAL DE CONTRATO
    function openContractModal(contractId = null) {
        console.log('📋 Abrindo modal de contrato, ID:', contractId);
        
        const modal = document.getElementById('contractModal');
        if (!modal) {
            console.error('❌ Modal de contrato não encontrado');
            showNotification('Erro: Modal de contrato não encontrado', 'error');
            return;
        }
        
        // Resetar formulário
        resetContractForm();
        
        // Definir título do modal
        const modalLabel = document.getElementById('contractModalLabel');
        if (modalLabel) {
            modalLabel.textContent = contractId ? 'Editar Contrato' : 'Novo Contrato';
        }
        
        // Se for edição, carregar dados
        if (contractId) {
            console.log('🔄 Carregando dados do contrato:', contractId);
            loadContractData(contractId);
        } else {
            // Mostrar seção de cliente por padrão
            toggleContractType('client');
        }
        
        // Mostrar modal
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Focar no primeiro campo
        setTimeout(() => {
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) firstInput.focus();
        }, 300);
        
        console.log('✅ Modal de contrato aberto');
    }
    
    // ✅ FECHAR MODAL DE CONTRATO
    function closeContractModal() {
        const modal = document.getElementById('contractModal');
        if (!modal) return;
        
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Resetar formulário após animação
        setTimeout(() => {
            resetContractForm();
        }, 300);
    }
    
    // ✅ ABRIR MODAL DE RENOVAÇÃO
    function renewContract(contractId) {
        console.log('🔄 Abrindo renovação para contrato:', contractId);
        
        const modal = document.getElementById('renewModal');
        if (!modal) {
            console.error('❌ Modal de renovação não encontrado');
            return;
        }
        
        // Carregar dados do contrato
        fetchContract(contractId)
            .then(contract => {
                if (contract) {
                    state.currentContract = contract;
                    
                    // Preencher dados no formulário
                    const currentEndDate = document.getElementById('current_end_date');
                    const contractIdInput = modal.querySelector('input[name="contract_id"]');
                    
                    if (currentEndDate) {
                        currentEndDate.value = formatDate(contract.end_date);
                    }
                    
                    if (contractIdInput) {
                        contractIdInput.value = contract.id;
                    }
                    
                    // Definir data mínima para renovação
                    const newEndDateInput = document.getElementById('new_end_date');
                    if (newEndDateInput) {
                        const minDate = new Date(contract.end_date);
                        minDate.setDate(minDate.getDate() + 1);
                        newEndDateInput.min = minDate.toISOString().split('T')[0];
                        newEndDateInput.value = '';
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar contrato para renovação:', error);
                showNotification('Erro ao carregar contrato', 'error');
            });
        
        // Mostrar modal
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    // ✅ FECHAR MODAL DE RENOVAÇÃO
    function closeRenewModal() {
        const modal = document.getElementById('renewModal');
        if (!modal) return;
        
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Resetar estado
        state.currentContract = null;
    }
    
    // ✅ FECHAR TODOS OS MODAIS
    function closeAllModals() {
        closeContractModal();
        closeRenewModal();
    }
    
    // ✅ RESETAR FORMULÁRIO DE CONTRATO
    function resetContractForm() {
        const form = document.getElementById('contractForm');
        if (!form) return;
        
        form.reset();
        state.currentContract = null;
        state.currentFile = null;
        
        // Resetar file preview
        const filePreview = document.getElementById('filePreview');
        if (filePreview) {
            filePreview.innerHTML = '';
            filePreview.style.display = 'none';
        }
        
        // Resetar file upload area
        const fileUploadArea = document.getElementById('fileUploadArea');
        if (fileUploadArea) {
            fileUploadArea.classList.remove('has-file');
        }
        
        // Resetar selects
        const clientIdSelect = document.getElementById('modal_client_id');
        const supplierIdSelect = document.getElementById('modal_supplier_id');
        
        if (clientIdSelect) {
            clientIdSelect.innerHTML = '<option value="">Selecione o Cliente</option>';
        }
        
        if (supplierIdSelect) {
            supplierIdSelect.innerHTML = '<option value="">Selecione o Fornecedor</option>';
        }
        
        // Mostrar seção de cliente por padrão
        toggleContractType('client');
        
        // Remover mensagens de erro
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());
        
        console.log('🔄 Formulário de contrato resetado');
    }
    
    // ✅ CARREGAR DADOS DO CONTRATO
    function loadContractData(contractId) {
		fetchContract(contractId)
			.then(contract => {
				if (!contract) {
					showNotification('Contrato não encontrado', 'error');
					return;
				}
				
				state.currentContract = contract;
				
				const form = document.getElementById('contractForm');
				if (!form) return;
				
				// ID do contrato
				const contractIdInput = form.querySelector('input[name="contract_id"]');
				if (contractIdInput) {
					contractIdInput.value = contract.id;
				}
				
				// Empresa
				const companySelect = document.getElementById('modal_company_id');
				if (companySelect) {
					companySelect.value = contract.company_id;
					setTimeout(() => {
						loadClientsAndSuppliers();
						
						setTimeout(() => {
							if (contract.contract_type === 'client' && contract.client_id) {
								const clientSelect = document.getElementById('modal_client_id');
								if (clientSelect) {
									clientSelect.value = contract.client_id;
								}
							} else if (contract.contract_type === 'supplier' && contract.supplier_id) {
								const supplierSelect = document.getElementById('modal_supplier_id');
								if (supplierSelect) {
									supplierSelect.value = contract.supplier_id;
								}
							}
						}, 500);
					}, 100);
				}
				
				// Tipo de contrato
				const contractType = contract.contract_type;
				const contractTypeInputs = form.querySelectorAll(`input[name="contract_type"][value="${contractType}"]`);
				if (contractTypeInputs.length > 0) {
					contractTypeInputs[0].checked = true;
					toggleContractType(contractType);
				}
				
				// Outros campos
				const fields = [
					'contract_number', 'title', 'description',
					'start_date', 'end_date', 'currency',
					'payment_terms', 'renewal_terms', 'status', 'notes'
				];
				
				fields.forEach(field => {
					const input = form.querySelector(`[name="${field}"]`);
					if (input && contract[field]) {
						input.value = contract[field];
					}
				});
				
				// ✅ VALOR FORMATADO CORRETAMENTE
				const valueInput = document.getElementById('modal_value');
				if (valueInput && contract.value) {
					// Converter número para formato brasileiro
					const formattedValue = parseFloat(contract.value).toLocaleString('pt-BR', {
						minimumFractionDigits: 2,
						maximumFractionDigits: 2
					});
					valueInput.value = formattedValue;
					console.log('💰 Valor carregado formatado:', contract.value, '->', formattedValue);
				}
				
				// Formatar data
				const startDateInput = document.getElementById('modal_start_date');
				const endDateInput = document.getElementById('modal_end_date');
				
				if (startDateInput && contract.start_date) {
					startDateInput.value = formatDateForInput(contract.start_date);
				}
				
				if (endDateInput && contract.end_date) {
					endDateInput.value = formatDateForInput(contract.end_date);
				}
				
				// Arquivo
				if (contract.contract_file) {
					showFilePreview(contract.contract_file);
				}
				
				console.log('✅ Dados do contrato carregados');
			})
			.catch(error => {
				console.error('❌ Erro ao carregar contrato:', error);
				showNotification('Erro ao carregar contrato', 'error');
			});
	}
    
    // ✅ FETCH CONTRACT
    function fetchContract(contractId) {
        return new Promise((resolve, reject) => {
            fetch(`${config.apiUrl}?action=get&id=${contractId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        resolve(data.data);
                    } else {
                        reject(new Error(data.message || 'Erro ao buscar contrato'));
                    }
                })
                .catch(error => {
                    console.error('❌ Erro fetch:', error);
                    reject(error);
                });
        });
    }
    
    // ✅ TOGGLE CONTRACT TYPE
    function toggleContractType(type) {
        const clientSection = document.getElementById('clientSection');
        const supplierSection = document.getElementById('supplierSection');
        
        console.log('🔄 Alternando tipo de contrato para:', type);
        
        if (type === 'client') {
            if (clientSection) clientSection.style.display = 'block';
            if (supplierSection) supplierSection.style.display = 'none';
        } else {
            if (clientSection) clientSection.style.display = 'none';
            if (supplierSection) supplierSection.style.display = 'block';
        }
    }
    
    // ✅ LOAD CLIENTS AND SUPPLIERS
    function loadClientsAndSuppliers() {
        const companySelect = document.getElementById('modal_company_id');
        if (!companySelect || !companySelect.value) {
            console.log('⚠️ Empresa não selecionada');
            return;
        }
        
        const companyId = companySelect.value;
        console.log('📋 Carregando clientes e fornecedores para empresa:', companyId);
        
        // Mostrar loading
        showLoadingForSelects();
        
        // Carregar clientes
        fetch(`${config.apiUrl}?action=get_clients&company_id=${companyId}`)
            .then(response => {
                if (!response.ok) throw new Error('Erro na resposta');
                return response.json();
            })
            .then(data => {
                console.log('📊 Dados de clientes:', data);
                if (data.success && data.data && Array.isArray(data.data)) {
                    const clientSelect = document.getElementById('modal_client_id');
                    if (clientSelect) {
                        // Salvar opção atual se for edição
                        const currentValue = state.currentContract ? state.currentContract.client_id : null;
                        
                        // Limpar e adicionar opção padrão
                        clientSelect.innerHTML = '<option value="">Selecione o Cliente</option>';
                        
                        // Adicionar clientes
                        data.data.forEach(client => {
                            const option = document.createElement('option');
                            option.value = client.id;
                            option.textContent = client.fantasy_name || client.name;
                            if (client.cpf_cnpj) {
                                option.textContent += ` (${client.cpf_cnpj})`;
                            }
                            clientSelect.appendChild(option);
                        });
                        
                        // Restaurar valor se for edição
                        if (currentValue) {
                            setTimeout(() => {
                                clientSelect.value = currentValue;
                            }, 100);
                        }
                        
                        console.log(`✅ ${data.data.length} cliente(s) carregado(s)`);
                    }
                } else {
                    console.warn('⚠️ Nenhum cliente encontrado ou dados inválidos:', data);
                    const clientSelect = document.getElementById('modal_client_id');
                    if (clientSelect) {
                        clientSelect.innerHTML = '<option value="">Nenhum cliente disponível</option>';
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar clientes:', error);
                const clientSelect = document.getElementById('modal_client_id');
                if (clientSelect) {
                    clientSelect.innerHTML = '<option value="">Erro ao carregar clientes</option>';
                }
            })
            .finally(() => {
                hideLoadingForSelects('client');
            });
        
        // Carregar fornecedores
        fetch(`${config.apiUrl}?action=get_suppliers&company_id=${companyId}`)
            .then(response => {
                if (!response.ok) throw new Error('Erro na resposta');
                return response.json();
            })
            .then(data => {
                console.log('📊 Dados de fornecedores:', data);
                if (data.success && data.data && Array.isArray(data.data)) {
                    const supplierSelect = document.getElementById('modal_supplier_id');
                    if (supplierSelect) {
                        // Salvar opção atual se for edição
                        const currentValue = state.currentContract ? state.currentContract.supplier_id : null;
                        
                        // Limpar e adicionar opção padrão
                        supplierSelect.innerHTML = '<option value="">Selecione o Fornecedor</option>';
                        
                        // Adicionar fornecedores
                        data.data.forEach(supplier => {
                            const option = document.createElement('option');
                            option.value = supplier.id;
                            option.textContent = supplier.fantasy_name || supplier.name;
                            if (supplier.cpf_cnpj) {
                                option.textContent += ` (${supplier.cpf_cnpj})`;
                            }
                            supplierSelect.appendChild(option);
                        });
                        
                        // Restaurar valor se for edição
                        if (currentValue) {
                            setTimeout(() => {
                                supplierSelect.value = currentValue;
                            }, 100);
                        }
                        
                        console.log(`✅ ${data.data.length} fornecedor(es) carregado(s)`);
                    }
                } else {
                    console.warn('⚠️ Nenhum fornecedor encontrado ou dados inválidos:', data);
                    const supplierSelect = document.getElementById('modal_supplier_id');
                    if (supplierSelect) {
                        supplierSelect.innerHTML = '<option value="">Nenhum fornecedor disponível</option>';
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar fornecedores:', error);
                const supplierSelect = document.getElementById('modal_supplier_id');
                if (supplierSelect) {
                    supplierSelect.innerHTML = '<option value="">Erro ao carregar fornecedores</option>';
                }
            })
            .finally(() => {
                hideLoadingForSelects('supplier');
            });
    }
    
    // ✅ MOSTRAR LOADING NOS SELECTS
    function showLoadingForSelects() {
        const clientSelect = document.getElementById('modal_client_id');
        const supplierSelect = document.getElementById('modal_supplier_id');
        
        if (clientSelect) {
            clientSelect.innerHTML = '<option value="">Carregando clientes...</option>';
            clientSelect.disabled = true;
        }
        
        if (supplierSelect) {
            supplierSelect.innerHTML = '<option value="">Carregando fornecedores...</option>';
            supplierSelect.disabled = true;
        }
    }
    
    // ✅ ESCONDER LOADING NOS SELECTS
    function hideLoadingForSelects(type = 'all') {
        if (type === 'client' || type === 'all') {
            const clientSelect = document.getElementById('modal_client_id');
            if (clientSelect) {
                clientSelect.disabled = false;
            }
        }
        
        if (type === 'supplier' || type === 'all') {
            const supplierSelect = document.getElementById('modal_supplier_id');
            if (supplierSelect) {
                supplierSelect.disabled = false;
            }
        }
    }
    
    // ✅ HANDLE FILE SELECT
   function handleFileSelect(event) {
		console.log('📁 handleFileSelect acionado');
		
		const fileInput = event.target;
		const file = fileInput.files[0];
		
		if (!file) {
			console.log('⚠️ Nenhum arquivo selecionado');
			return;
		}
		
		console.log('📁 Arquivo selecionado:', {
			name: file.name,
			size: file.size,
			type: file.type,
			lastModified: file.lastModified
		});
		
		// ✅ VALIDAÇÃO IMEDIATA
		const errors = validatePDFFile(file);
		if (errors.length > 0) {
			console.error('❌ Erros na validação:', errors);
			showNotification(errors.join(', '), 'error');
			
			// Resetar o input completamente
			setTimeout(() => {
				resetFileInput(fileInput);
			}, 100);
			
			return;
		}
		
		// ✅ VERIFICAÇÃO DE PDF VÁLIDO
		const reader = new FileReader();
		reader.onload = function(e) {
			try {
				const arr = new Uint8Array(e.target.result).subarray(0, 4);
				let header = '';
				for (let i = 0; i < arr.length; i++) {
					header += arr[i].toString(16);
				}
				
				console.log('🔍 Assinatura do arquivo:', header);
				
				// Verificar assinatura do PDF (%PDF)
				if (header !== '25504446') {
					showNotification('Arquivo não é um PDF válido', 'error');
					resetFileInput(fileInput);
					return;
				}
				
				// ✅ ARQUIVO VÁLIDO
				state.currentFile = file;
				showFilePreview(file);
				showNotification(`PDF válido: ${file.name} (${formatFileSize(file.size)})`, 'success');
				
			} catch (error) {
				console.error('❌ Erro na verificação do PDF:', error);
				showNotification('Erro ao verificar arquivo PDF', 'error');
				resetFileInput(fileInput);
			}
		};
		
		reader.onerror = function() {
			console.error('❌ Erro na leitura do arquivo');
			showNotification('Erro ao ler arquivo', 'error');
			resetFileInput(fileInput);
		};
		
		// Ler apenas os primeiros 4 bytes para verificar assinatura
		reader.readAsArrayBuffer(file.slice(0, 4));
	}
    
    console.log('📁 Arquivo selecionado:', file.name, 'Tipo:', file.type, 'Tamanho:', file.size);
    
    // ✅ VALIDAR TIPO - CORREÇÃO
    const allowedTypes = ['application/pdf', 'application/x-pdf'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(file.type) && fileExtension !== 'pdf') {
        showNotification('Apenas arquivos PDF são permitidos', 'error');
        resetFileInput(fileInput);
        removeFile();
        return;
    }
    
    // ✅ VALIDAR TAMANHO (10MB)
    const maxSize = 10 * 1024 * 1024; // 10MB
		if (file.size > maxSize) {
			showNotification(`Arquivo muito grande. Tamanho máximo: 10MB. Tamanho atual: ${formatFileSize(file.size)}`, 'error');
			resetFileInput(fileInput);
			removeFile();
			return;
		}
		
		// ✅ VERIFICAR SE É REALMENTE UM PDF
		const reader = new FileReader();
		reader.onload = function(e) {
			const arr = new Uint8Array(e.target.result).subarray(0, 4);
			let header = '';
			for (let i = 0; i < arr.length; i++) {
				header += arr[i].toString(16);
			}
			
			// Verificar assinatura do PDF
			if (header !== '25504446') { // %PDF em hex
				showNotification('Arquivo não é um PDF válido', 'error');
				resetFileInput(fileInput);
				removeFile();
				return;
			}
			
			state.currentFile = file;
			showFilePreview(file);
		};
		
		reader.onerror = function() {
			showNotification('Erro ao ler arquivo', 'error');
			resetFileInput(fileInput);
			removeFile();
		};
		
		reader.readAsArrayBuffer(file.slice(0, 4)); // Ler apenas os primeiros bytes
	}
    
	// ✅ FUNÇÃO PARA RESETAR INPUT DE ARQUIVO
	function resetFileInput(fileInput) {
		if (!fileInput) return;
		
		console.log('🔄 Resetando input de arquivo');
		
		// Criar novo input
		const newInput = document.createElement('input');
		newInput.type = 'file';
		newInput.id = 'modal_contract_file';
		newInput.name = 'contract_file';
		newInput.accept = '.pdf';
		newInput.style.display = 'none';
		newInput.classList.add('file-upload-input');
		
		// Substituir o input antigo
		if (fileInput.parentNode) {
			fileInput.parentNode.replaceChild(newInput, fileInput);
		}
		
		// Resetar preview visual
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
		
		// Adicionar eventos ao novo input
		newInput.addEventListener('change', handleFileSelect);
		
		// Configurar drag and drop novamente
		setTimeout(() => {
			const area = document.getElementById('fileUploadArea');
			if (area) {
				area.addEventListener('click', function() {
					newInput.click();
				});
			}
		}, 100);
		
		return newInput;
	}
	
	// ✅ LIDAR COM CANCELAMENTO DO EXPLORADOR
	function setupFileExplorerCancelHandler() {
		// Monitorar quando o modal é fechado
		const contractModal = document.getElementById('contractModal');
		if (contractModal) {
			const observer = new MutationObserver((mutations) => {
				mutations.forEach((mutation) => {
					if (mutation.attributeName === 'style') {
						const isVisible = contractModal.style.display === 'block';
						if (!isVisible) {
							// Modal foi fechado, resetar o input de arquivo
							console.log('🔒 Modal fechado, resetando upload');
							const fileInput = document.getElementById('modal_contract_file');
							if (fileInput) {
								resetFileInput(fileInput);
							}
						}
					}
				});
			});
			
			observer.observe(contractModal, { attributes: true });
		}
		
		// Também monitorar eventos de teclado (ESC)
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') {
				const fileInput = document.getElementById('modal_contract_file');
				if (fileInput && document.activeElement === fileInput) {
					console.log('⎋ ESC pressionado no explorador de arquivos');
					// Não fazer nada, deixar o comportamento padrão
				}
			}
		});
	}

	
	// ✅ FORMATAR TAMANHO DE ARQUIVO
	function formatFileSize(bytes) {
		if (bytes === 0) return '0 Bytes';
		const k = 1024;
		const sizes = ['Bytes', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
	}
	
    // ✅ SHOW FILE PREVIEW
    function showFilePreview(file) {
        const filePreview = document.getElementById('filePreview');
        const fileUploadArea = document.getElementById('fileUploadArea');
        
        if (!filePreview || !fileUploadArea) return;
        
        // Formatar tamanho do arquivo
        const formatFileSize = (bytes) => {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };
        
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
            removeBtn.addEventListener('click', removeFile);
        }
        
        showNotification('Arquivo selecionado com sucesso', 'success');
    }
    
    // ✅ REMOVER ARQUIVO
    function removeFile() {
        const fileInput = document.getElementById('modal_contract_file');
        const filePreview = document.getElementById('filePreview');
        const fileUploadArea = document.getElementById('fileUploadArea');
        
        if (fileInput) fileInput.value = '';
        if (filePreview) {
            filePreview.innerHTML = '';
            filePreview.style.display = 'none';
        }
        if (fileUploadArea) fileUploadArea.classList.remove('has-file');
        
        state.currentFile = null;
        showNotification('Arquivo removido', 'info');
    }
    
    // ✅ HANDLE SAVE CONTRACT
    function handleSaveContract(event) {
		event.preventDefault();
		
		const form = event.target;
		const formData = new FormData(form);
		const saveBtn = form.querySelector('#saveContractBtn');
		
		console.log('💾 Salvando contrato...');
		console.log('📋 FormData:', Array.from(formData.entries()));
		
		// ✅ VALIDAÇÃO CLIENT-SIDE
		const errors = validateFormClientSide(formData);
		if (errors.length > 0) {
			showNotification(errors.join(', '), 'error');
			return;
		}
		
		// Desabilitar botão
		if (saveBtn) {
			saveBtn.disabled = true;
			saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
		}
		
		// ✅ ENVIAR VIA AJAX
		fetch(`${config.apiUrl}?action=save`, {
			method: 'POST',
			body: formData
		})
		.then(response => {
			console.log('📨 Resposta recebida, status:', response.status);
			
			if (!response.ok) {
				// Tentar extrair mais informações do erro
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
				
				// ✅ FECHAR MODAL E RECARREGAR LISTA
				setTimeout(() => {
					closeContractModal();
					if (typeof window.contractsListManager !== 'undefined' && 
						typeof window.contractsListManager.filterContracts === 'function') {
						window.contractsListManager.filterContracts();
					} else {
						// Recarregar após 1 segundo para garantir que o contrato foi salvo
						setTimeout(() => window.location.reload(), 1000);
					}
				}, 1500);
			} else {
				throw new Error(data.message || 'Erro ao salvar contrato');
			}
		})
		.catch(error => {
			console.error('❌ Erro ao salvar:', error);
			
			// ✅ MENSAGEM DE ERRO MAIS DESCRITIVA
			let errorMessage = error.message || 'Erro ao salvar contrato';
			
			if (errorMessage.includes('500')) {
				errorMessage = 'Erro interno do servidor. Verifique os logs ou tente novamente.';
			} else if (errorMessage.includes('413')) {
				errorMessage = 'Arquivo muito grande. Tamanho máximo: 10MB';
			} else if (errorMessage.includes('415')) {
				errorMessage = 'Tipo de arquivo não suportado. Apenas PDF são permitidos.';
			}
			
			showNotification(errorMessage, 'error');
		})
		.finally(() => {
			// Reabilitar botão
			if (saveBtn) {
				saveBtn.disabled = false;
				saveBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Contrato';
			}
		});
	}
	
	// ✅ FUNÇÃO DE VALIDAÇÃO CLIENT-SIDE
	function validateFormClientSide(formData) {
		const errors = [];
		
		const companyId = formData.get('company_id');
		const contractNumber = formData.get('contract_number');
		const title = formData.get('title');
		const contractType = formData.get('contract_type');
		const startDate = formData.get('start_date');
		const endDate = formData.get('end_date');
		
		if (!companyId || companyId === '') {
			errors.push('Selecione uma empresa');
		}
		
		if (!contractNumber || contractNumber.trim() === '') {
			errors.push('Número do contrato é obrigatório');
		}
		
		if (!title || title.trim() === '') {
			errors.push('Título do contrato é obrigatório');
		}
		
		if (!contractType) {
			errors.push('Tipo de contrato é obrigatório');
		}
		
		if (contractType === 'client' && !formData.get('client_id')) {
			errors.push('Selecione um cliente');
		}
		
		if (contractType === 'supplier' && !formData.get('supplier_id')) {
			errors.push('Selecione um fornecedor');
		}
		
		if (!startDate) {
			errors.push('Data de início é obrigatória');
		}
		
		if (!endDate) {
			errors.push('Data de término é obrigatória');
		}
		
		if (startDate && endDate) {
			const start = new Date(startDate);
			const end = new Date(endDate);
			
			if (start > end) {
				errors.push('Data de início não pode ser posterior à data de término');
			}
		}
		
		// ✅ VALIDAR ARQUIVO SE EXISTIR
		const fileInput = document.getElementById('modal_contract_file');
		if (fileInput && fileInput.files.length > 0) {
			const file = fileInput.files[0];
			
			// Verificar tipo
			if (file.type !== 'application/pdf') {
				errors.push('Apenas arquivos PDF são permitidos');
			}
			
			// Verificar tamanho (10MB)
			const maxSize = 10 * 1024 * 1024;
			if (file.size > maxSize) {
				errors.push('Arquivo muito grande. Tamanho máximo: 10MB');
			}
		}
		
		return errors;
	}
    
    // ✅ HANDLE RENEW CONTRACT
    function handleRenewContract(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        console.log('🔄 Renovando contrato...');
        
        // Validar data de renovação
        const newEndDate = formData.get('new_end_date');
        if (!newEndDate) {
            showNotification('Selecione uma nova data de término', 'error');
            return;
        }
        
        // Desabilitar botão
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Renovando...';
        }
        
        // Enviar via AJAX
        fetch(`${config.apiUrl}?action=renew`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                
                // Fechar modal e recarregar
                setTimeout(() => {
                    closeRenewModal();
                    if (typeof window.contractsListManager !== 'undefined' && 
                        typeof window.contractsListManager.filterContracts === 'function') {
                        window.contractsListManager.filterContracts();
                    } else {
                        window.location.reload();
                    }
                }, 1500);
            } else {
                throw new Error(data.message || 'Erro ao renovar contrato');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao renovar:', error);
            showNotification(error.message || 'Erro ao renovar contrato', 'error');
        })
        .finally(() => {
            // Reabilitar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-redo"></i> Confirmar Renovação';
            }
        });
    }
    
    // ✅ EDIT CONTRACT
    function editContract(contractId) {
        openContractModal(contractId);
    }
    
    // ✅ DELETE CONTRACT
    function deleteContract(contractId) {
		// Verificar se já está processando
		if (window.isDeletingContract) {
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
		window.isDeletingContract = true;
		
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
				window.isDeletingContract = false;
			}, 1000);
		});
	}
	
	function formatCurrency(input) {
		// Remove tudo exceto números
		let value = input.value.replace(/\D/g, '');
		
		// Se estiver vazio, define como 0
		if (value === '') value = '0';
		
		// Converte para número e divide por 100 para ter decimais
		let number = parseInt(value, 10) / 100;
		
		// Formata como moeda brasileira
		input.value = number.toLocaleString('pt-BR', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		});
	}
	
	function convertValueForSubmit(value) {
		if (!value) return '0.00';
		
		// Remove pontos de milhar e substitui vírgula por ponto
		let cleanValue = value.toString()
			.replace(/\./g, '')  // Remove pontos
			.replace(',', '.');  // Substitui vírgula por ponto
		
		// Garante que é um número válido
		const num = parseFloat(cleanValue);
		return isNaN(num) ? '0.00' : num.toFixed(2);
	}
    
    // ✅ VALIDATE CONTRACT FORM
    function validateContractForm(formData) {
        const companyId = formData.get('company_id');
        const contractNumber = formData.get('contract_number');
        const title = formData.get('title');
        const contractType = formData.get('contract_type');
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date');
        
        // Validar campos obrigatórios
        const errors = [];
        
        if (!companyId) errors.push('Empresa é obrigatória');
        if (!contractNumber) errors.push('Número do contrato é obrigatório');
        if (!title) errors.push('Título do contrato é obrigatório');
        if (!contractType) errors.push('Tipo de contrato é obrigatório');
        if (!startDate) errors.push('Data de início é obrigatória');
        if (!endDate) errors.push('Data de término é obrigatória');
        
        if (contractType === 'client' && !formData.get('client_id')) {
            errors.push('Selecione um cliente para contrato com cliente');
        }
        
        if (contractType === 'supplier' && !formData.get('supplier_id')) {
            errors.push('Selecione um fornecedor para contrato com fornecedor');
        }
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (start > end) {
                errors.push('Data de início não pode ser posterior à data de término');
            }
        }
        
        if (errors.length > 0) {
            showNotification(errors.join(', '), 'error');
            return false;
        }
        
        return true;
    }
    
    // ✅ VALIDATE CONTRACT NUMBER
    function validateContractNumber() {
        const contractNumberInput = document.getElementById('modal_contract_number');
        const companySelect = document.getElementById('modal_company_id');
        const contractIdInput = document.querySelector('input[name="contract_id"]');
        
        if (!contractNumberInput || !companySelect || !companySelect.value) return;
        
        const contractNumber = contractNumberInput.value.trim();
        const companyId = companySelect.value;
        const contractId = contractIdInput ? contractIdInput.value : null;
        
        if (!contractNumber) return;
        
        // Verificar se número já existe
        let url = `${config.apiUrl}?action=check_number&number=${encodeURIComponent(contractNumber)}&company_id=${companyId}`;
        if (contractId) {
            url += `&exclude_id=${contractId}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.exists) {
                    showNotification('Número de contrato já existe para esta empresa', 'error');
                    contractNumberInput.classList.add('is-invalid');
                } else {
                    contractNumberInput.classList.remove('is-invalid');
                }
            })
            .catch(error => {
                console.error('❌ Erro ao validar número:', error);
            });
    }
    
    // ✅ VALIDATE DATES
    function validateDates() {
        const startDate = document.getElementById('modal_start_date');
        const endDate = document.getElementById('modal_end_date');
        
        if (!startDate || !endDate || !startDate.value || !endDate.value) return;
        
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        
        if (start > end) {
            showNotification('Data de início não pode ser posterior à data de término', 'error');
            startDate.classList.add('is-invalid');
            endDate.classList.add('is-invalid');
        } else {
            startDate.classList.remove('is-invalid');
            endDate.classList.remove('is-invalid');
        }
    }
    
    // ✅ VALIDATE CONTRACT TYPE
    function validateContractType() {
        const contractType = document.querySelector('input[name="contract_type"]:checked');
        if (!contractType) return;
        
        console.log('Tipo de contrato selecionado:', contractType.value);
        toggleContractType(contractType.value);
    }
    
    // ✅ FORMAT DATE
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    // ✅ FORMAT DATE FOR INPUT
    function formatDateForInput(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    }
    
    // ✅ SHOW NOTIFICATION
    function showNotification(message, type = 'info', duration = 5000) {
        // Remover notificações existentes
        const existingNotifications = document.querySelectorAll('.notification-toast');
        existingNotifications.forEach(notification => {
            if (notification.parentNode) {
                notification.remove();
            }
        });
        
        // Criar nova notificação
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                  type === 'error' ? 'exclamation-circle' : 
                                  type === 'warning' ? 'exclamation-triangle' : 
                                  'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Estilos inline para garantir visibilidade
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
            max-width: 400px;
            z-index: 99999;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            border-left: 4px solid;
            border-left-color: ${type === 'success' ? '#4CAF50' : 
                               type === 'error' ? '#F44336' : 
                               type === 'warning' ? '#FF9800' : '#2196F3'};
        `;
        
        document.body.appendChild(notification);
        
        // Mostrar
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Remover automaticamente
        setTimeout(() => {
            notification.style.transform = 'translateX(120%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, duration);
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
    
    // Adicionar estilos para notificações se não existirem
    function addNotificationStyles() {
        if (document.getElementById('contracts-notification-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'contracts-notification-styles';
        style.textContent = `
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 1rem 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                min-width: 300px;
                max-width: 400px;
                z-index: 99999;
                transform: translateX(120%);
                transition: transform 0.3s ease;
                border-left: 4px solid;
            }
            
            .notification-toast.success {
                border-left-color: #4CAF50;
            }
            
            .notification-toast.error {
                border-left-color: #F44336;
            }
            
            .notification-toast.warning {
                border-left-color: #FF9800;
            }
            
            .notification-toast.info {
                border-left-color: #2196F3;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex: 1;
            }
            
            .notification-content i {
                font-size: 1.25rem;
            }
            
            .notification-content i.fa-check-circle {
                color: #4CAF50;
            }
            
            .notification-content i.fa-exclamation-circle {
                color: #F44336;
            }
            
            .notification-content i.fa-exclamation-triangle {
                color: #FF9800;
            }
            
            .notification-content i.fa-info-circle {
                color: #2196F3;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: #666;
                cursor: pointer;
                font-size: 1rem;
                padding: 0.25rem;
                border-radius: 4px;
                transition: all 0.3s ease;
            }
            
            .notification-close:hover {
                background: #f5f5f5;
                color: #333;
            }
        `;
        
        document.head.appendChild(style);
    }
    
    // Adicionar estilos quando carregar
    addNotificationStyles();
    
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
        showNotification
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