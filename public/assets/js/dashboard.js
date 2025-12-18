// public/assets/js/header.js - COM PROTEÇÃO
(function() {
    // Verificar se já foi carregado
    if (window.headerManager) {
        console.log('🔧 HeaderManager já foi carregado, ignorando...');
        return;
    }

    console.log('🔧 Header Manager inicializado');

    class HeaderManager {
        constructor() {
            this.init();
        }

        init() {
            console.log('🎯 Header Manager inicializando...');
            this.initDropdowns();
            this.initCompanySelector();
            this.initPeriodSelector();
        }

        initDropdowns() {
            // Notificações
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationsDropdown = document.getElementById('notificationsDropdown');

            // Usuário
            const userBtn = document.getElementById('userBtn');
            const userDropdown = document.getElementById('userDropdown');

            if (notificationBtn && notificationsDropdown) {
                notificationBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleDropdown(notificationsDropdown, notificationBtn);
                });
            }

            if (userBtn && userDropdown) {
                userBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleDropdown(userDropdown, userBtn);
                });
            }

            // Fechar dropdowns ao clicar fora
            document.addEventListener('click', () => {
                this.closeAllDropdowns();
            });
        }

        toggleDropdown(dropdown, button) {
            const isVisible = dropdown.classList.contains('show');
            this.closeAllDropdowns();
            
            if (!isVisible) {
                dropdown.classList.add('show');
                button.classList.add('active');
            }
        }

        closeAllDropdowns() {
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            const notificationBtn = document.getElementById('notificationBtn');
            const userDropdown = document.getElementById('userDropdown');
            const userBtn = document.getElementById('userBtn');
            
            if (notificationsDropdown) notificationsDropdown.classList.remove('show');
            if (notificationBtn) notificationBtn.classList.remove('active');
            if (userDropdown) userDropdown.classList.remove('show');
            if (userBtn) userBtn.classList.remove('active');
        }

        initCompanySelector() {
            const companySelect = document.getElementById('companySelect');
            if (companySelect) {
                companySelect.addEventListener('change', (e) => {
                    console.log('🏢 Empresa selecionada:', e.target.value);
                });
            }
        }

        initPeriodSelector() {
            const periodSelect = document.getElementById('periodSelect');
            if (periodSelect) {
                periodSelect.addEventListener('change', (e) => {
                    console.log('📅 Período selecionado:', e.target.value);
                });
            }
        }

        addNotification(notification) {
            console.log('🔔 Adicionando notificação:', notification);
            // Implementação simplificada
            alert(notification.message || 'Notificação');
        }
    }

    // Inicializar quando DOM estiver pronto
    document.addEventListener('DOMContentLoaded', () => {
        window.headerManager = new HeaderManager();
    });

})();