import { apiCall } from '../api.js';
import { formatCurrency as formatCurrencyUtil, showToast, showLoading } from '../utils.js';

let appState = {};
let chartInstance = null;

export async function renderReportsView(state) {
    if (state) appState = state;
    const minDate = new Date().toISOString().split('T')[0];
    const currentYear = new Date().getFullYear();
    const startDefault = `${currentYear}-01`;
    const endDefault = `${currentYear}-12`;

    const viewContainer = document.getElementById('reports-view');
    viewContainer.innerHTML = `
        <div class="flex flex-col">
            <!-- KPI Cards Section -->
            <div id="kpi-cards-container" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 no-print">
                <!-- Card 1: Vendas no Ano -->
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase">Vendas no Ano (${currentYear})</p>
                            <p id="kpi-total-sales" class="text-2xl font-bold text-gray-800">R$ 0,00</p>
                        </div>
                        <div class="bg-green-100 p-2 rounded-full text-green-600">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Vendas Perdidas -->
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase">Vendas Perdidas (${currentYear})</p>
                            <p id="kpi-lost-sales" class="text-2xl font-bold text-gray-800">R$ 0,00</p>
                        </div>
                        <div class="bg-red-100 p-2 rounded-full text-red-600">
                            <i class="fas fa-thumbs-down text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Licitações Ativas -->
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase">Licitações Ativas</p>
                            <p id="kpi-active-bids" class="text-2xl font-bold text-gray-800">0</p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded-full text-blue-600">
                            <i class="fas fa-gavel text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section (Hidden by default, shown when data loaded) -->
            <div id="chart-container-wrapper" class="bg-white p-4 rounded-lg shadow mb-4 hidden no-print">
                <h3 class="text-lg font-bold text-gray-700 mb-2">Evolução de Vendas vs Metas</h3>
                <div class="h-64 w-full">
                    <canvas id="sales-chart"></canvas>
                </div>
            </div>

            <!-- Header e Filtros -->
            <div class="bg-white p-4 rounded-lg shadow mb-4 no-print border-l-4 border-indigo-600">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Relatórios
                    </h2>
                    
                    <div class="flex space-x-2 mt-2 md:mt-0 w-full md:w-auto">
                        <!-- Botão Toggle Filtros (Visível em todos dipositivos) -->
                        <button id="toggle-filters-btn" class="btn btn-secondary text-sm flex-grow md:flex-grow-0">
                            <i class="fas fa-filter mr-2"></i> Filtros
                        </button>

                         <!-- Botão de Metas (Apenas Gestor) -->
                        <button id="set-targets-btn" class="btn bg-purple-600 text-white hover:bg-purple-700 text-sm hidden md:inline-flex">
                            <i class="fas fa-bullseye mr-2"></i>Definir Objetivos
                        </button>
                    </div>
                </div>

                <!-- Barra de Filtros (Collapsible - Default Hidden) -->
                <!-- Removido md:flex para iniciar oculto também no desktop -->
                <div id="reports-filters-container" class="hidden flex-wrap items-end gap-4 mb-4 md:mb-0 transition-all duration-300 ease-in-out">
                    <div class="w-full md:w-auto">
                        <select id="report-type" class="form-select border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-40">
                            <option value="sales">Vendas Gerais</option>
                            <option value="forecast">Forecast (Previsão)</option>
                            <option value="funnel">Funil de Vendas</option>
                            <option value="lost_reasons">Propostas recusadas</option>
                            <option value="clients">Ranking de Clientes (Curva ABC)</option>
                            <option value="products">Vendas por Produto</option>
                            <option value="licitacoes_funnel">Licitações (Funil)</option>
                        </select>
                    </div>

                    <div class="flex space-x-2 w-full md:w-auto">
                         <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">De</label>
                            <input type="month" id="filter-start-date" class="form-input text-sm border-gray-300 rounded-md shadow-sm w-full md:w-48 font-semibold text-gray-700">
                        </div>
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Até</label>
                            <input type="month" id="filter-end-date" class="form-input text-sm border-gray-300 rounded-md shadow-sm w-full md:w-48 font-semibold text-gray-700">
                        </div>
                    </div>

                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Fornecedor</label>
                        <div id="filter-supplier-container" class="w-full md:w-64 relative">
                            <!-- Custom Multi-select injected here -->
                        </div>
                    </div>

                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Vendedor</label>
                         <div id="filter-user-container" class="w-full md:w-64 relative">
                            <!-- Custom Multi-select injected here -->
                        </div>
                    </div>

                    <!-- Novos Filtros (Fase 2) -->
                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Etapa</label>
                        <div id="filter-etapa-container" class="w-full md:w-48 relative"></div>
                    </div>
                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Origem</label>
                        <div id="filter-origem-container" class="w-full md:w-48 relative"></div>
                    </div>
                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-1">UF</label>
                        <div id="filter-uf-container" class="w-full md:w-32 relative"></div>
                    </div>
                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                        <div id="filter-status-container" class="w-full md:w-32 relative"></div>
                    </div>

                    <div class="flex flex-wrap gap-2 w-full md:w-auto ml-auto">
                        <button id="refresh-report-btn" class="btn btn-primary text-sm py-2 px-4 shadow-sm hover:shadow-md transition-shadow flex-grow md:flex-grow-0" title="Filtrar">
                             <i class="fas fa-filter mr-1"></i>Filtrar
                        </button>
                        <button id="export-excel-btn" class="btn bg-green-600 text-white hover:bg-green-700 text-sm py-2 px-4 shadow-sm hover:shadow-md transition-shadow flex-grow md:flex-grow-0" title="Excel">
                             <i class="fas fa-file-excel mr-1"></i>XLS
                        </button>
                        <button id="print-report-btn" class="btn btn-secondary text-sm py-2 px-4 shadow-sm hover:shadow-md transition-shadow flex-grow md:flex-grow-0" title="Imprimir/PDF">
                             <i class="fas fa-print mr-1"></i>PDF
                        </button>
                         <!-- Botão Metas no Mobile dentro do menu -->
                        <button id="set-targets-btn-mobile" class="md:hidden btn bg-purple-600 text-white hover:bg-purple-700 text-sm flex-grow w-full mt-2">
                            <i class="fas fa-bullseye mr-2"></i>Definir Objetivos
                        </button>
                    </div>
                </div>
            </div>

            <!-- Área de Relatórios (Tabelas) -->
            <div id="reports-output-area" class="print-container space-y-8 pb-8">
                <div id="report-loading" class="text-center p-8 hidden">
                    <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
                    <p class="mt-2 text-gray-500">Processando dados...</p>
                </div>
                <div id="report-content" class="space-y-8">
                    <!-- Tabelas injetadas aqui -->
                     <p class="text-center text-gray-500 mt-10">Use os filtros acima para gerar o relatório.</p>
                </div>
            </div>
        </div>

        <!-- Modal (Mantido) -->
        <div id="targets-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
             <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-4/5 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Definir Metas</h3>
                    <button class="close-modal"><i class="fas fa-times"></i></button>
                </div>
                <div class="mb-4">
                     <label class="block text-sm font-bold mb-1">Fornecedor</label>
                     <select id="target-supplier-select" class="border p-2 w-full rounded"></select>
                </div>
                <div id="targets-grid-container" class="overflow-x-auto mb-4"></div>
                <div class="flex justify-end space-x-2">
                    <button class="close-modal btn bg-gray-300">Cancelar</button>
                    <button id="save-targets-btn" class="btn bg-green-600 text-white">Salvar</button>
                </div>
            </div>
        </div>
        
        <style>
             /* Estilos de Impressão */
            @media print {
                @page { size: landscape; margin: 5mm; }
                body { background: white; -webkit-print-color-adjust: exact; }
                .no-print { display: none !important; }
                .sidebar, #main-header, #app-container { height: auto !important; overflow: visible !important; }
                #main-content { padding: 0 !important; }
                .print-container { overflow: visible !important; box-shadow: none !important; }
                
                table { page-break-inside: auto; width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }
                thead { display: table-header-group; }
                tr { page-break-inside: avoid; page-break-after: auto; }
                th, td { border: 1px solid #000; padding: 4px; text-align: right; }
                th { background-color: #f3f4f6 !important; font-weight: bold; text-align: center; }
                
                .supplier-header { background-color: #4f46e5 !important; color: white !important; font-size: 14px; text-align: left; padding: 8px; -webkit-print-color-adjust: exact; }
                .total-row td { background-color: #ffffcc !important; font-weight: bold; }
                .break-inside-avoid { page-break-inside: avoid; }
            }
            
            /* Tabela Padrão */
            .report-table { width: 100%; border-collapse: collapse; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .report-table th, .report-table td { border: 1px solid #e5e7eb; padding: 8px; text-align: right; font-size: 0.85rem; }
            .report-table th { background-color: #f9fafb; font-weight: 600; text-align: center; white-space: nowrap; }
            
            .cell-content { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; min-height: 32px; }
            .venda-val { font-weight: 600; color: #1f2937; }
            .meta-val { font-size: 0.7em; color: #9ca3af; }
            
            .text-green-600 { color: #059669; }
            .text-red-500 { color: #dc2626; }
            .bg-yellow-pale { background-color: #fef9c3; }

            /* Multi Select Custom Styles */
            .multiselect-dropdown { user-select: none; }
            .multiselect-button {
                 display: flex; justify-content: space-between; align-items: center;
                 width: 100%; padding: 0.5rem; background: white; border: 1px solid #d1d5db; border-radius: 0.375rem;
                 font-size: 0.875rem; color: #1f2937; cursor: pointer; text-align: left;
            }
            .multiselect-button:focus { outline: 2px solid #a5b4fc; border-color: #6366f1; }
            .multiselect-list {
                display: none; position: absolute; z-index: 50; width: 100%; max-height: 15rem; overflow-y: auto;
                background: white; border: 1px solid #d1d5db; border-radius: 0.375rem; margin-top: 0.25rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            .multiselect-list.show { display: block; }
            .multiselect-item {
                display: flex; align-items: center; padding: 0.5rem; cursor: pointer; transition: background-color 0.15s;
            }
            .multiselect-item:hover { background-color: #f3f4f6; }
            .multiselect-item input[type="checkbox"] { margin-right: 0.5rem; height: 1rem; width: 1rem; color: #4f46e5; border-radius: 0.25rem; border-color: #d1d5db; }

        </style>
    `;

    // Popula Filtros
    populateFilters();

    // Set default dates (current year)
    const now = new Date();
    // currentYear already declared at top of function
    document.getElementById('filter-start-date').value = `${currentYear}-01`;
    document.getElementById('filter-end-date').value = `${currentYear}-12`;

    // Event Listeners
    // document.getElementById('report-type').addEventListener('change', loadReportData); // Removido para evitar múltiplas chamadas
    document.getElementById('refresh-report-btn').addEventListener('click', loadReportData);
    // document.getElementById('filter-start-date').addEventListener('change', loadReportData); // Removido
    // document.getElementById('filter-end-date').addEventListener('change', loadReportData); // Removido
    document.getElementById('print-report-btn').addEventListener('click', () => window.print());
    document.getElementById('export-excel-btn').addEventListener('click', exportToExcel);

    // Toggle Filters Mobile & Desktop
    const toggleBtn = document.getElementById('toggle-filters-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const container = document.getElementById('reports-filters-container');
            container.classList.toggle('hidden');
            container.classList.toggle('flex'); // Add flex when visible to maintain layout
        });
    }

    // Mobile Target Button Listener
    const mobileTargetBtn = document.getElementById('set-targets-btn-mobile');
    if (mobileTargetBtn) {
        mobileTargetBtn.addEventListener('click', () => {
            const targetBtn = document.getElementById('set-targets-btn');
            if (targetBtn) targetBtn.click();
        });
    }

    // Modal
    setupModalLinks();

    // Restaura Filtros Salvos (se houver)
    restoreFilters();

    // Carrega Inicial
    loadReportData();
    loadKPIData();
}

