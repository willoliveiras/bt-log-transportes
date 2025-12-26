<?php

// public/login.php - NO INÍCIO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../app/config/config.php';
require_once '../app/config/database.php';
require_once '../app/core/Database.php';
require_once '../app/core/Session.php';
require_once '../app/models/UserModel.php';

$session = new Session();

// Verificar se já está logado
if ($session->isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? 'index.php?page=dashboard';
    header('Location: ' . $redirect);
    exit;
}

// Processar mensagens
$messages = [];
if (isset($_GET['logout'])) {
    $messages[] = ['type' => 'success', 'text' => 'Logout realizado com sucesso!'];
}
if (isset($_GET['timeout'])) {
    $messages[] = ['type' => 'warning', 'text' => 'Sessão expirada. Faça login novamente.'];
}
if (isset($_GET['unauthorized'])) {
    $messages[] = ['type' => 'error', 'text' => 'Acesso não autorizado.'];
}

// Processar login se formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $userModel = new UserModel();
    $user = $userModel->authenticate($email, $password);
    
    if ($user) {
        $session->setUser($user);
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = "Email ou senha inválidos";
    }
}
?>
<?php
// ... (todo o código PHP anterior permanece o mesmo)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BT Log Transportes</title>
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/utilities.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-body">
    <!-- Tela de carregamento -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-content">
            <div class="loading-logo">
                <div class="logo-animation">
                    <div class="logo-circle">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                    <div class="logo-text">BT Log</div>
                </div>
            </div>
            <div class="loading-progress">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p>Inicializando sistema...</p>
            </div>
        </div>
    </div>

    <!-- Container principal -->
    <div class="auth-container">
        <!-- Painel esquerdo (Branding) -->
        <div class="auth-branding">
            <div class="branding-content">
                <div class="brand-logo">
                    <div class="logo-circle">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                    <h1>BT Log <span>Transportes</span></h1>
                </div>
                <div class="brand-message">
                    <h2>Sistema de Gestão Integrada</h2>
                    <p>Controle total sobre sua operação logística com segurança e eficiência</p>
                </div>
                <div class="brand-features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Autenticação Segura</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard Intuitivo</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-cogs"></i>
                        <span>Controle Completo</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Painel direito (Login) -->
        <div class="auth-form-container">
            <div class="form-header">
                <h2>Acessar Sistema</h2>
                <p>Entre com suas credenciais para continuar</p>
            </div>

            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <div class="alert-content">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                        <span><?php echo $message['text']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>

            <form method="POST" class="auth-form" id="loginForm">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <div class="alert-content">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" id="email" name="email" required 
                           placeholder="seu@email.com" value="<?php echo $_POST['email'] ?? ''; ?>">
                    <label for="email">Endereço de Email</label>
                    <div class="input-focus-line"></div>
                </div>
                
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" id="password" name="password" required 
                           placeholder="••••••••">
                    <label for="password">Senha</label>
                    <div class="input-focus-line"></div>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-option">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Lembrar de mim
                    </label>
                    <a href="#" class="forgot-password">Esqueceu a senha?</a>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginBtn">
                    <span class="btn-text">Entrar no Sistema</span>
                    <div class="btn-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>Autenticando...</span>
                    </div>
                    <i class="fas fa-arrow-right btn-icon"></i>
                </button>

                <div class="auth-footer">
                    <p>Protegido por <i class="fas fa-shield-alt"></i> BT Log Security</p>
                    <p class="copyright">&copy; 2024 BT Log Transportes. Todos os direitos reservados.</p>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>