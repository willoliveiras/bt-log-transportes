<?php
// app/middleware/LogMiddleware.php

class LogMiddleware {
    private $session;

    public function __construct() {
        $this->session = new Session();
    }

    public function logRequest($route, $method) {
        if (!$this->session->isLoggedIn()) {
            return;
        }

        $userId = $this->session->get('user_id');
        $userName = $this->session->get('user_name');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        // Não logar requisições de assets e requests muito frequentes
        if ($this->shouldSkipLogging($route)) {
            return;
        }

        $logMessage = sprintf(
            "[%s] User: %s (ID: %d) - Route: %s - Method: %s - IP: %s - Agent: %s",
            date('Y-m-d H:i:s'),
            $userName,
            $userId,
            $route,
            $method,
            $ipAddress,
            $userAgent
        );

        error_log($logMessage);
    }

    private function shouldSkipLogging($route) {
        $skipRoutes = ['dashboard', 'api'];
        $skipPatterns = ['/\.(css|js|png|jpg|jpeg|gif|ico)$/i'];

        foreach ($skipRoutes as $skipRoute) {
            if (strpos($route, $skipRoute) === 0) {
                return true;
            }
        }

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $route)) {
                return true;
            }
        }

        return false;
    }
}
?>