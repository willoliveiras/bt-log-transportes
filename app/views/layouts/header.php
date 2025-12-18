<?php
// app/views/layouts/header.php - COM JS INLINE

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dados do usuário
$currentUser = [
    'id' => 1,
    'name' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrador',
    'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'admin@btlog.com',
    'role' => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'admin'
];

// Empresas
$companies = [
    ['id' => 1, 'name' => 'BT Log Transportes', 'color' => '#FF6B00'],
    ['id' => 2, 'name' => 'BT Log Express', 'color' => '#2196F3'],
    ['id' => 3, 'name' => 'BT Log Cargas', 'color' => '#4CAF50']
];

$baseUrl = '/bt-log-transportes/public';
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - BT Log Transportes</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/header.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

		<!-- CSS BASE -->
    <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/main.css">
    
    <!-- CSS ESPECÍFICO -->
    <?php 
		$pageTitle = $pageTitle ?? 'BT Log Transportes';
		$currentPage = $currentPage ?? $_GET['page'] ?? 'dashboard';
		$pageScript = $pageScript ?? ''; // ✅ CORREÇÃO - Definir variável padrão
    
        if ($currentPage === 'companies'): 
    ?>
        
    <?php elseif ($currentPage === 'clients'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/clients.css">
		
	<?php elseif ($currentPage === 'maintenance'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/maintenance.css">
		
	<?php elseif ($currentPage === 'bases'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/bases.css">
		
	<?php elseif ($currentPage === 'dashboard'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/dashboard.css">
		
	<?php elseif ($currentPage === 'accounts_payable'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/accounts_payable.css">
		
	<?php elseif ($currentPage === 'payroll'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/payroll.css">
    <?php endif; ?>
    
    <!-- Ícones -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">



 
 
 <!-- ✅ CARREGAR APENAS OS SCRIPTS NECESSÁRIOS PARA A PÁGINA -->
    
	<?php if ($currentPage === 'companies'): ?>
        <script src="/bt-log-transportes/public/assets/js/companies.js"></script> 
    
    <?php elseif ($currentPage === 'employees'): ?>
        <script src="/bt-log-transportes/public/assets/js/employees.js"></script>
        
    <?php elseif ($currentPage === 'drivers'): ?>
        <script src="/bt-log-transportes/public/assets/js/drivers.js"></script>
        
    <?php elseif ($currentPage === 'vehicles'): ?>
        <script src="/bt-log-transportes/public/assets/js/vehicles.js"></script>
    
    <?php elseif ($currentPage === 'bases'): ?>
        <script src="/bt-log-transportes/public/assets/js/bases.js"></script>
		
	<?php elseif ($currentPage === 'suppliers'): ?>
        <script src="/bt-log-transportes/public/assets/js/suppliers.js"></script>
        
    <?php elseif ($currentPage === 'clients'): ?>
        <script src="/bt-log-transportes/public/assets/js/clients.js"></script>
		
	<?php elseif ($currentPage === 'accounts_payable'): ?>
        <script src="/bt-log-transportes/public/assets/js/accounts_payable.js"></script>
		
	<?php elseif ($currentPage === 'accounts_receivable'): ?>
        <script src="/bt-log-transportes/public/assets/js/accounts_receivable.js"></script>
        
    <?php elseif ($currentPage === 'trips'): ?>
        <script src="/bt-log-transportes/public/assets/js/trips.js"></script>
    
    <?php elseif ($currentPage === 'maintenance'): ?>
        <script src="/bt-log-transportes/public/assets/js/maintenance.js"></script>
    
    <?php elseif ($currentPage === 'services'): ?>
        <script src="/bt-log-transportes/public/assets/js/services.js"></script>
        
    <?php elseif ($currentPage === 'payroll'): ?>
        <script src="/bt-log-transportes/public/assets/js/payroll.js"></script>
        
    <?php elseif ($currentPage === 'financial'): ?>
        <script src="/bt-log-transportes/public/assets/js/financial-dashboard.js"></script>
        
    <?php elseif ($pageScript === 'dashboard.js'): ?>
        <link rel="stylesheet" href="/bt-log-transportes/public/assets/css/dashboard.css">
    <?php endif; ?>
    
    <!-- ✅ CORREÇÃO LINHA 43 - Script específico da página -->
    <?php if (isset($pageScript) && !empty($pageScript) && $pageScript !== 'dashboard.js'): ?>
        <script src="/bt-log-transportes/public/assets/js/<?= $pageScript ?>"></script>
    <?php endif; ?>





</head>
    
<body>
    <!-- Overlay mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-truck-moving"></i>
                    <span class="logo-text">BT Log</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <!-- Principal -->
                <div class="menu-section" data-section="principal">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-home"></i>
                            <span>Principal</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=dashboard" class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" data-title="Dashboard">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                </div>

                <!-- Cadastros -->
                <div class="menu-section" data-section="cadastros">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-address-book"></i>
                            <span>Cadastros</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=companies" class="nav-link <?php echo ($currentPage === 'companies') ? 'active' : ''; ?>" data-title="Empresas">
                            <i class="fas fa-building"></i>
                            <span>Empresas</span>
                        </a>
                        <a href="index.php?page=employees" class="nav-link <?php echo ($currentPage === 'employees') ? 'active' : ''; ?>" data-title="Funcionários">
                            <i class="fas fa-users"></i>
                            <span>Funcionários</span>
                        </a>
                        <a href="index.php?page=services" class="nav-link <?php echo ($currentPage === 'services') ? 'active' : ''; ?>" data-title="Serviços">
                            <i class="fas fa-concierge-bell"></i>
                            <span>Serviços</span>
                        </a>
                        <a href="index.php?page=suppliers" class="nav-link <?php echo ($currentPage === 'suppliers') ? 'active' : ''; ?>" data-title="Fornecedores">
                            <i class="fas fa-truck-loading"></i>
                            <span>Fornecedores</span>
                        </a>
                        <a href="index.php?page=drivers" class="nav-link <?php echo ($currentPage === 'drivers') ? 'active' : ''; ?>" data-title="Motoristas">
                            <i class="fas fa-user-tie"></i>
                            <span>Motoristas</span>
                        </a>
                        <a href="index.php?page=vehicles" class="nav-link <?php echo ($currentPage === 'vehicles') ? 'active' : ''; ?>" data-title="Veículos">
                            <i class="fas fa-truck-moving"></i>
                            <span>Veículos</span>
                        </a>
                        <a href="index.php?page=bases" class="nav-link <?php echo ($currentPage === 'bases') ? 'active' : ''; ?>" data-title="Bases">
                            <i class="fas fa-warehouse"></i>
                            <span>Bases</span>
                        </a>
                        <a href="index.php?page=clients" class="nav-link <?php echo ($currentPage === 'clients') ? 'active' : ''; ?>" data-title="Clientes">
                            <i class="fas fa-user-friends"></i>
                            <span>Clientes</span>
                        </a>
                    </div>
                </div>
                
                <!-- Estoque -->
                <div class="menu-section" data-section="estoque">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-boxes"></i>
                            <span>Estoque</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=stock&action=inventory" class="nav-link <?php echo ($currentPage === 'stock') ? 'active' : ''; ?>" data-title="Inventário">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Inventário</span>
                        </a>
                        <a href="index.php?page=stock&action=movements" class="nav-link <?php echo ($currentPage === 'stock_movements') ? 'active' : ''; ?>" data-title="Movimentações">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Movimentações</span>
                        </a>
                        <a href="index.php?page=stock&action=reports" class="nav-link <?php echo ($currentPage === 'stock_reports') ? 'active' : ''; ?>" data-title="Relatórios">
                            <i class="fas fa-chart-bar"></i>
                            <span>Relatórios</span>
                        </a>
                    </div>
                </div>

                <!-- Operacional -->
                <div class="menu-section" data-section="operacional">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-cogs"></i>
                            <span>Operacional</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=trips" class="nav-link <?php echo ($currentPage === 'trips') ? 'active' : ''; ?>" data-title="Viagens">
                            <i class="fas fa-route"></i>
                            <span>Viagens</span>
                        </a>
                        <a href="index.php?page=maintenance" class="nav-link <?php echo ($currentPage === 'maintenance') ? 'active' : ''; ?>" data-title="Manutenção">
                            <i class="fas fa-tools"></i>
                            <span>Manutenção</span>
                        </a>
                    </div>
                </div>

                <!-- Contratos -->
                <div class="menu-section" data-section="contratos">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-file-contract"></i>
                            <span>Contratos</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=contracts&action=list" class="nav-link <?php echo ($currentPage === 'contracts') ? 'active' : ''; ?>" data-title="Contratos Ativos">
                            <i class="fas fa-file-signature"></i>
                            <span>Contratos Ativos</span>
                        </a>
                        <a href="index.php?page=contracts&action=expiring" class="nav-link <?php echo ($currentPage === 'contracts_expiring') ? 'active' : ''; ?>" data-title="Vencimento Próximo">
                            <i class="fas fa-clock"></i>
                            <span>Vencimento Próximo</span>
                        </a>
                        <a href="index.php?page=contracts&action=renew" class="nav-link <?php echo ($currentPage === 'contracts_renew') ? 'active' : ''; ?>" data-title="Renovação">
                            <i class="fas fa-redo"></i>
                            <span>Renovação</span>
                        </a>
                    </div>
                </div>

                <!-- Financeiro -->
                <div class="menu-section" data-section="financeiro">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Financeiro</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=financial" class="nav-link <?php echo $currentPage === 'financial' ? 'active' : ''; ?>" data-title="Dashboard Financeiro">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard Financeiro</span>
                        </a>
                        <a href="index.php?page=payroll" class="nav-link <?php echo $currentPage === 'payroll' ? 'active' : ''; ?>" data-title="Folha de Pagamento">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Folha de Pagamento</span>
                        </a>
                        <a href="index.php?page=accounts_payable" class="nav-link <?php echo ($currentPage === 'accounts_payable') ? 'active' : ''; ?>" data-title="Contas a Pagar">
                            <i class="fas fa-money-bill"></i>
                            <span>Contas a Pagar</span>
                        </a>
                        <a href="index.php?page=accounts_receivable" class="nav-link <?php echo ($currentPage === 'accounts_receivable') ? 'active' : ''; ?>" data-title="Contas a Receber">
                            <i class="fas fa-hand-holding-usd"></i>
                            <span>Contas a Receber</span>
                        </a>
                        <a href="index.php?page=chart_of_accounts" class="nav-link <?php echo $currentPage === 'chart_of_accounts' ? 'active' : ''; ?>" data-title="Plano de Contas">
                            <i class="fas fa-book"></i>
                            <span>Plano de Contas</span>
                        </a>
                    </div>
                </div>

                <!-- Relatórios -->
                <div class="menu-section" data-section="relatorios">
                    <div class="menu-header">
                        <div class="menu-title">
                            <i class="fas fa-chart-bar"></i>
                            <span>Relatórios</span>
                        </div>
                        <button class="menu-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="menu-links">
                        <a href="index.php?page=reports&type=financial" class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" data-title="Financeiros">
                            <i class="fas fa-file-invoice"></i>
                            <span>Financeiros</span>
                        </a>
                        <a href="index.php?page=reports&type=trips" class="nav-link <?php echo ($currentPage === 'reports_trips') ? 'active' : ''; ?>" data-title="Viagens">
                            <i class="fas fa-route"></i>
                            <span>Viagens</span>
                        </a>
                        <a href="index.php?page=reports&type=maintenance" class="nav-link <?php echo ($currentPage === 'reports_maintenance') ? 'active' : ''; ?>" data-title="Manutenção">
                            <i class="fas fa-tools"></i>
                            <span>Manutenção</span>
                        </a>
                    </div>
                </div>

                <!-- Sair -->
                <div class="nav-section">
                    <a href="logout.php" class="nav-link" data-title="Sair">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                </div>
                
                <div class="header-right">
                    <!-- Seletor de Empresa -->
                    <div class="company-selector">
                        <select id="companySelect" class="header-select">
                            <option value="all">Todas as Empresas</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" style="color: <?php echo $company['color']; ?>">
                                <?php echo $company['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Seletor de Período -->
                    <div class="period-selector">
                        <select id="periodSelect" class="header-select">
                            <option value="weekly">Semanal</option>
                            <option value="monthly" selected>Mensal</option>
                            <option value="quarterly">Trimestral</option>
                            <option value="yearly">Anual</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>

                    <!-- Notificações -->
                    <div class="notifications">
                        <button class="notification-btn" id="notificationBtn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-count">3</span>
                        </button>
                        
                        <!-- Dropdown de Notificações -->
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notifications-header">
                                <h4>Notificações</h4>
                                <span class="notifications-badge">3 novas</span>
                            </div>
                            <div class="notifications-list">
                                <div class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">Manutenção preventiva</div>
                                        <div class="notification-message">Veículo ABC-1234 precisa de revisão</div>
                                        <div class="notification-time">Há 2 horas</div>
                                    </div>
                                </div>
                            </div>
                            <div class="notifications-footer">
                                <a href="#" class="view-all-link">Ver todas as notificações</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="user-menu">
                        <button class="user-btn" id="userBtn">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($currentUser['name'], 0, 2)); ?>
                            </div>
                            <span class="user-name"><?php echo $currentUser['name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <!-- Dropdown do Usuário -->
                        <div class="user-dropdown" id="userDropdown">
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <div class="user-avatar large">
                                        <?php echo strtoupper(substr($currentUser['name'], 0, 2)); ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?php echo $currentUser['name']; ?></div>
                                        <div class="user-role"><?php echo ucfirst($currentUser['role']); ?></div>
                                        <div class="user-email"><?php echo $currentUser['email']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="index.php?page=profile" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Meu Perfil</span>
                            </a>
                            <a href="index.php?page=settings" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Configurações</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Sair</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">