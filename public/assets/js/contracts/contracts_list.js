// bt-log-transportes/public/assets/js/contracts/contracts_list.js
// GERENCIADOR DA LISTA DE CONTRATOS (APENAS LISTA) - VERSÃO COMPLETA CORRIGIDA

(function() {
    'use strict';
    
    console.log('📋 Contracts List Manager carregando...');
    
    // Configurações
    const config = {
        apiUrl: '/bt-log-transportes/public/api/contracts.php',
        itemsPerPage: 20,
        searchDelay: 300
    };
    
    // Estado
    let state = {
        currentPage: 1,
        totalPages: 1,
        currentSearch: '',
        currentFilters: {},
        selectedContracts: new Set(),
        isInitialized: false
    };
    
    // ✅ FUNÇÃO DE FALLBACK PARA BOTÕES (PRIMEIRO - GARANTIR FUNCIONALIDADE)
    function setupFallbackListeners() {
        console.log('🛡️ Configurando fallback listeners...');
        
        // Listener para botões de documento
        document.addEventListener('click', function(e) {
            const target = e.target;
            const button = target.closest('button');
            
            if (!button) return;
            
            // ✅ FALLBACK: Visualizar documento
            if (button.classList.contains('btn-view-document') || 
                (button.classList.contains('btn') && button.querySelector('.fa-eye'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const contractId = button.dataset.contractId || 
                                 button.closest('tr')?.dataset.contractId;
                
                console.log('👁️‍🗨️ Fallback: Visualizar documento, ID:', contractId);
                
                if (contractId) {
                    // Primeiro tentar usar o viewer se disponível
                    if (typeof window.contractsViewer !== 'undefined' && 
                        typeof window.contractsViewer.viewDocument === 'function') {
                        window.contractsViewer.viewDocument(contractId);
                    } else {
                        // Fallback: abrir modal básico
                        openBasicDocumentView(contractId);
                    }
                }
            }
            
            // ✅ FALLBACK: Download documento
            else if (button.classList.contains('btn-download-document') || 
                    (button.classList.contains('btn') && button.querySelector('.fa-download'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const fileName = button.dataset.filename || 
                               button.closest('tr')?.querySelector('[data-filename]')?.dataset.filename;
                
                console.log('📥 Fallback: Download documento, arquivo:', fileName);
                
                if (fileName) {
                    // Primeiro tentar usar o viewer se disponível
                    if (typeof window.contractsViewer !== 'undefined' && 
                        typeof window.contractsViewer.downloadDocument === 'function') {
                        window.contractsViewer.downloadDocument(fileName);
                    } else {
                        // Fallback: download direto
                        const url = `/bt-log-transportes/storage/contracts/${encodeURIComponent(fileName)}`;
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        
                        showNotification('Download iniciado', 'info');
                    }
                }
            }
            
            // ✅ FALLBACK: Visualizar contrato
            else if (button.classList.contains('btn-view-modern') || 
                    (button.classList.contains('action-btn-modern') && button.querySelector('.fa-eye'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const contractId = button.dataset.contractId || 
                                 button.closest('tr')?.dataset.contractId;
                
                console.log('📄 Fallback: Visualizar contrato, ID:', contractId);
                
                if (contractId) {
                    // Primeiro tentar usar o viewer se disponível
                    if (typeof window.contractsViewer !== 'undefined' && 
                        typeof window.contractsViewer.viewContract === 'function') {
                        window.contractsViewer.viewContract(contractId);
                    } else {
                        // Fallback: redirecionar
                        window.location.href = `/bt-log-transportes/public/index.php?page=contracts&action=view&id=${contractId}`;
                    }
                }
            }
            
            // ✅ FALLBACK: Editar contrato
            else if (button.classList.contains('btn-edit-modern') || 
                    (button.classList.contains('action-btn-modern') && button.querySelector('.fa-edit'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const contractId = button.dataset.contractId || 
                                 button.closest('tr')?.dataset.contractId;
                
                console.log('✏️ Fallback: Editar contrato, ID:', contractId);
                
                if (contractId) {
                    // Primeiro tentar usar o manager se disponível
                    if (typeof window.contractsManager !== 'undefined' && 
                        typeof window.contractsManager.editContract === 'function') {
                        window.contractsManager.editContract(contractId);
                    } else {
                        // Fallback: abrir modal de edição básico
                        openBasicEditModal(contractId);
                    }
                }
            }
            
            // ✅ FALLBACK: Renovar contrato
            else if (button.classList.contains('btn-renew-modern') || 
                    (button.classList.contains('action-btn-modern') && button.querySelector('.fa-redo'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const contractId = button.dataset.contractId || 
                                 button.closest('tr')?.dataset.contractId;
                
                console.log('🔄 Fallback: Renovar contrato, ID:', contractId);
                
                if (contractId) {
                    // Primeiro tentar usar o manager se disponível
                    if (typeof window.contractsManager !== 'undefined' && 
                        typeof window.contractsManager.renewContract === 'function') {
                        window.contractsManager.renewContract(contractId);
                    } else {
                        // Fallback: abrir modal de renovação básico
                        openBasicRenewModal(contractId);
                    }
                }
            }
            
            // ✅ FALLBACK: Deletar contrato
            else if (button.classList.contains('btn-delete-modern') || 
                    (button.classList.contains('action-btn-modern') && button.querySelector('.fa-trash'))) {
                e.preventDefault();
                e.stopPropagation();
                
                const contractId = button.dataset.contractId || 
                                 button.closest('tr')?.dataset.contractId;
                
                console.log('🗑️ Fallback: Deletar contrato, ID:', contractId);
                
                if (contractId && confirm('Tem certeza que deseja cancelar este contrato?\n\nEsta ação marcará o contrato como cancelado, mas manterá os dados no sistema.')) {
                    // Primeiro tentar usar o manager se disponível
                    if (typeof window.contractsManager !== 'undefined' && 
                        typeof window.contractsManager.deleteContract === 'function') {
                        window.contractsManager.deleteContract(contractId);
                    } else {
                        // Fallback: solicitação AJAX direta
                        deleteContractFallback(contractId);
                    }
                }
            }
        });
    }
    
    // ✅ FUNÇÃO FALLBACK PARA ABRIR DOCUMENTO
    function openBasicDocumentView(contractId) {
        console.log('📄 Abrindo documento básico para contrato:', contractId);
        
        fetch(`${config.apiUrl}?action=get&id=${contractId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar contrato');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data && data.data.file_url) {
                    // Abrir em nova aba
                    window.open(data.data.file_url, '_blank');
                    showNotification('Documento aberto em nova aba', 'info');
                } else {
                    showNotification('Documento não disponível para este contrato', 'warning');
                }
            })
            .catch(error => {
                console.error('Erro ao buscar documento:', error);
                showNotification('Erro ao carregar documento', 'error');
            });
    }
    
    // ✅ FUNÇÃO FALLBACK PARA ABRIR EDIÇÃO BÁSICA
    function openBasicEditModal(contractId) {
        console.log('✏️ Abrindo edição básica para contrato:', contractId);
        // Simplesmente redireciona para a página de edição
        window.location.href = `/bt-log-transportes/public/index.php?page=contracts&action=edit&id=${contractId}`;
    }
    
    // ✅ FUNÇÃO FALLBACK PARA ABRIR RENOVAÇÃO BÁSICA
    function openBasicRenewModal(contractId) {
        console.log('🔄 Abrindo renovação básica para contrato:', contractId);
        showNotification('Funcionalidade de renovação não disponível no momento', 'warning');
        // Poderia redirecionar para página específica ou mostrar modal simples
    }
    
    // ✅ FUNÇÃO FALLBACK PARA DELETAR
    function deleteContractFallback(contractId) {
        console.log('🗑️ Deletando contrato via fallback, ID:', contractId);
        
        const formData = new FormData();
        formData.append('id', contractId);
        
        fetch(`${config.apiUrl}?action=delete`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao cancelar contrato');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Recarregar página após 1 segundo
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Erro ao cancelar contrato');
            }
        })
        .catch(error => {
            console.error('Erro ao deletar:', error);
            showNotification(error.message || 'Erro ao cancelar contrato', 'error');
        });
    }
    
    // ✅ Inicialização
    function init() {
        if (state.isInitialized) {
            console.log('📋 Contracts List Manager já inicializado');
            return;
        }
        
        console.log('🚀 Contracts List Manager inicializando...');
        
        try {
            // ✅ CONFIGURAR FALLBACK PRIMEIRO (IMPORTANTE!)
            setupFallbackListeners();
            
            // Depois configurar o resto
            setupEventListeners();
            setupTableInteractions();
            initializeFilters();
            updateStatsCards();
            addDynamicStyles();
            
            state.isInitialized = true;
            console.log('✅ Contracts List Manager configurado com sucesso');
            
        } catch (error) {
            console.error('❌ Erro na inicialização:', error);
            showNotification('Erro ao inicializar lista de contratos', 'error');
        }
    }
    
    // ✅ Configurar Event Listeners
    function setupEventListeners() {
        // Busca
        const searchInput = document.getElementById('searchContracts');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.currentSearch = e.target.value;
                    state.currentPage = 1;
                    filterContracts();
                }, config.searchDelay);
            });
        }
        
        // Filtros
        const companyFilter = document.getElementById('companyFilter');
        const statusFilter = document.getElementById('filterStatus');
        const typeFilter = document.getElementById('filterType');
        
        if (companyFilter) {
            companyFilter.addEventListener('change', (e) => {
                state.currentFilters.company = e.target.value;
                state.currentPage = 1;
                filterContracts();
            });
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                state.currentFilters.status = e.target.value;
                state.currentPage = 1;
                filterContracts();
            });
        }
        
        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => {
                state.currentFilters.type = e.target.value;
                state.currentPage = 1;
                filterContracts();
            });
        }
        
        // Limpar Filtros
        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            clearBtn.addEventListener('click', clearFilters);
        }
        
        // Exportar
        const exportBtn = document.getElementById('exportContracts');
        if (exportBtn) {
            exportBtn.addEventListener('click', exportContracts);
        }
    }
    
    // ✅ Configurar Interações da Tabela
    function setupTableInteractions() {
        const tbody = document.getElementById('contractsTableBody');
        if (!tbody) return;
        
        // Seleção de linhas
        tbody.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (!row || !row.dataset.contractId) return;
            
            // Ignorar cliques em botões
            if (e.target.closest('button') || e.target.closest('a')) return;
            
            toggleRowSelection(row);
        });
        
        // Os botões são tratados pelo setupFallbackListeners()
    }
    
    // ✅ Inicializar Filtros
    function initializeFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('company')) {
            const companyFilter = document.getElementById('companyFilter');
            if (companyFilter) {
                companyFilter.value = urlParams.get('company');
                state.currentFilters.company = urlParams.get('company');
            }
        }
        
        if (urlParams.has('status')) {
            const statusFilter = document.getElementById('filterStatus');
            if (statusFilter) {
                statusFilter.value = urlParams.get('status');
                state.currentFilters.status = urlParams.get('status');
            }
        }
        
        if (urlParams.has('type')) {
            const typeFilter = document.getElementById('filterType');
            if (typeFilter) {
                typeFilter.value = urlParams.get('type');
                state.currentFilters.type = urlParams.get('type');
            }
        }
        
        // Aplicar filtros iniciais
        if (Object.keys(state.currentFilters).length > 0) {
            setTimeout(() => filterContracts(), 100);
        }
    }
    
    // ✅ Filtrar Contratos
    function filterContracts() {
        const rows = document.querySelectorAll('#contractsTableBody tr:not(.empty-row)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const contractId = row.dataset.contractId;
            if (!contractId) return;
            
            let shouldShow = true;
            
            // Aplicar busca
            if (state.currentSearch) {
                const text = row.textContent.toLowerCase();
                shouldShow = shouldShow && text.includes(state.currentSearch.toLowerCase());
            }
            
            // Aplicar filtros
            if (shouldShow && state.currentFilters.company && state.currentFilters.company !== 'all') {
                const companyName = row.querySelector('.contract-company')?.textContent || '';
                const companySelect = document.getElementById('companyFilter');
                const selectedOption = companySelect ? companySelect.options[companySelect.selectedIndex] : null;
                const selectedCompanyName = selectedOption ? selectedOption.textContent : '';
                
                shouldShow = shouldShow && companyName.includes(selectedCompanyName);
            }
            
            if (shouldShow && state.currentFilters.status && state.currentFilters.status !== 'all') {
                const statusElement = row.querySelector('.status-pill-modern');
                if (statusElement) {
                    const statusText = statusElement.textContent.toLowerCase();
                    const statusMap = {
                        'active': 'ativo',
                        'expired': 'vencido',
                        'expiring': 'à vencer',
                        'draft': 'rascunho',
                        'cancelled': 'cancelado'
                    };
                    
                    const expectedStatus = statusMap[state.currentFilters.status] || state.currentFilters.status;
                    shouldShow = shouldShow && statusText.includes(expectedStatus);
                }
            }
            
            if (shouldShow && state.currentFilters.type && state.currentFilters.type !== 'all') {
                const partyTypeElement = row.querySelector('.party-type-modern');
                if (partyTypeElement) {
                    const partyType = partyTypeElement.textContent.toLowerCase();
                    const typeMap = {
                        'client': 'cliente',
                        'supplier': 'fornecedor'
                    };
                    
                    const expectedType = typeMap[state.currentFilters.type] || state.currentFilters.type;
                    shouldShow = shouldShow && partyType.includes(expectedType);
                }
            }
            
            // Aplicar visibilidade
            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });
        
        // Atualizar contador
        updateSearchCounter(visibleCount);
        
        // Atualizar paginação
        updatePagination(visibleCount);
        
        return visibleCount;
    }
    
    // ✅ Atualizar contador de busca
    function updateSearchCounter(count) {
        const counter = document.getElementById('searchCounter');
        if (!counter) {
            // Criar contador se não existir
            const tableHeader = document.querySelector('.contracts-table-header');
            if (tableHeader) {
                const counterDiv = document.createElement('div');
                counterDiv.id = 'searchCounter';
                counterDiv.className = 'search-counter';
                counterDiv.style.cssText = 'font-size: 0.9rem; color: var(--color-gray); margin-top: 0.5rem;';
                tableHeader.appendChild(counterDiv);
            } else {
                return;
            }
        }
        
        const finalCounter = document.getElementById('searchCounter');
        const totalRows = document.querySelectorAll('#contractsTableBody tr:not(.empty-row)').length;
        
        if (state.currentSearch || Object.keys(state.currentFilters).some(key => 
            state.currentFilters[key] && state.currentFilters[key] !== 'all')) {
            finalCounter.textContent = `${count} de ${totalRows} contratos encontrados`;
            finalCounter.style.display = 'block';
        } else {
            finalCounter.style.display = 'none';
        }
    }
    
    // ✅ Atualizar paginação
    function updatePagination(visibleCount) {
        state.totalPages = Math.ceil(visibleCount / config.itemsPerPage);
        
        // Mostrar/esconder linhas baseado na página
        const rows = document.querySelectorAll('#contractsTableBody tr:not(.empty-row)');
        let visibleIndex = 0;
        
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                visibleIndex++;
                const shouldShow = visibleIndex > (state.currentPage - 1) * config.itemsPerPage && 
                                  visibleIndex <= state.currentPage * config.itemsPerPage;
                row.style.display = shouldShow ? '' : 'none';
            }
        });
        
        // Atualizar controles de paginação
        updatePaginationControls(visibleCount);
    }
    
    // ✅ Atualizar controles de paginação
    function updatePaginationControls(totalItems) {
        let paginationContainer = document.querySelector('.pagination');
        
        // Criar container se não existir
        if (!paginationContainer) {
            const tableFooter = document.querySelector('.table-footer');
            if (tableFooter) {
                paginationContainer = document.createElement('div');
                paginationContainer.className = 'pagination';
                tableFooter.appendChild(paginationContainer);
            } else {
                return;
            }
        }
        
        // Limpar paginação existente
        paginationContainer.innerHTML = '';
        
        // Se não houver itens suficientes para paginação, não mostrar
        if (totalItems <= config.itemsPerPage) {
            paginationContainer.style.display = 'none';
            return;
        }
        
        paginationContainer.style.display = 'flex';
        
        // Botão anterior
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.dataset.action = 'prev';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = state.currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (state.currentPage > 1) {
                state.currentPage--;
                filterContracts();
                scrollToTop();
            }
        });
        paginationContainer.appendChild(prevBtn);
        
        // Números de página
        const maxVisiblePages = 5;
        let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(state.totalPages, startPage + maxVisiblePages - 1);
        
        // Ajustar início se necessário
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // Primeira página
        if (startPage > 1) {
            const firstPageBtn = createPageButton(1);
            paginationContainer.appendChild(firstPageBtn);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '...';
                paginationContainer.appendChild(ellipsis);
            }
        }
        
        // Páginas do meio
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = createPageButton(i);
            paginationContainer.appendChild(pageBtn);
        }
        
        // Última página
        if (endPage < state.totalPages) {
            if (endPage < state.totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '...';
                paginationContainer.appendChild(ellipsis);
            }
            
            const lastPageBtn = createPageButton(state.totalPages);
            paginationContainer.appendChild(lastPageBtn);
        }
        
        // Botão próximo
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.dataset.action = 'next';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = state.currentPage === state.totalPages;
        nextBtn.addEventListener('click', () => {
            if (state.currentPage < state.totalPages) {
                state.currentPage++;
                filterContracts();
                scrollToTop();
            }
        });
        paginationContainer.appendChild(nextBtn);
        
        // Atualizar informações
        updatePaginationInfo(totalItems);
    }
    
    // ✅ Criar botão de página
    function createPageButton(pageNumber) {
        const button = document.createElement('button');
        button.className = pageNumber === state.currentPage ? 'pagination-current' : 'pagination-btn';
        button.dataset.action = 'page';
        button.dataset.page = pageNumber;
        button.textContent = pageNumber;
        
        if (pageNumber !== state.currentPage) {
            button.addEventListener('click', () => {
                state.currentPage = pageNumber;
                filterContracts();
                scrollToTop();
            });
        }
        
        return button;
    }
    
    // ✅ Atualizar informações de paginação
    function updatePaginationInfo(totalItems) {
        const startItem = Math.min(totalItems, (state.currentPage - 1) * config.itemsPerPage + 1);
        const endItem = Math.min(totalItems, state.currentPage * config.itemsPerPage);
        
        let infoElement = document.querySelector('.pagination-info');
        if (!infoElement) {
            const tableFooter = document.querySelector('.table-footer');
            if (tableFooter) {
                infoElement = document.createElement('div');
                infoElement.className = 'pagination-info';
                tableFooter.insertBefore(infoElement, document.querySelector('.pagination'));
            } else {
                return;
            }
        }
        
        infoElement.textContent = `Mostrando ${startItem}-${endItem} de ${totalItems} contratos`;
    }
    
    // ✅ Carregar página específica
    function loadPage(page) {
        state.currentPage = page;
        filterContracts();
        scrollToTop();
    }
    
    // ✅ Rolar para o topo
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // ✅ Alternar seleção da linha
    function toggleRowSelection(row) {
        const contractId = row.dataset.contractId;
        if (!contractId) return;
        
        if (state.selectedContracts.has(contractId)) {
            state.selectedContracts.delete(contractId);
            row.classList.remove('contract-row-selected');
        } else {
            state.selectedContracts.add(contractId);
            row.classList.add('contract-row-selected');
        }
        
        updateSelectionBadge();
    }
    
    // ✅ Atualizar badge de seleção
    function updateSelectionBadge() {
        let badge = document.getElementById('selectionBadge');
        
        if (!badge && state.selectedContracts.size > 0) {
            badge = document.createElement('div');
            badge.id = 'selectionBadge';
            badge.className = 'selection-badge';
            badge.innerHTML = `
                <span id="selectedCount">${state.selectedContracts.size}</span> contrato(s) selecionado(s)
                <button class="btn-clear-selection">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.body.appendChild(badge);
            
            // Adicionar evento ao botão de limpar
            const clearBtn = badge.querySelector('.btn-clear-selection');
            if (clearBtn) {
                clearBtn.addEventListener('click', clearSelection);
            }
            
            setTimeout(() => {
                badge.classList.add('show');
            }, 10);
        }
        
        if (badge) {
            if (state.selectedContracts.size > 0) {
                const countSpan = document.getElementById('selectedCount');
                if (countSpan) {
                    countSpan.textContent = state.selectedContracts.size;
                }
                badge.classList.add('show');
            } else {
                badge.classList.remove('show');
                setTimeout(() => {
                    if (badge.parentNode) {
                        badge.remove();
                    }
                }, 300);
            }
        }
    }
    
    // ✅ Limpar seleção
    function clearSelection() {
        state.selectedContracts.clear();
        
        const selectedRows = document.querySelectorAll('.contract-row-selected');
        selectedRows.forEach(row => {
            row.classList.remove('contract-row-selected');
        });
        
        updateSelectionBadge();
        showNotification('Seleção limpa', 'info');
    }
    
    // ✅ Ordenar tabela
    function sortTable(tableId, columnIndex) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
        
        rows.sort((a, b) => {
            const aText = a.children[columnIndex].textContent.trim();
            const bText = b.children[columnIndex].textContent.trim();
            
            // Tentar converter para números se possível
            const aNum = extractNumber(aText);
            const bNum = extractNumber(bText);
            
            if (aNum !== null && bNum !== null) {
                return aNum - bNum;
            }
            
            // Comparar datas
            const aDate = parseDate(aText);
            const bDate = parseDate(bText);
            
            if (aDate && bDate) {
                return aDate - bDate;
            }
            
            // Comparação de texto
            return aText.localeCompare(bText);
        });
        
        // Reordenar linhas
        rows.forEach(row => {
            tbody.appendChild(row);
        });
        
        // Atualizar indicador de ordenação
        updateSortIndicator(tableId, columnIndex);
    }
    
    // ✅ Extrair número de texto
    function extractNumber(text) {
        const match = text.match(/[\d,.]+/);
        if (!match) return null;
        
        const num = parseFloat(match[0].replace(/\./g, '').replace(',', '.'));
        return isNaN(num) ? null : num;
    }
    
    // ✅ Analisar data
    function parseDate(text) {
        const formats = [
            /(\d{2})\/(\d{2})\/(\d{4})/, // dd/mm/yyyy
            /(\d{4})-(\d{2})-(\d{2})/,   // yyyy-mm-dd
        ];
        
        for (const format of formats) {
            const match = text.match(format);
            if (match) {
                if (match[3] && match[3].length === 4) {
                    // dd/mm/yyyy
                    return new Date(match[3], match[2] - 1, match[1]);
                } else {
                    // yyyy-mm-dd
                    return new Date(match[1], match[2] - 1, match[3]);
                }
            }
        }
        
        return null;
    }
    
    // ✅ Atualizar indicador de ordenação
    function updateSortIndicator(tableId, columnIndex) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.classList.remove('sort-asc', 'sort-desc');
            
            if (index === columnIndex) {
                header.classList.add('sort-asc');
            }
        });
    }
    
    // ✅ Limpar filtros
    function clearFilters() {
        // Resetar valores dos filtros
        const companyFilter = document.getElementById('companyFilter');
        const statusFilter = document.getElementById('filterStatus');
        const typeFilter = document.getElementById('filterType');
        const searchInput = document.getElementById('searchContracts');
        
        if (companyFilter) companyFilter.value = 'all';
        if (statusFilter) statusFilter.value = 'all';
        if (typeFilter) typeFilter.value = 'all';
        if (searchInput) searchInput.value = '';
        
        // Resetar estado
        state.currentSearch = '';
        state.currentFilters = {};
        state.currentPage = 1;
        
        // Recarregar página sem parâmetros
        const url = new URL(window.location.href);
        const paramsToRemove = ['company', 'status', 'type', 'page', 'search'];
        
        paramsToRemove.forEach(param => {
            url.searchParams.delete(param);
        });
        
        // Recarregar filtros
        filterContracts();
        
        // Mostrar notificação
        showNotification('Filtros limpos', 'info');
    }
    
    // ✅ Exportar contratos
    function exportContracts() {
        if (state.selectedContracts.size > 0) {
            // Exportar contratos selecionados
            exportSelectedContracts();
        } else {
            // Exportar todos os contratos visíveis
            exportVisibleContracts();
        }
    }
    
    // ✅ Exportar contratos selecionados
    async function exportSelectedContracts() {
        try {
            const contractIds = Array.from(state.selectedContracts);
            
            showNotification(`Preparando exportação de ${contractIds.length} contrato(s)...`, 'info');
            
            // Simulação de download
            setTimeout(() => {
                const data = `Contratos selecionados (${contractIds.length}):\n` + 
                            contractIds.map(id => `Contrato ID: ${id}`).join('\n');
                
                const blob = new Blob([data], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `contratos_selecionados_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                showNotification(`${contractIds.length} contrato(s) exportado(s) com sucesso!`, 'success');
            }, 1000);
            
        } catch (error) {
            console.error('❌ Erro ao exportar contratos:', error);
            showNotification('Erro ao exportar contratos. Tente novamente.', 'error');
        }
    }
    
    // ✅ Exportar contratos visíveis
    async function exportVisibleContracts() {
        try {
            const visibleRows = document.querySelectorAll('#contractsTableBody tr:not(.empty-row)[style*="display: table-row"], #contractsTableBody tr:not(.empty-row):not([style*="display: none"])');
            const contractIds = [];
            
            visibleRows.forEach(row => {
                const contractId = row.getAttribute('data-contract-id');
                if (contractId) {
                    contractIds.push(contractId);
                }
            });
            
            if (contractIds.length === 0) {
                showNotification('Nenhum contrato para exportar', 'warning');
                return;
            }
            
            showNotification(`Preparando exportação de ${contractIds.length} contrato(s)...`, 'info');
            
            // Simulação de download
            setTimeout(() => {
                const data = `Contratos visíveis (${contractIds.length}):\n` + 
                            contractIds.map(id => `Contrato ID: ${id}`).join('\n');
                
                const blob = new Blob([data], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `contratos_lista_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                showNotification(`${contractIds.length} contrato(s) exportado(s) com sucesso!`, 'success');
            }, 1000);
            
        } catch (error) {
            console.error('❌ Erro ao exportar contratos:', error);
            showNotification('Erro ao exportar contratos. Tente novamente.', 'error');
        }
    }
    
    // ✅ Atualizar cards de estatísticas
    function updateStatsCards() {
        const stats = document.querySelectorAll('.stat-card-discreet');
        
        stats.forEach(stat => {
            stat.addEventListener('click', () => {
                const statId = stat.id;
                
                switch(statId) {
                    case 'statTotalContracts':
                        document.getElementById('filterStatus').value = 'all';
                        break;
                    case 'statExpiringSoon':
                        document.getElementById('filterStatus').value = 'expiring';
                        break;
                    case 'statTotalValue':
                        sortTable('contractsTable', 3);
                        showNotification('Tabela ordenada por valor', 'info');
                        return; // Não aplicar filtros
                    case 'statByType':
                        // Não fazer nada específico, apenas clicável
                        break;
                }
                
                filterContracts();
                showNotification(`Filtro aplicado: ${statId.replace('stat', '').replace(/([A-Z])/g, ' $1')}`, 'info');
            });
        });
    }
    
    // ✅ Mostrar notificação
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
        
        document.body.appendChild(notification);
        
        // Mostrar com animação
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remover automaticamente
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, duration);
    }
    
    // ✅ Adicionar CSS para componentes dinâmicos
    function addDynamicStyles() {
        if (document.getElementById('contracts-list-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'contracts-list-styles';
        style.textContent = `
            .contract-row-selected {
                background-color: rgba(255, 107, 0, 0.1) !important;
                box-shadow: inset 0 0 0 2px var(--color-primary);
            }
            
            .selection-badge {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--color-primary);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-lg);
                display: flex;
                align-items: center;
                gap: 1rem;
                z-index: 1000;
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s ease;
            }
            
            .selection-badge.show {
                transform: translateY(0);
                opacity: 1;
            }
            
            .btn-clear-selection {
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .btn-clear-selection:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            
            .search-counter {
                font-size: 0.9rem;
                color: var(--color-gray);
                margin-top: 0.5rem;
            }
            
            .pagination {
                display: flex;
                gap: 0.5rem;
                align-items: center;
            }
            
            .pagination-btn {
                padding: 0.5rem 1rem;
                border: 1px solid var(--color-gray-light);
                background: var(--color-white);
                color: var(--color-gray-dark);
                border-radius: var(--border-radius);
                cursor: pointer;
                font-size: 0.9rem;
                transition: all 0.3s ease;
                min-width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .pagination-btn:hover:not(:disabled) {
                background: var(--color-primary);
                color: var(--color-white);
                border-color: var(--color-primary);
            }
            
            .pagination-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            .pagination-current {
                padding: 0.5rem 1rem;
                background: var(--color-primary);
                color: var(--color-white);
                border-radius: var(--border-radius);
                font-weight: 600;
                font-size: 0.9rem;
                min-width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .pagination-ellipsis {
                padding: 0.5rem;
                color: var(--color-gray);
            }
            
            .sort-asc::after {
                content: ' ↑';
                font-size: 0.8rem;
                color: var(--color-primary);
            }
            
            .sort-desc::after {
                content: ' ↓';
                font-size: 0.8rem;
                color: var(--color-primary);
            }
            
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--color-white);
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-lg);
                padding: 1rem 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                min-width: 300px;
                max-width: 400px;
                z-index: 9999;
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
                border-left: 4px solid;
            }
            
            .notification-toast.show {
                transform: translateX(0);
                opacity: 1;
            }
            
            .notification-toast.success {
                border-left-color: var(--color-success);
            }
            
            .notification-toast.error {
                border-left-color: var(--color-error);
            }
            
            .notification-toast.warning {
                border-left-color: var(--color-warning);
            }
            
            .notification-toast.info {
                border-left-color: var(--color-info);
            }
            
            .notification-content {
                flex: 1;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            
            .notification-content i {
                font-size: 1.25rem;
            }
            
            .notification-content i.fa-check-circle {
                color: var(--color-success);
            }
            
            .notification-content i.fa-exclamation-circle {
                color: var(--color-error);
            }
            
            .notification-content i.fa-exclamation-triangle {
                color: var(--color-warning);
            }
            
            .notification-content i.fa-info-circle {
                color: var(--color-info);
            }
            
            .notification-close {
                background: none;
                border: none;
                color: var(--color-gray);
                cursor: pointer;
                font-size: 1rem;
                padding: 0.25rem;
                border-radius: var(--border-radius);
                transition: all 0.3s ease;
            }
            
            .notification-close:hover {
                background: var(--color-gray-light);
                color: var(--color-black);
            }
        `;
        
        document.head.appendChild(style);
    }
    
    // ✅ FUNÇÃO PARA INICIALIZAR A PÁGINA
    function initializePage() {
        console.log('🌐 Inicializando página de contratos (Lista)...');
        
        // Verificar se os elementos necessários existem
        const hasContractsTable = document.getElementById('contractsTable') !== null;
        
        console.log('Elementos encontrados:', {
            contractsTable: hasContractsTable
        });
        
        // Inicializar list manager
        init();
        
        console.log('✅ Página de contratos (Lista) inicializada');
    }
    
    // API pública - Exportar para o escopo global
    window.contractsListManager = {
        init,
        initializePage,
        filterContracts,
        clearSelection,
        clearFilters,
        exportContracts,
        showNotification,
        loadPage,
        sortTable,
        // ✅ EXPORTAR FUNÇÕES DE FALLBACK
        openBasicDocumentView,
        deleteContractFallback
    };
    
    console.log('📋 Contracts List Manager exportado para window.contractsListManager');
    
    // ✅ IMPORTANTE: Configurar fallback listeners imediatamente
    // Isso garante que os botões funcionem mesmo antes da inicialização completa
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM carregado, configurando fallback listeners imediatamente...');
            setupFallbackListeners();
            console.log('📄 DOM carregado, inicializando Contracts List Manager...');
            setTimeout(initializePage, 100);
        });
    } else {
        console.log('📄 DOM já carregado, configurando fallback listeners imediatamente...');
        setupFallbackListeners();
        console.log('📄 DOM já carregado, inicializando Contracts List Manager agora...');
        setTimeout(initializePage, 100);
    }
    
})();