<?php
// app/middleware/AuthMiddleware.php

class AuthMiddleware {
    private $session;
    private $publicRoutes = ['login', 'logout'];

    public function __construct() {
        $this->session = new Session();
    }

    public function handle($route) {
        // Se a rota é pública, não precisa de autenticação
        if (in_array($route, $this->publicRoutes)) {
            return true;
        }

        // Verificar se usuário está logado
        if (!$this->session->isLoggedIn()) {
            $this->redirectToLogin();
            return false;
        }

        // Verificar timeout da sessão
        $this->session->checkTimeout();

        // Verificar permissões para a rota
        if (!$this->checkPermissions($route)) {
            $this->redirectToUnauthorized();
            return false;
        }

        // Atualizar último acesso
        $this->updateLastAccess();

        return true;
    }

    private function checkPermissions($route) {
        $userRole = $this->session->get('user_role');
        
        // Mapeamento de rotas por perfil
        $permissions = $this->getRolePermissions();
        
        // Super admin tem acesso a tudo
        if ($userRole === 'super_admin') {
            return true;
        }

        // Verificar se a rota está permitida para o perfil
        $allowedRoutes = $permissions[$userRole] ?? [];
        
        // Se a rota não está na lista de permitidas, negar acesso
        if (!in_array($route, $allowedRoutes) && !in_array('*', $allowedRoutes)) {
            error_log("Tentativa de acesso não autorizado: Usuário {$this->session->get('user_id')} tentou acessar {$route}");
            return false;
        }

        return true;
    }

    private function getRolePermissions() {
        return [
            'admin' => [
                'dashboard', 'companies', 'employees', 'drivers', 'vehicles', 
                'clients', 'trips', 'maintenance', 'accounts_payable', 
                'accounts_receivable', 'reports', 'profile', 'settings'
            ],
            'financeiro' => [
                'dashboard', 'accounts_payable', 'accounts_receivable', 
                'reports', 'profile'
            ],
            'comercial' => [
                'dashboard', 'clients', 'trips', 'profile'
            ]
        ];
    }

    private function redirectToLogin() {
        header('Location: /bt-log-transportes/public/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    private function redirectToUnauthorized() {
        http_response_code(403);
        include '../app/views/errors/403.php';
        exit;
    }

    private function updateLastAccess() {
        // Aqui podemos registrar o último acesso do usuário se necessário
        // Por enquanto, apenas atualizamos o tempo da sessão
        $this->session->set('last_access', time());
    }
}
?>