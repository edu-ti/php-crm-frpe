// js/script.js (Arquivo Principal)

import { apiCall } from './api.js';
import { showToast, showLoading } from './utils.js';
import { renderDashboardView } from './views/dashboard.js';
import { renderFunilView } from './views/kanban.js';
import { renderClientsView } from './views/clients.js';
import { renderProposalsView, resetProposalState } from './views/proposals.js';
import { renderSettingsView } from './views/settings.js';
import { renderAgendaView } from './views/agenda.js';
import { renderLeadsView } from './views/leads.js';
import { renderCatalogView } from './views/catalog.js';
import { renderEmailMarketingView } from './views/email_marketing.js'; // Importa a nova view

// O estado da aplicação
export let appState = {
    currentUser: null,
    users: [],
    opportunities: [],
    organizations: [],
    contacts: [],
    clients_pf: [],
    proposals: [],
    pre_proposals: [],
    stages: [],
    fornecedores: [],
    vendasFornecedores: [],
    agendamentos: [],
    leads: [],
    products: [],
    charts: {},
    proposal: null,
    clientsView: { activeTab: 'organizations', searchTerm: '', isFormVisible: false, editingId: null },
    proposalsView: { currentPage: 1 },
    proposalSort: { column: 'data_criacao', direction: 'desc' },
    funilView: { activeTab: 'vendas', year: new Date().getFullYear(), selectedFornecedorId: null },
    activeView: 'dashboard',
    // Estado para a nova view de Email Marketing
    emailMarketingView: {
        selectedInterests: [],
        subject: '',
        body: '',
        recipientCount: 0,
        recipientEmails: []
    }
};

document.addEventListener('DOMContentLoaded', initializeApp);

export async function initializeApp() { // Tornada exportável para ser chamada de outros módulos
    showLoading(true);
    try {
        const data = await apiCall('get_data');
        Object.assign(appState, data);
        // Garante que o estado da nova view exista
        if (!appState.emailMarketingView) {
             appState.emailMarketingView = {
                selectedInterests: [],
                subject: '',
                body: '',
                recipientCount: 0,
                recipientEmails: []
            };
        }
        renderUI();
        // Tenta manter a view ativa, senão volta para o dashboard
        const viewToRender = appState.activeView && document.getElementById(`${appState.activeView}-view`) ? appState.activeView : 'dashboard';
        switchView(viewToRender);

    } catch (error) {
        console.error("Falha ao inicializar o app:", error);
        if (error.message && error.message.includes('401')) { // Verifica se error.message existe
            window.location.href = 'login.html'; // Redireciona para login.html
        } else {
            const appRoot = document.getElementById('app-root');
             if(appRoot) { // Verifica se appRoot existe
                 appRoot.innerHTML = `<div class="p-8 text-center"><p class="text-red-500">Erro fatal ao carregar dados. Verifique a conexão e tente recarregar.</p><p class="text-sm text-gray-600 mt-2">${error.message || 'Erro desconhecido.'}</p></div>`;
            }
        }
    } finally {
        showLoading(false);
    }
}

function switchView(viewName) {
    // Verifica se a view existe antes de tentar mudar
    if (!document.getElementById(`${viewName}-view`)) {
        console.warn(`View "${viewName}" não encontrada. Redirecionando para o dashboard.`);
        viewName = 'dashboard';
    }

    appState.activeView = viewName;
    document.querySelectorAll('.view-container').forEach(view => view.classList.add('hidden'));
    document.getElementById(`${viewName}-view`)?.classList.remove('hidden');

    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    document.getElementById(`nav-link-${viewName}`)?.classList.add('active');

    const renderFunction = {
        'dashboard': renderDashboardView,
        'funil': renderFunilView,
        'agenda': renderAgendaView,
        'clients': renderClientsView,
        'proposals': renderProposalsView,
        'leads': renderLeadsView,
        'settings': renderSettingsView,
        'catalog': renderCatalogView,
        'email-marketing': renderEmailMarketingView // Adiciona a nova função de renderização
    }[viewName];

    if (renderFunction) {
        try {
            renderFunction();
        } catch (error) {
            console.error(`Erro ao renderizar a view "${viewName}":`, error);
            // Opcional: Mostrar uma mensagem de erro na UI
             const viewContainer = document.getElementById(`${viewName}-view`);
             if (viewContainer) {
                 viewContainer.innerHTML = `<p class="text-red-500 p-4">Ocorreu um erro ao carregar esta seção.</p>`;
             }
        }
    } else {
         console.error(`Função de renderização para "${viewName}" não encontrada.`);
    }
}