function restoreFilters() {
    try {
        const saved = localStorage.getItem('reports_filters');
        if (!saved) return;
        const f = JSON.parse(saved);

        if (f.type) document.getElementById('report-type').value = f.type;
        if (f.start) document.getElementById('filter-start-date').value = f.start;
        if (f.end) document.getElementById('filter-end-date').value = f.end;

        // Restore MultiSelects
        const restoreMulti = (id, values) => {
            if (!values || !Array.isArray(values)) return;
            const checks = document.querySelectorAll(`.${id}-checkbox`);
            checks.forEach(chk => {
                chk.checked = values.includes(chk.value);
            });
            const defaultText = document.getElementById(`${id}-btn`).getAttribute('data-default-text') || 'Selecionar';
            updateMultiSelectText(id, defaultText);
        };

        if (f.suppliers) restoreMulti('supplier-select', f.suppliers);
        if (f.users) restoreMulti('user-select', f.users);
        if (f.etapas) restoreMulti('etapa-select', f.etapas);
        if (f.origens) restoreMulti('origem-select', f.origens);
        if (f.ufs) restoreMulti('uf-select', f.ufs);
        if (f.statuses) restoreMulti('status-select', f.statuses);

    } catch (e) {
        console.error("Erro ao restaurar filtros:", e);
    }
}

