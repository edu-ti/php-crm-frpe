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
                            <option value="lost_reasons">Motivos de Perda</option>
                            <option value="clients">Ranking de Clientes (Curva ABC)</option>
                            <option value="products">Vendas por Produto</option>
                            <option value="licitacoes">Licitações</option>
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
    document.getElementById('report-type').addEventListener('change', loadReportData);
    document.getElementById('refresh-report-btn').addEventListener('click', loadReportData);
    document.getElementById('filter-start-date').addEventListener('change', loadReportData);
    document.getElementById('filter-end-date').addEventListener('change', loadReportData);
    // document.getElementById('filter-supplier').addEventListener('change', loadReportData); // Removed standard select
    // document.getElementById('filter-user').addEventListener('change', loadReportData); // Removed standard select
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

    // Carrega Inicial
    loadReportData();
    loadKPIData();
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
            <button type="button" class="multiselect-button" onclick="toggleMultiSelect('${selectId}')" id="${selectId}-btn">
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
    const defaultText = id.includes('supplier') ? 'Todos os Fornecedores' : 'Todos os Vendedores';
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

        currentReportData = response.report_data;
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
        renderSalesChart(data); // Using the main chart area, no separate table for now or maybe a simple summary
        // For forecast, we might want a simple summary table below too.
        const html = renderForecastTable(data);
        container.innerHTML = html;
        return;
    }

    if (type === 'funnel') {
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
        } else if (type === 'licitacoes') {
            tableHtml = renderLicitationsTable(group.rows);
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

    // Show chart for Sales, Clients, Funnel, Lost Reasons, Forecast
    if (!['sales', 'clients', 'funnel', 'lost_reasons', 'forecast'].includes(type) || !data || data.length === 0) {
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
        if (titleEl) titleEl.innerText = "Distribuição de Motivos de Perda";

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
    if (type === 'funnel') {
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

function renderSalesTable(group, monthsRange) {
    const rows = group.rows;
    const userTargetsEnabled = group.user_targets_enabled !== 0; // Default true if missing
    const supplierMetaMensal = parseFloat(group.meta_mensal) || 0;

    // We'll calculate the periodic goal for the Total row based on selected months
    // Ideally this comes from backend, but here we can approximate: meta_mensal * num_months
    const numMonths = monthsRange.length;

    const monthKeys = monthsRange.map(m => m.key);

    // Helper format
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    // Calculate Totals per Month (Sum of Users)
    const totals = monthKeys.reduce((acc, monthKey) => {
        acc[monthKey] = { venda: 0, meta: 0, saldo: 0 };
        rows.forEach(row => {
            const cellData = row.dados_mes[monthKey] || { venda: 0, meta: 0 };
            const venda = parseFloat(cellData.venda) || 0;
            const meta = parseFloat(cellData.meta) || 0;
            acc[monthKey].venda += venda;
            // If user targets enabled, sum them up. Else we'll handle meta differently in display (use global)
            acc[monthKey].meta += meta;
            acc[monthKey].saldo += (venda - meta);
        });
        return acc;
    }, {});

    // Headers
    const monthHeaders = monthsRange.map(m =>
        `<th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">${m.label}</th>`
    ).join('');

    // Body
    const tableBody = rows.map(row => {
        let rowVenda = 0, rowMeta = 0;

        const cells = monthKeys.map(key => {
            const d = row.dados_mes[key] || { venda: 0, meta: 0 };
            const v = parseFloat(d.venda) || 0;
            const m = parseFloat(d.meta) || 0;
            const s = v - m;
            rowVenda += v; rowMeta += m;

            const saldoClass = s >= 0 ? 'text-green-600' : 'text-red-600';
            const bgClass = (userTargetsEnabled && m > 0) ? (v >= m ? 'bg-green-50' : 'bg-red-50') : '';

            return `
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500 border-r border-gray-200 text-right ${bgClass}">
                    <div class="font-medium text-gray-900">${v > 0 ? format(v) : '-'}</div>
                    ${(userTargetsEnabled && m > 0) ? `<div class="text-gray-400 text-[10px]">M: ${format(m)}</div>` : ''}
                    ${(userTargetsEnabled && m > 0) ? `<div class="${saldoClass} font-bold border-t border-gray-100 mt-1 pt-1 text-[10px]">S: ${format(s)}</div>` : ''}
                </td>
            `;
        }).join('');

        const rowSaldo = rowVenda - rowMeta;
        const rowSaldoClass = rowSaldo >= 0 ? 'text-green-600' : 'text-red-600';

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                    ${row.vendedor_nome}
                </td>
                ${cells}
                <td class="px-4 py-3 whitespace-nowrap text-sm text-right bg-gray-50 font-bold border-l border-gray-200">
                    <div>${format(rowVenda)}</div>
                    ${userTargetsEnabled ? `<div class="text-[10px] text-gray-500">M: ${format(rowMeta)}</div>` : ''}
                    ${userTargetsEnabled ? `<div class="${rowSaldoClass} text-[10px] border-t border-gray-200 pt-1">S: ${format(rowSaldo)}</div>` : ''}
                </td>
            </tr>
        `;
    }).join('');

    // Totals Row Construction
    const totalsCells = monthKeys.map(key => {
        const t = totals[key];
        // If targets disabled, use supplier global meta monthly divided or just flat?
        // Usually global meta is monthly.
        const metaVal = userTargetsEnabled ? t.meta : supplierMetaMensal;
        const saldoVal = t.venda - metaVal;

        const sClass = saldoVal >= 0 ? 'text-green-600' : 'text-red-600';
        return `
            <td class="px-2 py-3 whitespace-nowrap text-xs text-right font-bold bg-gray-100 border-r border-gray-200">
                <div>${format(t.venda)}</div>
                <div class="text-gray-500 text-[10px]">${format(metaVal)}</div>
                <div class="${sClass} text-[10px]">${format(saldoVal)}</div>
            </td>
        `;
    }).join('');

    const grandVenda = Object.values(totals).reduce((a, b) => a + b.venda, 0);
    // Grand Meta: If user targets enabled, sum of user metas. If disabled, Sum of Monthly Global Metas for the period.
    const grandMeta = userTargetsEnabled
        ? Object.values(totals).reduce((a, b) => a + b.meta, 0)
        : (supplierMetaMensal * numMonths);

    const grandSaldo = grandVenda - grandMeta;
    const grandSaldoClass = grandSaldo >= 0 ? 'text-green-600' : 'text-red-600';

    return `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48 sticky left-0 bg-gray-50 z-10">Vendedor</th>
                        ${monthHeaders}
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">TOTAL</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${tableBody}
                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 border-r border-gray-200 sticky left-0 bg-gray-100 z-10">TOTAIS</td>
                        ${totalsCells}
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold bg-gray-200 border-l border-gray-200">
                            <div>${format(grandVenda)}</div>
                            <div class="text-gray-500 text-[10px]">${format(grandMeta)}</div>
                            <div class="${grandSaldoClass} text-[10px]">${format(grandSaldo)}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
}

function renderStateReport(group) {
    const stateSales = group.state_sales || {};
    const stateGoals = group.state_goals || {};

    // Get unique states from both sales and goals
    const states = [...new Set([...Object.keys(stateSales), ...Object.keys(stateGoals)])].sort();

    if (states.length === 0) return ''; // No state data

    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    let rowsHtml = '';
    let totalSales = 0;
    let totalGoal = 0;

    states.forEach(uf => {
        const sales = parseFloat(stateSales[uf]) || 0;
        const goal = parseFloat(stateGoals[uf]) || 0; // This is meta_anual usually
        const balance = sales - goal;

        totalSales += sales;
        totalGoal += goal;

        const balClass = balance >= 0 ? 'text-green-600' : 'text-red-500';

        rowsHtml += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${uf}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">${format(sales)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">${format(goal)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold ${balClass}">${format(balance)}</td>
            </tr>
        `;
    });

    const totalBal = totalSales - totalGoal;
    const totalBalClass = totalBal >= 0 ? 'text-green-600' : 'text-red-500';

    return `
        <div class="mt-8">
            <h4 class="text-md font-bold text-gray-700 mb-3 px-1 border-l-4 border-blue-500 pl-2">Performance por Estado</h4>
            <div class="overflow-x-auto rounded-lg shadow border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Vendas (Período)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Meta Anual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${rowsHtml}
                        <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                            <td class="px-6 py-4 text-sm text-gray-900">TOTAIS</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">${format(totalSales)}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-700">${format(totalGoal)}</td>
                            <td class="px-6 py-4 text-sm text-right ${totalBalClass}">${format(totalBal)}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    `;
}

function renderClientsTable(data) {
    const container = document.getElementById('report-results'); // Main container, actually in renderReports we use 'tableContainer' inside wrapper. 
    // But data for clients comes as a flat array in 'data', unlike sales which is grouped?
    // Wait, backend returns 'data' as array of rows. 
    // update renderReports to handle this structure difference.

    // For clients report, 'data' is the array of clients.
    // We shouldn't be using the 'group' loop if type is clients because it's not grouped by supplier in the same way?
    // Actually the backend code for 'clients' returns `['data' => $rows, 'type' => 'clients']`.
    // And `loadReportData` calls `renderReports(currentReportData, ...)`.
    // If 'clients', `currentReportData` is the array of rows.

    // In renderReports (line 482): `data.forEach(group => { ... })`
    // This expects grouping.
    // My backend implementation for 'clients' returned flat rows. 
    // So `renderReports` will break iterate over rows thinking they are groups?
    // I need to intercept inside renderReports BEFORE the forEach loop.

    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    const totalRevenue = data.reduce((acc, row) => acc + (parseFloat(row.valor_total) || 0), 0);

    return `
        <div class="mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid">
            <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 flex justify-between items-center">
                <h3 class="font-bold text-indigo-700">Ranking de Clientes (Curva ABC)</h3>
                <span class="text-xs bg-indigo-200 text-indigo-800 px-2 py-1 rounded-full">Top ${data.length}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd Vendas</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% Part.</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${data.map((row, index) => {
        const val = parseFloat(row.valor_total) || 0;
        const percent = totalRevenue > 0 ? (val / totalRevenue) * 100 : 0;
        return `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-center font-bold text-gray-500 border-r border-gray-100">${index + 1}</td>
                                    <td class="px-6 py-3 text-left font-medium text-gray-700">
                                        ${row.cliente_nome}
                                        ${index < 3 ? '<i class="fas fa-trophy text-yellow-500 ml-2"></i>' : ''}
                                    </td>
                                    <td class="px-6 py-3 text-center text-gray-600">${row.qtd_vendas}</td>
                                    <td class="px-6 py-3 text-right font-bold text-gray-800">${format(val)}</td>
                                    <td class="px-6 py-3 text-right text-gray-500">${percent.toFixed(1)}%</td>
                                </tr>
                            `;
    }).join('')}
                        <tr class="bg-gray-100 font-bold border-t-2 border-gray-200">
                            <td colspan="2" class="px-6 py-3 text-right text-gray-900">TOTAL</td>
                            <td class="px-6 py-3 text-center text-gray-900">${data.reduce((acc, r) => acc + parseInt(r.qtd_vendas), 0)}</td>
                            <td class="px-6 py-3 text-right text-gray-900">${format(totalRevenue)}</td>
                            <td class="px-6 py-3 text-right text-gray-900">100.0%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderFunnelTable(data) {
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
    const totalRevenue = data.reduce((acc, row) => acc + (parseFloat(row.valor_total) || 0), 0);
    const totalCount = data.reduce((acc, row) => acc + parseInt(row.qtd_oportunidades), 0);

    return `
        <div class="mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid">
            <div class="px-6 py-4 bg-teal-50 border-b border-teal-100 flex justify-between items-center">
                <h3 class="font-bold text-teal-700">Funil de Vendas (Conversão)</h3>
                <span class="text-xs bg-teal-200 text-teal-800 px-2 py-1 rounded-full">${totalCount} Oportunidades</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etapa</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor em Pipeline</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% (Volume)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${data.map((row, index) => {
        const val = parseFloat(row.valor_total) || 0;
        const count = parseInt(row.qtd_oportunidades) || 0;
        const percent = totalCount > 0 ? (count / totalCount) * 100 : 0;

        // Color Logic for Funnel visualization (Optional, creates a gradient effect)
        // const opacity = 1 - (index * 0.1); 
        // style="background-color: rgba(20, 184, 166, ${Math.max(0.1, opacity)})"

        return `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-left font-medium text-gray-700">
                                        <div class="flex items-center">
                                            <span class="w-6 h-6 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-xs mr-3 font-bold">${index + 1}</span>
                                            ${row.etapa_nome}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-600 font-bold">${count}</td>
                                    <td class="px-6 py-4 text-right text-gray-800">${format(val)}</td>
                                    <td class="px-6 py-4 text-right text-gray-500">
                                        <div class="flex items-center justify-end">
                                            <span class="mr-2">${percent.toFixed(1)}%</span>
                                            <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-teal-500 h-1.5 rounded-full" style="width: ${percent}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;
    }).join('')}
                        <tr class="bg-gray-100 font-bold border-t-2 border-gray-200">
                            <td class="px-6 py-4 text-right text-gray-900">TOTAL</td>
                            <td class="px-6 py-4 text-center text-gray-900">${totalCount}</td>
                            <td class="px-6 py-4 text-right text-gray-900">${format(totalRevenue)}</td>
                            <td class="px-6 py-4 text-right text-gray-900">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderLostReasonsTable(data) {
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
    const totalCount = data.reduce((acc, row) => acc + parseInt(row.qtd), 0);

    return `
        <div class="mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid">
            <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex justify-between items-center">
                <h3 class="font-bold text-red-700">Análise de Motivos de Perda</h3>
                <span class="text-xs bg-red-200 text-red-800 px-2 py-1 rounded-full">${totalCount} Perdidas</span>
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

// Fallback logic to prevent "is not defined" errors during cache updates
window.renderForecastChart = function (data) {
    if (typeof renderSalesChart === 'function') {
        renderSalesChart(data, [], 'forecast');
    }
}

function renderForecastTable(data) {
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
    const totalForecast = data.reduce((acc, row) => acc + (parseFloat(row.forecast_ponderado) || 0), 0);
    const totalPipeline = data.reduce((acc, row) => acc + (parseFloat(row.pipeline_total) || 0), 0);

    return `
        <div class="mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid">
            <div class="px-6 py-4 bg-purple-50 border-b border-purple-100 flex justify-between items-center">
                <h3 class="font-bold text-purple-700">Forecast (Previsão de Fechamento)</h3>
                <span class="text-xs bg-purple-200 text-purple-800 px-2 py-1 rounded-full">Total Ponderado: ${format(totalForecast)}</span>
            </div>
            <div class="p-6">
                <!-- Forecast Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
                        <span class="block text-xs font-semibold text-gray-400 uppercase">Pipeline Total</span>
                        <span class="block text-xl font-bold text-gray-800">${format(totalPipeline)}</span>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200 text-center">
                        <span class="block text-xs font-semibold text-indigo-400 uppercase">Forecast Ponderado</span>
                        <span class="block text-xl font-bold text-indigo-700">${format(totalForecast)}</span>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200 text-center">
                        <span class="block text-xs font-semibold text-green-400 uppercase">Confiança Geral</span>
                        <span class="block text-xl font-bold text-green-700">${totalPipeline > 0 ? ((totalForecast / totalPipeline) * 100).toFixed(1) + '%' : '0%'}</span>
                    </div>
                </div>

                <div class="flex items-center justify-center text-gray-400 text-sm italic">
                    (Visualize a evolução temporal no gráfico acima)
                </div>
            </div>
        </div>
    `;
}

function renderProductsTable(rows) {
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    return `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Unit. (Médio/Max)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${rows.map(row => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900">${row.produto_nome || '-'}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center">${row.quantidade}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">${format(row.valor_unitario)}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium text-right">${format(row.valor_total)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderLicitationsTable(rows) {
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

    return `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Edital</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UASG</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objeto</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                     ${rows.map(row => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium">${row.numero_edital || '-'}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${row.uasg || '-'}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs" title="${row.objeto || ''}">${row.objeto || '-'}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right">${format(row.valor_total)}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    ${row.fase_id || 'Ativo'}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function getMonthsBetween(start, end) {
    const s = new Date(start + '-01T00:00:00');
    const e = new Date(end + '-01T00:00:00');
    s.setMinutes(s.getMinutes() + s.getTimezoneOffset());
    e.setMinutes(e.getMinutes() + e.getTimezoneOffset());

    const result = [];
    let curr = new Date(s);

    while (curr <= e) {
        const y = curr.getFullYear();
        const m = curr.getMonth() + 1;
        const key = `${y}-${m}`;
        const label = curr.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' }).toUpperCase().replace('.', '');
        result.push({ key, label });
        curr.setMonth(curr.getMonth() + 1);
    }
    return result;
}

function exportToExcel() {
    const content = document.getElementById('reports-output-area').cloneNode(true);
    // Remove loading
    const loading = content.querySelector('#report-loading');
    if (loading) loading.remove();

    const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 5px; }
                h3 { font-size: 14px; font-weight: bold; background-color: #eee; }
            </style>
        </head>
        <body>
            ${content.innerHTML}
        </body>
        </html>
    `;

    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Relatorio_${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function setupModalLinks() {
    const modal = document.getElementById('targets-modal');
    if (!modal) return;
    const close = modal.querySelector('.close-modal');
    const closeBtn = modal.querySelector('.close-modal.btn'); // Cancel button

    const setTargetsBtn = document.getElementById('set-targets-btn');
    if (setTargetsBtn) {
        setTargetsBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            const supSelect = document.getElementById('target-supplier-select');
            const suppliers = appState.fornecedores || [];
            supSelect.innerHTML = '<option value="">Selecione...</option>' +
                suppliers.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');

            document.getElementById('targets-grid-container').innerHTML = '<p class="text-gray-500 italic p-4">Selecione um fornecedor para editar (Necessário selecionar Data Inicial).</p>';
            supSelect.onchange = (e) => loadTargetsEditor(e.target.value);
        });
    }

    const hide = () => modal.classList.add('hidden');
    if (close) close.addEventListener('click', hide);
    if (closeBtn) closeBtn.addEventListener('click', hide);

    document.getElementById('save-targets-btn').addEventListener('click', saveTargets);
}

function loadTargetsEditor(supplierId, year = null) {
    if (!supplierId) return;
    const container = document.getElementById('targets-grid-container');
    const allUsers = appState.users.filter(u => ['Vendedor', 'Representante', 'Comercial', 'Gestor', 'Analista'].includes(u.role));

    // Determine year: passed arg > current filter > current real year
    if (!year) {
        const startVal = document.getElementById('filter-start-date').value;
        year = startVal ? startVal.split('-')[0] : new Date().getFullYear();
    }

    // Show loading skeleton or similar? For now just keep old until fetch done.

    // Fetch Data from Backend
    apiCall('get_supplier_targets', { params: { supplier_id: supplierId, year: year } })
        .then(response => {
            if (!response.success) {
                container.innerHTML = `<p class="text-red-500">Erro ao carregar metas: ${response.error}</p>`;
                return;
            }

            const data = response.data;
            const metaAnualTotal = data.meta_anual || 0;
            const stateTargets = data.state_targets || {};
            const targets = data.targets || {};
            const userTargetsEnabled = data.user_targets_enabled !== 0; // Default true

            // Initialize states from DB or default
            let states = Object.keys(stateTargets);
            if (states.length === 0) states = ['PE', 'PB', 'RN'];

            // Helper to format/parse (relying on global helpers added below)
            const fmt = (v) => formatCurrency(v);

            // Styles
            // Styles
            const inputClass = "form-input text-right text-xs border-gray-300 rounded w-full focus:ring-indigo-500 focus:border-indigo-500 font-mono p-1 h-8";
            const headerClass = "border bg-gray-100 text-center w-24 px-1 text-[10px] font-bold uppercase";

            // --- HEADER ---
            let html = `
                <div class="mb-6">
                    <div class="p-5 bg-white rounded-lg border border-gray-200 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                        <div class="flex flex-wrap gap-6 items-center" id="header-state-inputs">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wider">Ano Base</label>
                                <input type="number" id="target-year-input" class="form-input font-bold text-gray-900 w-24 text-center border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500" value="${year}">
                            </div>
                             
                            <div class="pl-6 border-l border-gray-200">
                                 <label class="block text-xs font-bold text-gray-700 mb-1 text-indigo-900 uppercase tracking-wider">Meta Global (R$)</label>
                                 <input type="text" id="sup-meta-annual-display" class="form-input text-right font-bold text-sm text-gray-900 w-48 bg-gray-50 border-gray-200" value="${fmt(metaAnualTotal)}" readonly>
                                 <input type="hidden" id="sup-meta-annual" value="${metaAnualTotal}">
                                 <p class="text-[10px] text-gray-400 mt-1 flex items-center"><i class="fas fa-calculator mr-1"></i> Soma automática</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <div id="add-state-container" class="flex items-center gap-2">
                                <button id="btn-show-add-state" class="bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300 text-xs px-4 py-2 rounded-md flex items-center transition-colors shadow-sm font-medium">
                                    <i class="fas fa-plus-circle mr-2"></i> Adicionar Estado
                                </button>
                                
                                <div id="add-state-form" class="hidden flex items-center gap-2 animate-fade-in">
                                     <input type="text" id="new-state-input" class="form-input text-sm border-gray-300 rounded w-20 uppercase font-bold text-center" placeholder="UF" maxlength="2">
                                     <button id="btn-confirm-add-state" class="bg-green-600 hover:bg-green-700 text-white p-2 rounded shadow-sm hover:scale-105 transition-transform" title="Confirmar">
                                        <i class="fas fa-check"></i>
                                     </button>
                                     <button id="btn-cancel-add-state" class="bg-gray-200 hover:bg-gray-300 text-gray-600 p-2 rounded shadow-sm hover:scale-105 transition-transform" title="Cancelar">
                                        <i class="fas fa-times"></i>
                                     </button>
                                </div>
                            </div>
                            
                            <div class="flex items-center text-xs text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span>Os valores são formatados automaticamente como moeda.</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // --- STATE GRID ---
            html += `<div class="mb-6 border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 font-bold text-sm flex justify-between items-center text-gray-700">
                    <div class="flex items-center">
                        <i class="fas fa-map-marked-alt mr-2 text-indigo-500"></i>
                        <span>Metas por Estado (Mensal)</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm bg-white" id="state-grid-table">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="p-3 text-left border-b w-32 font-bold text-xs uppercase tracking-wider">Estado</th>`;
            for (let i = 1; i <= 12; i++) html += `<th class="${headerClass}">${i}</th>`;
            html += `</tr></thead><tbody></tbody></table></div></div>`;

            // --- USER GRID ---
            html += `<div class="mb-4 border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 font-bold text-sm flex justify-between items-center text-gray-700">
                    <div class="flex items-center">
                        <i class="fas fa-users mr-2 text-indigo-500"></i>
                        <span>Metas por Vendedor</span>
                    </div>
                    <div class="flex items-center">
                        <label class="inline-flex items-center cursor-pointer group">
                            <input type="checkbox" id="toggle-user-targets" class="form-checkbox h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" ${userTargetsEnabled ? 'checked' : ''}>
                            <span class="ml-2 text-xs font-medium text-gray-600 group-hover:text-indigo-600 transition-colors">Habilitar metas por vendedor</span>
                        </label>
                    </div>
                </div>
                <div class="overflow-x-auto transition-opacity duration-300 ${userTargetsEnabled ? '' : 'opacity-50 pointer-events-none'}" id="user-grid-wrapper">
                    <table class="w-full text-sm bg-white relative">
                        <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600">
                            <tr>
                                <th class="p-3 text-left border-b min-w-[200px] font-bold text-xs uppercase tracking-wider">
                                    Vendedor
                                    <span class="text-[10px] font-normal text-gray-400 ml-1 block normal-case">(Marque para ativar)</span>
                                </th>`;
            for (let i = 1; i <= 12; i++) html += `<th class="${headerClass}">${i}</th>`;
            html += `</tr></thead><tbody>`;

            allUsers.forEach(u => {
                let userTargets = targets[u.id] || {};
                let hasTarget = Object.values(userTargets).some(v => v > 0);

                let cells = '';
                for (let i = 1; i <= 12; i++) {
                    let val = userTargets[i] || 0;
                    cells += `<td class="border p-1"><input type="text" class="${inputClass} user-month-input currency-input" data-user="${u.id}" data-month="${i}" value="${val > 0 ? fmt(val) : ''}" ${hasTarget ? '' : 'disabled'}></td>`;
                }

                html += `<tr class="hover:bg-indigo-50 transition-colors group">
                    <td class="p-2 border text-gray-700 flex items-center bg-white sticky left-0 z-10 group-hover:bg-indigo-50 transition-colors">
                        <input type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600 rounded border-gray-300 mr-2 user-active-check focus:ring-indigo-500" data-user="${u.id}" ${hasTarget ? 'checked' : ''}>
                        <span class="${hasTarget ? 'font-bold text-gray-900' : ''}">${u.nome}</span>
                    </td>
                    ${cells}
                </tr>`;
            });
            html += `</tbody></table></div></div>`;

            container.innerHTML = html;

            // --- CURRENCY BEHAVIOR ---
            const attachCurrencyEvents = (input) => {
                input.addEventListener('focus', function () {
                    this.select();
                });

                input.addEventListener('blur', function () {
                    const val = parseCurrency(this.value);
                    if (val > 0) this.value = formatCurrency(val);
                    else this.value = '';
                });

                // Simple restriction (optional)
                input.addEventListener('keypress', function (e) {
                    if (!/[\d,.]/.test(e.key) && e.key.length === 1 && e.key !== 'Enter') e.preventDefault();
                });
            };

            // --- DYNAMIC STATE FUNCTIONS ---
            const stateHeaderContainer = document.getElementById('header-state-inputs');
            const stateGridBody = document.querySelector('#state-grid-table tbody');

            const addStateToUI = (uf, annualVal = 0, monthlyData = {}) => {
                // Check duplicate
                if (container.querySelector(`.state-annual-input[data-state="${uf}"]`)) {
                    showToast(`Estado ${uf} já adicionado.`, 'warning');
                    return;
                }

                // 1. Add Header Input
                const div = document.createElement('div');
                div.innerHTML = `
                    <label class="block text-xs font-bold text-gray-700 mb-1">Meta Anual ${uf} (R$)</label>
                    <input type="text" class="form-input text-right text-gray-900 font-bold text-sm w-40 border-gray-300 rounded state-annual-input currency-input focus:ring-indigo-500 focus:border-indigo-500" data-state="${uf}" value="${annualVal > 0 ? fmt(annualVal) : ''}" placeholder="R$ 0,00">
                `;
                stateHeaderContainer.appendChild(div);

                // 2. Add Grid Row
                const tr = document.createElement('tr');
                let cells = '';
                for (let i = 1; i <= 12; i++) {
                    let val = monthlyData[i] || 0;
                    cells += `<td class="border p-1"><input type="text" class="${inputClass} state-month-input currency-input" data-state="${uf}" data-month="${i}" value="${val > 0 ? fmt(val) : ''}"></td>`;
                }
                tr.innerHTML = `
                    <td class="p-2 border font-bold text-gray-700 bg-gray-50 flex justify-between items-center group">
                        <span class="w-8 text-center bg-white border rounded px-1 text-xs shadow-sm">${uf}</span>
                        <button class="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity btn-remove-state p-1 rounded hover:bg-red-50" data-state="${uf}" title="Remover Estado">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                    ${cells}
                `;
                stateGridBody.appendChild(tr);

                // 3. Attach Events
                const annInput = div.querySelector('input');
                attachCurrencyEvents(annInput);
                annInput.addEventListener('blur', () => updateGrandTotal(container));

                tr.querySelectorAll('.state-month-input').forEach(inp => {
                    attachCurrencyEvents(inp);
                    inp.addEventListener('blur', () => {
                        let sum = 0;
                        container.querySelectorAll(`.state-month-input[data-state="${uf}"]`).forEach(mInp => {
                            sum += parseCurrency(mInp.value);
                        });
                        // Update Annual with formatted sum
                        annInput.value = sum > 0 ? formatCurrency(sum) : '';
                        updateGrandTotal(container);
                    });
                });

                tr.querySelector('.btn-remove-state').addEventListener('click', () => {
                    if (confirm(`Remover estado ${uf}?`)) {
                        div.remove();
                        tr.remove();
                        updateGrandTotal(container);
                    }
                });
            };

            // Initial Render of States
            states.forEach(uf => {
                const sData = stateTargets[uf] || {};
                addStateToUI(uf, sData.meta_anual || 0, sData.meta_mensal || {});
            });

            // Attach to existing user inputs
            container.querySelectorAll('.currency-input').forEach(inp => attachCurrencyEvents(inp));

            // Add State Logic (Custom UI)
            const btnShow = document.getElementById('btn-show-add-state');
            const formAdd = document.getElementById('add-state-form');
            const inputAdd = document.getElementById('new-state-input');
            const btnConfirm = document.getElementById('btn-confirm-add-state');
            const btnCancel = document.getElementById('btn-cancel-add-state');

            if (btnShow && formAdd) {
                btnShow.addEventListener('click', () => {
                    btnShow.classList.add('hidden');
                    formAdd.classList.remove('hidden');
                    inputAdd.value = '';
                    inputAdd.focus();
                });

                const hideAddForm = () => {
                    formAdd.classList.add('hidden');
                    btnShow.classList.remove('hidden');
                };

                btnCancel.addEventListener('click', hideAddForm);

                const performAdd = () => {
                    const uf = inputAdd.value.trim().toUpperCase();
                    if (uf && uf.length === 2) {
                        addStateToUI(uf);
                        updateGrandTotal(container);
                        hideAddForm();
                    } else {
                        showToast("Sigla inválida (Use 2 letras, ex: SP)", "error");
                        inputAdd.focus();
                    }
                };

                btnConfirm.addEventListener('click', performAdd);

                inputAdd.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') performAdd();
                });

                inputAdd.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') hideAddForm();
                });
            }

            // --- EVENT LISTENERS (Standard) ---

            // Year Change
            const yearInput = document.getElementById('target-year-input');
            if (yearInput) {
                yearInput.addEventListener('change', (e) => loadTargetsEditor(supplierId, e.target.value));
            }

            // User Targets Toggle
            const toggleUsers = document.getElementById('toggle-user-targets');
            const userWrapper = document.getElementById('user-grid-wrapper');
            if (toggleUsers) {
                toggleUsers.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        userWrapper.classList.remove('opacity-50', 'pointer-events-none');
                    } else {
                        userWrapper.classList.add('opacity-50', 'pointer-events-none');
                    }
                });
            }

            // User Rows Checkbox
            container.querySelectorAll('.user-active-check').forEach(chk => {
                chk.addEventListener('change', (e) => {
                    const uid = e.target.dataset.user;
                    const inputs = container.querySelectorAll(`.user-month-input[data-user="${uid}"]`);
                    inputs.forEach(inp => {
                        inp.disabled = !e.target.checked;
                        if (!e.target.checked) inp.value = '';
                    });
                    e.target.nextElementSibling.classList.toggle('font-bold', e.target.checked);
                    e.target.nextElementSibling.classList.toggle('text-gray-900', e.target.checked);
                });
            });

        })
        .catch(err => {
            console.error(err);
            container.innerHTML = `<p class="text-red-500">Erro de conexão ao buscar metas.</p>`;
        });
}

// --- CURRENCY HELPERS ---
function parseCurrency(str) {
    if (!str || str === '') return 0;
    if (typeof str === 'number') return str;
    // Remove "R$", trim, remove "." thousands sep, replace "," with "."
    let s = str.toString().replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
    return parseFloat(s) || 0;
}

function formatCurrency(val) {
    if (val === undefined || val === null || val === '') return '';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
}

function updateGrandTotal(container) {
    let grand = 0;
    container.querySelectorAll('.state-annual-input').forEach(inp => {
        grand += parseCurrency(inp.value);
    });
    const disp = document.getElementById('sup-meta-annual-display');
    const val = document.getElementById('sup-meta-annual');
    if (val) val.value = grand;
    if (disp) disp.value = formatCurrency(grand);
}

async function saveTargets() {
    const inputs = document.querySelectorAll('.target-edit-input');
    // Re-query inputs? No, we use specific collectors below.

    const supplierId = document.getElementById('target-supplier-select').value;

    // year
    const yearInput = document.getElementById('target-year-input');
    const year = yearInput ? yearInput.value : new Date().getFullYear();

    // Supplier Goals
    const supAnnualInput = document.getElementById('sup-meta-annual');
    const supAnnual = parseFloat(supAnnualInput ? supAnnualInput.value : 0) || 0;

    // User Enabled
    const userTargetsToggle = document.getElementById('toggle-user-targets');
    const userTargetsEnabled = userTargetsToggle ? userTargetsToggle.checked : false;

    // State Targets
    const stateTargets = {};
    const stateInputs = document.querySelectorAll('.state-annual-input');
    stateInputs.forEach(input => {
        const uf = input.dataset.state;
        const ann = parseCurrency(input.value);
        const monthly = {};
        document.querySelectorAll(`.state-month-input[data-state="${uf}"]`).forEach(inp => {
            const m = inp.dataset.month;
            const val = parseCurrency(inp.value);
            monthly[m] = val;
        });
        stateTargets[uf] = {
            annual: ann,
            monthly: monthly
        };
    });

    // User Targets
    const targets = [];
    if (userTargetsEnabled) {
        const inputs = document.querySelectorAll('.user-month-input');
        inputs.forEach(inp => {
            if (!inp.disabled) {
                const val = parseCurrency(inp.value);
                if (val > 0) {
                    targets.push({
                        usuario_id: inp.dataset.user,
                        fornecedor_id: supplierId,
                        mes: inp.dataset.month,
                        valor: val
                    });
                }
            }
        });
    }

    showLoading(true);
    try {
        const payload = {
            year,
            supplier_id: supplierId,
            supplier_goals: { annual: supAnnual, monthly: 0 },
            state_targets: stateTargets,
            targets,
            user_targets_enabled: userTargetsEnabled
        };
        const res = await apiCall('save_targets', { method: 'POST', body: JSON.stringify(payload) });
        if (res.success) {
            showToast('Metas salvas com sucesso!', 'success');
            document.getElementById('targets-modal').classList.add('hidden');
            loadReportData();
        } else {
            showToast(res.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Erro de conexão', 'error');
    } finally {
        showLoading(false);
    }
}
