// chart_of_accounts.js - VERSÃO REFORMULADA

if (!window.chartOfAccountsLoaded) {
    window.chartOfAccountsLoaded = true;

    // Funções de modal
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

    // Toggle dos grupos
    window.toggleGroup = function(groupId) {
        const groupBody = document.getElementById('group-' + groupId);
        const button = groupBody.previousElementSibling.querySelector('button');
        const icon = button.querySelector('i');
        
        groupBody.classList.toggle('collapsed');
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
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

    // Submit do formulário
    const accountForm = document.getElementById('accountForm');
    if (accountForm) {
        accountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const accountGroup = document.getElementById('account_group').value;
            const accountCode = document.getElementById('account_code').value;
            const accountName = document.getElementById('account_name').value;
            
            if (!accountGroup || !accountCode || !accountName) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const submitBtn = accountForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitBtn.disabled = true;
            
            const formData = new FormData(accountForm);
            
            fetch(accountForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    closeModal('accountModal');
                    window.location.reload();
                } else {
                    throw new Error('Erro na resposta do servidor');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar conta. Tente novamente.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // Funções globais
    window.deleteAccount = function(id) {
        if (confirm('Tem certeza que deseja excluir esta conta?')) {
            fetch(`index.php?page=chart_of_accounts&action=delete&id=${id}`)
                .then(response => response.ok && window.location.reload())
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao excluir conta.');
                });
        }
    };

    window.editAccount = function(id) {
        alert('Funcionalidade de edição em desenvolvimento.');
    };

    // Fechar alertas
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);

    console.log('Chart of Accounts JS carregado!');
}