async function loadKPIData() {
    try {
        const response = await apiCall('get_report_kpis');
        if (response.success && response.kpis) {
            const { total_sales_year, lost_sales_year, active_bids } = response.kpis;

            const elTotal = document.getElementById('kpi-total-sales');
            if (elTotal) elTotal.innerText = formatCurrencyUtil(total_sales_year);

            const elLost = document.getElementById('kpi-lost-sales');
            if (elLost) elLost.innerText = formatCurrencyUtil(lost_sales_year);

            const elBids = document.getElementById('kpi-active-bids');
            if (elBids) elBids.innerText = active_bids;
        }
    } catch (e) {
        console.error("Erro ao carregar KPIs:", e);
    }
}

let currentReportData = [];

function populateFilters() {
    const suppliers = appState.fornecedores || [];
    renderMultiSelect('filter-supplier-container', 'supplier-select', suppliers.map(s => ({ value: s.id, label: s.nome })), 'Todos os Fornecedores');

    const users = appState.users || [];
    const sellers = users.filter(u => ['Vendedor', 'Representante', 'Comercial', 'Gestor', 'Analista'].includes(u.role));
    renderMultiSelect('filter-user-container', 'user-select', sellers.map(u => ({ value: u.id, label: u.nome })), 'Todos os Vendedores');

    // --- Novos Filtros ---

    // Etapas
    const stages = appState.stages || [];
    // Flatten funnels if structured differently, but appState.stages usually is flat list or we extract from funnels
    // If appState.stages is just list of objects with id/nome
    renderMultiSelect('filter-etapa-container', 'etapa-select', stages.map(s => ({ value: s.id, label: s.nome })), 'Todas as Etapas');

    // Origem
    const origens = ['Indicação', 'Google', 'Site', 'Instagram', 'Facebook', 'Email Marketing', 'Feira/Evento', 'Importado', 'Outros'];
    renderMultiSelect('filter-origem-container', 'origem-select', origens.map(o => ({ value: o, label: o })), 'Todas as Origens');

    // UF (Extract from Organizations)
    const orgs = appState.organizations || [];
    const ufs = [...new Set(orgs.map(o => o.estado).filter(uf => uf))].sort(); // Unique non-empty UFs
    renderMultiSelect('filter-uf-container', 'uf-select', ufs.map(uf => ({ value: uf, label: uf })), 'Todos os Estados');

    // Status
    const statuses = ['Aberto', 'Ganho', 'Perdido'];
    renderMultiSelect('filter-status-container', 'status-select', statuses.map(s => ({ value: s, label: s })), 'Todos os Status');

    // Close dropdowns on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.multiselect-dropdown')) {
            document.querySelectorAll('.multiselect-list').forEach(el => el.classList.remove('show'));
        }
    });
}

