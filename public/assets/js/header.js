[file name]: header.js
[file content begin]
// public/assets/js/header.js
// JavaScript para Header - CORRIGIDO

console.log('🚀 Header Manager BT Log - Iniciando...');

// Aguardar DOM estar completamente carregado
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM carregado - Iniciando configuração do menu');
    
    // ===== ELEMENTOS =====
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Verificar se elementos existem
    if (!sidebar || !sidebarToggle) {
        console.error('❌ Elementos do menu não encontrados!');
        return;
    }
    
    console.log(`📁 Sidebar encontrada: ${sidebar ? 'Sim' : 'Não'}`);
    console.log(`🔘 Botão sidebar: ${sidebarToggle ? 'Sim' : 'Não'}`);
    
    // ===== SIDEBAR PRINCIPAL =====
    function setupSidebar() {
        console.log('⚙️ Configurando sidebar principal...');
        
        // Carregar estado salvo do localStorage
        const savedState = localStorage.getItem('sidebarCollapsed');
        const shouldCollapse = savedState === 'true';
        
        // Aplicar estado inicial
        if (shouldCollapse) {
            sidebar.classList.add('collapsed');
            console.log('📁 Sidebar carregada como RECOLHIDA');
        } else {
            sidebar.classList.remove('collapsed');
            console.log('📁 Sidebar carregada como EXPANDIDA');
        }
        
        // Adicionar evento de toggle
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('📁 Alternando estado da sidebar...');
            
            // Alternar classe collapsed
            sidebar.classList.toggle('collapsed');
            const isNowCollapsed = sidebar.classList.contains('collapsed');
            
            // Salvar no localStorage
            localStorage.setItem('sidebarCollapsed', isNowCollapsed);
            
            // Log do estado
            console.log(`📁 Sidebar: ${isNowCollapsed ? 'RECOLHIDA' : 'EXPANDIDA'}`);
            console.log(`💾 Estado salvo: ${isNowCollapsed}`);
            
            // Forçar reflow para garantir transição
            sidebar.style.display = 'none';
            sidebar.offsetHeight; // Trigger reflow
            sidebar.style.display = 'flex';
        });
        
        // Prevenir problemas de transição durante o redimensionamento
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('collapsed');
                }
            }, 250);
        });
    }
    
    // ===== SEÇÕES DO MENU (ACORDEÃO) =====
    function setupMenuSections() {
        console.log('⚙️ Configurando seções do menu...');
        
        const menuSections = document.querySelectorAll('.menu-section');
        console.log(`🔧 ${menuSections.length} seções de menu encontradas`);
        
        if (menuSections.length === 0) {
            console.warn('⚠️ Nenhuma seção de menu encontrada!');
            return;
        }
        
        // Carregar seção expandida do localStorage
        let expandedSection = localStorage.getItem('expandedSection');
        
        // Se não houver seção salva, expandir a seção que tem link ativo
        if (!expandedSection) {
            menuSections.forEach(function(section) {
                const hasActiveLink = section.querySelector('.nav-link.active');
                if (hasActiveLink) {
                    expandedSection = section.dataset.section;
                    console.log(`🎯 Seção "${expandedSection}" será expandida (tem link ativo)`);
                }
            });
        }
        
        // Se ainda não houver seção, expandir a primeira
        if (!expandedSection && menuSections.length > 0) {
            expandedSection = menuSections[0].dataset.section;
            console.log(`🎯 Expandindo primeira seção: "${expandedSection}"`);
        }
        
        // Configurar cada seção
        menuSections.forEach(function(section) {
            const sectionId = section.dataset.section;
            const toggleBtn = section.querySelector('.menu-toggle');
            const menuHeader = section.querySelector('.menu-header');
            const menuLinks = section.querySelector('.menu-links');
            
            if (!sectionId || !menuHeader) {
                console.warn(`⚠️ Seção sem ID ou header:`, section);
                return;
            }
            
            // Verificar se esta seção deve estar expandida
            const shouldExpand = sectionId === expandedSection;
            
            // Aplicar estado inicial
            if (shouldExpand) {
                section.classList.remove('collapsed');
                console.log(`📂 Seção "${sectionId}" inicializada como EXPANDIDA`);
            } else {
                section.classList.add('collapsed');
            }
            
            // Configurar clique no header da seção
            menuHeader.addEventListener('click', function(e) {
                // Não fazer nada se clicou no botão de toggle
                if (e.target.closest('.menu-toggle')) {
                    return;
                }
                toggleMenuSection(section);
            });
            
            // Configurar clique no botão de toggle
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMenuSection(section);
                });
            }
        });
        
        // Função para alternar seção (comportamento acordeão)
        function toggleMenuSection(clickedSection) {
            const sectionId = clickedSection.dataset.section;
            const isCurrentlyCollapsed = clickedSection.classList.contains('collapsed');
            
            console.log(`🎯 Alternando seção: "${sectionId}"`);
            console.log(`📌 Estado atual: ${isCurrentlyCollapsed ? 'RECOLHIDA' : 'EXPANDIDA'}`);
            
            if (isCurrentlyCollapsed) {
                // EXPANDIR esta seção
                console.log(`📂 Expandindo seção "${sectionId}"...`);
                
                // FECHAR todas as outras seções primeiro
                menuSections.forEach(function(section) {
                    if (section !== clickedSection) {
                        section.classList.add('collapsed');
                        console.log(`📂 Fechando seção "${section.dataset.section}"`);
                    }
                });
                
                // EXPANDIR esta seção
                clickedSection.classList.remove('collapsed');
                expandedSection = sectionId;
                
                console.log(`✅ Seção "${sectionId}" EXPANDIDA`);
            } else {
                // RECOLHER esta seção
                console.log(`📂 Recolhendo seção "${sectionId}"...`);
                
                clickedSection.classList.add('collapsed');
                expandedSection = null; // Nenhuma seção expandida
                
                console.log(`✅ Seção "${sectionId}" RECOLHIDA`);
            }
            
            // Salvar estado no localStorage
            localStorage.setItem('expandedSection', expandedSection);
            console.log(`💾 Estado salvo: "${expandedSection}"`);
            
            // Verificar visualmente
            console.log('🔍 Verificação de estado:');
            menuSections.forEach(function(section) {
                const isCollapsed = section.classList.contains('collapsed');
                console.log(`   ${section.dataset.section}: ${isCollapsed ? 'RECOLHIDA' : 'EXPANDIDA'}`);
            });
        }
    }
    
    // ===== MENU MOBILE =====
    function setupMobileMenu() {
        console.log('⚙️ Configurando menu mobile...');
        
        if (!mobileMenuToggle || !sidebarOverlay) {
            console.warn('⚠️ Elementos do menu mobile não encontrados');
            return;
        }
        
        // Abrir menu mobile
        mobileMenuToggle.addEventListener('click', function() {
            console.log('📱 Abrindo menu mobile...');
            
            sidebar.classList.add('show-mobile');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            console.log('✅ Menu mobile ABERTO');
        });
        
        // Fechar menu mobile (clicar no overlay)
        sidebarOverlay.addEventListener('click', function() {
            console.log('📱 Fechando menu mobile...');
            
            sidebar.classList.remove('show-mobile');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
            
            console.log('✅ Menu mobile FECHADO');
        });
        
        // Fechar menu ao clicar em link no mobile
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    console.log('📱 Link clicado no mobile - Fechando menu');
                    
                    sidebar.classList.remove('show-mobile');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Fechar menu ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show-mobile')) {
                console.log('ESC pressionado - Fechando menu mobile');
                
                sidebar.classList.remove('show-mobile');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }
    
    // ===== DROPDOWNS (Notificações e Usuário) =====
    function setupDropdowns() {
        console.log('⚙️ Configurando dropdowns...');
        
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        const userBtn = document.getElementById('userBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        // Notificações
        if (notificationBtn && notificationsDropdown) {
            notificationBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🔔 Alternando dropdown de notificações');
                
                // Fechar user dropdown se aberto
                if (userDropdown && userDropdown.classList.contains('show')) {
                    userDropdown.classList.remove('show');
                    userBtn.classList.remove('active');
                }
                
                // Alternar notificações
                notificationsDropdown.classList.toggle('show');
                notificationBtn.classList.toggle('active');
            });
        }
        
        // Usuário
        if (userBtn && userDropdown) {
            userBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('👤 Alternando dropdown do usuário');
                
                // Fechar notificações se abertas
                if (notificationsDropdown && notificationsDropdown.classList.contains('show')) {
                    notificationsDropdown.classList.remove('show');
                    notificationBtn.classList.remove('active');
                }
                
                // Alternar usuário
                userDropdown.classList.toggle('show');
                userBtn.classList.toggle('active');
            });
        }
        
        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function(e) {
            const isNotification = e.target.closest('.notifications');
            const isUserMenu = e.target.closest('.user-menu');
            
            if (!isNotification && notificationBtn && notificationsDropdown) {
                notificationsDropdown.classList.remove('show');
                notificationBtn.classList.remove('active');
            }
            
            if (!isUserMenu && userBtn && userDropdown) {
                userDropdown.classList.remove('show');
                userBtn.classList.remove('active');
            }
        });
        
        // Fechar dropdowns ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (notificationsDropdown && notificationsDropdown.classList.contains('show')) {
                    notificationsDropdown.classList.remove('show');
                    notificationBtn.classList.remove('active');
                }
                if (userDropdown && userDropdown.classList.contains('show')) {
                    userDropdown.classList.remove('show');
                    userBtn.classList.remove('active');
                }
            }
        });
    }
    
    // ===== SELECTORES (Empresa e Período) =====
    function setupSelectors() {
        console.log('⚙️ Configurando seletores...');
        
        const companySelect = document.getElementById('companySelect');
        const periodSelect = document.getElementById('periodSelect');
        
        if (companySelect) {
            companySelect.addEventListener('change', function() {
                console.log('🏢 Empresa selecionada:', this.value);
                // Aqui você pode adicionar lógica para atualizar dados baseados na empresa
            });
        }
        
        if (periodSelect) {
            periodSelect.addEventListener('change', function() {
                console.log('📅 Período selecionado:', this.value);
                // Aqui você pode adicionar lógica para atualizar dados baseados no período
            });
        }
    }
    
    // ===== INICIALIZAÇÃO =====
    function initHeaderManager() {
        console.log('🚀 Inicializando Header Manager...');
        
        try {
            setupSidebar();
            setupMenuSections();
            setupMobileMenu();
            setupDropdowns();
            setupSelectors();
            
            console.log('✅ Header Manager completamente configurado!');
            console.log('=== TESTE O ACORDEÃO ===');
            console.log('1. Clique em "Cadastros" → outras seções fecham');
            console.log('2. Clique em "Financeiro" → Cadastros fecha');
            console.log('3. Apenas UMA seção pode estar aberta por vez');
            console.log('4. Estado salvo no localStorage');
            
        } catch (error) {
            console.error('❌ Erro ao configurar Header Manager:', error);
        }
    }
    
    // Inicializar com pequeno delay para garantir tudo carregado
    setTimeout(initHeaderManager, 100);
    
    // Adicionar classe para indicar que JS está funcionando
    document.body.classList.add('js-enabled');
});

// Exportar para uso global (se necessário)
window.HeaderManager = {
    init: function() {
        console.log('Header Manager inicializado via window');
    }
};
[file content end]