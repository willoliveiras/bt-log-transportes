// public/assets/js/auth.js
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnLoading = loginBtn.querySelector('.btn-loading');
    const loadingScreen = document.getElementById('loadingScreen');

    // Mostrar tela de carregamento inicial
    setTimeout(() => {
        loadingScreen.classList.add('fade-out');
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 500);
    }, 2000);

    // Animação do formulário de login
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Mostrar loading no botão
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';
        loginBtn.disabled = true;

        // Simular delay de processamento
        setTimeout(() => {
            // Submeter o formulário
            this.submit();
        }, 1500);
    });

    // Efeitos de foco nos inputs
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Prevenir múltiplos cliques
    let isSubmitting = false;
    loginForm.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return;
        }
        isSubmitting = true;
    });
});