function renderMultiSelect(containerId, selectId, options, defaultText) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let html = `
        <div class="multiselect-dropdown relative" id="${selectId}-wrapper">
            <button type="button" class="multiselect-button" onclick="toggleMultiSelect('${selectId}')" id="${selectId}-btn" data-default-text="${defaultText}">
                <span class="truncate block" id="${selectId}-text">${defaultText}</span>
                <i class="fas fa-chevron-down text-gray-400 text-xs ml-2"></i>
            </button>
            <div class="multiselect-list" id="${selectId}-list">
                <div class="p-2 border-b border-gray-100 flex justify-between">
                     <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium" onclick="toggleAllMultiSelect('${selectId}', true)">Marcar Todos</button>
                     <button type="button" class="text-xs text-gray-500 hover:text-gray-700" onclick="toggleAllMultiSelect('${selectId}', false)">Limpar</button>
                </div>
                <!-- Options -->
                ${options.map(opt => `
                    <label class="multiselect-item">
                        <input type="checkbox" value="${opt.value}" class="${selectId}-checkbox" onchange="updateMultiSelectText('${selectId}', '${defaultText}')">
                        <span class="text-sm text-gray-700">${opt.label}</span>
                    </label>
                `).join('')}
            </div>
        </div>
    `;
    container.innerHTML = html;
}

