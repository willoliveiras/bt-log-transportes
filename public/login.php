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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BT Log Transportes</title>
    <link rel="stylesheet" href="assets/css/reset.css">
	<link rel="stylesheet" href="assets/css/auth.css">
	<link rel="stylesheet" href="assets/css/utilities.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="assets/images/logo.png" alt="BT Log Transportes" id="login-logo">
                <h1>BT Log Transportes</h1>
            </div>
        </div>
        
        <div class="login-form-container">
			<?php foreach ($messages as $message): ?>
				<div class="alert alert-<?php echo $message['type']; ?>">
					<?php echo $message['text']; ?>
				</div>
			<?php endforeach; ?>
            <form method="POST" class="login-form" id="loginForm">
                <h2>Bem-vindo de volta</h2>
                <p class="subtitle">Faça login em sua conta</p>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="seu@email.com" value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Sua senha">
                </div>
                
                <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                    <span class="btn-text">Entrar</span>
                    <div class="btn-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>Entrando...</span>
                    </div>
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <p>&copy; 2024 BT Log Transportes. Todos os direitos reservados.</p>
        </div>
    </div>

    <!-- Tela de carregamento -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-content">
            <div class="loading-logo">
                <div class="logo-animation">
                    <div class="logo-circle"></div>
                    <div class="logo-text">BT Log</div>
                </div>
            </div>
            <div class="loading-progress">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p>Carregando sistema...</p>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>