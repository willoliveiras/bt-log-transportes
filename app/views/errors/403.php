<?php
// app/views/errors/403.php
$pageTitle = 'Acesso Não Autorizado';
require_once '../layouts/header.php';
?>

<div class="error-container">
    <div class="error-content">
        <div class="error-icon">
            <i class="icon-lock"></i>
        </div>
        <h1>403 - Acesso Negado</h1>
        <p>Você não tem permissão para acessar esta página.</p>
        <div class="error-actions">
            <a href="index.php?page=dashboard" class="btn btn-primary">
                Voltar ao Dashboard
            </a>
            <a href="logout.php" class="btn btn-secondary">
                Fazer Logout
            </a>
        </div>
    </div>
</div>

<style>
.error-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 2rem;
}

.error-content {
    text-align: center;
    max-width: 500px;
}

.error-icon {
    font-size: 4rem;
    color: #FF6B00;
    margin-bottom: 2rem;
}

.error-icon i {
    font-size: 4rem;
}

.error-content h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 1rem;
}

.error-content p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .error-actions {
        flex-direction: column;
    }
    
    .error-actions .btn {
        width: 100%;
    }
}
</style>

<?php
require_once '../layouts/footer.php';
?>