// Global scope helpers for onclick events (since module scope)
window.toggleMultiSelect = function (id) {
    const list = document.getElementById(`${id}-list`);
    // Close others
    document.querySelectorAll('.multiselect-list').forEach(el => {
        if (el.id !== `${id}-list`) el.classList.remove('show');
    });
    list.classList.toggle('show');
};

window.toggleAllMultiSelect = function (id, selectAll) {
    const checkboxes = document.querySelectorAll(`.${id}-checkbox`);
    checkboxes.forEach(cb => cb.checked = selectAll);
    const btn = document.getElementById(`${id}-btn`);
    const defaultText = btn ? btn.getAttribute('data-default-text') : 'Selecionar';
    updateMultiSelectText(id, defaultText);
};

window.updateMultiSelectText = function (id, defaultText) {
    const checkboxes = document.querySelectorAll(`.${id}-checkbox:checked`);
    const btnText = document.getElementById(`${id}-text`);
    const total = document.querySelectorAll(`.${id}-checkbox`).length;

    if (checkboxes.length === 0) {
        btnText.innerText = defaultText; // Or 'Nenhum selecionado' but user wants default 'Todos' behavior if none explicit? Actually usually none means all in filters, or validation error. User screenshot implies 'Todos' is default.
        // If 0 selected, let's treat as "All" for UI Text or "Nenhum"?
        // Usually filters: empty means all. Let's keep "Todos" text if 0.
        // But logic is: if 0 selected sends null -> backend treats as all.
        btnText.innerText = defaultText;
        return;
    }

    if (checkboxes.length === total) {
        btnText.innerText = defaultText; // All selected
        return;
    }

    if (checkboxes.length === 1) {
        // Find label
        const label = checkboxes[0].nextElementSibling.innerText;
        btnText.innerText = label;
    } else {
        btnText.innerText = `${checkboxes.length} selecionados`;
    }
};

window.getMultiSelectValues = function (id) {
    const checkboxes = document.querySelectorAll(`.${id}-checkbox:checked`);
    // If none selected, return empty (implies all in backend logic if we passed null, or restrict?)
    // Based on backend change: empty sends null.
    return Array.from(checkboxes).map(cb => cb.value);
};

