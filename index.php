<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>Teste BT Log</h1>";
echo "<p>Se esta mensagem aparece, o PHP está funcionando.</p>";

echo "<h2>Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p>✅ Usuário logado: " . $_SESSION['user_name'] . "</p>";
    echo '<a href="index.php?page=dashboard">Ir para Dashboard</a>';
} else {
    echo "<p>❌ Usuário NÃO está logado</p>";
    echo '<a href="login.php">Fazer Login</a>';
}