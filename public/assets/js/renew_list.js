// public/assets/js/contracts_renew.js
// GERENCIADOR DE RENOVAÇÃO DE CONTRATOS

window.contractsRenewManager = (function() {
    'use strict';
    
    // Configurações
    const config = {
        apiUrl: '/bt-log-transportes/public/api/contracts.php',
        searchDelay: 300
    };
    
    // Estado
    let state = {
        currentSearch: '',
        selectedContracts: new Set(),
        currentFilters: {},
        renewalData: [],
        recentRenewals: []
    };
    
    // Inicialização
    function init() {
        console.log('🔄 Contracts Renew Manager inicializado');
        
        setupEventListeners();
        loadRenewalData();
        loadRecentRenewals();
        
        console.log('✅ Contracts Renew Manager configurado com sucesso');
    }
    
    // Configurar Event Listeners
    function setupEventListeners() {
        // Busca
        const searchInput = document.getElementById('searchRenewal');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.currentSearch = e.target.value;
                    filterRenewalContracts();
                }, config.searchDelay);
            });
        }
        
        // Filtros
        const companyFilter = document.getElementById('renewCompanyFilter');
        const statusFilter = document.getElementById('renewStatusFilter');
        
        if (companyFilter) {
            companyFilter.addEventListener('change', (e) => {
                state.currentFilters.company = e.target.value;
                filterRenewalContracts();
            });
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                state.currentFilters.status = e.target.value;
                filterRenewalContracts();
            });
        }
        
        // Limpar Filtros
        const resetBtn = document.getElementById('resetRenewalFilters');
        if (resetBtn) {
            resetBtn.addEventListener('click', resetRenewalFilters);
        }
        
        // Botão voltar
        const backBtn = document.querySelector('.header-actions .btn');
        if (backBtn && backBtn.textContent.includes('Voltar')) {
            backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'index.php?page=contracts&action=list';
            });
        }
        
        // Carregar mais renovações
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-load-more')) {
                loadMoreRenewals();
            }
        });
    }
    
    // Carregar dados de renovação
    async function loadRenewalData() {
        try {
            showLoading('#renewalTableBody');
            
            const urlParams = new URLSearchParams();
            if (state.currentFilters.company) {
                urlParams.append('company_id', state.currentFilters.company);
            }
            if (state.currentFilters.status) {
                urlParams.append('status', state.currentFilters.status);
            }
            
            const response = await fetch(`${config.apiUrl}?action=get_renewal_data&${urlParams.toString()}`);
            const result = await response.json();
            
            if (result.success) {
                state.renewalData = result.data;
                renderRenewalTable();
            } else {
                showError('Erro ao carregar dados de renovação: ' + result.message);
            }
        } catch (error) {
            console.error('❌ Erro ao carregar dados de renovação:', error);
            showError('Erro ao carregar dados de renovação');
        }
    }
    
    // Carregar renovações recentes
    async function loadRecentRenewals() {
        try {
            showLoading('#recentRenewals');
            
            const response = await fetch(`${config.apiUrl}?action=get_recent_renewals`);
            const result = await response.json();
            
            if (result.success) {
                state.recentRenewals = result.data;
                renderRecentRenewals();
            } else {
                showError('Erro ao carregar renovações recentes: ' + result.message);
            }
        } catch (error) {
            console.error('❌ Erro ao carregar renovações recentes:', error);
            showError('Erro ao carregar renovações recentes');
        }
    }
    
    // Carregar mais renovações
    async function loadMoreRenewals() {
        try {
            const btn = document.querySelector('.btn-load-more');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
                btn.disabled = true;
            }
            
            const response = await fetch(`${config.apiUrl}?action=get_more_renewals&offset=${state.recentRenewals.length}`);
            const result = await response.json();
            
            if (result.success) {
                state.recentRenewals = [...state.recentRenewals, ...result.data];
                renderRecentRenewals();
            }
            
            if (btn) {
                btn.innerHTML = '<i class="fas fa-plus"></i> Carregar Mais';
                btn.disabled = false;
            }
        } catch (error) {
            console.error('❌ Erro ao carregar mais renovações:', error);
            showError('Erro ao carregar mais renovações');
        }
    }
    
    // Renderizar tabela de renovação
    function renderRenewalTable() {
        const tbody = document.getElementById('renewalTableBody');
        if (!tbody) return;
        
        if (state.renewalData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state-modern">
                            <div class="empty-icon-modern">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Nenhum Contrato para Renovar</h3>
                            <p>Todos os contratos estão em dia ou não há contratos que necessitem de renovação no momento.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        let filteredData = state.renewalData;
        
        // Aplicar busca
        if (state.currentSearch) {
            const searchTerm = state.currentSearch.toLowerCase();
            filteredData = filteredData.filter(contract => 
                contract.title.toLowerCase().includes(searchTerm) ||
                contract.contract_number.toLowerCase().includes(searchTerm) ||
                contract.company_name.toLowerCase().includes(searchTerm)
            );
        }
        
        // Aplicar filtros
        if (state.currentFilters.company && state.currentFilters.company !== 'all') {
            filteredData = filteredData.filter(contract => 
                contract.company_id == state.currentFilters.company
            );
        }
        
        if (state.currentFilters.status && state.currentFilters.status !== 'all') {
            filteredData = filteredData.filter(contract => 
                contract.renewal_status === state.currentFilters.status
            );
        }
        
        if (filteredData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state-modern">
                            <div class="empty-icon-modern">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>Nenhum resultado encontrado</h3>
                            <p>Não foram encontrados contratos com os critérios de busca/filtro aplicados.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = filteredData.map(contract => `
            <tr data-contract-id="${contract.id}">
                <td>
                    <div class="contract-card-modern">
                        <div class="contract-avatar-modern" style="background: linear-gradient(135deg, ${contract.company_color || '#FF6B00'}, ${contract.company_color || '#E55A00'});">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="contract-info-modern">
                            <div class="contract-name-modern">${escapeHtml(contract.title)}</div>
                            <div class="contract-details-modern">
                                <span class="contract-number">
                                    <i class="fas fa-hashtag"></i>
                                    ${escapeHtml(contract.contract_number)}
                                </span>
                                <span class="contract-company">
                                    <i class="fas fa-building"></i>
                                    ${escapeHtml(contract.company_name)}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="expiration-card-modern">
                        <div class="expiration-date">
                            <i class="fas fa-calendar-times"></i>
                            ${formatDate(contract.end_date)}
                        </div>
                        <div class="expiration-days">
                            <span class="days-badge days-${getUrgencyClass(contract)}">
                                ${getDaysRemaining(contract.end_date)} dias
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="status-pill-modern ${getRenewalStatusClass(contract)}">
                        <i class="fas fa-${getRenewalStatusIcon(contract)}"></i>
                        ${getRenewalStatusText(contract)}
                    </span>
                </td>
                <td>
                    ${contract.last_renewal_date ? `
                        <div class="renewal-info">
                            <div class="renewal-date">${formatDate(contract.last_renewal_date)}</div>
                            <div class="renewal-user">${contract.last_renewal_user || 'Sistema'}</div>
                        </div>
                    ` : '<span class="no-renewal">Nunca renovado</span>'}
                </td>
                <td>
                    <div class="suggestion-date">
                        <i class="fas fa-calendar-check"></i>
                        ${getNextRenewalSuggestion(contract)}
                    </div>
                </td>
                <td>
                    <div class="actions-toolbar-modern">
                        <button class="action-btn-modern btn-renew-modern" 
                                onclick="startRenewalProcess(${contract.id})"
                                title="Iniciar Renovação">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="action-btn-modern btn-view-modern" 
                                onclick="window.contractsManager.viewContract(${contract.id})"
                                title="Visualizar Contrato">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn-modern btn-schedule-modern" 
                                onclick="scheduleRenewal(${contract.id})"
                                title="Agendar Renovação">
                            <i class="fas fa-calendar-plus"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    // Renderizar renovações recentes
    function renderRecentRenewals() {
		const container = document.getElementById('recentRenewals');
		if (!container) return;
		
		if (state.recentRenewals.length === 0) {
			container.innerHTML = `
				<div class="empty-state-modern" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; text-align: center; width: 100%;">
					<div class="empty-icon-modern">
						<i class="fas fa-history"></i>
					</div>
					<h3 style="text-align: center; width: 100%;">Nenhuma Renovação Recente</h3>
					<p style="text-align: center; width: 100%; max-width: 400px; margin: 0 auto;">Não há registros de renovações nos últimos 30 dias.</p>
				</div>
			`;
			return;
		}
		
		container.innerHTML = state.recentRenewals.map(renewal => `
			<div class="renewal-card">
				<div class="renewal-header">
					<h4 class="renewal-title">${escapeHtml(renewal.contract_title)}</h4>
					<span class="renewal-date">${formatDate(renewal.renewal_date)}</span>
					<div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; color: var(--color-gray); margin-top: 0.25rem;">
						<span>${escapeHtml(renewal.company_name)}</span>
						<span>•</span>
						<span>${escapeHtml(renewal.user_name)}</span>
					</div>
				</div>
				<div class="renewal-info">
					<div class="renewal-item">
						<span class="renewal-label">Contrato:</span>
						<span class="renewal-value">${escapeHtml(renewal.contract_number)}</span>
					</div>
					<div class="renewal-item">
						<span class="renewal-label">Vencimento Anterior:</span>
						<span class="renewal-value">${formatDate(renewal.old_end_date)}</span>
					</div>
					<div class="renewal-item">
						<span class="renewal-label">Novo Vencimento:</span>
						<span class="renewal-value success">${formatDate(renewal.new_end_date)}</span>
					</div>
					<div class="renewal-item">
						<span class="renewal-label">Status:</span>
						<span class="renewal-value ${renewal.status === 'concluída' ? 'success' : 'warning'}">
							${renewal.status === 'concluída' ? 'Concluída' : 'Pendente'}
						</span>
					</div>
				</div>
				<div class="renewal-actions">
					<button class="btn-view-details" onclick="viewRenewalDetails(${renewal.id})">
						<i class="fas fa-eye"></i> Detalhes
					</button>
					<button class="btn-renew-again" onclick="renewAgain(${renewal.contract_id})">
						<i class="fas fa-redo"></i> Renovar Novamente
					</button>
				</div>
			</div>
		`).join('');
		
		// Adicionar botão "Carregar Mais" se houver mais dados
		if (state.recentRenewals.length % 5 === 0 && state.recentRenewals.length > 0) {
			container.innerHTML += `
				<div class="load-more-container">
					<button class="btn-load-more">
						<i class="fas fa-plus"></i> Carregar Mais Renovações
					</button>
				</div>
			`;
		}
	}
		
    // Filtrar contratos para renovação
    function filterRenewalContracts() {
        renderRenewalTable();
        updateSearchCounter();
    }
    
    // Atualizar contador de busca
    function updateSearchCounter() {
        const counter = document.getElementById('searchCounter');
        if (!counter) return;
        
        const visibleRows = document.querySelectorAll('#renewalTableBody tr[data-contract-id]');
        const totalRows = state.renewalData.length;
        
        if (state.currentSearch || Object.keys(state.currentFilters).some(key => 
            state.currentFilters[key] && state.currentFilters[key] !== 'all')) {
            counter.textContent = `${visibleRows.length} de ${totalRows} contratos encontrados`;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }
    
    // Resetar filtros de renovação
    function resetRenewalFilters() {
        const companyFilter = document.getElementById('renewCompanyFilter');
        const statusFilter = document.getElementById('renewStatusFilter');
        const searchInput = document.getElementById('searchRenewal');
        
        if (companyFilter) companyFilter.value = 'all';
        if (statusFilter) statusFilter.value = 'expiring_soon';
        if (searchInput) searchInput.value = '';
        
        state.currentSearch = '';
        state.currentFilters = {};
        
        loadRenewalData();
        showNotification('Filtros resetados com sucesso', 'success');
    }
    
    // Funções auxiliares
    function getUrgencyClass(contract) {
        const daysRemaining = getDaysRemaining(contract.end_date);
        
        if (daysRemaining <= 7) return 'critical';
        if (daysRemaining <= 15) return 'high';
        if (daysRemaining <= 30) return 'medium';
        return 'low';
    }
    
    function getDaysRemaining(endDate) {
        const today = new Date();
        const end = new Date(endDate);
        const diffTime = end - today;
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }
    
    function getRenewalStatusClass(contract) {
        switch(contract.renewal_status) {
            case 'urgent': return 'warning';
            case 'due_soon': return 'info';
            case 'on_time': return 'success';
            case 'overdue': return 'error';
            default: return 'info';
        }
    }
    
    function getRenewalStatusIcon(contract) {
        switch(contract.renewal_status) {
            case 'urgent': return 'exclamation-triangle';
            case 'due_soon': return 'clock';
            case 'on_time': return 'check-circle';
            case 'overdue': return 'exclamation-circle';
            default: return 'info-circle';
        }
    }
    
    function getRenewalStatusText(contract) {
        switch(contract.renewal_status) {
            case 'urgent': return 'Urgente';
            case 'due_soon': return 'A Vencer';
            case 'on_time': return 'No Prazo';
            case 'overdue': return 'Atrasado';
            default: return 'Pendente';
        }
    }
    
    function getNextRenewalSuggestion(contract) {
        const endDate = new Date(contract.end_date);
        const suggestionDate = new Date(endDate);
        suggestionDate.setDate(suggestionDate.getDate() - 30); // Sugerir 30 dias antes
        
        return formatDate(suggestionDate);
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showLoading(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando...</p>
                </div>
            `;
        }
    }
    
    function showError(message) {
        const tbody = document.getElementById('renewalTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="error-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Erro ao carregar dados</h3>
                            <p>${message}</p>
                            <button onclick="window.contractsRenewManager.loadRenewalData()" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Tentar Novamente
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    function showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                              type === 'error' ? 'exclamation-circle' : 
                              type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Fechar ao clicar
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
        
        // Remover automaticamente
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) notification.remove();
                }, 300);
            }
        }, duration);
    }
    
    // Funções globais para uso nos botões
    window.startRenewalProcess = function(contractId) {
        window.contractsManager.renewContract(contractId);
    };
    
    window.scheduleRenewal = function(contractId) {
        // Implementar agendamento de renovação
        showNotification('Funcionalidade de agendamento em desenvolvimento', 'info');
    };
    
    window.viewRenewalDetails = function(renewalId) {
        // Implementar visualização de detalhes da renovação
        showNotification('Visualização de detalhes em desenvolvimento', 'info');
    };
    
    window.renewAgain = function(contractId) {
        window.contractsManager.renewContract(contractId);
    };
    
    // API pública
    return {
        init,
        loadRenewalData,
        loadRecentRenewals,
        resetRenewalFilters,
        showNotification
    };
})();

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    window.contractsRenewManager.init();
});