async function loadReportData() {
    const container = document.getElementById('report-content');
    const loading = document.getElementById('report-loading');

    const type = document.getElementById('report-type').value;
    const start = document.getElementById('filter-start-date').value;
    const end = document.getElementById('filter-end-date').value;

    // Get Multi-select values
    const supplierIds = window.getMultiSelectValues('supplier-select');
    const userIds = window.getMultiSelectValues('user-select');
    const etapaIds = window.getMultiSelectValues('etapa-select');
    const origemIds = window.getMultiSelectValues('origem-select');
    const ufIds = window.getMultiSelectValues('uf-select');
    const statusIds = window.getMultiSelectValues('status-select');

    // If empty is "All", let's pass empty array/null.
    // If user explicitly unchecks all, it implies "None"? Or "All"? 
    // In most dashboard filters, clearing selection = All.
    // My backend handles empty as All. Note UI says "Todos" when empty.

    const supplierPayload = supplierIds.length > 0 ? supplierIds.join(',') : '';
    const userPayload = userIds.length > 0 ? userIds.join(',') : '';
    const etapaPayload = etapaIds.length > 0 ? etapaIds.join(',') : '';
    const origemPayload = origemIds.length > 0 ? origemIds.join(',') : '';
    const ufPayload = ufIds.length > 0 ? ufIds.join(',') : '';
    const statusPayload = statusIds.length > 0 ? statusIds.join(',') : '';

    // Save Filters to LocalStorage
    localStorage.setItem('reports_filters', JSON.stringify({
        type: type,
        start: start,
        end: end,
        suppliers: supplierIds,
        users: userIds,
        etapas: etapaIds,
        origens: origemIds,
        ufs: ufIds,
        statuses: statusIds
    }));

    if (!start || !end) {
        showToast('Selecione o período.', 'warning');
        return;
    }

    loading.classList.remove('hidden');
    container.innerHTML = ''; // Limpa anterior

    try {
        // For end date, we need last day of month.
        let formattedEnd = '';
        if (end) {
            const [y, m] = end.split('-');
            const lastDay = new Date(y, m, 0).getDate();
            formattedEnd = `${end}-${lastDay}`;
        }

        const params = {
            report_type: type,
            start_date: `${start}-01`,
            end_date: formattedEnd,
            supplier_id: supplierPayload,
            user_id: userPayload,
            etapa_id: etapaPayload,
            origem: origemPayload, // API expects 'origem' (plural handled by explode) or 'origem_id'? Handler has 'origei' typo variable but gets 'origem'.
            uf: ufPayload,
            status: statusPayload
        };
        const response = await apiCall('get_report_data', { params });

        if (!response.success) throw new Error(response.error);

        // Standardized report_data
        currentReportData = response.report_data || []; // Fallback empty array
        renderReports(currentReportData, container, type, start, end);

    } catch (error) {
        console.error("Erro:", error);
        container.innerHTML = `<div class="bg-red-50 p-4 border border-red-200 text-red-700 rounded text-center">Erro ao carregar relatório: ${error.message}</div>`;
    } finally {
        loading.classList.add('hidden');
    }
}

