// bt-log-transportes/public/assets/js/contracts/main.js
// COORDENADOR SIMPLIFICADO - APENAS CARREGAMENTO DE SCRIPTS

(function() {
    'use strict';
    
    console.log('🚀 Coordenador de contratos carregando...');
    
    // Lista de scripts na ordem correta
    const scripts = [
        '/bt-log-transportes/public/assets/js/contracts/contracts_manager.js',
        '/bt-log-transportes/public/assets/js/contracts/contracts_viewer.js',
        '/bt-log-transportes/public/assets/js/contracts/contracts_list.js'
    ];
    
    // Estado
    let scriptsLoaded = 0;
    let isInitializing = false;
    
    // ✅ Verificar se é página de contratos
    function isContractsPage() {
        const path = window.location.pathname;
        const search = window.location.search;
        
        return path.includes('contracts') || 
               search.includes('page=contracts') ||
               document.querySelector('.contracts-dashboard') !== null ||
               document.getElementById('contractsTable') !== null ||
               document.getElementById('contractModal') !== null;
    }
    
    // ✅ Carregar scripts
    function loadScripts() {
        if (!isContractsPage()) {
            console.log('📄 Não é página de contratos, ignorando...');
            return;
        }
        
        console.log('📦 Carregando scripts para página de contratos...');
        
        scripts.forEach(scriptSrc => {
            const script = document.createElement('script');
            script.src = scriptSrc;
            script.async = false; // Importante: carregar em ordem
            
            script.onload = () => {
                scriptsLoaded++;
                console.log(`✅ ${scriptSrc} carregado (${scriptsLoaded}/${scripts.length})`);
                
                if (scriptsLoaded === scripts.length) {
                    console.log('🎉 Todos os scripts carregados');
                    initializeManagers();
                }
            };
            
            script.onerror = () => {
                scriptsLoaded++;
                console.error(`❌ Falha ao carregar ${scriptSrc}`);
                
                if (scriptsLoaded === scripts.length) {
                    initializeManagers();
                }
            };
            
            document.head.appendChild(script);
        });
    }
    
    // ✅ Inicializar managers
    function initializeManagers() {
        if (isInitializing) return;
        isInitializing = true;
        
        console.log('🔄 Inicializando managers de contratos...');
        
        const managers = [
            { name: 'contractsManager', init: 'initializePage' },
            { name: 'contractsViewer', init: 'initializePage' },
            { name: 'contractsListManager', init: 'initializePage' }
        ];
        
        managers.forEach(manager => {
            if (window[manager.name] && typeof window[manager.name][manager.init] === 'function') {
                try {
                    console.log(`✅ Inicializando ${manager.name}...`);
                    window[manager.name][manager.init]();
                } catch (error) {
                    console.error(`❌ Erro em ${manager.name}:`, error);
                }
            } else {
                console.warn(`⚠️ ${manager.name} não disponível`);
            }
        });
        
        console.log('✅ Managers inicializados');
    }
    
    // ✅ Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM carregado, iniciando coordenador...');
            loadScripts();
        });
    } else {
        console.log('📄 DOM já carregado, iniciando coordenador agora...');
        loadScripts();
    }
    
    // Exportar API mínima
    window.contractsMain = {
        loadScripts,
        initializeManagers,
        isContractsPage
    };
    
})();