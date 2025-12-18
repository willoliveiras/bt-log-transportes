// public/assets/js/main.js - CORRIGIDO
(function() {
    'use strict';

    // Proteção contra carregamento duplo
    if (window.BTLogSystemLoaded) {
        console.log('🚀 Sistema BT Log já carregado');
        return;
    }
    window.BTLogSystemLoaded = true;

    console.log('🚀 Sistema BT Log inicializando...');

    class BTLogSystem {
        constructor() {
            this.init();
        }

        init() {
            console.log('🔧 BT Log System inicializado');
            this.initGlobalEvents();
            this.initModals();
            this.initNotifications();
        }

        initGlobalEvents() {
            // Fechar modais ao clicar fora
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                    document.body.classList.remove('modal-open');
                }
            });

            // Fechar modais com ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });
        }

        initModals() {
            console.log('🎯 Modais inicializados');
        }

        initNotifications() {
            console.log('🔔 Sistema de notificações pronto');
        }

        closeAllModals() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
            document.body.classList.remove('modal-open');
        }

        showNotification(type, message, duration = 5000) {
            // Criar notificação simples
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10002;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 400px;
            `;
            
            const bgColors = {
                'success': '#4CAF50',
                'error': '#F44336', 
                'warning': '#FF9800',
                'info': '#2196F3'
            };
            
            notification.style.background = bgColors[type] || '#666';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, duration);
        }
    }

    // Inicialização única
    if (!window.btLogSystem) {
        window.btLogSystem = new BTLogSystem();
    }

    // Função global para toggle do menu do usuário
    window.toggleUserMenu = function() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    };

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('userDropdown');
        const avatar = document.querySelector('.user-avatar');
        
        if (dropdown && !dropdown.contains(e.target) && (!avatar || !avatar.contains(e.target))) {
            dropdown.classList.remove('show');
        }
    });

})();