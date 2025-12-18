// public/assets/js/financial-dashboard.js
(function() {
    'use strict';

    if (window.FinancialDashboardLoaded) return;
    window.FinancialDashboardLoaded = true;

    class FinancialDashboard {
        constructor() {
            this.charts = {};
            this.currentData = null;
            this.isInitialized = false;
        }

        init() {
            if (this.isInitialized) return;

            console.log('💰 Inicializando FinancialDashboard...');
            this.setupEvents();
            this.loadDashboardData();
            this.isInitialized = true;
        }

        setupEvents() {
            // Mostrar/ocultar range de datas customizado
            const periodFilter = document.getElementById('periodFilter');
            if (periodFilter) {
                periodFilter.addEventListener('change', (e) => {
                    const customRange = document.getElementById('customDateRange');
                    if (customRange) {
                        customRange.style.display = e.target.value === 'custom' ? 'flex' : 'none';
                    }
                });
            }
        }

        async loadDashboardData() {
            try {
                this.showLoadingStates();
                
                const companyFilter = document.getElementById('companyFilter')?.value || '';
                const periodFilter = document.getElementById('periodFilter')?.value || 'month';
                const startDate = document.getElementById('startDate')?.value || '';
                const endDate = document.getElementById('endDate')?.value || '';

                const params = new URLSearchParams({
                    company: companyFilter,
                    period: periodFilter
                });

                if (startDate) params.append('start_date', startDate);
                if (endDate) params.append('end_date', endDate);

                const response = await fetch(`/bt-log-transportes/public/api/financial.php?action=dashboard_data&${params}`);
                const result = await response.json();

                if (result.success) {
                    this.currentData = result.data;
                    this.updateDashboard(result.data);
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                console.error('❌ Erro ao carregar dados:', error);
                this.showError('Erro ao carregar dados do dashboard: ' + error.message);
            }
        }

        updateDashboard(data) {
            this.updateKPIs(data.kpis);
            this.updateCharts(data);
            this.updateDueAccounts(data.due_accounts);
            this.updateTopClients(data.top_clients);
            this.updateAlerts(data);
        }

        updateKPIs(kpis) {
            if (!kpis) return;

            // Atualizar KPIs principais
            this.updateMetric('totalRevenue', kpis.total_revenue);
            this.updateMetric('totalExpenses', kpis.total_expenses);
            this.updateMetric('netProfit', kpis.net_profit);
            
            // Calcular fluxo de caixa (receitas - despesas)
            const cashFlow = parseFloat(kpis.total_revenue) - parseFloat(kpis.total_expenses);
            this.updateMetric('cashFlow', cashFlow);
        }

        updateMetric(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = this.formatCurrency(value);
                
                // Adicionar cor baseada no valor
                if (value < 0) {
                    element.style.color = 'var(--color-error)';
                } else if (value > 0) {
                    element.style.color = 'var(--color-success)';
                } else {
                    element.style.color = 'var(--color-gray)';
                }
            }
        }

        updateCharts(data) {
            this.createRevenueExpensesChart(data);
            this.createCashFlowChart(data.cash_flow);
            this.createRevenueByCategoryChart(data.revenue_by_category);
            this.createExpensesByCategoryChart(data.expenses_by_category);
            this.createCompanyComparisonChart(data.company_comparison);
        }

        createRevenueExpensesChart(data) {
            const ctx = document.getElementById('revenueExpensesChart');
            if (!ctx) return;

            // Destruir chart anterior se existir
            if (this.charts.revenueExpenses) {
                this.charts.revenueExpenses.destroy();
            }

            const kpis = data.kpis || {};
            
            this.charts.revenueExpenses = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Receitas', 'Despesas', 'Lucro'],
                    datasets: [{
                        label: 'Valores (R$)',
                        data: [
                            parseFloat(kpis.total_revenue || 0),
                            parseFloat(kpis.total_expenses || 0),
                            parseFloat(kpis.net_profit || 0)
                        ],
                        backgroundColor: [
                            'rgba(76, 175, 80, 0.8)',
                            'rgba(244, 67, 54, 0.8)',
                            'rgba(33, 150, 243, 0.8)'
                        ],
                        borderColor: [
                            'rgb(76, 175, 80)',
                            'rgb(244, 67, 54)',
                            'rgb(33, 150, 243)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }

        createCashFlowChart(cashFlowData) {
            const ctx = document.getElementById('cashFlowChart');
            if (!ctx) return;

            if (this.charts.cashFlow) {
                this.charts.cashFlow.destroy();
            }

            const labels = cashFlowData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('pt-BR', {month: 'short', year: '2-digit'});
            });

            const revenue = cashFlowData.map(item => parseFloat(item.revenue));
            const expenses = cashFlowData.map(item => parseFloat(item.expenses));
            const netCashFlow = cashFlowData.map(item => parseFloat(item.net_cash_flow));

            this.charts.cashFlow = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: revenue,
                            borderColor: 'rgb(76, 175, 80)',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Despesas',
                            data: expenses,
                            borderColor: 'rgb(244, 67, 54)',
                            backgroundColor: 'rgba(244, 67, 54, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Fluxo Líquido',
                            data: netCashFlow,
                            borderColor: 'rgb(33, 150, 243)',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }

        createRevenueByCategoryChart(revenueData) {
            const ctx = document.getElementById('revenueByCategoryChart');
            if (!ctx) return;

            if (this.charts.revenueByCategory) {
                this.charts.revenueByCategory.destroy();
            }

            const labels = revenueData.map(item => item.category);
            const data = revenueData.map(item => parseFloat(item.amount));

            this.charts.revenueByCategory = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#FF6B00', '#4CAF50', '#2196F3', '#FF9800', '#9C27B0',
                            '#00BCD4', '#8BC34A', '#FF5722', '#607D8B', '#795548'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${context.label}: R$ ${value.toLocaleString('pt-BR')} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        createExpensesByCategoryChart(expensesData) {
            const ctx = document.getElementById('expensesByCategoryChart');
            if (!ctx) return;

            if (this.charts.expensesByCategory) {
                this.charts.expensesByCategory.destroy();
            }

            const labels = expensesData.map(item => item.category);
            const data = expensesData.map(item => parseFloat(item.amount));

            this.charts.expensesByCategory = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#F44336', '#FF9800', '#FFC107', '#8BC34A', '#00BCD4',
                            '#03A9F4', '#3F51B5', '#9C27B0', '#E91E63', '#795548'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${context.label}: R$ ${value.toLocaleString('pt-BR')} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        createCompanyComparisonChart(companyData) {
            const ctx = document.getElementById('companyComparisonChart');
            if (!ctx) return;

            if (this.charts.companyComparison) {
                this.charts.companyComparison.destroy();
            }

            const labels = companyData.map(item => item.company_name);
            const revenue = companyData.map(item => parseFloat(item.revenue));
            const expenses = companyData.map(item => parseFloat(item.expenses));
            const tripRevenue = companyData.map(item => parseFloat(item.trip_revenue));

            this.charts.companyComparison = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: revenue,
                            backgroundColor: 'rgba(76, 175, 80, 0.8)'
                        },
                        {
                            label: 'Despesas',
                            data: expenses,
                            backgroundColor: 'rgba(244, 67, 54, 0.8)'
                        },
                        {
                            label: 'Faturamento Viagens',
                            data: tripRevenue,
                            backgroundColor: 'rgba(255, 107, 0, 0.8)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false
                        },
                        y: {
                            stacked: false,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            });
        }

        updateDueAccounts(dueAccounts) {
            const container = document.getElementById('dueAccountsList');
            if (!container) return;

            if (!dueAccounts || dueAccounts.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>Nenhuma conta a vencer</h3>
                        <p>Todas as contas estão em dia!</p>
                    </div>
                `;
                return;
            }

            let html = '';
            dueAccounts.forEach(account => {
                const daysUntilDue = parseInt(account.days_until_due);
                let daysText = 'hoje';
                let daysClass = 'text-danger';
                
                if (daysUntilDue > 0) {
                    daysText = `em ${daysUntilDue} dias`;
                    daysClass = daysUntilDue <= 7 ? 'text-warning' : 'text-info';
                } else if (daysUntilDue < 0) {
                    daysText = `há ${Math.abs(daysUntilDue)} dias`;
                    daysClass = 'text-danger';
                }

                html += `
                    <div class="due-account-item">
                        <div class="account-info">
                            <div class="account-description">
                                <strong>${account.description}</strong>
                                <div class="text-small">${account.client_name || 'N/A'}</div>
                            </div>
                            <div class="text-small ${daysClass}">
                                Vence ${daysText}
                            </div>
                        </div>
                        <div class="account-details">
                            <span class="account-type ${account.type}">
                                ${account.type === 'receivable' ? 'A Receber' : 'A Pagar'}
                            </span>
                            <div class="account-amount">
                                <strong>R$ ${this.formatCurrency(account.amount)}</strong>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        updateTopClients(topClients) {
            const container = document.getElementById('topClientsList');
            if (!container) return;

            if (!topClients || topClients.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>Nenhum cliente</h3>
                        <p>Nenhum faturamento registrado no período.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            topClients.forEach((client, index) => {
                html += `
                    <div class="client-item">
                        <div class="client-info">
                            <div class="client-name">
                                ${client.client_name}
                            </div>
                            <div class="client-stats">
                                ${client.invoice_count} faturas • Média: R$ ${this.formatCurrency(client.avg_invoice)}
                            </div>
                        </div>
                        <div class="client-amount">
                            <strong>R$ ${this.formatCurrency(client.total_amount)}</strong>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        updateAlerts(data) {
            // Buscar alertas via API separada
            this.loadFinancialAlerts();
        }

        async loadFinancialAlerts() {
            try {
                const companyFilter = document.getElementById('companyFilter')?.value || '';
                
                const response = await fetch(`/bt-log-transportes/public/api/financial.php?action=alerts&company=${companyFilter}`);
                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    this.showAlerts(result.data);
                } else {
                    this.hideAlerts();
                }

            } catch (error) {
                console.error('❌ Erro ao carregar alertas:', error);
                this.hideAlerts();
            }
        }

        showAlerts(alerts) {
            const container = document.getElementById('financialAlerts');
            if (!container) return;

            let html = '';
            alerts.forEach(alert => {
                const icon = this.getAlertIcon(alert.alert_type);
                const priorityClass = alert.priority || 'medium';
                
                html += `
                    <div class="alert-item ${priorityClass}">
                        <div class="alert-icon">
                            ${icon}
                        </div>
                        <div class="alert-content">
                            <div class="alert-title">${alert.title}</div>
                            <div class="alert-message">${alert.message}</div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            container.style.display = 'block';
        }

        hideAlerts() {
            const container = document.getElementById('financialAlerts');
            if (container) {
                container.style.display = 'none';
            }
        }

        getAlertIcon(alertType) {
            const icons = {
                'account_due': '⏰',
                'account_overdue': '⚠️',
                'low_cash': '💰'
            };
            return icons[alertType] || 'ℹ️';
        }

        showLoadingStates() {
            // Mostrar estados de carregamento nos containers
            const containers = ['dueAccountsList', 'topClientsList'];
            containers.forEach(containerId => {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = `
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Carregando...</span>
                        </div>
                    `;
                }
            });
        }

        formatCurrency(value) {
            return parseFloat(value || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        filterByCompany(companyId) {
            this.applyFilter('company', companyId);
        }

        filterByPeriod(period) {
            this.applyFilter('period', period);
        }

        applyCustomDateRange() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                this.showError('Selecione ambas as datas');
                return;
            }

            this.applyFilter('custom', '', {start_date: startDate, end_date: endDate});
        }

        applyFilter(type, value, additionalParams = {}) {
            const url = new URL(window.location);
            
            if (value) {
                url.searchParams.set(type, value);
            } else {
                url.searchParams.delete(type);
            }

            // Adicionar parâmetros adicionais
            Object.keys(additionalParams).forEach(key => {
                if (additionalParams[key]) {
                    url.searchParams.set(key, additionalParams[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });

            window.location.href = url.toString();
        }

        refreshData() {
            this.loadDashboardData();
        }

        showError(message) {
            const alertDiv = document.createElement('div');
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                background: #F44336;
                color: white;
                font-weight: 600;
                z-index: 10000;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        toggleChartView(chartType) {
            // Implementar alternância entre tipos de gráfico
            console.log('Alternando visualização do gráfico:', chartType);
        }
    }

    // Inicialização
    if (!window.financialDashboard) {
        window.financialDashboard = new FinancialDashboard();
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.financialDashboard.init();
            }, 500);
        });
    }

    console.log('💰 financial-dashboard.js carregado com sucesso!');

})();