<?php
// app/views/layouts/footer.php
$baseUrl = '/bt-log-transportes/public';
?>
            </div><!-- Fecha content-area -->
        </main><!-- Fecha main-content -->
    </div><!-- Fecha layout -->
    
    <!-- JavaScript INLINE (funciona porque está no final) -->
    <script>
    // ===== HEADER MANAGER - FUNCIONAL =====
    (function() {
        console.log('🚀 Header Manager iniciando...');
        
        // ===== ELEMENTOS =====
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        // ===== SIDEBAR PRINCIPAL =====
        if (sidebarToggle) {
            // Carregar estado salvo
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                console.log('📁 Sidebar carregada como RECOLHIDA');
            }
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                sidebar.classList.toggle('collapsed');
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
                
                console.log('📁 Sidebar:', isCollapsed ? 'RECOLHIDA' : 'EXPANDIDA');
            });
        }
        
        // ===== SEÇÕES DO MENU (ACORDEÃO) =====
        const menuSections = document.querySelectorAll('.menu-section');
        console.log(`🔧 ${menuSections.length} seções de menu encontradas`);
        
        // Variável para controlar seção expandida
        let expandedSection = localStorage.getItem('expandedSection') || 'principal';
        
        // Configurar cada seção
        menuSections.forEach(function(section) {
            const sectionId = section.dataset.section;
            const toggleBtn = section.querySelector('.menu-toggle');
            const menuHeader = section.querySelector('.menu-header');
            
            // Verificar se tem link ativo
            const hasActiveLink = section.querySelector('.nav-link.active');
            if (hasActiveLink) {
                expandedSection = sectionId;
            }
            
            // Aplicar estado inicial
            if (sectionId === expandedSection) {
                section.classList.remove('collapsed');
            } else {
                section.classList.add('collapsed');
            }
            
            // Toggle no header (clicar no título)
            if (menuHeader) {
                menuHeader.addEventListener('click', function(e) {
                    // Não fazer nada se clicou no botão de toggle
                    if (e.target.closest('.menu-toggle')) {
                        return;
                    }
                    toggleMenuSection(section);
                });
            }
            
            // Toggle no botão (clicar na seta)
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMenuSection(section);
                });
            }
        });
        
        // Função para toggle da seção (comportamento acordeão)
        function toggleMenuSection(clickedSection) {
            const sectionId = clickedSection.dataset.section;
            const isCollapsed = clickedSection.classList.contains('collapsed');
            
            if (isCollapsed) {
                // FECHAR TODAS AS OUTRAS SEÇÕES
                menuSections.forEach(function(section) {
                    if (section !== clickedSection) {
                        section.classList.add('collapsed');
                    }
                });
                
                // EXPANDIR ESTA SEÇÃO
                clickedSection.classList.remove('collapsed');
                expandedSection = sectionId;
                
                console.log(`📂 Seção "${sectionId}" EXPANDIDA (outras fechadas)`);
            } else {
                // RECOLHER ESTA SEÇÃO
                clickedSection.classList.add('collapsed');
                expandedSection = 'principal';
                
                console.log(`📂 Seção "${sectionId}" RECOLHIDA`);
            }
            
            // Salvar no localStorage
            localStorage.setItem('expandedSection', expandedSection);
        }
        
        // ===== MENU MOBILE =====
        if (mobileMenuToggle && sidebarOverlay) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.add('show-mobile');
                sidebarOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
                console.log('📱 Menu mobile ABERTO');
            });
            
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show-mobile');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
                console.log('📱 Menu mobile FECHADO');
            });
            
            // Fechar menu ao clicar em link no mobile
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('show-mobile');
                        sidebarOverlay.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                });
            });
        }
        
        // ===== DROPDOWNS =====
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        const userBtn = document.getElementById('userBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        // Notificações
        if (notificationBtn && notificationsDropdown) {
            notificationBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Fechar user dropdown se aberto
                if (userDropdown.classList.contains('show')) {
                    userDropdown.classList.remove('show');
                    userBtn.classList.remove('active');
                }
                
                // Toggle notificações
                notificationsDropdown.classList.toggle('show');
                notificationBtn.classList.toggle('active');
            });
        }
        
        // Usuário
        if (userBtn && userDropdown) {
            userBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Fechar notificações se abertas
                if (notificationsDropdown.classList.contains('show')) {
                    notificationsDropdown.classList.remove('show');
                    notificationBtn.classList.remove('active');
                }
                
                // Toggle usuário
                userDropdown.classList.toggle('show');
                userBtn.classList.toggle('active');
            });
        }
        
        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notifications') && !e.target.closest('.user-menu')) {
                notificationsDropdown.classList.remove('show');
                notificationBtn.classList.remove('active');
                userDropdown.classList.remove('show');
                userBtn.classList.remove('active');
            }
        });
        
        // ===== SELECTORES =====
        const companySelect = document.getElementById('companySelect');
        const periodSelect = document.getElementById('periodSelect');
        
        if (companySelect) {
            companySelect.addEventListener('change', function() {
                console.log('🏢 Empresa selecionada:', this.value);
            });
        }
        
        if (periodSelect) {
            periodSelect.addEventListener('change', function() {
                console.log('📅 Período selecionado:', this.value);
            });
        }
        
        // ===== MENSAGEM FINAL =====
        console.log('✅ Header Manager completamente configurado!');
        console.log('=== TESTE O ACORDEÃO ===');
        console.log('1. Clique em "Cadastros" → outras seções fecham');
        console.log('2. Clique em "Financeiro" → Cadastros fecha');
        console.log('3. Apenas UMA seção pode estar aberta por vez');
        
    })();
    </script>
</body>
</html>