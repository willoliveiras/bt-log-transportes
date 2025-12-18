// public/assets/js/employees.js - VERSÃO CORRIGIDA
(function() {
    'use strict';

    if (window.EmployeesManagerLoaded) {
        console.log('🔧 Employees Manager já carregado');
        return;
    }
    window.EmployeesManagerLoaded = true;

    console.log('👥 Employees Manager - CARREGANDO VERSÃO CORRIGIDA');

    class EmployeesManager {
        constructor() {
            this.currentEmployeeId = null;
            this.isInitialized = false;
            this.saving = false;
            this.deleting = false;
            console.log('✅ EmployeesManager instanciado');
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 EmployeesManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando EmployeesManager...');
            this.setupAllEvents();
            this.isInitialized = true;
            
            this.exposeMethods();
            
            console.log('✅ EmployeesManager inicializado com sucesso!');
        }

        exposeMethods() {
            window.employeesManager = this;
            window.viewEmployee = (employeeId) => this.viewEmployee(employeeId);
            window.editEmployee = (employeeId) => this.editEmployee(employeeId);
            window.deleteEmployee = (employeeId, employeeName) => this.deleteEmployee(employeeId, employeeName);
            window.openEmployeeForm = (employeeId = null) => this.openEmployeeForm(employeeId);
            
            console.log('🔧 Métodos employees expostos globalmente');
        }

        setupAllEvents() {
            this.setupButtonEvents();
            this.setupModalEvents();
            this.setupFormEvents();
        }

        setupButtonEvents() {
            console.log('🔧 Configurando eventos dos botões...');
            
            // ✅ CORREÇÃO: Referência direta aos métodos usando arrow functions
            const self = this;

            const newEmployeeBtn = document.getElementById('newEmployeeBtn');
            if (newEmployeeBtn) {
                newEmployeeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 BOTÃO NOVO FUNCIONÁRIO CLICADO!');
                    self.openEmployeeForm();
                });
            }

            const emptyStateBtn = document.querySelector('.empty-state .btn-primary');
            if (emptyStateBtn) {
                emptyStateBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('🎯 BOTÃO EMPTY STATE CLICADO!');
                    self.openEmployeeForm();
                });
            }

            // ✅ CORREÇÃO CRÍTICA: Event delegation com referência correta
            document.addEventListener('click', (e) => {
                const editBtn = e.target.closest('.btn-edit') || e.target.closest('.employee-edit-btn');
                if (editBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✏️ BOTÃO EDIT CLICADO!');
                    
                    const row = editBtn.closest('tr');
                    const employeeId = row?.getAttribute('data-employee-id') || editBtn.getAttribute('data-employee-id');
                    
                    if (employeeId) {
                        console.log('✏️ Editando funcionário ID:', employeeId);
                        self.editEmployee(employeeId); // ✅ CORREÇÃO AQUI
                    }
                    return;
                }

                const viewBtn = e.target.closest('.btn-view') || e.target.closest('.employee-view-btn');
                if (viewBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('👁️ BOTÃO VIEW CLICADO!');
                    
                    const row = viewBtn.closest('tr');
                    const employeeId = row?.getAttribute('data-employee-id') || viewBtn.getAttribute('data-employee-id');
                    
                    if (employeeId) {
                        console.log('👁️ Visualizando funcionário ID:', employeeId);
                        self.viewEmployee(employeeId); // ✅ CORREÇÃO AQUI
                    }
                    return;
                }

                const deleteBtn = e.target.closest('.btn-delete') || e.target.closest('.employee-delete-btn');
                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🗑️ BOTÃO DELETE CLICADO!');
                    
                    const row = deleteBtn.closest('tr');
                    const employeeId = row?.getAttribute('data-employee-id') || deleteBtn.getAttribute('data-employee-id');
                    const employeeName = deleteBtn.getAttribute('data-employee-name');
                    
                    if (employeeId) {
                        console.log('🗑️ Excluindo:', employeeName, 'ID:', employeeId);
                        self.deleteEmployee(employeeId, employeeName); // ✅ CORREÇÃO AQUI
                    }
                    return;
                }
            });

            console.log('✅ Eventos dos botões configurados!');
        }

        // 🎯 MÉTODO VIEW EMPLOYEE - CORRIGIDO
        viewEmployee(employeeId) {
            console.log('🚀 EXECUTANDO viewEmployee:', employeeId);
            
            this.currentEmployeeId = employeeId;
            
            const modal = document.getElementById('employeeModal');
            if (!modal) {
                alert('❌ Modal não encontrado!');
                return;
            }
            
            // Configurar como visualização
            const title = document.getElementById('modalEmployeeTitle');
            if (title) title.textContent = 'Visualizar Funcionário';
            
            const saveBtn = document.getElementById('saveEmployeeButton');
            if (saveBtn) saveBtn.style.display = 'none';
            
            // Abrir modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            // Carregar dados
            this.loadEmployeeData(employeeId);
            
            // Desabilitar campos
            this.setFormReadOnly(true);
            
            console.log('✅ Modal aberto em modo visualização');
        }

        // 🎯 MÉTODO EDIT EMPLOYEE - CORRIGIDO
        editEmployee(employeeId) {
            console.log('✏️ EXECUTANDO editEmployee:', employeeId);
            this.openEmployeeForm(employeeId);
        }

        // 🎯 MÉTODO DELETE EMPLOYEE - CORRIGIDO
        async deleteEmployee(employeeId, employeeName) {
            if (this.deleting) {
                console.log('⏳ Exclusão já em andamento...');
                return;
            }
            
            let displayName = 'Funcionário';
            if (employeeName && employeeName !== 'null' && employeeName !== 'undefined' && employeeName.trim() !== '') {
                displayName = employeeName;
            }
            
            if (confirm(`Tem certeza que deseja excluir "${displayName}"?`)) {
                this.deleting = true;
                
                try {
                    const formData = new FormData();
                    formData.append('id', employeeId);
                    
                    const response = await fetch('/bt-log-transportes/public/api/employees.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Funcionário excluído com sucesso!');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir funcionário');
                    }
                    
                } catch (error) {
                    console.error('Erro ao excluir:', error);
                    alert('Erro: ' + error.message);
                } finally {
                    this.deleting = false;
                }
            }
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos do modal...');
            
            const modal = document.getElementById('employeeModal');
            if (!modal) {
                console.error('❌ MODAL NÃO ENCONTRADO!');
                return;
            }

            console.log('✅ Modal encontrado');

            // ✅ CORREÇÃO: Usar arrow functions para manter o contexto
            const self = this;

            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    self.closeEmployeeModal();
                });
            }

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    self.closeEmployeeModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal[style*="display: block"]');
                    if (openModal) {
                        self.closeEmployeeModal();
                    }
                }
            });

            const cancelBtn = document.getElementById('cancelEmployeeButton');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    self.closeEmployeeModal();
                });
            }

            const saveBtn = document.getElementById('saveEmployeeButton');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    console.log('💾 Botão salvar funcionário clicado');
                    self.saveEmployee();
                });
            }

            console.log('✅ Eventos do modal configurados!');
        }

        setupFormEvents() {
            console.log('🔧 Configurando eventos do formulário...');
            this.setupMasks();
            this.setupSalaryCalculations();
            this.setupPhotoPreview();
            console.log('✅ Eventos do formulário configurados!');
        }

        setupMasks() {
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

            const cpfInput = document.getElementById('cpf');
            if (cpfInput) {
                cpfInput.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    }
                    e.target.value = value;
                });
            }

            const numericFields = ['rg', 'ctps', 'pis_pasep', 'titulo_eleitor', 'reservista'];
            numericFields.forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (input) {
                    input.addEventListener('input', (e) => {
                        e.target.value = e.target.value.replace(/\D/g, '');
                    });
                }
            });
        }

        setupSalaryCalculations() {
            const calculateTotals = () => {
                const salary = parseFloat(document.getElementById('salary')?.value || 0);
                const inss = parseFloat(document.getElementById('inss')?.value || 0);
                const irrf = parseFloat(document.getElementById('irrf')?.value || 0);
                const fgts = parseFloat(document.getElementById('fgts')?.value || 0);
                const valeTransporte = parseFloat(document.getElementById('vale_transporte')?.value || 0);
                const valeRefeicao = parseFloat(document.getElementById('vale_refeicao')?.value || 0);
                const planoSaude = parseFloat(document.getElementById('plano_saude')?.value || 0);
                const outrosDescontos = parseFloat(document.getElementById('outros_descontos')?.value || 0);
                const commission = parseFloat(document.getElementById('commission_rate')?.value || 0);

                const totalDescontos = inss + irrf + fgts + valeTransporte + valeRefeicao + planoSaude + outrosDescontos;
                const commissionValue = salary * (commission / 100);
                const salarioLiquido = salary + commissionValue - totalDescontos;

                const totalDescontosEl = document.getElementById('totalDescontos');
                const salarioLiquidoEl = document.getElementById('salarioLiquido');
                
                if (totalDescontosEl) totalDescontosEl.textContent = `R$ ${totalDescontos.toFixed(2)}`;
                if (salarioLiquidoEl) salarioLiquidoEl.textContent = `R$ ${salarioLiquido.toFixed(2)}`;
            };

            const discountFields = [
                'salary', 'inss', 'irrf', 'fgts', 'vale_transporte', 
                'vale_refeicao', 'plano_saude', 'outros_descontos', 'commission_rate'
            ];

            discountFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', calculateTotals);
                }
            });

            setTimeout(calculateTotals, 100);
        }

        setupPhotoPreview() {
			const photoInput = document.getElementById('employee_photo');
			const fileInfo = document.getElementById('photoFileInfo');
			
			if (photoInput && fileInfo) {
				photoInput.addEventListener('change', (e) => {
					const file = e.target.files[0];
					if (file) {
						fileInfo.textContent = `Arquivo selecionado: ${file.name}`;
						fileInfo.style.color = '#4CAF50';
						
						// ✅ CORREÇÃO: Preview da foto
						this.createPhotoPreview(file);
					} else {
						fileInfo.textContent = 'Nenhum arquivo selecionado';
						fileInfo.style.color = '#666';
						this.resetPhotoPreview();
					}
				});
			}
		}
		
		// ✅ NOVO MÉTODO: Criar preview da foto
		createPhotoPreview(file) {
			const photoPreview = document.getElementById('photoPreview');
			if (!photoPreview) {
				console.error('❌ photoPreview não encontrado');
				return;
			}

			const reader = new FileReader();
			reader.onload = (e) => {
				photoPreview.src = e.target.result;
				photoPreview.style.display = 'block';
				photoPreview.alt = 'Preview da foto';
				
				// ✅ CORREÇÃO: Remover placeholder se existir
				const placeholder = document.querySelector('.employee-photo-placeholder-large');
				if (placeholder) {
					placeholder.style.display = 'none';
				}
			};
			reader.onerror = () => {
				console.error('❌ Erro ao ler arquivo da foto');
				this.resetPhotoPreview();
			};
			reader.readAsDataURL(file);
		}

        // 🎯 MÉTODO OPEN EMPLOYEE FORM - CORRIGIDO
        openEmployeeForm(employeeId = null) {
            console.log('🎯 EXECUTANDO openEmployeeForm:', employeeId);
            
            this.currentEmployeeId = employeeId;
            const modal = document.getElementById('employeeModal');
            const title = document.getElementById('modalEmployeeTitle');
            const saveBtn = document.getElementById('saveEmployeeButton');

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
            if (employeeId) {
                title.textContent = 'Editar Funcionário';
                // Carregar empresas primeiro, depois dados do funcionário
                this.loadCompaniesForForm().then(() => {
                    this.loadEmployeeData(employeeId);
                }).catch(error => {
                    console.error('❌ Erro ao carregar empresas:', error);
                    this.loadEmployeeData(employeeId); // Carregar mesmo sem empresas
                });
            } else {
                title.textContent = 'Novo Funcionário';
                // Para novo funcionário, apenas carregar empresas
                this.loadCompaniesForForm().then(() => {
                    this.resetForm();
                }).catch(error => {
                    console.error('❌ Erro ao carregar empresas:', error);
                    this.resetForm(); // Resetar mesmo sem empresas
                });
            }

            // Abrir modal IMEDIATAMENTE
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ MODAL FUNCIONÁRIO ABERTO!');
        }

        // 🎯 MÉTODO SET FORM READ ONLY
        setFormReadOnly(readOnly) {
            console.log('🔒 Modo leitura:', readOnly);
            const form = document.getElementById('employeeForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.id !== 'cancelEmployeeButton') {
                    input.disabled = readOnly;
                }
            });
        }

        // 🎯 MÉTODO CLOSE EMPLOYEE MODAL
        closeEmployeeModal() {
            console.log('🔒 Fechando modal funcionário...');
            const modal = document.getElementById('employeeModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.classList.remove('modal-open');
                this.resetForm();
            }
        }

        // 🎯 MÉTODO RESET FORM
        resetForm() {
            const form = document.getElementById('employeeForm');
            if (form) {
                form.reset();
                
                // Resetar ID
                const employeeIdInput = document.getElementById('employeeId');
                if (employeeIdInput) {
                    employeeIdInput.value = '';
                }
                
                // Resetar foto
                this.resetPhotoPreview();
                
                // Resetar arquivo
                const fileInfo = document.getElementById('photoFileInfo');
                if (fileInfo) {
                    fileInfo.textContent = 'Nenhum arquivo selecionado';
                    fileInfo.style.color = '#666';
                }

                // Habilitar formulário
                this.setFormReadOnly(false);

                // Resetar totais
                const totalDescontosEl = document.getElementById('totalDescontos');
                const salarioLiquidoEl = document.getElementById('salarioLiquido');
                if (totalDescontosEl) totalDescontosEl.textContent = 'R$ 0,00';
                if (salarioLiquidoEl) salarioLiquidoEl.textContent = 'R$ 0,00';
            }
        }

        resetPhotoPreview() {
			const photoPreview = document.getElementById('photoPreview');
			const placeholder = document.querySelector('.employee-photo-placeholder-large');
			const previewText = document.querySelector('.photo-preview-text');
			
			if (photoPreview) {
				photoPreview.src = '';
				photoPreview.style.display = 'none';
			}
			
			if (placeholder) {
				placeholder.style.display = 'flex';
			}
			
			if (previewText) {
				previewText.textContent = 'Foto será exibida aqui';
			}
			
			const fileInfo = document.getElementById('photoFileInfo');
			if (fileInfo) {
				fileInfo.textContent = 'Nenhum arquivo selecionado';
				fileInfo.style.color = '#666';
			}
		}

        // ✅ MÉTODO: Carregar empresas para o formulário
        async loadCompaniesForForm() {
            try {
                console.log('🏢 Carregando empresas para o formulário...');
                
                const response = await fetch('/bt-log-transportes/public/api/employees.php?action=companies');
                
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    console.log('✅ Empresas carregadas:', result.data.length);
                    this.populateCompaniesDropdown(result.data);
                    return true;
                } else {
                    throw new Error(result.message || 'Erro ao carregar empresas');
                }
                
            } catch (error) {
                console.error('❌ Erro ao carregar empresas:', error);
                this.showCompaniesError();
                return false;
            }
        }

        // ✅ MÉTODO: Popular dropdown de empresas
        populateCompaniesDropdown(companies) {
            const companySelect = document.getElementById('company_id');
            if (!companySelect) {
                console.error('❌ Select de empresas não encontrado');
                return;
            }

            // Limpar opções existentes (exceto a primeira)
            while (companySelect.options.length > 1) {
                companySelect.remove(1);
            }

            // Adicionar empresas
            companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.id;
                option.textContent = company.name;
                if (company.color) {
                    option.style.color = company.color;
                }
                companySelect.appendChild(option);
            });

            console.log(`✅ Dropdown de empresas atualizado: ${companies.length} empresas`);
        }

        // ✅ MÉTODO: Mostrar erro de carregamento de empresas
        showCompaniesError() {
            const companySelect = document.getElementById('company_id');
            if (companySelect) {
                // Manter apenas a primeira opção
                while (companySelect.options.length > 1) {
                    companySelect.remove(1);
                }
                
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Erro ao carregar empresas';
                option.disabled = true;
                companySelect.appendChild(option);
            }
            
            console.error('❌ Não foi possível carregar a lista de empresas');
        }

        // 🎯 MÉTODO LOAD EMPLOYEE DATA
        async loadEmployeeData(employeeId) {
            console.log('🔍 Iniciando loadEmployeeData para ID:', employeeId);
            
            try {
                const apiUrl = `/bt-log-transportes/public/api/employees.php?action=get&id=${employeeId}`;
                console.log('🔍 URL da API:', apiUrl);
                
                const response = await fetch(apiUrl);
                console.log('🔍 Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('🔍 JSON recebido:', result);

                if (result.success && result.data) {
                    console.log('✅ Dados recebidos da API - COMPLETO');
                    console.log('🔍 Nome do funcionário:', result.data.name);
                    console.log('🔍 ID do funcionário:', result.data.id);
                    
                    // ✅ VERIFICAÇÃO CRÍTICA: Confirmar que é o funcionário correto
                    if (result.data.id != employeeId) {
                        console.error('❌ ERRO CRÍTICO: ID do funcionário não corresponde!');
                        console.error('❌ Esperado:', employeeId, 'Recebido:', result.data.id);
                        alert('Erro: Dados do funcionário incorretos. Recarregue a página.');
                        return;
                    }
                    
                    this.populateForm(result.data);
                } else {
                    console.error('❌ API retornou erro:', result.message);
                    throw new Error(result.message || 'Erro ao carregar dados');
                }
            } catch (error) {
                console.error('❌ Erro completo ao carregar dados:', error);
                alert('Erro ao carregar dados do funcionário: ' + error.message);
            }
        }

        // 🎯 MÉTODO POPULATE FORM - CORREÇÃO CRÍTICA
        populateForm(employee) {
            console.log('📝 Preenchendo formulário com dados do funcionário:');
            console.log('📝 ID:', employee.id);
            console.log('📝 Nome:', employee.name);
            
            // ✅ CORREÇÃO CRÍTICA: Definir o currentEmployeeId ANTES de preencher os campos
            this.currentEmployeeId = employee.id;
            console.log('🎯 CurrentEmployeeId definido como:', this.currentEmployeeId);

            // ✅ CORREÇÃO: Definir o employeeId no campo hidden
            const employeeIdInput = document.getElementById('employeeId');
            if (employeeIdInput) {
                employeeIdInput.value = employee.id || '';
                console.log('✅ Campo hidden employeeId preenchido:', employee.id);
            } else {
                console.error('❌ Campo hidden employeeId não encontrado!');
            }
            
            // Preencher campos normais
            const fields = {
                'company_id': employee.company_id || '',
                'name': employee.name || '',
                'cpf': employee.cpf || '',
                'rg': employee.rg || '',
                'birth_date': employee.birth_date || '',
                'ctps': employee.ctps || '',
                'pis_pasep': employee.pis_pasep || '',
                'titulo_eleitor': employee.titulo_eleitor || '',
                'reservista': employee.reservista || '',
                'nome_mae': employee.nome_mae || '',
                'nome_pai': employee.nome_pai || '',
                'naturalidade': employee.naturalidade || '',
                'nacionalidade': employee.nacionalidade || '',
                'email': employee.email || '',
                'phone': employee.phone || '',
                'address': employee.address || '',
                'position': employee.position || '',
                'salary': employee.salary || '',
                'inss': employee.inss || '0',
                'irrf': employee.irrf || '0',
                'fgts': employee.fgts || '0',
                'vale_transporte': employee.vale_transporte || '0',
                'vale_refeicao': employee.vale_refeicao || '0',
                'plano_saude': employee.plano_saude || '0',
                'outros_descontos': employee.outros_descontos || '0',
                'commission_rate': employee.commission_rate || '0'
            };

            Object.keys(fields).forEach(fieldId => {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.value = fields[fieldId];
                }
            });

            // Preencher selects
            const selects = {
                'estado_civil': employee.estado_civil,
                'grau_instrucao': employee.grau_instrucao,
                'tipo_sanguineo': employee.tipo_sanguineo
            };

            Object.keys(selects).forEach(selectId => {
                const element = document.getElementById(selectId);
                if (element && selects[selectId]) {
                    element.value = selects[selectId];
                }
            });

            // Preencher checkboxes
            const isDriverCheckbox = document.getElementById('is_driver');
            const isActiveCheckbox = document.getElementById('is_active');
            
            if (isDriverCheckbox) {
                isDriverCheckbox.checked = employee.is_driver ? true : false;
            }
            
            if (isActiveCheckbox) {
                isActiveCheckbox.checked = employee.is_active !== undefined ? 
                                      (employee.is_active ? true : false) : true;
            }

            console.log('✅ Formulário preenchido completamente');
            console.log('🎯 CurrentEmployeeId final:', this.currentEmployeeId);
            
            // Atualizar preview da foto e recálculos
            this.loadEmployeePhoto(employee.photo, employee.name);
            setTimeout(() => this.setupSalaryCalculations(), 500);
        }

        // 🎯 MÉTODO LOAD EMPLOYEE PHOTO
        loadEmployeePhoto(photoPath, employeeName) {
			const photoPreview = document.getElementById('photoPreview');
			const placeholder = document.querySelector('.employee-photo-placeholder-large');
			const previewText = document.querySelector('.photo-preview-text');
			
			if (!photoPreview || !placeholder) {
				console.error('❌ Elementos do preview não encontrados');
				return;
			}

			console.log('🖼️ Carregando foto existente:', photoPath);

			if (photoPath && photoPath.trim() !== '' && photoPath !== 'null') {
				// ✅ CORREÇÃO: Usar caminho absoluto sempre
				const timestamp = new Date().getTime();
				let cleanPhotoPath = photoPath;
				
				// Garantir que não tenha barras duplicadas
				if (cleanPhotoPath.startsWith('/')) {
					cleanPhotoPath = cleanPhotoPath.substring(1);
				}
				if (cleanPhotoPath.startsWith('bt-log-transportes/')) {
					cleanPhotoPath = cleanPhotoPath.replace('bt-log-transportes/', '');
				}
				if (cleanPhotoPath.startsWith('public/')) {
					cleanPhotoPath = cleanPhotoPath.replace('public/', '');
				}
				
				// ✅ CORREÇÃO: Caminho absoluto correto
				const photoUrl = `/bt-log-transportes/${cleanPhotoPath}?t=${timestamp}`;
				
				console.log('🖼️ URL final da foto:', photoUrl);
				
				// ✅ CORREÇÃO: Verificar se a imagem carrega com sucesso
				const img = new Image();
				img.onload = () => {
					console.log('✅ Foto carregada com sucesso');
					photoPreview.src = photoUrl;
					photoPreview.style.display = 'block';
					photoPreview.alt = employeeName || 'Foto do funcionário';
					placeholder.style.display = 'none';
					
					if (previewText) {
						previewText.textContent = 'Foto atual';
					}
					
					const fileInfo = document.getElementById('photoFileInfo');
					if (fileInfo) {
						fileInfo.textContent = 'Foto atual carregada';
						fileInfo.style.color = '#4CAF50';
					}
				};
				
				img.onerror = () => {
					console.error('❌ Erro ao carregar foto, usando placeholder');
					console.error('❌ URL que falhou:', photoUrl);
					photoPreview.style.display = 'none';
					placeholder.style.display = 'flex';
					
					if (previewText) {
						previewText.textContent = 'Erro ao carregar foto';
					}
					
					const fileInfo = document.getElementById('photoFileInfo');
					if (fileInfo) {
						fileInfo.textContent = 'Erro ao carregar foto';
						fileInfo.style.color = '#F44336';
					}
					
					// ✅ CORREÇÃO: Tentar caminho alternativo
					this.tryAlternativePhotoPath(photoPath, employeeName);
				};
				
				img.src = photoUrl;
				
			} else {
				console.log('🖼️ Nenhuma foto encontrada, usando placeholder');
				this.showPhotoPlaceholder();
			}
		}
		
		
		tryAlternativePhotoPath(photoPath, employeeName) {
			console.log('🔄 Tentando caminhos alternativos para:', photoPath);
			
			const alternativePaths = [
				`/bt-log-transportes/public/${photoPath}`,
				`/bt-log-transportes/${photoPath}`,
				`/${photoPath}`,
				photoPath
			];
			
			const photoPreview = document.getElementById('photoPreview');
			
			for (let i = 0; i < alternativePaths.length; i++) {
				const testUrl = alternativePaths[i] + '?t=' + new Date().getTime();
				console.log(`🔄 Tentando caminho ${i + 1}:`, testUrl);
				
				const testImg = new Image();
				testImg.onload = () => {
					console.log(`✅ Foto carregada com caminho alternativo ${i + 1}`);
					photoPreview.src = testUrl;
					photoPreview.style.display = 'block';
					document.querySelector('.employee-photo-placeholder-large').style.display = 'none';
					return;
				};
				testImg.src = testUrl;
			}
		}
		
		
		showPhotoPlaceholder() {
			const photoPreview = document.getElementById('photoPreview');
			const placeholder = document.querySelector('.employee-photo-placeholder-large');
			const previewText = document.querySelector('.photo-preview-text');
			
			if (photoPreview) photoPreview.style.display = 'none';
			if (placeholder) placeholder.style.display = 'flex';
			if (previewText) previewText.textContent = 'Nenhuma foto disponível';
			
			const fileInfo = document.getElementById('photoFileInfo');
			if (fileInfo) {
				fileInfo.textContent = 'Nenhuma foto disponível';
				fileInfo.style.color = '#666';
			}
		}

        // 🎯 MÉTODO SAVE EMPLOYEE - CORREÇÃO CRÍTICA
        async saveEmployee() {
            if (this.saving) {
                console.log('⏳ Salvamento já em andamento...');
                return;
            }
            
            this.saving = true;
            console.log('💾 Iniciando salvamento do funcionário...');
            
            // Validar formulário primeiro
            if (!this.validateForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveEmployeeButton');
            this.setLoadingState(saveBtn, true);

            try {
                // ✅ CORREÇÃO CRÍTICA: Criar FormData manualmente para garantir que todos os campos sejam incluídos
                const formData = new FormData();
                
                console.log('🎯 CurrentEmployeeId:', this.currentEmployeeId);
                
                // ✅ CORREÇÃO CRÍTICA: Adicionar employeeId PRIMEIRO se existir
                if (this.currentEmployeeId) {
                    formData.append('employeeId', this.currentEmployeeId);
                    console.log('✅ employeeId adicionado ao FormData:', this.currentEmployeeId);
                }

                // ✅ CORREÇÃO: Coletar TODOS os campos do formulário manualmente
                const fieldsToCollect = [
                    'company_id', 'name', 'cpf', 'rg', 'birth_date', 'ctps', 'pis_pasep',
                    'titulo_eleitor', 'reservista', 'nome_mae', 'nome_pai', 'naturalidade',
                    'nacionalidade', 'email', 'phone', 'address', 'estado_civil', 'grau_instrucao',
                    'tipo_sanguineo', 'position', 'salary', 'inss', 'irrf', 'fgts', 'vale_transporte',
                    'vale_refeicao', 'plano_saude', 'outros_descontos', 'commission_rate', 'is_driver', 'is_active'
                ];

                fieldsToCollect.forEach(fieldName => {
                    const element = document.getElementById(fieldName);
                    if (element) {
                        if (element.type === 'checkbox') {
                            formData.append(fieldName, element.checked ? '1' : '0');
                            console.log(`✅ Campo ${fieldName}:`, element.checked ? '1' : '0');
                        } else {
                            formData.append(fieldName, element.value || '');
                            console.log(`✅ Campo ${fieldName}:`, element.value || '');
                        }
                    } else {
                        console.warn(`⚠️ Campo não encontrado: ${fieldName}`);
                    }
                });

                // ✅ CORREÇÃO: Adicionar arquivo de foto se existir
                const photoInput = document.getElementById('employee_photo');
                if (photoInput && photoInput.files.length > 0) {
                    formData.append('employee_photo', photoInput.files[0]);
                    console.log('📸 Foto anexada ao formulário');
                }

                // ✅ CORREÇÃO: Log completo dos dados que serão enviados
                console.log('📤 Dados que serão enviados para a API:');
                console.log('🔑 employeeId:', this.currentEmployeeId);
                for (let pair of formData.entries()) {
                    if (pair[0] !== 'employee_photo') {
                        console.log(`- ${pair[0]}: ${pair[1]}`);
                    }
                }

                // Determinar a ação
                const action = this.currentEmployeeId ? 'update' : 'create';
                const apiUrl = `/bt-log-transportes/public/api/employees.php?action=${action}`;
                
                console.log(`🚀 Enviando para: ${apiUrl}`);
                console.log(`🎯 Modo: ${this.currentEmployeeId ? 'EDIÇÃO' : 'CRIAÇÃO'}`);

                // Enviar para a API
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                // ✅ CORREÇÃO: Verificar se a resposta é válida
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
                }

                const result = await response.json();
                console.log('📥 Resposta da API:', result);

                if (result.success) {
                    console.log('✅ FUNCIONÁRIO SALVO COM SUCESSO!');
                    this.showToast(result.message || 'Funcionário salvo com sucesso!', 'success');
                    this.closeEmployeeModal();
                    
                    // Recarregar a página após um breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // ✅ CORREÇÃO: Mostrar erro específico da API
                    throw new Error(result.message || 'Erro desconhecido ao salvar funcionário');
                }
                
            } catch (error) {
                console.error('💥 Erro ao salvar funcionário:', error);
                this.showToast('Erro: ' + error.message, 'error');
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        // 🎯 MÉTODO VALIDATE FORM
        validateForm() {
            console.log('🔍 Validando formulário...');
            
            const name = document.getElementById('name');
            const position = document.getElementById('position');
            const company_id = document.getElementById('company_id');
            const salary = document.getElementById('salary');
            
            let isValid = true;
            let errorMessage = '';

            if (!name || !name.value.trim()) {
                errorMessage = 'O nome do funcionário é obrigatório';
                name.focus();
                isValid = false;
            } else if (!position || !position.value.trim()) {
                errorMessage = 'O cargo é obrigatório';
                position.focus();
                isValid = false;
            } else if (!company_id || !company_id.value) {
                errorMessage = 'A empresa é obrigatória';
                company_id.focus();
                isValid = false;
            } else if (!salary || !salary.value || parseFloat(salary.value) <= 0) {
                errorMessage = 'O salário é obrigatório e deve ser maior que zero';
                salary.focus();
                isValid = false;
            }

            if (!isValid) {
                this.showToast(errorMessage, 'error');
            }

            console.log('✅ Validação do formulário:', isValid ? 'APROVADO' : 'REPROVADO');
            return isValid;
        }

        // 🎯 MÉTODO SET LOADING STATE
        setLoadingState(button, isLoading) {
            if (!button) return;
            
            if (isLoading) {
                button.innerHTML = `
                    <div class="btn-loading">
                        <div class="loading-spinner"></div>
                        <span>Salvando...</span>
                    </div>
                `;
                button.disabled = true;
            } else {
                button.innerHTML = `
                    <span class="btn-text">Salvar Funcionário</span>
                `;
                button.disabled = false;
            }
        }

        showToast(message, type = 'info') {
            // Implementação simples de toast - pode ser substituída por uma biblioteca
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'error' ? '#f44336' : type === 'success' ? '#4CAF50' : '#2196F3'};
                color: white;
                border-radius: 4px;
                z-index: 10000;
                font-weight: bold;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                document.body.removeChild(toast);
            }, 5000);
        }
		
		

    }

    // 🚀 INICIALIZAÇÃO COMPLETA
    console.log('🚀 CRIANDO employeesManager...');
    window.employeesManager = new EmployeesManager();
    window.employeesManager.exposeMethods();
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('📝 DOM Carregado - inicializando employeesManager...');
            setTimeout(() => {
                if (window.employeesManager && !window.employeesManager.isInitialized) {
                    window.employeesManager.init();
                }
            }, 100);
        });
    } else {
        console.log('📝 DOM Já carregado - inicializando agora...');
        setTimeout(() => {
            if (window.employeesManager && !window.employeesManager.isInitialized) {
                window.employeesManager.init();
            }
        }, 100);
    }

})();