function renderReports(data, container, type, startStr, endStr) {
    if (!data) data = [];
    if (!Array.isArray(data)) {
        console.warn('Data is not an array, converting from object:', data);
        data = Object.values(data);
    }

    if (data.length === 0) {
        container.innerHTML = `<div class="bg-blue-50 p-8 border border-blue-200 text-blue-700 rounded text-center">Nenhum dado encontrado para o período.</div>`;
        return;
    }

    const monthsRange = getMonthsBetween(startStr, endStr);

    // Render Chart
    if (typeof renderSalesChart === 'function') {
        renderSalesChart(data, monthsRange, type);
    }

    if (type === 'clients') {
        // Clients report is flat, not grouped
        const html = renderClientsTable(data);
        container.innerHTML = html;
        return;
    }

    if (type === 'forecast') {
        // Chart is already rendered via generic call at top if data exists
        // renderSalesChart(data, monthsRange, 'forecast'); 
        // For forecast, we might want a simple summary table below too.
        const html = renderForecastTable(data);
        container.innerHTML = html;
        return;
    }

    if (type === 'funnel' || type === 'licitacoes_funnel') {
        const html = renderFunnelTable(data);
        container.innerHTML = html;
        return;
    }

    if (type === 'lost_reasons') {
        const html = renderLostReasonsTable(data);
        container.innerHTML = html;
        return;
    }

    data.forEach(group => {
        const wrapper = document.createElement('div');
        wrapper.className = "mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid";

        const header = document.createElement('div');
        header.className = "px-6 py-4 bg-gray-50 border-b border-gray-200";
        header.innerHTML = `<h3 class="text-lg font-medium text-gray-900">${group.fornecedor_nome || 'Fornecedor'}</h3>`;
        wrapper.appendChild(header);

        let tableHtml = '';
        if (type === 'sales') {
            tableHtml = renderSalesTable(group, monthsRange);
            // Append State Report
            const stateReportHtml = renderStateReport(group);
            if (stateReportHtml) {
                tableHtml += stateReportHtml;
            }
        } else if (type === 'products') {
            tableHtml = renderProductsTable(group.rows);
        }

        const tableContainer = document.createElement('div');
        tableContainer.innerHTML = tableHtml;
        wrapper.appendChild(tableContainer);

        container.appendChild(wrapper);
    });
}