function renderUI() {
    const { currentUser } = appState;
    if (!currentUser) return;
    const { permissions } = currentUser;

    const appRoot = document.getElementById('app-root');
    appRoot.innerHTML = `
        <div id="app-container" class="flex h-screen bg-gray-100">
            <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>
            <aside id="sidebar" class="sidebar w-64 bg-white shadow-md flex flex-col flex-shrink-0">
                <div class="p-4 border-b h-16 flex items-center justify-center">
                    <img src="imagens/LOGO-FR.webp" alt="Logo" class="h-10">
                </div>
                <nav id="main-nav" class="flex-1 p-2 space-y-1"></nav>
            </aside>
            <main class="flex-1 flex flex-col overflow-hidden">
                <header id="main-header" class="bg-white p-4 shadow-sm border-b flex justify-between items-center h-16">
                    <button id="menu-toggle-btn" class="md:hidden action-btn"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-gray-800 hidden sm:block">Bem-vindo, ${currentUser.nome}!</h1>
                    <div class="flex items-center space-x-4">
                        <button id="refresh-data-btn" class="action-btn" title="Atualizar Dados"><i class="fas fa-sync-alt"></i></button>
                        <div class="relative">
                            <button id="user-menu-btn" class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold">
                                    ${currentUser.nome ? currentUser.nome.charAt(0).toUpperCase() : '?'}
                                </div>
                            </button>
                            <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-20 hidden">
                                <a href="#" id="logout-btn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sair</a>
                            </div>
                        </div>
                    </div>
                </header>
                <div id="main-content" class="flex-1 overflow-y-auto p-6"></div>
            </main>
        </div>
    `;

    // Navegação Principal
    const navLinks = [
        { id: 'dashboard', icon: 'fa-chart-pie', text: 'Dashboard', permission: true },
        { id: 'funil', icon: 'fa-columns', text: 'Funil Vendas', permission: true },
        { id: 'leads', icon: 'fa-filter', text: 'Funil Leads Online', permission: permissions.canSeeLeads },
        { id: 'agenda', icon: 'fa-calendar-alt', text: 'Agenda', permission: true },
        { id: 'clients', icon: 'fa-users', text: 'Clientes', permission: true },
        { id: 'proposals', icon: 'fa-file-invoice-dollar', text: 'Propostas', permission: true },
        { id: 'catalog', icon: 'fa-book-open', text: 'Catálogo', permission: permissions.canSeeCatalog }, // Usar permissão
        { id: 'email-marketing', icon: 'fa-envelope-open-text', text: 'E-mail Marketing', permission: permissions.canManageLeads }, // Nova aba e permissão
        { id: 'settings', icon: 'fa-cog', text: 'Configurações', permission: permissions.canSeeSettings }
    ];

    document.getElementById('main-nav').innerHTML = navLinks
        .filter(link => link.permission) // Filtra baseado na permissão do usuário
        .map(link => `<a href="#" id="nav-link-${link.id}" class="nav-link" data-view="${link.id}"><i class="fas ${link.icon} mr-3 w-5"></i><span>${link.text}</span></a>`)
        .join('');

    // Conteúdo das Views
    document.getElementById('main-content').innerHTML = `
        <div id="dashboard-view" class="view-container"></div>
        <div id="funil-view" class="view-container"></div>
        <div id="agenda-view" class="view-container"></div>
        <div id="clients-view" class="view-container"></div>
        <div id="proposals-view" class="view-container"></div>
        <div id="leads-view" class="view-container"></div>
        <div id="email-marketing-view" class="view-container hidden"></div> <!-- Novo container para a view -->
        <div id="settings-view" class="view-container"></div>
        <div id="catalog-view" class="view-container"></div>
    `;

    addGlobalEventListeners();
}

function addGlobalEventListeners() {
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) logoutBtn.addEventListener('click', logout);

    const refreshBtn = document.getElementById('refresh-data-btn');
     if(refreshBtn) refreshBtn.addEventListener('click', () => {
        showToast('Atualizando dados...', 'info');
        initializeApp(); // Chama a função para recarregar tudo
    });


    const userMenuBtn = document.getElementById('user-menu-btn');
    if(userMenuBtn) userMenuBtn.addEventListener('click', () => {
         document.getElementById('user-menu-dropdown')?.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        const userMenuButton = document.getElementById('user-menu-btn');
        const userMenuDropdown = document.getElementById('user-menu-dropdown');
        // Fecha o menu do usuário se clicar fora dele
        if (userMenuButton && userMenuDropdown && !userMenuButton.contains(e.target) && !userMenuDropdown.contains(e.target)) {
             userMenuDropdown.classList.add('hidden');
        }
    });

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            switchView(link.dataset.view);
            // Em mobile, esconde o menu após clicar
            if (window.innerWidth < 768) {
                document.getElementById('sidebar')?.classList.remove('open');
                document.getElementById('sidebar-overlay')?.classList.add('hidden');
            }
        });
    });

    // Lógica do menu responsivo
    const menuToggleBtn = document.getElementById('menu-toggle-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if(menuToggleBtn && sidebar && overlay) {
        menuToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('hidden');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        });
    }
}

async function logout() {
    try {
        await apiCall('logout', { method: 'POST' });
    } catch(e) {
        console.error("Logout falhou.", e);
         // Mesmo que falhe no servidor, força o redirecionamento
    } finally {
         window.location.href = 'login.html'; // Redireciona para login.html
    }
}
