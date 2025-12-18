// public/assets/js/expiring.js
// GERENCIADOR DE CONTRATOS À VENCER

window.contractsExpiringManager = (function() {
    'use strict';
    
    // Configurações
    const config = {
        apiUrl: '/bt-log-transportes/public/api/contracts.php',
        exportUrl: '/bt-log-transportes/public/api/export.php',
        searchDelay: 300
    };
    
    // Estado
    let state = {
        currentSearch: '',
        selectedContracts: new Set(),
        sortColumn: 'days_remaining',
        sortDirection: 'asc',
        currentFilters: {},
        urgentContracts: new Set()
    };
    
    // Inicialização
    function init() {
        console.log('⏰ Contracts Expiring Manager inicializado');
        
        setupEventListeners();
        setupTableInteractions();
        setupModalFix();
        initializeFilters();
        updateRealTimeCounters();
        highlightUrgentContracts();
        
        console.log('✅ Contracts Expiring Manager configurado com sucesso');
    }
    
    // Configurar Event Listeners
    function setupEventListeners() {
        // Busca
        const searchInput = document.getElementById('searchExpiring');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.currentSearch = e.target.value;
                    filterContracts();
                }, config.searchDelay);
            });
        }
        
        // Exportar
        const exportBtn = document.getElementById('exportExpiringBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', handleExport);
        }
        
        // Filtros
        const urgencyFilter = document.getElementById('filterUrgency');
        if (urgencyFilter) {
            urgencyFilter.addEventListener('change', (e) => {
                state.currentFilters.urgency = e.target.value;
                filterByUrgency(e.target.value);
            });
        }
        
        // Ordenação por cabeçalhos
        const headers = document.querySelectorAll('#expiringTable th');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const column = getColumnFromHeader(header);
                if (column) {
                    sortTable(column);
                }
            });
        });
        
        // Botão voltar
        const backBtn = document.querySelector('.header-actions .btn');
        if (backBtn && backBtn.textContent.includes('Voltar')) {
            backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'index.php?page=contracts&action=list';
            });
        }
    }
    
    // Corrigir eventos de modais
    function setupModalFix() {
        document.addEventListener('click', function(e) {
            // Botões de visualizar contrato
            if (e.target.closest('.btn-view-modern') || 
                (e.target.classList.contains('btn-view-modern') && e.target.tagName === 'BUTTON')) {
                const row = e.target.closest('tr');
                if (row && row.dataset.contractId) {
                    const contractId = row.dataset.contractId;
                    e.preventDefault();
                    window.contractsManager.viewContract(contractId);
                }
            }
            
            // Botões de editar
            if (e.target.closest('.btn-edit-modern') || 
                (e.target.classList.contains('btn-edit-modern') && e.target.tagName === 'BUTTON')) {
                const row = e.target.closest('tr');
                if (row && row.dataset.contractId) {
                    const contractId = row.dataset.contractId;
                    e.preventDefault();
                    window.contractsManager.editContract(contractId);
                }
            }
            
            // Botões de renovar
            if (e.target.closest('.btn-renew-modern') || 
                (e.target.classList.contains('btn-renew-modern') && e.target.tagName === 'BUTTON')) {
                const row = e.target.closest('tr');
                if (row && row.dataset.contractId) {
                    const contractId = row.dataset.contractId;
                    e.preventDefault();
                    window.contractsManager.renewContract(contractId);
                }
            }
            
            // Visualizar documento PDF
            if (e.target.closest('.btn-view-document-sm') || 
                (e.target.classList.contains('btn-view-document-sm') && e.target.tagName === 'BUTTON')) {
                const row = e.target.closest('tr');
                if (row && row.dataset.contractId) {
                    const contractId = row.dataset.contractId;
                    e.preventDefault();
                    window.contractsManager.viewDocument(contractId);
                }
            }
        });
    }
    
    // Configurar Interações da Tabela
    function setupTableInteractions() {
        const tbody = document.getElementById('expiringTableBody');
        if (!tbody) return;
        
        // Seleção de linhas
        tbody.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (!row || !row.dataset.contractId) return;
            
            // Ignorar cliques em botões
            if (e.target.closest('button') || e.target.closest('a') || 
                e.target.closest('.actions-toolbar-modern') || 
                e.target.closest('.btn-view-document-sm')) {
                return;
            }
            
            toggleRowSelection(row);
        });
    }
    
    // Inicializar Filtros
    function initializeFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('urgency')) {
            const urgencyFilter = document.getElementById('filterUrgency');
            if (urgencyFilter) {
                urgencyFilter.value = urlParams.get('urgency');
                state.currentFilters.urgency = urlParams.get('urgency');
                filterByUrgency(urlParams.get('urgency'));
            }
        }
    }
    
    // Filtrar Contratos
    function filterContracts() {
        const rows = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)');
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
            
            // Aplicar filtro de urgência
            if (shouldShow && state.currentFilters.urgency && state.currentFilters.urgency !== 'all') {
                const urgency = getRowUrgency(row);
                shouldShow = shouldShow && urgency === state.currentFilters.urgency;
            }
            
            // Aplicar visibilidade
            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });
        
        // Atualizar contador
        updateSearchCounter(visibleCount);
    }
    
    // Filtrar por urgência
    function filterByUrgency(urgency) {
        state.currentFilters.urgency = urgency;
        
        if (urgency === 'all') {
            // Mostrar todos
            const rows = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)');
            rows.forEach(row => row.style.display = '');
        } else {
            // Filtrar por urgência
            const rows = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)');
            rows.forEach(row => {
                const rowUrgency = getRowUrgency(row);
                row.style.display = rowUrgency === urgency ? '' : 'none';
            });
        }
        
        // Atualizar contador
        const visibleCount = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)[style*="display: table-row"], #expiringTableBody tr:not(.empty-row):not([style*="display: none"])').length;
        updateSearchCounter(visibleCount);
        
        showNotification(`Filtrado por: ${getUrgencyLabel(urgency)}`, 'info');
    }
    
    // Obter urgência da linha
    function getRowUrgency(row) {
        const urgencyClass = Array.from(row.classList).find(cls => 
            cls.startsWith('urgency-')
        );
        
        if (!urgencyClass) return '';
        return urgencyClass.replace('urgency-', '');
    }
    
    // Obter label de urgência
    function getUrgencyLabel(urgency) {
        switch(urgency) {
            case 'critical': return 'Crítico (≤ 7 dias)';
            case 'high': return 'Urgente (8-15 dias)';
            case 'medium': return 'Atenção (16-30 dias)';
            default: return 'Todos';
        }
    }
    
    // Atualizar contador de busca
    function updateSearchCounter(count) {
        let counter = document.getElementById('searchCounter');
        const totalRows = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)').length;
        
        if (!counter && (state.currentSearch || state.currentFilters.urgency)) {
            counter = document.createElement('div');
            counter.id = 'searchCounter';
            counter.className = 'search-counter';
            
            const searchBox = document.querySelector('.search-box');
            if (searchBox) {
                searchBox.parentNode.insertBefore(counter, searchBox.nextSibling);
            }
        }
        
        if (counter) {
            if (state.currentSearch || (state.currentFilters.urgency && state.currentFilters.urgency !== 'all')) {
                counter.textContent = `${count} de ${totalRows} contratos`;
                counter.style.display = 'block';
            } else {
                counter.style.display = 'none';
            }
        }
    }
    
    // Alternar seleção da linha
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
    
    // Atualizar badge de seleção
    function updateSelectionBadge() {
        let badge = document.getElementById('selectionBadge');
        
        if (!badge && state.selectedContracts.size > 0) {
            badge = document.createElement('div');
            badge.id = 'selectionBadge';
            badge.className = 'selection-badge';
            badge.innerHTML = `
                <span id="selectedCount">${state.selectedContracts.size}</span> contrato(s) selecionado(s)
                <button onclick="window.contractsExpiringManager.clearSelection()" class="btn-clear-selection">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.body.appendChild(badge);
            
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
    
    // Limpar seleção
    function clearSelection() {
        state.selectedContracts.clear();
        
        const selectedRows = document.querySelectorAll('.contract-row-selected');
        selectedRows.forEach(row => {
            row.classList.remove('contract-row-selected');
        });
        
        updateSelectionBadge();
        showNotification('Seleção limpa', 'info');
    }
    
    // Obter coluna do cabeçalho
    function getColumnFromHeader(header) {
        const text = header.textContent.toLowerCase();
        
        if (text.includes('contrato')) return 'contract';
        if (text.includes('vencimento')) return 'expiration';
        if (text.includes('dias')) return 'days_remaining';
        if (text.includes('valor')) return 'value';
        
        return null;
    }
    
    // Ordenar tabela
    function sortTable(column) {
        // Alternar direção se for a mesma coluna
        if (state.sortColumn === column) {
            state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            state.sortColumn = column;
            state.sortDirection = 'asc';
        }
        
        const tbody = document.getElementById('expiringTableBody');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
        
        rows.sort((a, b) => {
            let aValue, bValue;
            
            switch(column) {
                case 'contract':
                    aValue = a.querySelector('.contract-name-modern')?.textContent || '';
                    bValue = b.querySelector('.contract-name-modern')?.textContent || '';
                    break;
                case 'expiration':
                    aValue = a.querySelector('.expiration-date')?.textContent || '';
                    bValue = b.querySelector('.expiration-date')?.textContent || '';
                    break;
                case 'days_remaining':
                    const aDaysText = a.querySelector('.countdown-days')?.textContent || '0 dias';
                    const bDaysText = b.querySelector('.countdown-days')?.textContent || '0 dias';
                    aValue = parseInt(aDaysText.replace(' dias', '')) || 0;
                    bValue = parseInt(bDaysText.replace(' dias', '')) || 0;
                    break;
                case 'value':
                    const aValueText = a.querySelector('.value-amount-modern')?.textContent || 'R$ 0,00';
                    const bValueText = b.querySelector('.value-amount-modern')?.textContent || 'R$ 0,00';
                    aValue = parseFloat(aValueText.replace('R$ ', '').replace(/\./g, '').replace(',', '.'));
                    bValue = parseFloat(bValueText.replace('R$ ', '').replace(/\./g, '').replace(',', '.'));
                    break;
                default:
                    return 0;
            }
            
            if (state.sortDirection === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        // Reordenar linhas
        rows.forEach(row => {
            tbody.appendChild(row);
        });
        
        // Atualizar indicadores de ordenação
        updateSortHeaders(column);
        
        showNotification(`Tabela ordenada por ${getColumnLabel(column)} (${state.sortDirection === 'asc' ? 'crescente' : 'decrescente'})`, 'info');
    }
    
    // Obter label da coluna
    function getColumnLabel(column) {
        switch(column) {
            case 'contract': return 'Contrato';
            case 'expiration': return 'Vencimento';
            case 'days_remaining': return 'Dias Restantes';
            case 'value': return 'Valor';
            default: return column;
        }
    }
    
    // Atualizar cabeçalhos de ordenação
    function updateSortHeaders(column) {
        const headers = document.querySelectorAll('#expiringTable th');
        
        headers.forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
            
            const headerColumn = getColumnFromHeader(header);
            if (headerColumn === column) {
                header.classList.add(`sort-${state.sortDirection}`);
                
                // Adicionar/atualizar ícone
                let icon = header.querySelector('.sort-icon');
                if (!icon) {
                    icon = document.createElement('i');
                    icon.className = 'sort-icon fas';
                    header.appendChild(icon);
                }
                
                icon.className = `sort-icon fas fa-sort-${state.sortDirection === 'asc' ? 'up' : 'down'}`;
                icon.style.marginLeft = '8px';
            } else {
                // Remover ícones de outras colunas
                const icon = header.querySelector('.sort-icon');
                if (icon) {
                    icon.remove();
                }
            }
        });
    }
    
    // Atualizar contadores em tempo real
    function updateRealTimeCounters() {
        const rows = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)');
        
        rows.forEach(row => {
            updateRowUrgency(row);
        });
        
        // Agendar próxima atualização
        setTimeout(updateRealTimeCounters, 60000); // Atualizar a cada minuto
    }
    
    // Atualizar urgência da linha
    function updateRowUrgency(row) {
        const daysElement = row.querySelector('.countdown-days');
        const statusElement = row.querySelector('.countdown-status');
        const badgeElement = row.querySelector('.days-badge');
        const fillElement = row.querySelector('.countdown-fill');
        
        if (!daysElement || !statusElement || !badgeElement || !fillElement) return;
        
        const daysText = daysElement.textContent;
        const days = parseInt(daysText.replace(' dias', '')) || 0;
        
        // Determinar urgência
        let urgencyClass, statusText;
        
        if (days <= 7) {
            urgencyClass = 'critical';
            statusText = 'Crítico';
            state.urgentContracts.add(row.dataset.contractId);
        } else if (days <= 15) {
            urgencyClass = 'high';
            statusText = 'Urgente';
        } else if (days <= 30) {
            urgencyClass = 'medium';
            statusText = 'Atenção';
        } else {
            // Remover da tabela se não estiver mais nos próximos 30 dias?
            return;
        }
        
        // Atualizar classes
        row.className = row.className.replace(/urgency-\w+/, '').trim();
        row.classList.add(`urgency-${urgencyClass}`);
        
        badgeElement.className = badgeElement.className.replace(/days-\w+/, '').trim();
        badgeElement.classList.add(`days-${urgencyClass}`);
        
        statusElement.className = statusElement.className.replace(/status-\w+/, '').trim();
        statusElement.classList.add(`status-${urgencyClass}`);
        
        fillElement.className = fillElement.className.replace(/fill-\w+/, '').trim();
        fillElement.classList.add(`fill-${urgencyClass}`);
        
        statusElement.textContent = statusText;
        
        // Atualizar largura da barra
        const width = Math.max(0, Math.min(100, (30 - days) / 30 * 100));
        fillElement.style.width = `${width}%`;
        
        // Atualizar badge
        badgeElement.textContent = `${days} dias`;
    }
    
    // Destacar contratos urgentes
    function highlightUrgentContracts() {
        const urgentRows = document.querySelectorAll('.urgency-critical');
        
        if (urgentRows.length > 0) {
            // Adicionar animação de destaque
            urgentRows.forEach(row => {
                row.style.animation = 'pulseCritical 2s infinite';
            });
            
            // Mostrar alerta se houver contratos críticos
            const alertSection = document.querySelector('.alerts-section');
            if (alertSection) {
                const criticalAlert = document.createElement('div');
                criticalAlert.className = 'alert alert-error';
                criticalAlert.innerHTML = `
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Atenção Crítica!</h4>
                        <p>${urgentRows.length} contrato(s) com vencimento crítico (≤ 7 dias). Ação imediata necessária.</p>
                    </div>
                `;
                alertSection.appendChild(criticalAlert);
            }
        }
    }
    
    // Lidar com exportação
    async function handleExport() {
        if (state.selectedContracts.size > 0) {
            await exportSelectedContracts();
        } else {
            await exportVisibleContracts();
        }
    }
    
    // Exportar contratos selecionados
    async function exportSelectedContracts() {
        try {
            const contractIds = Array.from(state.selectedContracts);
            
            const response = await fetch(`${config.exportUrl}?type=expiring&ids=${contractIds.join(',')}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `contratos_vencimento_selecionados_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                showNotification(`${contractIds.length} contrato(s) exportado(s) com sucesso!`, 'success');
            } else {
                throw new Error('Erro ao exportar contratos');
            }
        } catch (error) {
            console.error('❌ Erro ao exportar contratos:', error);
            showNotification('Erro ao exportar contratos. Tente novamente.', 'error');
        }
    }
    
    // Exportar contratos visíveis
    async function exportVisibleContracts() {
        try {
            const visibleRows = document.querySelectorAll('#expiringTableBody tr:not(.empty-row)[style*="display: table-row"], #expiringTableBody tr:not(.empty-row):not([style*="display: none"])');
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
            
            const response = await fetch(`${config.exportUrl}?type=expiring&ids=${contractIds.join(',')}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `contratos_a_vencer_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                showNotification(`${contractIds.length} contrato(s) exportado(s) com sucesso!`, 'success');
            } else {
                throw new Error('Erro ao exportar contratos');
            }
        } catch (error) {
            console.error('❌ Erro ao exportar contratos:', error);
            showNotification('Erro ao exportar contratos. Tente novamente.', 'error');
        }
    }
    
    // Mostrar notificação
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
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Adicionar ao corpo
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
    
    // API pública
    return {
        init,
        clearSelection,
        filterByUrgency,
        sortTable,
        handleExport,
        showNotification
    };
})();

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    window.contractsExpiringManager.init();
});