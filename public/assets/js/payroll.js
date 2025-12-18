// public/assets/js/payroll.js
(function() {
    'use strict';

    if (window.PayrollManagerLoaded) return;
    window.PayrollManagerLoaded = true;

    class PayrollManager {
        constructor() {
            this.isInitialized = false;
            this.modal = null;
        }

        init() {
            if (this.isInitialized) return;

            console.log('💰 Inicializando PayrollManager...');
            this.setupEvents();
            this.isInitialized = true;
        }

        setupEvents() {
            // Botão Gerar Folha
            const generateBtn = document.getElementById('generatePayrollBtn');
            if (generateBtn) {
                generateBtn.addEventListener('click', () => {
                    this.openGenerateModal();
                });
            }
			
			const recalcBtn = document.getElementById('recalculateCommissionsBtn');
			if (recalcBtn) {
				recalcBtn.addEventListener('click', () => {
					this.recalculateCommissions();
				});
			}
			

            // Botão Exportar
            const exportBtn = document.getElementById('exportPayrollBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    this.exportPayroll();
                });
            }

            // Modal events
            this.setupModalEvents();
        }
		
		 // Função EDITAR PAGAMENTO
		editPayment(payrollId) {
			// Por enquanto, vamos redirecionar para uma página de edição
			// Ou abrir um modal de edição
			this.showAlert('Funcionalidade de edição em desenvolvimento', 'info');
			
			// Para implementar depois:
			// this.openEditModal(payrollId);
		}

		// Função IMPRIMIR
		printPayroll(payrollId) {
			// Abrir detalhes e depois imprimir
			this.viewDetails(payrollId);
			
			// Depois que os detalhes carregarem, mostrar botão de impressão
			setTimeout(() => {
				const printButton = document.getElementById('printPayrollButton');
				if (printButton) {
					printButton.style.display = 'inline-flex';
				}
			}, 1000);
			
			this.showAlert('Abra os detalhes e use o botão Imprimir', 'info');
		}
		
		// Abrir modal para excluir folha
		openDeleteModal() {
			const modalHtml = `
				<div class="modal" id="deletePayrollModal">
					<div class="modal-content">
						<div class="modal-header">
							<h3><i class="fas fa-exclamation-triangle"></i> Excluir Folha de Pagamento</h3>
							<button class="modal-close">&times;</button>
						</div>
						<div class="modal-body">
							<form id="deletePayrollForm">
								<div class="form-group">
									<label for="delete_company" class="form-label">
										<i class="fas fa-building"></i>
										Empresa *
									</label>
									<select id="delete_company" name="company_id" class="form-select" required>
										<option value="">Selecione a empresa</option>
										${this.getCompanyOptions()}
									</select>
								</div>
								
								<div class="form-group">
									<label for="delete_month" class="form-label">
										<i class="fas fa-calendar"></i>
										Mês/Ano a Excluir *
									</label>
									<input type="month" id="delete_month" name="reference_month" 
										   class="form-input"
										   value="${new Date().toISOString().substring(0,7)}" 
										   required>
								</div>
								
								<div class="form-info alert alert-danger">
									<div class="alert-icon">
										<i class="fas fa-exclamation-circle"></i>
									</div>
									<div class="alert-content">
										<strong>ATENÇÃO:</strong> Esta ação é irreversível e irá:
										<ul>
											<li>Excluir TODOS os registros da folha do mês selecionado</li>
											<li>Remover históricos de pagamento</li>
											<li>Não poderá ser desfeita</li>
										</ul>
										<strong>Confirme que deseja prosseguir.</strong>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" onclick="payrollManager.closeDeleteModal()">
								<i class="fas fa-times"></i>
								Cancelar
							</button>
							<button type="button" class="btn btn-danger" onclick="payrollManager.deletePayroll()">
								<i class="fas fa-trash"></i>
								<span class="btn-text">Excluir Folha</span>
								<div class="btn-loading" style="display: none;">
									<div class="loading-spinner"></div>
									<span>Excluindo...</span>
								</div>
							</button>
						</div>
					</div>
				</div>
			`;

			document.body.insertAdjacentHTML('beforeend', modalHtml);
			this.showModal('deletePayrollModal');
		}
		
		
		// Fechar modal de exclusão
		closeDeleteModal() {
			const modal = document.getElementById('deletePayrollModal');
			if (modal) {
				modal.remove();
			}
		}
		
		
		// Método para recalcular comissões
		async recalculateCommissions() {
			if (confirm('Deseja recalcular TODAS as comissões das viagens concluídas?\n\nEsta ação atualizará o campo commission_amount de todas as viagens.')) {
				try {
					const response = await fetch('/bt-log-transportes/public/api/payroll.php?action=recalculate_commissions');
					const result = await response.json();

					if (result.success) {
						this.showAlert(result.message, 'success');
					} else {
						throw new Error(result.message);
					}

				} catch (error) {
					console.error('❌ Erro ao recalcular comissões:', error);
					this.showAlert('Erro ao recalcular comissões: ' + error.message, 'error');
				}
			}
		}
		
		// Excluir folha
		async deletePayroll() {
			const form = document.getElementById('deletePayrollForm');
			if (!form) return;

			const companyId = document.getElementById('delete_company').value;
			const month = document.getElementById('delete_month').value;

			if (!companyId || !month) {
				this.showAlert('Selecione a empresa e o mês para excluir', 'warning');
				return;
			}

			if (!confirm(`CONFIRMA EXCLUSÃO DA FOLHA?\n\nEmpresa: ${document.getElementById('delete_company').options[document.getElementById('delete_company').selectedIndex].text}\nMês: ${month}\n\nEsta ação NÃO poderá ser desfeita!`)) {
				return;
			}

			const button = form.querySelector('.btn-danger');
			this.setLoadingState(button, true);

			try {
				const formData = new FormData();
				formData.append('company_id', companyId);
				formData.append('reference_month', month);

				const response = await fetch('/bt-log-transportes/public/api/payroll.php?action=delete_payroll', {
					method: 'POST',
					body: formData
				});

				const result = await response.json();

				if (result.success) {
					this.showAlert(result.message, 'success');
					this.closeDeleteModal();
					setTimeout(() => window.location.reload(), 2000);
				} else {
					throw new Error(result.message);
				}

			} catch (error) {
				console.error('❌ Erro ao excluir folha:', error);
				this.showAlert('Erro ao excluir folha: ' + error.message, 'error');
			} finally {
				this.setLoadingState(button, false);
			}
		}

		// Função ESTORNAR PAGAMENTO
		async reversePayment(payrollId) {
			if (confirm('Deseja estornar este pagamento? O status voltará para "Pendente".')) {
				try {
					const formData = new FormData();
					formData.append('payroll_id', payrollId);

					const response = await fetch('/bt-log-transportes/public/api/payroll.php?action=reverse_payment', {
						method: 'POST',
						body: formData
					});

					const result = await response.json();

					if (result.success) {
						this.showAlert('Pagamento estornado com sucesso!', 'success');
						setTimeout(() => window.location.reload(), 1500);
					} else {
						throw new Error(result.message);
					}

				} catch (error) {
					console.error('❌ Erro ao estornar pagamento:', error);
					this.showAlert('Erro ao estornar pagamento: ' + error.message, 'error');
				}
			}
		}

        setupModalEvents() {
            this.modal = document.getElementById('payrollModal');
            if (!this.modal) return;

            // Fechar modal
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

            // Botão Fechar
            const closeButton = document.getElementById('closePayrollButton');
            if (closeButton) {
                closeButton.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            // Botão Imprimir
            const printButton = document.getElementById('printPayrollButton');
            if (printButton) {
                printButton.addEventListener('click', () => {
                    window.print();
                });
            }
        }

        openGenerateModal() {
            // Criar modal dinâmico para gerar folha
            const modalHtml = `
                <div class="modal" id="generatePayrollModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Gerar Folha de Pagamento</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="generatePayrollForm">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="generate_company">Empresa *</label>
                                        <select id="generate_company" name="company_id" required>
                                            <option value="">Selecione a empresa</option>
                                            ${this.getCompanyOptions()}
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="generate_month">Mês/Ano de Referência *</label>
                                        <input type="month" id="generate_month" name="reference_month" 
                                               value="${new Date().toISOString().substring(0,7)}" required>
                                    </div>
                                </div>
                                <div class="form-info">
                                    <p><strong>⚠️ Atenção:</strong> Esta ação irá:</p>
                                    <ul>
                                        <li>Calcular folha para todos os funcionários ativos</li>
                                        <li>Incluir comissões do período</li>
                                        <li>Aplicar benefícios e descontos cadastrados</li>
                                        <li>Gerar registros com status "Pendente"</li>
                                    </ul>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="payrollManager.closeGenerateModal()">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="payrollManager.generatePayroll()">
                                <span class="btn-text">Gerar Folha</span>
                                <div class="btn-loading" style="display: none;">
                                    <div class="loading-spinner"></div>
                                    <span>Processando...</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            this.showModal('generatePayrollModal');
        }

        closeGenerateModal() {
            const modal = document.getElementById('generatePayrollModal');
            if (modal) {
                modal.remove();
            }
        }

        getCompanyOptions() {
            const companySelect = document.getElementById('companyFilter');
            if (companySelect) {
                return companySelect.innerHTML;
            }
            return '';
        }

        async generatePayroll() {
			const form = document.getElementById('generatePayrollForm');
			if (!form) return;

			const companyId = document.getElementById('generate_company').value;
			const month = document.getElementById('generate_month').value;

			if (!companyId || !month) {
				this.showAlert('Preencha todos os campos obrigatórios', 'warning');
				return;
			}

			const button = form.querySelector('.btn-primary');
			this.setLoadingState(button, true);

			try {
				const formData = new FormData();
				formData.append('company_id', companyId);
				formData.append('reference_month', month);

				const response = await fetch('/bt-log-transportes/public/api/payroll.php?action=generate', {
					method: 'POST',
					body: formData
				});

				// Verificar se a resposta é JSON válido
				const responseText = await response.text();
				
				let result;
				try {
					result = JSON.parse(responseText);
				} catch (parseError) {
					console.error('❌ Resposta não é JSON válido:', responseText);
					throw new Error('Resposta inválida do servidor. Verifique o console para detalhes.');
				}

				if (result.success) {
					this.showAlert(result.message, 'success');
					this.closeGenerateModal();
					setTimeout(() => window.location.reload(), 2000);
				} else {
					throw new Error(result.message);
				}

			} catch (error) {
				console.error('❌ Erro ao gerar folha:', error);
				
				// Mostrar mensagem mais amigável
				let errorMessage = error.message;
				if (error.message.includes('Resposta inválida')) {
					errorMessage = 'Erro no servidor. Verifique se há erros PHP ou se a API está funcionando.';
				}
				
				this.showAlert('Erro ao gerar folha: ' + errorMessage, 'error');
			} finally {
				this.setLoadingState(button, false);
			}
		}

        async viewDetails(payrollId) {
            try {
                const response = await fetch(`/bt-log-transportes/public/api/payroll.php?action=get_details&id=${payrollId}`);
                const result = await response.json();

                if (result.success) {
                    this.showPayrollDetails(result.data);
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                console.error('❌ Erro ao carregar detalhes:', error);
                this.showAlert('Erro ao carregar detalhes: ' + error.message, 'error');
            }
        }

        showPayrollDetails(data) {
            const detailsHtml = this.generateDetailsHtml(data);
            
            const detailsContainer = document.getElementById('payrollDetails');
            if (detailsContainer) {
                detailsContainer.innerHTML = detailsHtml;
            }

            // Mostrar botão de impressão
            const printButton = document.getElementById('printPayrollButton');
            if (printButton) {
                printButton.style.display = 'inline-flex';
            }

            this.showModal('payrollModal');
        }

        generateDetailsHtml(data) {
            const breakdown = data.breakdown || {};
            const proventos = breakdown.proventos || {};
            const descontos = breakdown.descontos || {};

            return `
                <div class="payroll-header">
                    <div class="employee-info">
                        <h4>${data.employee_name}</h4>
                        <p>${data.position} • CPF: ${data.cpf || 'N/A'}</p>
                        <p>${data.company_name} • ${new Date(data.reference_month).toLocaleDateString('pt-BR', {month: 'long', year: 'numeric'})}</p>
                    </div>
                    <div class="payroll-summary">
                        <div class="summary-item">
                            <span>Salário Líquido:</span>
                            <strong>R$ ${this.formatCurrency(data.net_salary)}</strong>
                        </div>
                        <div class="status-badge ${data.status === 'pago' ? 'active' : 'warning'}">
                            ${data.status === 'pago' ? 'Pago' : 'Pendente'}
                        </div>
                    </div>
                </div>

                <div class="payroll-breakdown">
                    <div class="breakdown-section">
                        <h4>🟢 Proventos</h4>
                        ${Object.entries(proventos).map(([key, value]) => `
                            <div class="breakdown-item">
                                <span>${key}</span>
                                <span class="breakdown-value positive">+ R$ ${this.formatCurrency(value.value)}</span>
                            </div>
                        `).join('')}
                        <div class="breakdown-item total">
                            <span>Total de Proventos</span>
                            <span class="breakdown-value positive">
                                + R$ ${this.formatCurrency(data.base_salary + data.commissions + data.benefits)}
                            </span>
                        </div>
                    </div>

                    <div class="breakdown-section">
                        <h4>🔴 Descontos</h4>
                        ${Object.entries(descontos).map(([key, value]) => `
                            <div class="breakdown-item">
                                <span>${key}</span>
                                <span class="breakdown-value negative">- R$ ${this.formatCurrency(value.value)}</span>
                            </div>
                        `).join('')}
                        <div class="breakdown-item total">
                            <span>Total de Descontos</span>
                            <span class="breakdown-value negative">
                                - R$ ${this.formatCurrency(data.discounts)}
                            </span>
                        </div>
                    </div>

                    <div class="breakdown-section">
                        <div class="breakdown-item total" style="background: var(--color-primary-light);">
                            <span>🟰 SALÁRIO LÍQUIDO</span>
                            <span class="breakdown-value" style="color: var(--color-primary); font-size: 1.3rem;">
                                R$ ${this.formatCurrency(data.net_salary)}
                            </span>
                        </div>
                    </div>
                </div>

                ${data.status === 'pago' && data.payment_date ? `
                    <div class="payment-info">
                        <p><strong>💳 Pagamento realizado em:</strong> ${new Date(data.payment_date).toLocaleDateString('pt-BR')}</p>
                    </div>
                ` : ''}
            `;
        }

        async markAsPaid(payrollId) {
            if (confirm('Deseja marcar este pagamento como realizado?')) {
                try {
                    const formData = new FormData();
                    formData.append('payroll_id', payrollId);
                    formData.append('payment_date', new Date().toISOString().split('T')[0]);

                    const response = await fetch('/bt-log-transportes/public/api/payroll.php?action=mark_paid', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showAlert('Pagamento registrado com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message);
                    }

                } catch (error) {
                    console.error('❌ Erro ao marcar como pago:', error);
                    this.showAlert('Erro ao registrar pagamento: ' + error.message, 'error');
                }
            }
        }

        exportPayroll() {
            const companyFilter = document.getElementById('companyFilter')?.value || '';
            const monthFilter = document.getElementById('monthFilter')?.value || '';
            
            const url = new URL('/bt-log-transportes/public/api/payroll.php', window.location.origin);
            url.searchParams.set('action', 'export');
            url.searchParams.set('company', companyFilter);
            url.searchParams.set('month', monthFilter);
            url.searchParams.set('format', 'excel');
            
            window.open(url.toString(), '_blank');
        }

        showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                document.body.classList.add('modal-open');
            }
        }

        closeModal() {
            if (this.modal) {
                this.modal.style.display = 'none';
            }
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
        }

        formatCurrency(value) {
            return parseFloat(value || 0).toFixed(2).replace('.', ',');
        }

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

        showAlert(message, type = 'info') {
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

        filterByCompany(companyId) {
            this.applyFilter('company', companyId);
        }

        filterByMonth(month) {
            this.applyFilter('month', month);
        }

        applyFilter(type, value) {
            const url = new URL(window.location);
            if (value) {
                url.searchParams.set(type, value);
            } else {
                url.searchParams.delete(type);
            }
            window.location.href = url.toString();
        }
    }

    // Inicialização
    if (!window.payrollManager) {
        window.payrollManager = new PayrollManager();
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.payrollManager.init();
            }, 500);
        });
    }

    console.log('💰 payroll.js carregado com sucesso!');

})();