import { apiCall } from '../api.js';
import { formatCurrency as formatCurrencyUtil, showToast, showLoading } from '../utils.js';

let appState = {};

export async function renderReportsView(state) {
    if (state) appState = state;
    const minDate = new Date().toISOString().split('T')[0];
    const currentYear = new Date().getFullYear();
    const startDefault = `${currentYear}-01`;
    const endDefault = `${currentYear}-12`;

    const viewContainer = document.getElementById('reports-view');
    viewContainer.innerHTML = `
        <div class="flex flex-col h-full">
            <!-- Header e Filtros -->
            <div class="bg-white p-4 rounded-lg shadow mb-4 no-print border-l-4 border-indigo-600">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Relatórios
                    </h2>
                    
                    <div class="flex space-x-2 mt-2 md:mt-0">
                         <!-- Botão de Metas (Apenas Gestor) -->
                        <button id="set-targets-btn" class="btn bg-purple-600 text-white hover:bg-purple-700 text-sm">
                            <i class="fas fa-bullseye mr-2"></i>Definir Objetivos
                        </button>
                    </div>
                </div>

                <!-- Barra de Filtros -->
                <div class="flex flex-wrap items-end gap-4 mb-4 md:mb-0">
                    <div>
                        <select id="report-type" class="form-select border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-40">
                            <option value="sales">Vendas Gerais</option>
                            <option value="products">Vendas por Produto</option>
                            <option value="licitacoes">Licitações</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                         <div class="flex flex-col">
                            <label class="text-xs text-gray-500 mb-1">De</label>
                            <input type="month" id="filter-start-date" class="form-input text-sm border-gray-300 rounded-md shadow-sm w-32">
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs text-gray-500 mb-1">Até</label>
                            <input type="month" id="filter-end-date" class="form-input text-sm border-gray-300 rounded-md shadow-sm w-32">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Fornecedor</label>
                        <select id="filter-supplier" class="form-select w-64 text-sm border-gray-300 rounded-md text-ellipsis overflow-hidden">
                            <option value="">Todos</option>
                            <!-- Populated via JS -->
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Vendedor</label>
                        <select id="filter-user" class="form-select w-64 text-sm border-gray-300 rounded-md text-ellipsis overflow-hidden">
                            <option value="">Todos</option>
                             <!-- Populated via JS -->
                        </select>
                    </div>

                    <div class="flex space-x-2 flex-grow md:flex-grow-0 ml-auto">
                        <button id="refresh-report-btn" class="btn btn-primary text-sm py-2 px-4" title="Filtrar">
                             <i class="fas fa-filter mr-1"></i>Filtrar
                        </button>
                        <button id="export-excel-btn" class="btn bg-green-600 text-white hover:bg-green-700 text-sm py-2 px-4" title="Excel">
                             <i class="fas fa-file-excel mr-1"></i>XLS
                        </button>
                        <button id="print-report-btn" class="btn btn-secondary text-sm py-2 px-4" title="Imprimir/PDF">
                             <i class="fas fa-print mr-1"></i>PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Área de Relatórios (Tabelas) -->
            <div id="reports-output-area" class="flex-1 overflow-y-auto print-container space-y-8 pb-8">
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
                tr { page-break-inside: avoid; page-break-after: auto; }
                th, td { border: 1px solid #000; padding: 4px; text-align: right; }
                th { background-color: #f3f4f6 !important; font-weight: bold; text-align: center; }
                
                .supplier-header { background-color: #4f46e5 !important; color: white !important; font-size: 14px; text-align: left; padding: 8px; -webkit-print-color-adjust: exact; }
                .total-row td { background-color: #ffffcc !important; font-weight: bold; }
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
    document.getElementById('filter-supplier').addEventListener('change', loadReportData);
    document.getElementById('filter-user').addEventListener('change', loadReportData);
    document.getElementById('print-report-btn').addEventListener('click', () => window.print());
    document.getElementById('export-excel-btn').addEventListener('click', exportToExcel);

    // Modal
    setupModalLinks();

    // Carrega Inicial
    loadReportData();
}

let currentReportData = [];

function populateFilters() {
    const suppliers = appState.fornecedores || [];
    const supSelect = document.getElementById('filter-supplier');
    if (supSelect) supSelect.innerHTML = '<option value="">Todos os Fornecedores</option>' +
        suppliers.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');

    const users = appState.users || [];
    const userSelect = document.getElementById('filter-user');
    // Filtra apenas vendedores/comerciais
    const sellers = users.filter(u => ['Vendedor', 'Representante', 'Comercial', 'Gestor', 'Analista'].includes(u.role));
    if (userSelect) userSelect.innerHTML = '<option value="">Todos os Vendedores</option>' +
        sellers.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
}

async function loadReportData() {
    const container = document.getElementById('report-content');
    const loading = document.getElementById('report-loading');

    const type = document.getElementById('report-type').value;
    const start = document.getElementById('filter-start-date').value;
    const end = document.getElementById('filter-end-date').value;
    const supplierId = document.getElementById('filter-supplier').value;
    const userId = document.getElementById('filter-user').value;

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
            supplier_id: supplierId,
            user_id: userId
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

    document.getElementById('set-targets-btn')?.addEventListener('click', () => {
        modal.classList.remove('hidden');
        const supSelect = document.getElementById('target-supplier-select');
        const suppliers = appState.fornecedores || [];
        supSelect.innerHTML = '<option value="">Selecione...</option>' +
            suppliers.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');

        document.getElementById('targets-grid-container').innerHTML = '<p class="text-gray-500 italic p-4">Selecione um fornecedor para editar (Necessário selecionar Data Inicial).</p>';
        supSelect.onchange = (e) => loadTargetsEditor(e.target.value);
    });

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
            const inputClass = "form-input text-right text-sm border-gray-300 rounded w-full focus:ring-indigo-500 focus:border-indigo-500 font-mono";
            const headerClass = "border bg-gray-100 text-center w-24 px-1 text-xs font-bold";

            // --- HEADER ---
            let html = `
                <div class="mb-6">
                    <div class="p-5 bg-white rounded-lg border border-gray-200 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                        <div class="flex flex-wrap gap-6 items-end" id="header-state-inputs">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wider">Ano Base</label>
                                <input type="number" id="target-year-input" class="form-input font-bold text-gray-900 w-24 text-center border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500" value="${year}">
                            </div>
                             
                            <div class="pl-6 border-l border-gray-200">
                                 <label class="block text-xs font-bold text-gray-700 mb-1 text-indigo-900 uppercase tracking-wider">Meta Global (R$)</label>
                                 <input type="text" id="sup-meta-annual-display" class="form-input text-right font-bold text-gray-900 w-48 bg-gray-50 border-gray-200" value="${fmt(metaAnualTotal)}" readonly>
                                 <input type="hidden" id="sup-meta-annual" value="${metaAnualTotal}">
                                 <p class="text-[10px] text-gray-400 mt-1 flex items-center"><i class="fas fa-calculator mr-1"></i> Soma automática dos estados</p>
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
                    <input type="text" class="form-input text-right text-gray-900 font-bold w-32 border-gray-300 rounded state-annual-input currency-input focus:ring-indigo-500 focus:border-indigo-500" data-state="${uf}" value="${annualVal > 0 ? fmt(annualVal) : ''}" placeholder="R$ 0,00">
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
    const year = document.getElementById('target-year-input')?.value || new Date().getFullYear();

    // Supplier Goals
    const supAnnual = parseFloat(document.getElementById('sup-meta-annual')?.value) || 0;

    // User Enabled
    const userTargetsEnabled = document.getElementById('toggle-user-targets')?.checked;

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
