<?php
// app/views/errors/404.php

// Definir variáveis para o header
$pageTitle = 'Página Não Encontrada';
$currentPage = 'error';

// Usar o header existente
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="content-area">
    <div class="empty-state">
        <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: var(--color-warning);"></i>
        <h1>404 - Página Não Encontrada</h1>
        <p>A página que você está procurando não existe ou foi movida.</p>
        <div class="mt-2">
            <a href="index.php?page=dashboard" class="btn btn-primary">
                <i class="fas fa-home"></i> Voltar para o Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

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