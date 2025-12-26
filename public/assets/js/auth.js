// public/assets/js/auth.js - Atualizado
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnLoading = loginBtn.querySelector('.btn-loading');
    const loadingScreen = document.getElementById('loadingScreen');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Mostrar tela de carregamento inicial
    setTimeout(() => {
        loadingScreen.classList.add('fade-out');
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 500);
    }, 2000);

    // Alternar visibilidade da senha
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }

    // Efeitos nos inputs
    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        // Verificar se há valor inicial
        if (input.value) {
            input.parentElement.classList.add('has-value');
        }

        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            if (this.value) {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });

        // Animar label ao digitar
        input.addEventListener('input', function() {
            if (this.value) {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });
    });

    // Prevenir múltiplos envios
    let isSubmitting = false;
    
    loginForm.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return;
        }
        
        isSubmitting = true;
        
        // Mostrar loading no botão
        btnText.style.opacity = '0';
        btnLoading.style.display = 'flex';
        loginBtn.disabled = true;
        
        // Efeito de shake se houver erro
        const inputs = this.querySelectorAll('input[required]');
        let hasError = false;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.parentElement.classList.add('shake');
                setTimeout(() => {
                    input.parentElement.classList.remove('shake');
                }, 500);
                hasError = true;
            }
        });
        
        if (hasError) {
            e.preventDefault();
            resetButtonState();
            return;
        }
        
        // Simular delay de processamento
        setTimeout(() => {
            this.submit();
        }, 1500);
    });

    // Reset do estado do botão
    function resetButtonState() {
        setTimeout(() => {
            btnText.style.opacity = '1';
            btnLoading.style.display = 'none';
            loginBtn.disabled = false;
            isSubmitting = false;
        }, 1000);
    }

    // Efeito de shake
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .shake {
            animation: shake 0.5s ease-in-out;
        }
    `;
    document.head.appendChild(style);

    // Focar no primeiro input se estiver vazio
    const emailInput = document.getElementById('email');
    if (emailInput && !emailInput.value) {
        setTimeout(() => {
            emailInput.focus();
        }, 300);
    }
});