function renderSalesChart(data, monthsRange, type) {
    const ctx = document.getElementById('sales-chart');
    const container = document.getElementById('chart-container-wrapper');

    if (!ctx || !container) return;

    // Show chart for Sales, Clients, Funnel, Lost Reasons, Forecast, Licitacoes Funnel
    if (!['sales', 'clients', 'funnel', 'lost_reasons', 'forecast', 'licitacoes_funnel'].includes(type) || !data || data.length === 0) {
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');

    // Destroy existing chart if any
    if (chartInstance) {
        chartInstance.destroy();
    }

    // --- FORECAST CHART ---
    if (type === 'forecast') {
        const labels = data.map(r => r.mes);
        const forecastValues = data.map(r => parseFloat(r.forecast_ponderado) || 0);
        const pipelineValues = data.map(r => parseFloat(r.pipeline_total) || 0);

        const titleEl = container.querySelector('h3');
        if (titleEl) titleEl.innerText = "Forecast vs Pipeline Total";

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Previsão Ponderada',
                        data: forecastValues,
                        backgroundColor: 'rgba(124, 58, 237, 0.6)', // Purple-600
                        borderColor: 'rgba(124, 58, 237, 1)',
                        borderWidth: 1,
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Pipeline Total',
                        data: pipelineValues,
                        borderColor: 'rgba(156, 163, 175, 1)', // Gray-400
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.1,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
        return;
    }

    // --- LOST REASONS CHART ---
    if (type === 'lost_reasons') {
        const labels = data.map(r => r.motivo);
        const values = data.map(r => parseInt(r.qtd));

        const titleEl = container.querySelector('h3');
        if (titleEl) titleEl.innerText = "Distribuição de Propostas Recusadas";

        const backgroundColors = [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 205, 86)',
            'rgb(201, 203, 207)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
            'rgb(255, 159, 64)'
        ];

        chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColors,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Important for fitting
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
        return;
    }

    // --- FUNNEL CHART ---
    if (type === 'funnel' || type === 'licitacoes_funnel') {
        const labels = data.map(r => r.etapa_nome);
        const values = data.map(r => parseInt(r.qtd_oportunidades));
        // const valuesVal = data.map(r => parseFloat(r.valor_total)); // Maybe toggle between count/value?

        const titleEl = container.querySelector('h3');
        if (titleEl) titleEl.innerText = "Funil de Vendas (Quantidade)";

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Oportunidades',
                    data: values,
                    backgroundColor: 'rgba(20, 184, 166, 0.6)', // Teal-500
                    borderColor: 'rgba(20, 184, 166, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8, // Make bars thicker
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.raw + ' Oportunidades';
                            }
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
        return;
    }

    // --- CLIENT CLASSIFICATION CHART ---
    if (type === 'clients') {
        const topClients = data.slice(0, 10); // Top 10
        const labels = topClients.map(c => c.cliente_nome);
        const values = topClients.map(c => parseFloat(c.valor_total) || 0);

        // Update Title (Hack: We might want to make title dynamic in HTML, but here we go)
        const titleEl = container.querySelector('h3');
        if (titleEl) titleEl.innerText = "Top 10 Clientes (Valor Total)";

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Valor Comprado',
                    data: values,
                    backgroundColor: 'rgba(79, 70, 229, 0.6)', // Indigo-600
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal Bar
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: "compact" }).format(value);
                            }
                        }
                    }
                }
            }
        });
        return;
    } else {
        // Reset Title for Sales
        const titleEl = container.querySelector('h3');
        if (titleEl) titleEl.innerText = "Evolução de Vendas vs Metas";
    }

    // --- SALES CHART (Existing Logic) ---
    // Process Data
    const labels = monthsRange.map(m => m.label);
    const monthKeys = monthsRange.map(m => m.key);

    // Aggregating Totals
    const salesData = monthKeys.map(key => {
        let sum = 0;
        data.forEach(group => {
            (group.rows || []).forEach(row => {
                const cell = row.dados_mes[key];
                if (cell) sum += (parseFloat(cell.venda) || 0);
            });
        });
        return sum;
    });

    const goalsData = monthKeys.map(key => {
        let sum = 0;
        data.forEach(group => {
            // Check if user targets are enabled
            const userTargetsEnabled = group.user_targets_enabled !== 0; // Default true

            if (userTargetsEnabled) {
                // Sum individual user targets
                (group.rows || []).forEach(row => {
                    const cell = row.dados_mes[key];
                    if (cell) sum += (parseFloat(cell.meta) || 0);
                });
            } else {
                // Use monthly meta from supplier (flat)
                // Note: Logic in table uses 'meta_mensal' from supplier for total row if targets disabled
                sum += (parseFloat(group.meta_mensal) || 0);
            }
        });
        return sum;
    });

    // Chart Configuration
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Vendas Realizadas',
                    data: salesData,
                    borderColor: '#059669', // Green-600
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Meta',
                    data: goalsData,
                    borderColor: '#DC2626', // Red-600
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5], // Dashed line
                    tension: 0.1,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumSignificantDigits: 3 }).format(value);
                        }
                    }
                }
            }
        }
    });
}

function renderLostReasonsTable(data) {
    const totalCount = data.reduce((acc, row) => acc + parseInt(row.qtd), 0);

    return `
        <div class="mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid">
            <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex justify-between items-center">
                <h3 class="font-bold text-red-700">Análise de Propostas recusadas</h3>
                <span class="text-xs bg-red-200 text-red-800 px-2 py-1 rounded-full">${totalCount} Recusadas</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                <!-- Tabela -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${data.map((row, index) => {
        const count = parseInt(row.qtd) || 0;
        const percent = totalCount > 0 ? (count / totalCount) * 100 : 0;
        return `
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-700">${row.motivo}</td>
                                        <td class="px-4 py-3 text-sm text-center text-gray-600">${count}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-500">${percent.toFixed(1)}%</td>
                                    </tr>
                                `;
    }).join('')}
                        </tbody>
                    </table>
                </div>
                
                <!-- Chart Area (Managed by separate canvas in main container usually, but let's try embedding specific here or use the main one) -->
                <!-- Note: The main chart logic uses #sales-chart which is outside this container. -->
                <!-- For Pie chart, it's better to use the main chart area. -->
                <div class="flex items-center justify-center text-gray-400 text-sm italic">
                    (Visualize o gráfico acima)
                </div>
            </div>
        </div>
    `;
}

// ... (rest of file unchanged)
