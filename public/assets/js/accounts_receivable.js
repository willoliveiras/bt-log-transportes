// accounts_receivable.js - VERSÃO SIMPLIFICADA E SEGURA

// Verificar se já foi executado
if (!window.accountsReceivableLoaded) {
    window.accountsReceivableLoaded = true;
    
    // Funções básicas de modal
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    };

    // Event listeners
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModal(modal.id);
                }
            });
        }
    });

    // Conta recorrente
    const isRecurring = document.getElementById('is_recurring');
    if (isRecurring) {
        isRecurring.addEventListener('change', function(e) {
            const recurrenceFields = document.getElementById('recurrence_fields');
            if (recurrenceFields) {
                recurrenceFields.style.display = e.target.checked ? 'block' : 'none';
            }
        });
    }

    // Busca
    const searchReceivable = document.getElementById('searchReceivable');
    if (searchReceivable) {
        searchReceivable.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Filtro de status
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function(e) {
            const status = e.target.value;
            const rows = document.querySelectorAll('.data-table tbody tr');
            
            rows.forEach(row => {
                const statusCell = row.querySelector('.status-badge');
                if (statusCell) {
                    const rowStatus = statusCell.textContent.trim().toLowerCase();
                    const shouldShow = !status || rowStatus === status.toLowerCase();
                    row.style.display = shouldShow ? '' : 'none';
                }
            });
        });
    }

    // Período
    const periodSelect = document.getElementById('periodSelect');
    if (periodSelect) {
        periodSelect.addEventListener('change', function(e) {
            const url = new URL(window.location.href);
            url.searchParams.set('period', e.target.value);
            window.location.href = url.toString();
        });
    }

    // Submit do formulário
    const receivableForm = document.getElementById('receivableForm');
    if (receivableForm) {
        receivableForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Formulário de recebível submetido');
            
            // Validar campos obrigatórios
            const description = document.getElementById('description').value;
            const amount = document.getElementById('amount').value;
            const dueDate = document.getElementById('due_date').value;
            const chartAccount = document.getElementById('chart_account_id').value;
            
            if (!description || !amount || !dueDate || !chartAccount) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            // Mostrar loading
            const submitBtn = receivableForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitBtn.disabled = true;
            
            // Enviar formulário
            const formData = new FormData(receivableForm);
            
            fetch(receivableForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Resposta recebida:', response);
                if (response.ok) {
                    // Fechar modal e recarregar
                    closeModal('receivableModal');
                    window.location.reload();
                } else {
                    throw new Error('Erro na resposta do servidor');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar conta. Tente novamente.');
                // Restaurar botão
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // Funções globais para botões
    window.markAsReceived = function(id) {
        if (confirm('Deseja marcar esta conta como recebida?')) {
            fetch(`index.php?page=accounts_receivable&action=mark_received&id=${id}`)
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Erro ao marcar conta como recebida.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao marcar conta como recebida.');
                });
        }
    };

    window.deleteReceivable = function(id) {
        if (confirm('Tem certeza que deseja excluir esta conta?')) {
            fetch(`index.php?page=accounts_receivable&action=delete&id=${id}`)
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Erro ao excluir conta.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao excluir conta.');
                });
        }
    };

    window.editReceivable = function(id) {
        alert('Funcionalidade de edição será implementada em breve.');
    };

    // Fechar alertas automaticamente
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);

    console.log('Accounts Receivable JS carregado com sucesso!');
}