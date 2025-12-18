<?php
// public/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../app/config/config.php';
require_once '../app/core/Session.php';

$session = new Session();

// Registrar log de logout
if ($session->isLoggedIn()) {
    $userId = $session->get('user_id');
    $userName = $session->get('user_name');
    error_log("Logout: Usuário {$userName} (ID: {$userId}) fez logout");
}

// Destruir sessão completamente
$session->destroy();

// Limpar cookies de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirecionar para login com mensagem
header('Location: login.php?logout=1');
exit;
?>