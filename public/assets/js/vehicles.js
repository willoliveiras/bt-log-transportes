// public/assets/js/vehicles.js - SISTEMA COMPLETO DE VEÍCULOS CORRIGIDO
(function() {
    'use strict';

    if (window.VehiclesManagerLoaded) {
        console.log('🔧 Vehicles Manager já carregado');
        return;
    }
    window.VehiclesManagerLoaded = true;

    console.log('🚗 Vehicles Manager carregado');

    class VehiclesManager {
        constructor() {
            this.currentVehicleId = null;
            this.isInitialized = false;
            this.eventListeners = new Set();
            this.modal = null;
            this.saving = false;
            this.deleting = false;
        }

        init() {
            if (this.isInitialized) {
                console.log('🔧 VehiclesManager já inicializado');
                return;
            }

            console.log('🎯 Inicializando VehiclesManager...');
            
            this.removeAllEventListeners();
            
            setTimeout(() => {
                this.setupAllEvents();
                this.initVehicleSubtypes();
                this.isInitialized = true;
                console.log('✅ VehiclesManager inicializado com sucesso!');
            }, 100);
        }

        removeAllEventListeners() {
            console.log('🧹 Removendo event listeners antigos do VehiclesManager...');
            
            const elementsToClean = [
                'newVehicleBtn',
                'cancelVehicleButton',
                'saveVehicleButton',
                'type'
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
            console.log('🔧 Configurando eventos dos botões do VehiclesManager...');
            
            // Botão "Novo Veículo"
            const newVehicleBtn = document.getElementById('newVehicleBtn');
            if (newVehicleBtn && !this.eventListeners.has('newVehicleBtn')) {
                newVehicleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎯 [VEHICLES] Botão novo veículo clicado');
                    this.openVehicleForm();
                });
                this.eventListeners.add('newVehicleBtn');
            }

            // Delegation handler para veículos
            if (!this.eventListeners.has('delegation')) {
                this.delegationHandler = (e) => {
                    const vehicleRow = e.target.closest('tr[data-vehicle-id]');
                    if (!vehicleRow) return;

                    // Botão Editar
                    const editBtn = e.target.closest('.btn-edit');
                    if (editBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const vehicleId = vehicleRow.getAttribute('data-vehicle-id');
                        console.log('✏️ [VEHICLES] Editando veículo:', vehicleId);
                        this.editVehicle(vehicleId);
                        return;
                    }

                    // Botão Excluir
                    const deleteBtn = e.target.closest('.btn-delete');
                    if (deleteBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const vehicleId = vehicleRow.getAttribute('data-vehicle-id');
                        
                        let vehicleName = 'Veículo';
                        const brandElement = vehicleRow.querySelector('.vehicle-info strong');
                        const plateElement = vehicleRow.querySelector('.vehicle-plate');
                        if (brandElement && plateElement) {
                            vehicleName = `${brandElement.textContent} - ${plateElement.textContent}`;
                        }
                        
                        console.log('🗑️ [VEHICLES] Excluindo veículo:', vehicleName);
                        this.deleteVehicle(vehicleId, vehicleName);
                        return;
                    }

                    // Botão Visualizar
                    const viewBtn = e.target.closest('.btn-view');
                    if (viewBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const vehicleId = vehicleRow.getAttribute('data-vehicle-id');
                        console.log('👁️ [VEHICLES] Visualizando veículo:', vehicleId);
                        this.viewVehicle(vehicleId);
                        return;
                    }
                };
                
                document.addEventListener('click', this.delegationHandler);
                this.eventListeners.add('delegation');
            }

            console.log('✅ Eventos dos botões do VehiclesManager configurados!');
        }

        setupModalEvents() {
            console.log('🔧 Configurando eventos do modal de veículos...');
            
            this.modal = document.getElementById('vehicleModal');
            
            if (!this.modal) {
                console.log('ℹ️ Modal de veículos ainda não carregado, aguardando...');
                setTimeout(() => {
                    this.modal = document.getElementById('vehicleModal');
                    if (this.modal) {
                        console.log('✅ Modal de veículos encontrado após delay');
                        this.setupModalEventListeners();
                    } else {
                        console.error('❌ Modal de veículos não encontrado após múltiplas tentativas');
                    }
                }, 500);
                return;
            }

            console.log('✅ Modal de veículos encontrado');
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
                    this.closeVehicleModal();
                });
                this.eventListeners.add('modalClose');
            }

            // Fechar clicando fora
            if (!this.eventListeners.has('modalOutsideClick')) {
                this.modal.addEventListener('click', (e) => {
                    if (e.target === this.modal) {
                        this.closeVehicleModal();
                    }
                });
                this.eventListeners.add('modalOutsideClick');
            }

            // Botão Cancelar
            const cancelBtn = document.getElementById('cancelVehicleButton');
            if (cancelBtn && !this.eventListeners.has('cancelButton')) {
                cancelBtn.addEventListener('click', () => {
                    this.closeVehicleModal();
                });
                this.eventListeners.add('cancelButton');
            }

            // Botão Salvar
            const saveBtn = document.getElementById('saveVehicleButton');
            if (saveBtn && !this.eventListeners.has('saveButton')) {
                saveBtn.addEventListener('click', () => {
                    console.log('💾 [VEHICLES] Botão salvar veículo clicado');
                    this.saveVehicle();
                });
                this.eventListeners.add('saveButton');
            }

            // Evento para mudança de tipo
            const typeSelect = document.getElementById('type');
            if (typeSelect && !this.eventListeners.has('typeChange')) {
                typeSelect.addEventListener('change', (e) => {
                    this.onTypeChange(e.target.value);
                });
                this.eventListeners.add('typeChange');
            }

            console.log('✅ Eventos do modal de veículos configurados!');
        }

        setupFormEvents() {
            // Aguardar um pouco para garantir que os elementos do formulário estejam carregados
            setTimeout(() => {
                // Máscara para placa
                const plateInput = document.getElementById('plate');
                if (plateInput && !this.eventListeners.has('plateMask')) {
                    plateInput.addEventListener('input', (e) => {
                        let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                        if (value.length > 7) value = value.substring(0, 7);
                        e.target.value = value;
                    });
                    this.eventListeners.add('plateMask');
                }

                // Máscara para chassi (apenas letras e números)
                const chassisInput = document.getElementById('chassis_number');
                if (chassisInput && !this.eventListeners.has('chassisMask')) {
                    chassisInput.addEventListener('input', (e) => {
                        e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    });
                    this.eventListeners.add('chassisMask');
                }

                // Validação de ano
                const yearInput = document.getElementById('year');
                if (yearInput && !this.eventListeners.has('yearValidation')) {
                    yearInput.addEventListener('blur', (e) => {
                        this.validateYear(e.target.value);
                    });
                    this.eventListeners.add('yearValidation');
                }

                console.log('✅ Eventos do formulário de veículos configurados!');
            }, 200);
        }

        // Inicializar subtipos de veículos
        initVehicleSubtypes() {
            this.vehicleSubtypes = {
                carro: [
                    { value: 'hatch', label: 'Hatch' },
                    { value: 'sedan', label: 'Sedan' },
                    { value: 'suv', label: 'SUV' },
                    { value: 'coupe', label: 'Coupé' },
                    { value: 'convertible', label: 'Conversível' },
                    { value: 'wagon', label: 'Perua' }
                ],
                motocicleta: [
                    { value: 'street', label: 'Street' },
                    { value: 'sport', label: 'Esportiva' },
                    { value: 'cruiser', label: 'Cruiser' },
                    { value: 'touring', label: 'Touring' },
                    { value: 'offroad', label: 'Off-road' },
                    { value: 'scooter', label: 'Scooter' }
                ],
                caminhonete: [
                    { value: 'compacta', label: 'Compacta' },
                    { value: 'media', label: 'Média' },
                    { value: 'grande', label: 'Grande' },
                    { value: 'luxo', label: 'Luxo' }
                ],
                pickup: [
                    { value: 'compacta', label: 'Compacta' },
                    { value: 'media', label: 'Média' },
                    { value: 'grande', label: 'Grande' },
                    { value: 'luxo', label: 'Luxo' }
                ],
                van: [
                    { value: 'carga', label: 'Van de Carga' },
                    { value: 'passageiros', label: 'Van de Passageiros' },
                    { value: 'minivan', label: 'Minivan' }
                ],
                minivan: [
                    { value: 'compacta', label: 'Compacta' },
                    { value: 'grande', label: 'Grande' },
                    { value: 'luxo', label: 'Luxo' }
                ],
                onibus: [
                    { value: 'urbano', label: 'Urbano' },
                    { value: 'rodoviario', label: 'Rodoviário' },
                    { value: 'micro', label: 'Microônibus' },
                    { value: 'articulado', label: 'Articulado' }
                ],
                microonibus: [
                    { value: 'van', label: 'Van' },
                    { value: 'minibus', label: 'Minibus' }
                ],
                caminhao: [
                    { value: 'toco', label: 'Toco' },
                    { value: 'truck', label: 'Truck' },
                    { value: 'carreta', label: 'Carreta' },
                    { value: 'bitrem', label: 'Bitrem' },
                    { value: 'rodotrem', label: 'Rodotrem' },
                    { value: 'vuc', label: 'VUC' },
                    { value: 'cavalo_mecanico', label: 'Cavalo Mecânico' }
                ],
                caminhao_toco: [
                    { value: 'simples', label: 'Simples' },
                    { value: 'duplo', label: 'Duplo' }
                ],
                caminhao_truck: [
                    { value: 'simples', label: 'Simples' },
                    { value: 'duplo', label: 'Duplo' }
                ],
                caminhao_carreta: [
                    { value: 'simples', label: 'Simples' },
                    { value: 'ls', label: 'LS' },
                    { value: 'bitrem', label: 'Bitrem' }
                ],
                caminhao_bitrem: [
                    { value: 'sete_eixos', label: '7 Eixos' },
                    { value: 'nove_eixos', label: '9 Eixos' }
                ],
                caminhao_rodotrem: [
                    { value: 'sete_eixos', label: '7 Eixos' },
                    { value: 'nove_eixos', label: '9 Eixos' }
                ],
                utilitario: [
                    { value: 'comercial', label: 'Comercial' },
                    { value: 'furgão', label: 'Furgão' },
                    { value: 'pickup', label: 'Pickup' }
                ],
                suv: [
                    { value: 'compacto', label: 'Compacto' },
                    { value: 'medio', label: 'Médio' },
                    { value: 'grande', label: 'Grande' },
                    { value: 'luxo', label: 'Luxo' }
                ],
                hatch: [
                    { value: 'compacto', label: 'Compacto' },
                    { value: 'medio', label: 'Médio' },
                    { value: 'grande', label: 'Grande' }
                ],
                sedan: [
                    { value: 'compacto', label: 'Compacto' },
                    { value: 'medio', label: 'Médio' },
                    { value: 'grande', label: 'Grande' },
                    { value: 'luxo', label: 'Luxo' }
                ],
                hatchback: [
                    { value: 'compacto', label: 'Compacto' },
                    { value: 'medio', label: 'Médio' },
                    { value: 'grande', label: 'Grande' }
                ],
                outros: [
                    { value: 'especial', label: 'Especial' },
                    { value: 'implemento', label: 'Implemento Rodoviário' },
                    { value: 'reboque', label: 'Reboque' },
                    { value: 'semi_reboque', label: 'Semi-reboque' }
                ]
            };
        }

        // Mudança de tipo de veículo
        onTypeChange(vehicleType) {
            console.log(`🔄 [VEHICLES] Tipo selecionado: ${vehicleType}`);
            
            const subtypeSelect = document.getElementById('vehicle_subtype');
            if (!subtypeSelect) return;

            // Limpar opções atuais
            subtypeSelect.innerHTML = '<option value="">Selecione o subtipo</option>';

            // Adicionar opções baseadas no tipo
            if (vehicleType && this.vehicleSubtypes[vehicleType]) {
                this.vehicleSubtypes[vehicleType].forEach(subtype => {
                    const option = document.createElement('option');
                    option.value = subtype.value;
                    option.textContent = subtype.label;
                    subtypeSelect.appendChild(option);
                });
                
                subtypeSelect.disabled = false;
            } else {
                subtypeSelect.disabled = true;
            }

            // Ajustar capacidade padrão baseada no tipo
            this.adjustDefaultCapacity(vehicleType);
        }

        // Ajustar capacidade padrão baseada no tipo
        adjustDefaultCapacity(vehicleType) {
            const capacityInput = document.getElementById('capacity');
            const capacityUnit = document.getElementById('capacity_unit');
            
            if (!capacityInput || !capacityUnit) return;

            const defaultCapacities = {
                'carro': { value: 380, unit: 'kg' },
                'motocicleta': { value: 150, unit: 'kg' },
                'caminhonete': { value: 800, unit: 'kg' },
                'pickup': { value: 1000, unit: 'kg' },
                'van': { value: 1500, unit: 'kg' },
                'minivan': { value: 600, unit: 'kg' },
                'onibus': { value: 40, unit: 'passageiros' },
                'microonibus': { value: 20, unit: 'passageiros' },
                'caminhao': { value: 12000, unit: 'kg' },
                'caminhao_toco': { value: 12000, unit: 'kg' },
                'caminhao_truck': { value: 23000, unit: 'kg' },
                'caminhao_carreta': { value: 33000, unit: 'kg' },
                'caminhao_bitrem': { value: 45000, unit: 'kg' },
                'caminhao_rodotrem': { value: 57000, unit: 'kg' },
                'suv': { value: 500, unit: 'kg' },
                'hatch': { value: 350, unit: 'kg' },
                'sedan': { value: 450, unit: 'kg' },
                'hatchback': { value: 400, unit: 'kg' }
            };

            if (defaultCapacities[vehicleType] && !capacityInput.value) {
                capacityInput.value = defaultCapacities[vehicleType].value;
                capacityUnit.value = defaultCapacities[vehicleType].unit;
            }
        }

        // Validar ano
        validateYear(year) {
            if (!year) return true;
            
            const currentYear = new Date().getFullYear();
            const vehicleYear = parseInt(year);
            
            if (vehicleYear < 1900 || vehicleYear > (currentYear + 1)) {
                this.showAlert('Ano do veículo deve estar entre 1900 e ' + (currentYear + 1), 'warning');
                return false;
            }
            
            return true;
        }

        // Método para obter ícone do veículo
        getVehicleIcon(vehicleType) {
            const icons = {
                'carro': 'car',
                'motocicleta': 'motorcycle',
                'caminhonete': 'truck-pickup',
                'pickup': 'truck-pickup',
                'van': 'van',
                'minivan': 'van',
                'onibus': 'bus',
                'microonibus': 'bus',
                'caminhao': 'truck',
                'caminhao_toco': 'truck',
                'caminhao_truck': 'truck',
                'caminhao_carreta': 'trailer',
                'caminhao_bitrem': 'trailer',
                'caminhao_rodotrem': 'trailer',
                'utilitario': 'truck',
                'suv': 'car',
                'hatch': 'car',
                'sedan': 'car',
                'hatchback': 'car',
                'outros': 'truck'
            };
            
            return icons[vehicleType] || 'truck';
        }

        showAlert(message, type = 'info') {
            // Criar alerta temporário
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

        // ✅ MÉTODO PRINCIPAL: Abrir modal
        openVehicleForm(vehicleId = null) {
            console.log('🎯 [VEHICLES] ABRINDO MODAL! VehicleId:', vehicleId);
            
            this.currentVehicleId = vehicleId;

            // Buscar o modal
            this.modal = document.getElementById('vehicleModal');
            
            if (!this.modal) {
                console.error('❌ MODAL VEÍCULOS NÃO ENCONTRADO!');
                alert('Erro: Modal não encontrado. Verifique se o HTML do modal está correto.');
                return;
            }

            const title = document.getElementById('modalVehicleTitle');

            if (vehicleId) {
                if (title) title.textContent = 'Editar Veículo';
                this.loadVehicleData(vehicleId);
            } else {
                if (title) title.textContent = 'Novo Veículo';
                this.resetForm();
            }

            // Abrir modal
            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            
            console.log('✅ [VEHICLES] MODAL VEÍCULOS ABERTO COM SUCESSO!');
        }

        // ✅ MÉTODO: Fechar modal
        closeVehicleModal() {
            console.log('🔒 [VEHICLES] Fechando modal...');
            if (this.modal) {
                this.modal.style.display = 'none';
            } else {
                const anyModal = document.getElementById('vehicleModal');
                if (anyModal) {
                    anyModal.style.display = 'none';
                }
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            this.resetForm();
            this.setFormReadOnly(false);
        }

        // ✅ MÉTODO: Editar veículo
        editVehicle(vehicleId) {
            console.log('✏️ [VEHICLES] Editando veículo:', vehicleId);
            this.setFormReadOnly(false);
            this.openVehicleForm(vehicleId);
        }

        // ✅ MÉTODO: Visualizar veículo
        viewVehicle(vehicleId) {
            console.log('👁️ [VEHICLES] Visualizando veículo:', vehicleId);
            this.openVehicleForm(vehicleId);
            this.setFormReadOnly(true);
        }

        // ✅ MÉTODO: Definir formulário como somente leitura
        setFormReadOnly(readOnly) {
            const form = document.getElementById('vehicleForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.id !== 'cancelVehicleButton') {
                    input.disabled = readOnly;
                }
            });

            const saveBtn = document.getElementById('saveVehicleButton');
            if (saveBtn) {
                saveBtn.style.display = readOnly ? 'none' : 'block';
            }

            const title = document.getElementById('modalVehicleTitle');
            if (title && readOnly) {
                title.textContent = 'Visualizar Veículo';
            }
        }

        // ✅ MÉTODO: Resetar formulário
        resetForm() {
            const form = document.getElementById('vehicleForm');
            if (form) {
                form.reset();
                
                const vehicleIdField = document.getElementById('vehicleId');
                if (vehicleIdField) {
                    vehicleIdField.value = '';
                }
                
                // Resetar subtipo
                const subtypeSelect = document.getElementById('vehicle_subtype');
                if (subtypeSelect) {
                    subtypeSelect.innerHTML = '<option value="">Selecione o subtipo</option>';
                    subtypeSelect.disabled = true;
                }
                
                // Resetar status para disponível
                const statusSelect = document.getElementById('status');
                if (statusSelect) {
                    statusSelect.value = 'disponivel';
                }
                
                // Marcar como ativo
                const isActiveCheckbox = document.getElementById('is_active');
                if (isActiveCheckbox) {
                    isActiveCheckbox.checked = true;
                }
            } else {
                console.warn('⚠️ [VEHICLES] Formulário não encontrado para reset');
            }
        }

        // ✅ MÉTODO: Carregar dados do veículo
        async loadVehicleData(vehicleId) {
            console.log(`📥 [VEHICLES] Carregando veículo ${vehicleId}`);
            
            try {
                const apiUrl = `/bt-log-transportes/public/api/vehicles.php?action=get&id=${vehicleId}`;
                console.log(`🔗 [VEHICLES] URL: ${apiUrl}`);
                
                const response = await fetch(apiUrl);
                
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                
                const result = await response.json();

                if (result.success && result.data) {
                    this.populateForm(result.data);
                    console.log('✅ [VEHICLES] Dados do veículo carregados com sucesso');
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados do veículo');
                }
            } catch (error) {
                console.error('❌ [VEHICLES] Erro ao carregar dados:', error);
                this.showAlert('Erro ao carregar dados do veículo: ' + error.message, 'error');
                // Carregar dados mock para desenvolvimento
                this.loadMockData(vehicleId);
            }
        }
        
        // ✅ MÉTODO: Preencher formulário com dados
        populateForm(vehicle) {
            console.log('📝 [VEHICLES] Preenchendo formulário com dados:', vehicle);
            
            const vehicleIdField = document.getElementById('vehicleId');
            if (vehicleIdField) {
                vehicleIdField.value = vehicle.id;
            }

            // Preencher campos básicos
            this.setValue('company_id', vehicle.company_id || '');
            this.setValue('plate', vehicle.plate || '');
            this.setValue('brand', vehicle.brand || '');
            this.setValue('model', vehicle.model || '');
            this.setValue('year', vehicle.year || '');
            this.setValue('color', vehicle.color || '');
            this.setValue('chassis_number', vehicle.chassis_number || '');

            // Preencher tipo e subtipo
            const typeSelect = document.getElementById('type');
            if (typeSelect && vehicle.type) {
                typeSelect.value = vehicle.type;
                // Disparar change event para carregar subtipos
                setTimeout(() => {
                    typeSelect.dispatchEvent(new Event('change'));
                    
                    // Preencher subtipo após um delay para garantir que as opções foram carregadas
                    setTimeout(() => {
                        this.setValue('vehicle_subtype', vehicle.vehicle_subtype || '');
                    }, 200);
                }, 100);
            }

            // Preencher capacidade
            this.setValue('capacity', vehicle.capacity || '');
            this.setValue('capacity_unit', vehicle.capacity_unit || 'kg');

            // Preencher combustível
            this.setValue('fuel_type', vehicle.fuel_type || '');
            this.setValue('fuel_capacity', vehicle.fuel_capacity || '');
            this.setValue('average_consumption', vehicle.average_consumption || '');
            this.setValue('current_km', vehicle.current_km || '');

            // Preencher documentos
            this.setValue('registration_number', vehicle.registration_number || '');
            this.setValue('registration_expiry', vehicle.registration_expiry || '');
            this.setValue('insurance_company', vehicle.insurance_company || '');
            this.setValue('insurance_number', vehicle.insurance_number || '');
            this.setValue('insurance_expiry', vehicle.insurance_expiry || '');

            // Preencher status
            this.setValue('status', vehicle.status || 'disponivel');
            
            const isActiveCheckbox = document.getElementById('is_active');
            if (isActiveCheckbox) {
                isActiveCheckbox.checked = vehicle.is_active !== undefined ? vehicle.is_active : true;
            }

            // Preencher observações
            this.setValue('notes', vehicle.notes || '');
        }

        // Helper para definir valores
        setValue(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.value = value;
            }
        }

        // ✅ MÉTODO: Carregar dados mock para desenvolvimento
        loadMockData(vehicleId) {
            console.log('🎭 [VEHICLES] Carregando dados mock');
            
            const mockData = {
                id: vehicleId,
                company_id: 1,
                plate: 'ABC1D23',
                brand: 'Volkswagen',
                model: 'Golf',
                year: '2022',
                color: 'Preto',
                chassis_number: '1234567890ABCDEFG',
                type: 'hatch',
                vehicle_subtype: 'hatch',
                capacity: '380',
                capacity_unit: 'kg',
                fuel_type: 'gasolina',
                fuel_capacity: '50',
                average_consumption: '12.5',
                current_km: '45000',
                registration_number: '123456789',
                registration_expiry: '2024-12-31',
                insurance_company: 'Porto Seguro',
                insurance_number: 'PS123456',
                insurance_expiry: '2024-06-30',
                status: 'disponivel',
                is_active: true,
                notes: 'Veículo em perfeito estado de conservação.'
            };

            this.populateForm(mockData);
        }

        // ✅ MÉTODO: Salvar veículo - CORRIGIDO
        async saveVehicle() {
            if (this.saving) return;
            
            this.saving = true;
            console.log('💾 [VEHICLES] Salvando veículo...');
            
            if (!this.validateForm()) {
                this.saving = false;
                return;
            }

            const saveBtn = document.getElementById('saveVehicleButton');
            this.setLoadingState(saveBtn, true);

            try {
                const formData = new FormData(document.getElementById('vehicleForm'));

                // Adicionar debug dos dados
                console.log('📋 [VEHICLES] Dados do formulário:');
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: ${value}`);
                }

                const vehicleId = this.currentVehicleId;
                const apiUrl = '/bt-log-transportes/public/api/vehicles.php?action=save';
                
                console.log(`🚀 [VEHICLES] Enviando para API: ${apiUrl}, ID: ${vehicleId}`);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                console.log('📡 [VEHICLES] Status da resposta:', response.status);
                console.log('📡 [VEHICLES] Headers:', response.headers);

                const responseText = await response.text();
                console.log('📡 [VEHICLES] Resposta completa:', responseText);

                // Verificar se a resposta é JSON válido
                if (!responseText.trim().startsWith('{')) {
                    console.error('❌ [VEHICLES] Resposta não é JSON:', responseText.substring(0, 200));
                    throw new Error('Resposta do servidor não é JSON válido');
                }

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('❌ [VEHICLES] Erro ao parsear JSON:', parseError);
                    console.error('❌ [VEHICLES] Resposta bruta:', responseText.substring(0, 500));
                    throw new Error('Resposta inválida do servidor - não é JSON válido');
                }

                console.log('📊 [VEHICLES] Resposta parseada:', result);

                if (result.success) {
                    console.log('✅ [VEHICLES] VEÍCULO SALVO COM SUCESSO!');
                    this.showAlert('Veículo salvo com sucesso!', 'success');
                    this.closeVehicleModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Erro ao salvar veículo');
                }
                
            } catch (error) {
                console.error('💥 [VEHICLES] Erro:', error);
                this.showAlert('Erro ao salvar veículo: ' + error.message, 'error');
            } finally {
                this.saving = false;
                this.setLoadingState(saveBtn, false);
            }
        }

        // ✅ MÉTODO: Excluir veículo
        async deleteVehicle(vehicleId, vehicleName) {
            if (this.deleting) return;
            
            let displayName = 'Veículo';
            if (vehicleName && vehicleName !== 'null' && vehicleName !== 'undefined' && vehicleName.trim() !== '') {
                displayName = vehicleName;
            }
            
            if (confirm(`Tem certeza que deseja excluir o veículo "${displayName}"?`)) {
                this.deleting = true;
                
                try {
                    const formData = new FormData();
                    formData.append('id', vehicleId);
                    
                    console.log(`🗑️ [VEHICLES] Excluindo veículo: ${displayName}`);
                    
                    const apiUrl = '/bt-log-transportes/public/api/vehicles.php?action=delete';
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Veículo excluído com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Erro ao excluir veículo');
                    }
                    
                } catch (error) {
                    console.error('❌ [VEHICLES] Erro ao excluir:', error);
                    this.showAlert('Erro ao excluir veículo: ' + error.message, 'error');
                } finally {
                    this.deleting = false;
                }
            }
        }

        // ✅ MÉTODO: Validar formulário
        validateForm() {
			const plate = document.getElementById('plate');
			const brand = document.getElementById('brand');
			const model = document.getElementById('model');
			const year = document.getElementById('year');
			const type = document.getElementById('type');
			const fuelType = document.getElementById('fuel_type');
			const company = document.getElementById('company_id');
			
			// Validar empresa
			if (!company || !company.value) {
				this.showAlert('A empresa é obrigatória', 'warning');
				company.focus();
				return false;
			}

			// Validar placa
			if (!plate || !plate.value.trim()) {
				this.showAlert('A placa do veículo é obrigatória', 'warning');
				plate.focus();
				return false;
			}
			
			if (plate.value.length < 7) {
				this.showAlert('A placa deve ter 7 caracteres', 'warning');
				plate.focus();
				return false;
			}
			
			// Validar marca
			if (!brand || !brand.value.trim()) {
				this.showAlert('A marca do veículo é obrigatória', 'warning');
				brand.focus();
				return false;
			}
			
			// Validar modelo
			if (!model || !model.value.trim()) {
				this.showAlert('O modelo do veículo é obrigatório', 'warning');
				model.focus();
				return false;
			}
			
			// Validar ano
			if (!year || !year.value) {
				this.showAlert('O ano do veículo é obrigatório', 'warning');
				year.focus();
				return false;
			}
			
			if (!this.validateYear(year.value)) {
				year.focus();
				return false;
			}
			
			// Validar tipo
			if (!type || !type.value) {
				this.showAlert('O tipo do veículo é obrigatório', 'warning');
				type.focus();
				return false;
			}
			
			// Validar combustível
			if (!fuelType || !fuelType.value) {
				this.showAlert('O tipo de combustível é obrigatório', 'warning');
				fuelType.focus();
				return false;
			}
			
			return true;
		}

        // ✅ MÉTODO: Definir estado de loading
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

        // ✅ MÉTODO: Filtrar por empresa
        filterByCompany(companyId) {
            const url = new URL(window.location);
            if (companyId) {
                url.searchParams.set('company', companyId);
            } else {
                url.searchParams.delete('company');
            }
            window.location.href = url.toString();
        }

        // ✅ MÉTODO: Filtrar por tipo
        filterByType(type) {
            const url = new URL(window.location);
            if (type) {
                url.searchParams.set('type', type);
            } else {
                url.searchParams.delete('type');
            }
            window.location.href = url.toString();
        }

        // ✅ MÉTODO: Filtrar por status
        filterByStatus(status) {
            const url = new URL(window.location);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }

        // ✅ MÉTODO: Atualizar lista
        refreshVehicles() {
            window.location.reload();
        }
    }

    // Inicialização
    if (!window.vehiclesManager) {
        window.vehiclesManager = new VehiclesManager();
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.vehiclesManager.init();
            }, 500);
        });

        if (document.readyState !== 'loading') {
            setTimeout(() => {
                if (window.vehiclesManager && !window.vehiclesManager.isInitialized) {
                    window.vehiclesManager.init();
                }
            }, 800);
        }
    }

})();