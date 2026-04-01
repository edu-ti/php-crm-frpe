import { apiCall } from '../api.js';
import { formatCurrency as formatCurrencyUtil, showToast, showLoading } from '../utils.js';

let appState = {};
let chartInstance = null;

export async function renderReportsView(state) {
    if (state) appState = state;
    const currentYear = new Date().getFullYear();
    const currentUser = appState.currentUser || {};
    const isAdminOrAnalyst = ['Gestor', 'Analista', 'Admin'].includes(currentUser.role);

    const viewContainer = document.getElementById('reports-view');
    viewContainer.innerHTML = `
        <div id="reports-module-container" class="flex flex-col min-h-screen bg-gray-50 text-gray-900">
            <!-- Professional BI Header & Tabs -->
            <div class="bg-white border-b no-print">
                <div class="px-6 py-4 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Gestão de Performance & BI
                        </h2>
                        <p class="text-sm text-gray-500">Monitoramento estratégico e análise de resultados</p>
                    </div>
                </div>
                
                <div class="px-6 flex space-x-8 overflow-x-auto">
                    <button class="report-tab active whitespace-nowrap py-4 border-b-2 border-transparent" data-tab="bi-dashboard">
                        <i class="fas fa-th-large mr-2"></i>Dashboard BI
                    </button>
                    <button class="report-tab whitespace-nowrap py-4 border-b-2 border-transparent" data-tab="detailed-reports">
                        <i class="fas fa-list-alt mr-2"></i>Relatórios Detalhes
                    </button>
                    ${isAdminOrAnalyst ? `
                    <button class="report-tab whitespace-nowrap py-4 border-b-2 border-transparent" data-tab="performance-mgmt">
                        <i class="fas fa-calculator mr-2"></i>Metas e Comissões
                    </button>` : ''}
                </div>
            </div>

            <div id="report-tab-content" class="p-4 md:p-6 flex-1">
                <!-- Tab contents injected here -->
            </div>

            <!-- Global Modals (Accessible from any tab) -->
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
            <!-- Hidden trigger for legacy logic -->
            <button id="set-targets-btn" class="hidden"></button>
        </div>

        <style>
            /* Glassmorphism & Premium UI Tokens */
            #reports-module-container { --glass: rgba(255, 255, 255, 0.7); --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
            
            #reports-module-container .report-tab {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-weight: 600;
                color: #94a3b8;
                padding: 1rem 1.5rem;
                position: relative;
            }
            #reports-module-container .report-tab:hover { color: #6366f1; }
            #reports-module-container .report-tab.active { color: #4f46e5; }
            #reports-module-container .report-tab.active::after {
                content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
                background: linear-gradient(90deg, #4f46e5, #818cf8); border-radius: 3px;
                animation: slideIn 0.3s ease-out;
            }

            @keyframes slideIn { from { width: 0; left: 50%; } to { width: 100%; left: 0; } }

            /* Bento Filters Style */
            #reports-module-container .filter-card {
                background: white;
                padding: 1rem;
                border-radius: 1.25rem;
                border: 1px solid #f1f5f9;
                box-shadow: var(--shadow-sm);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            #reports-module-container .filter-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
            #reports-module-container .filter-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; margin-bottom: 0.5rem; }
            #reports-module-container .filter-label i { margin-right: 0.5rem; color: #4f46e5; opacity: 0.6; }

            /* Custom Seller Card (Dashboard) */
            #reports-module-container .seller-card {
                min-width: 220px; flex: 1; min-height: 100px;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                padding: 1.25rem; border-radius: 1.25rem; border: 1px solid #e2e8f0;
                display: flex; align-items: center; gap: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s;
            }
            #reports-module-container .seller-card:hover { border-color: #818cf8; transform: scale(1.02); }
            #reports-module-container .seller-avatar {
                width: 48px; height: 48px; border-radius: 50%;
                background: #f1f5f9; display: flex; align-items: center; justify-content: center;
                font-weight: 900; color: #4f46e5; font-size: 1.25rem; border: 2px solid #eef2ff;
            }

            /* Custom Month Selectors */
            #reports-module-container .custom-date-row { display: flex; gap: 0.5rem; align-items: center; width: 100%; }
            #reports-module-container .custom-date-item {
                flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem;
                border-radius: 0.75rem; font-size: 0.75rem; font-weight: 600; color: #334155;
                outline: none; cursor: pointer; transition: all 0.2s;
            }
            #reports-module-container .custom-date-item:hover { border-color: #818cf8; background: white; }

            /* MultiSelect Dropdown Modern Style */
            #reports-module-container .multiselect-dropdown { width: 100%; position: relative; }
            #reports-module-container .multiselect-button {
                width: 100%; display: flex; align-items: center; justify-content: space-between;
                padding: 0.6rem 0.8rem; background: #f8fafc; border: 1px solid #e2e8f0;
                border-radius: 0.75rem; font-size: 0.75rem; font-weight: 600; color: #334155;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;
            }
            #reports-module-container .multiselect-button:hover { border-color: #818cf8; background: white; }
            #reports-module-container .multiselect-list {
                position: absolute; top: calc(100% + 5px); left: 0; right: 0;
                background: white; border: 1px solid #e2e8f0; border-radius: 1rem;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 50;
                display: none; flex-direction: column; max-height: 300px;
            }
            #reports-module-container .multiselect-list.show { display: flex; }
            #reports-module-container .multiselect-item {
                display: flex; align-items: center; padding: 0.6rem 0.8rem; gap: 0.6rem;
                cursor: pointer; transition: background 0.2s;
            }
            #reports-module-container .multiselect-item:hover { background: #f1f5f9; }
            #reports-module-container .multiselect-item input[type="checkbox"] {
                width: 1rem; height: 1rem; border-radius: 4px; border: 2px solid #cbd5e1;
                cursor: pointer; accent-color: #4f46e5;
            }

            /* KPIs and Cards Refinement */
            #reports-module-container .kpi-card {
                background: white; border: 1px solid #f1f5f9; border-radius: 1.5rem;
                padding: 1.5rem; box-shadow: var(--shadow-sm); transition: all 0.3s;
            }
            #reports-module-container .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }

            /* Grid Layouts */
            #reports-module-container .bento-grid { display: grid; gap: 1.5rem; }
            @media (min-width: 768px) {
                #reports-module-container .bento-grid { grid-template-columns: repeat(3, 1fr); }
                #reports-module-container .col-span-2 { grid-column: span 2 / span 2; }
                #reports-module-container .col-span-3 { grid-column: span 3 / span 3; }
            }

            @media print {
                .no-print { display: none !important; }
                #reports-module-container { background: white !important; }
                #main-content { padding: 0 !important; margin: 0 !important; }
            }
        </style>
    `;

    // Add Tab Event Listeners
    document.querySelectorAll('.report-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            const target = e.currentTarget.dataset.tab;
            document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
            e.currentTarget.classList.add('active');
            switchReportView(target);
        });
    });

    // Default View
    switchReportView('bi-dashboard');
}

async function switchReportView(tab) {
    const container = document.getElementById('report-tab-content');
    container.innerHTML = `<div class="flex justify-center p-20"><i class="fas fa-spinner fa-spin text-4xl text-indigo-600"></i></div>`;

    switch (tab) {
        case 'bi-dashboard':
            renderBIDashboard(container);
            break;
        case 'detailed-reports':
            renderDetailedReports(container);
            break;
        case 'performance-mgmt':
            renderPerformanceMgmt(container);
            break;
    }
}

async function renderBIDashboard(container) {
    const currentYear = new Date().getFullYear();
    container.innerHTML = `
        <div class="bento-grid">
            <!-- Row 1: Key Performance Metrics -->
            <div class="kpi-card bg-indigo-600 !text-black !p-7 shadow-indigo-200 shadow-xl border-none">
                <div class="flex flex-col">
                    <p class="text-[10px] font-black uppercase text-green-900 tracking-widest opacity-70 mb-1">Total Vendido ${currentYear}</p>
                    <h3 id="bi-sales-total" class="text-3xl font-black mb-4">R$ 0,00</h3>
                    <div class="w-full bg-white/20 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-white h-full" style="width: 75%"></div>
                    </div>
                </div>
            </div>

            <div class="kpi-card !p-7 group hover:bg-slate-50">
                <div class="flex flex-col">
                    <p class="text-[10px] font-black uppercase text-blue-600 mb-1">Aprovado no Mês</p>
                    <h3 id="bi-month-sales" class="text-3xl font-black text-slate-800">R$ 0,00</h3>
                    <p class="text-xs text-slate-400 mt-2 flex items-center">
                        <i class="fas fa-arrow-up text-emerald-500 mr-1"></i> <span class="font-bold text-slate-600">+12%</span> em relação ao mês anterior
                    </p>
                </div>
            </div>

            <div class="kpi-card !p-7">
                <div class="flex flex-col">
                    <p class="text-[10px] font-black uppercase text-rose-600 mb-1">Perdas de Oportunidades</p>
                    <h3 id="bi-lost-total" class="text-3xl font-black text-slate-800">R$ 0,00</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-2">Valor acumulado no ano</p>
                </div>
            </div>

            <!-- Row 2: Secondary KPIs & Sellers -->
            <div class="kpi-card !bg-emerald-50 border-emerald-100 flex items-center justify-between">
                <div>
                    <h3 id="bi-bids-count" class="text-4xl font-black text-emerald-700">0</h3>
                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Licitações Ativas</p>
                </div>
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm">
                    <i class="fas fa-gavel text-2xl text-emerald-500"></i>
                </div>
            </div>

            <div class="col-span-2 bg-white rounded-2xl border border-slate-100 p-6 shadow-sm overflow-hidden relative">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h4 class="font-black text-slate-800 text-sm uppercase">Performance por Vendedor</h4>
                        <p class="text-[10px] text-slate-500">Mês de ${new Intl.DateTimeFormat('pt-BR', { month: 'long' }).format(new Date())}</p>
                    </div>
                </div>
                <div id="bi-top-sellers" class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
                    <div class="flex items-center justify-center p-10 w-full text-slate-300 font-bold">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Analisando resultados...
                    </div>
                </div>
            </div>

            <!-- Row 3: Charts -->
            <div class="col-span-2 bg-white rounded-2xl border border-slate-100 p-8 shadow-sm">
                <div class="flex justify-between items-center mb-8">
                     <h4 class="font-black text-slate-800 uppercase tracking-wider">Evolução de Mercado</h4>
                     <div class="flex gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-600">Vendas</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-500">Metas</span>
                     </div>
                </div>
                <div class="h-80 w-full"><canvas id="bi-main-chart"></canvas></div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm flex flex-col justify-center">
                 <h4 class="font-black text-slate-800 uppercase tracking-widest text-center mb-6">Mix de Fornecedores</h4>
                 <div class="h-64 w-full relative">
                    <canvas id="bi-supplier-chart"></canvas>
                 </div>
            </div>
        </div>
    `;

    try {
        const [kpiData, chartData] = await Promise.all([
            apiCall('get_report_data', { params: { report_type: 'bi_kpis', start_date: `${currentYear}-01-01` } }),
            apiCall('get_report_data', { params: { report_type: 'sales_vs_goals', start_date: `${currentYear}-01-01` } })
        ]);

        if (kpiData.success) {
            document.getElementById('bi-sales-total').innerText = formatCurrencyUtil(kpiData.total_sales || 0);
            document.getElementById('bi-month-sales').innerText = formatCurrencyUtil(kpiData.month_sales || 0);
            document.getElementById('bi-lost-total').innerText = formatCurrencyUtil(kpiData.lost_sales || 0);
            document.getElementById('bi-bids-count').innerText = kpiData.active_bids || 0;

            const sellerContainer = document.getElementById('bi-top-sellers');
            if (sellerContainer && kpiData.sales_by_vendedor) {
                sellerContainer.innerHTML = kpiData.sales_by_vendedor.map((s, i) => {
                    const initials = s.vendedor.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    return `
                        <div class="seller-card">
                            <div class="seller-avatar">${initials}</div>
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-slate-800 uppercase truncate w-32" title="${s.vendedor}">${s.vendedor}</span>
                                <span class="text-sm font-bold text-indigo-600">${formatCurrencyUtil(s.total)}</span>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Vendas Aprovadas</span>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

        if (chartData.success) {
            const ctx = document.getElementById('bi-main-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Realizado',
                            data: chartData.sales,
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            borderColor: '#4f46e5',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Meta',
                            data: chartData.goals,
                            borderColor: '#10b981',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v.toLocaleString() } } }
                }
            });
        }

        // Mini chart for variation or another metric
        const ctxSup = document.getElementById('bi-supplier-chart').getContext('2d');
        const salesBySup = await apiCall('get_report_data', { params: { report_type: 'by_supplier', start_date: `${currentYear}-01-01` } });
        if (Array.isArray(salesBySup)) {
            new Chart(ctxSup, {
                type: 'doughnut',
                data: {
                    labels: salesBySup.slice(0, 5).map(s => s.label),
                    datasets: [{
                        data: salesBySup.slice(0, 5).map(s => s.value),
                        backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
            });
        }

    } catch (e) {
        console.error("Dashboard error:", e);
    }
}

async function renderPerformanceMgmt(container) {
    const currentYear = new Date().getFullYear();
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    container.innerHTML = `
        <div class="flex flex-col space-y-6">
            <div class="bg-indigo-900 rounded-[2rem] p-6 text-white shadow-2xl flex flex-col md:flex-row justify-between items-center gap-6 no-print overflow-hidden relative">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                <div class="flex items-center gap-6 relative z-10">
                    <div class="bg-indigo-600/50 p-4 rounded-2xl backdrop-blur-sm border border-indigo-500/30">
                        <i class="fas fa-calculator text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold tracking-tight">Performance Financeira</h3>
                        <p class="text-indigo-200/80 text-[10px] font-black uppercase tracking-[0.2em]">Gestão de metas e comissões</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-4 bg-white/5 p-2 rounded-2xl backdrop-blur-xl border border-white/10 relative z-10">
                    <div class="flex bg-white/10 rounded-xl p-1 gap-1 border border-white/5">
                        <select id="perf-month-select" class="bg-transparent border-none text-white text-xs font-bold uppercase cursor-pointer outline-none focus:ring-0">
                            ${months.map((m, i) => `<option value="${i + 1}" class="bg-indigo-900" ${i + 1 === new Date().getMonth() + 1 ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>
                        <select id="perf-year-select" class="bg-transparent border-none text-white text-xs font-black cursor-pointer outline-none focus:ring-0">
                            <option value="${currentYear}" class="bg-indigo-900" selected>${currentYear}</option>
                            <option value="${currentYear - 1}" class="bg-indigo-900">${currentYear - 1}</option>
                        </select>
                    </div>
                    
                    <div class="h-6 w-[1px] bg-white/20"></div>

                    <div class="flex gap-2">
                        <button id="load-performance-btn" class="bg-indigo-500 hover:bg-indigo-400 text-white w-10 h-10 rounded-xl transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group">
                            <i class="fas fa-play group-hover:scale-110 transition-transform"></i>
                        </button>
                        <button id="export-performance-btn" class="bg-emerald-500 hover:bg-emerald-400 text-white px-5 h-10 rounded-xl font-black uppercase text-[10px] transition-all shadow-lg shadow-emerald-500/20 flex items-center active:scale-95">
                            <i class="fas fa-file-pdf mr-2"></i>Exportar
                        </button>
                        <button id="config-targets-btn" class="bg-amber-500 hover:bg-amber-400 text-white px-5 h-10 rounded-xl font-black uppercase text-[10px] transition-all shadow-lg shadow-amber-500/20 flex items-center active:scale-95">
                            <i class="fas fa-bullseye mr-2"></i>Metas
                        </button>
                    </div>
                </div>
            </div>

            <div id="performance-output" class="bg-white rounded-[2rem] shadow-xl shadow-indigo-100 border border-slate-50 overflow-hidden min-h-[500px]">
                <div class="flex flex-col items-center justify-center p-40 text-slate-300">
                    <div class="relative mb-6">
                        <div class="absolute inset-0 bg-indigo-50 rounded-full animate-ping opacity-25"></div>
                        <i class="fas fa-fingerprint text-6xl relative z-10"></i>
                    </div>
                    <p class="font-black uppercase tracking-widest text-sm">Pronto para processar dados</p>
                    <p class="text-xs font-medium text-slate-400 mt-2">Selecione o período e clique no botão Play acima</p>
                </div>
            </div>
        </div>
    `;

    // Bind Performance listeners immediately
    const btnLoad = document.getElementById('load-performance-btn');
    if (btnLoad) {
        btnLoad.onclick = async () => {
            const m = document.getElementById('perf-month-select').value;
            const y = document.getElementById('perf-year-select').value;
            const selectedMonth = `${y}-${m.padStart(2, '0')}`;
            await loadPerformanceData(container, selectedMonth);
        };
    }

    const btnExport = document.getElementById('export-performance-btn');
    if (btnExport) {
        btnExport.onclick = () => {
            const m = document.getElementById('perf-month-select').value;
            const y = document.getElementById('perf-year-select').value;
            const selectedMonth = `${y}-${m.padStart(2, '0')}`;
            exportToPDF('performance', selectedMonth);
        };
    }

    const btnConfig = document.getElementById('config-targets-btn');
    if (btnConfig) {
        btnConfig.onclick = () => {
            // Dispatch event or click legacy hidden button if it exists
            const targetBtn = document.getElementById('set-targets-btn');
            if (targetBtn) targetBtn.click();
        };
    }
}

function renderDetailedReports(container) {
    container.innerHTML = `
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 no-print border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Relatórios Analíticos</h3>
                    <p class="text-sm text-gray-500">Filtre dados por fornecedor, vendedor e geografia.</p>
                </div>
                
                <div class="flex space-x-2 mt-2 md:mt-0 w-full md:w-auto">
                    <button id="toggle-filters-btn" class="btn btn-secondary text-sm px-4">
                        <i class="fas fa-filter mr-2"></i> Filtros
                    </button>
                    <!-- Legacy buttons kept for internal logic if needed, but hidden -->
                    <button id="set-targets-btn" class="hidden">Definir Objetivos</button>
                </div>
            </div>

            <div id="reports-filters-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 transition-all">
                <div class="filter-card border-l-4 border-l-indigo-500">
                    <div class="filter-label"><i class="fas fa-file-invoice"></i>Tipo de Relatório</div>
                    <select id="report-type" class="w-full bg-slate-50 border-none text-sm font-bold focus:ring-0 cursor-pointer">
                        <option value="sales">Vendas Gerais</option>
                        <option value="contratos">Contratos</option>
                        <option value="forecast">Forecast (Previsão)</option>
                        <option value="funnel">Funil de Vendas</option>
                        <option value="lost_reasons">Propostas recusadas</option>
                        <option value="clients">Relatório de Clientes</option>
                        <option value="products">Vendas por Produto</option>
                        <option value="licitacoes_funnel">Licitações (Funil)</option>
                        <option value="supplier_funnel">Fábricas (Funil)</option>
                    </select>
                </div>

                <div class="filter-card border-l-4 border-l-blue-400">
                    <div class="filter-label"><i class="fas fa-calendar-alt"></i>Período de Análise</div>
                    <div class="flex items-center gap-2">
                        <div class="custom-date-row">
                             <input type="month" id="filter-start-date" class="custom-date-item">
                             <span class="text-slate-300">→</span>
                             <input type="month" id="filter-end-date" class="custom-date-item">
                        </div>
                    </div>
                </div>

                <div class="filter-card border-l-4 border-l-emerald-400">
                    <div class="filter-label"><i class="fas fa-building"></i>Fábrica / Sócio</div>
                    <div class="flex gap-1 w-full">
                         <div id="filter-supplier-container" class="flex-1"></div>
                         <div id="filter-user-container" class="flex-1"></div>
                    </div>
                </div>

                 <div class="filter-card border-l-4 border-l-amber-400">
                    <div class="filter-label"><i class="fas fa-map-marker-alt"></i>Geografia & Cliente</div>
                    <div class="flex gap-1 w-full">
                        <div id="filter-uf-container" class="flex-1"></div>
                        <div id="filter-client-container" class="flex-1"></div>
                    </div>
                </div>
                
                <div class="filter-card border-l-4 border-l-indigo-300 lg:col-span-2">
                    <div class="filter-label"><i class="fas fa-tasks"></i>Etapa Processual</div>
                    <div id="filter-etapa-container" class="w-full"></div>
                </div>

                <div class="lg:col-span-2 flex items-center justify-end">
                    <div class="bg-indigo-600 p-1.5 rounded-2xl flex gap-2 w-full shadow-lg shadow-indigo-100">
                        <button id="refresh-report-btn" class="flex-1 bg-white text-indigo-700 py-3 rounded-xl font-black uppercase text-xs hover:bg-slate-50 transition-all">
                             <i class="fas fa-rocket mr-2"></i>Sincronizar Dados
                        </button>
                        <button id="export-excel-btn" class="aspect-square bg-emerald-500 text-white px-4 rounded-xl hover:bg-emerald-600 transition-all"><i class="fas fa-file-excel"></i></button>
                        <button id="print-report-btn" class="aspect-square bg-rose-500 text-white px-4 rounded-xl hover:bg-rose-600 transition-all"><i class="fas fa-file-pdf"></i></button>
                    </div>
                </div>
            </div>
            
            <div id="active-filters-pills" class="flex flex-wrap gap-2 mt-4 hidden w-full"></div>
        </div>

        <div id="chart-container-wrapper" class="bg-white p-6 rounded-xl shadow-sm mb-6 hidden no-print border border-gray-100">
            <h4 class="font-bold text-gray-700 mb-4">Evolução de Vendas</h4>
            <div class="h-80 w-full">
                <canvas id="sales-chart"></canvas>
            </div>
        </div>

        <div id="reports-output-area" class="print-container">
            <div id="report-loading" class="text-center p-20 hidden">
                <i class="fas fa-spinner fa-spin text-5xl text-indigo-600"></i>
            </div>
            <div id="report-content" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                 <div class="p-20 text-center text-gray-400">
                    <i class="fas fa-chart-bar text-6xl mb-4 opacity-20"></i>
                    <p>Selecione os parâmetros acima para realizar a análise detalhada.</p>
                 </div>
            </div>
        </div>
    `;

    // Re-bind listeners for detailed reports
    const currentYear = new Date().getFullYear();
    document.getElementById('filter-start-date').value = `${currentYear}-01`;
    document.getElementById('filter-end-date').value = `${currentYear}-12`;
    document.getElementById('refresh-report-btn').addEventListener('click', loadReportData);
    document.getElementById('print-report-btn').addEventListener('click', () => exportToPDF());
    document.getElementById('export-excel-btn').addEventListener('click', exportToExcel);
    document.getElementById('toggle-filters-btn').addEventListener('click', () => {
        document.getElementById('reports-filters-container').classList.toggle('hidden');
    });

    populateFilters();
    setupModalLinks();
    restoreFilters();
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
        if (f.clients) restoreMulti('client-select', f.clients);
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
    const defaultSuppliers = ['BRASIL MEDICA', 'HEALTH', 'INSTRAMED', 'LIVANOVA', 'MASIMO', 'MERIL', 'MICROMED', 'NIPRO', 'SIGMAFIX'];
    const suppliers = (appState.fornecedores || []).filter(s => {
        const name = (s.nome_fantasia || s.nome || '').toUpperCase().trim();
        return defaultSuppliers.some(d => name.includes(d));
    });
    renderMultiSelect('filter-supplier-container', 'supplier-select', suppliers.map(s => ({ value: s.id, label: s.nome_fantasia || s.nome })), 'Todos os Fornecedores');

    const users = appState.users || [];
    const sellers = users.filter(u => ['Vendedor', 'Representante', 'Comercial', 'Gestor', 'Analista'].includes(u.role));
    renderMultiSelect('filter-user-container', 'user-select', sellers.map(u => ({ value: u.id, label: u.nome })), 'Todos os Vendedores');

    // Cliente (Organizations + PF)
    const clients = [];
    appState.organizations?.forEach(o => clients.push({ value: 'org-' + o.id, label: o.nome_fantasia || o.razao_social || 'Org ' + o.id }));
    appState.clients_pf?.forEach(p => clients.push({ value: 'pf-' + p.id, label: p.nome || 'PF ' + p.id }));
    clients.sort((a, b) => a.label.localeCompare(b.label));
    renderMultiSelect('filter-client-container', 'client-select', clients, 'Todos os Clientes');

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
                <div class="p-2 border-b border-gray-100 flex flex-col gap-2 shrink-0">
                     <input type="text" placeholder="Pesquisar..." class="w-full text-xs p-1.5 border border-gray-300 rounded focus:outline-none focus:border-indigo-500" oninput="filterMultiSelect('${selectId}', this.value)" onclick="event.stopPropagation()">
                     <div class="flex justify-between">
                         <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium" onclick="toggleAllMultiSelect('${selectId}', true)">Marcar Todos</button>
                         <button type="button" class="text-xs text-gray-500 hover:text-gray-700" onclick="toggleAllMultiSelect('${selectId}', false)">Limpar</button>
                     </div>
                </div>
                <div class="overflow-y-auto max-h-48 flex-grow">
                    <!-- Options -->
                    ${options.map(opt => `
                        <label class="multiselect-item" data-label="${opt.label.toLowerCase().replace(/"/g, '&quot;')}">
                            <input type="checkbox" value="${opt.value}" class="${selectId}-checkbox" onchange="updateMultiSelectText('${selectId}', '${defaultText}')">
                            <span class="text-sm text-gray-700">${opt.label}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    container.innerHTML = html;
}

// Global scope helpers for onclick events (since module scope)
window.filterMultiSelect = function (id, term) {
    const list = document.getElementById(`${id}-list`);
    if (!list) return;
    const items = list.querySelectorAll('.multiselect-item');
    const lowerTerm = term.toLowerCase();

    items.forEach(el => {
        const label = el.getAttribute('data-label') || '';
        if (label.includes(lowerTerm)) {
            el.style.display = 'flex';
        } else {
            el.style.display = 'none';
        }
    });
};

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
    const total = document.querySelectorAll(`.${id}-checkbox`).length;
    // If none are selected OR all are selected, we pass empty to bypass the SQL IN() filter entirely.
    // This handles NULLs correctly (as filtering by all options explicitly often drops NULL DB values).
    if (checkboxes.length === 0 || checkboxes.length === total) {
        return [];
    }
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
    const clientIds = window.getMultiSelectValues('client-select');
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
    const clientPayload = clientIds.length > 0 ? clientIds.join(',') : '';
    const etapaPayload = etapaIds.length > 0 ? etapaIds.join(',') : '';
    const origemPayload = origemIds.length > 0 ? origemIds.join(',') : '';
    const ufPayload = ufIds.length > 0 ? ufIds.join(',') : '';
    const statusPayload = statusIds.length > 0 ? statusIds.join(',') : '';

    if (typeof updateFilterPills === 'function') {
        updateFilterPills(type, start, end, supplierIds, userIds, clientIds, etapaIds, origemIds, ufIds, statusIds);
    }    // Save Filters to LocalStorage
    localStorage.setItem('reports_filters', JSON.stringify({
        type: type,
        start: start,
        end: end,
        suppliers: supplierIds,
        users: userIds,
        clients: clientIds,
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
            cliente_id: clientPayload,
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
        const chartContainer = document.getElementById('chart-container-wrapper');
        if (chartContainer) chartContainer.classList.add('hidden');
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
        renderSalesChart(data, monthsRange, 'forecast');
        // For forecast, we might want a simple summary table below too.
        const html = renderForecastTable(data);
        container.innerHTML = html;
        return;
    }

    if (type === 'funnel' || type === 'licitacoes_funnel') {
        const html = renderFunnelTable(data, type);
        container.innerHTML = html;
        return;
    }

    if (type === 'contratos') {
        const html = renderContractsTable(data);
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
        header.className = "px-6 py-4 bg-blue-50 border-b border-gray-200";
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

    // Show chart for Sales, Contratos, Clients, Funnel, Lost Reasons, Forecast, Licitacoes Funnel
    if (!['sales', 'contratos', 'clients', 'funnel', 'lost_reasons', 'forecast', 'licitacoes_funnel'].includes(type) || !data || data.length === 0) {
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
    if (type === 'funnel' || type === 'licitacoes_funnel' || type === 'contratos') {
        const labels = data.map(r => r.etapa_nome);
        const values = data.map(r => parseInt(r.qtd_oportunidades));
        // const valuesVal = data.map(r => parseFloat(r.valor_total)); // Maybe toggle between count/value?

        const titleEl = container.querySelector('h3');
        if (titleEl) {
            if (type === 'licitacoes_funnel') titleEl.innerText = "Funil de Licitações (Quantidade)";
            else if (type === 'contratos') titleEl.innerText = "Funil Financeiro (Quantidade de Contratos)";
            else titleEl.innerText = "Funil de Vendas (Quantidade)";
        }

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
        const acumulado = topClients.map(c => parseFloat(c.percentual_acumulado) || 0);

        const titleEl = container.querySelector('h3');
        if (titleEl) titleEl.innerText = "Curva ABC (Top 10 Clientes)";

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Faturamento Bruto',
                        data: values,
                        backgroundColor: 'rgba(79, 70, 229, 0.6)', // Indigo-600
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: '% Acumulado (Curva ABC)',
                        data: acumulado,
                        borderColor: 'rgba(239, 68, 68, 1)', // Red-500
                        backgroundColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (context.dataset.yAxisID === 'y1') {
                                    return context.raw + '% Acumulado';
                                }
                                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function (value) {
                                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: "compact" }).format(value);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        min: 0,
                        max: 100,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function (value) { return value + '%'; }
                        }
                    }
                }
            }
        });
        return;
    } else {
        // Reset Title for Sales
        const titleEl = container.querySelector('h3');
        if (titleEl) {
            titleEl.innerText = "Evolução de Vendas vs Metas";
        }
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
        acc[monthKey] = { venda: 0, faturado: 0, meta: 0, saldo: 0 };
        rows.forEach(row => {
            const cellData = row.dados_mes[monthKey] || { venda: 0, faturado: 0, meta: 0 };
            const venda = parseFloat(cellData.venda) || 0;
            const faturado = parseFloat(cellData.faturado) || 0;
            const meta = parseFloat(cellData.meta) || 0;
            acc[monthKey].venda += venda;
            acc[monthKey].faturado += faturado;
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
    const tableBody = rows.filter(row => {
        // Hide rows with no sales/NF if filters like Client, UF, etc. are active
        const filterActive = (document.getElementById('client-select-btn')?.innerText.trim() !== 'Todos os Clientes') ||
            (document.getElementById('uf-select-btn')?.innerText.trim() !== 'Todos os Estados') ||
            (document.getElementById('status-select-btn')?.innerText.trim() !== 'Todos os Status') ||
            (document.getElementById('origem-select-btn')?.innerText.trim() !== 'Todas as Origens');

        if (!filterActive) return true;

        const hasActivity = monthKeys.some(key => {
            const d = row.dados_mes[key] || {};
            return (parseFloat(d.venda) || 0) > 0 || (parseFloat(d.faturado) || 0) > 0;
        });
        return hasActivity;
    }).map(row => {
        let rowVenda = 0, rowFaturado = 0, rowMeta = 0;

        const cells = monthKeys.map(key => {
            const d = row.dados_mes[key] || { venda: 0, faturado: 0, meta: 0 };
            const v = parseFloat(d.venda) || 0;
            const f = parseFloat(d.faturado) || 0;
            const m = parseFloat(d.meta) || 0;
            const s = v - m;
            rowVenda += v; rowFaturado += f; rowMeta += m;

            const saldoClass = s >= 0 ? 'text-green-600' : 'text-red-600';
            const bgClass = (userTargetsEnabled && m > 0) ? (v >= m ? 'bg-green-50' : 'bg-red-50') : '';

            let progressHtml = '';
            if (userTargetsEnabled && m > 0) {
                const pct = Math.min((v / m) * 100, 100).toFixed(0);
                const pcolor = v >= m ? 'bg-green-500' : 'bg-yellow-500';
                progressHtml = `
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 border border-gray-300">
                    <div class="${pcolor} h-1.5 rounded-full" style="width: ${pct}%"></div>
                </div>`;
            }

            return `
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500 border-r border-gray-200 text-right ${bgClass}">
                    <div class="font-medium text-gray-900">${v > 0 ? format(v) : '-'}</div>
                    ${(userTargetsEnabled && m > 0) ? `<div class="text-gray-400 text-[10px]">M: ${format(m)}</div>` : ''}
                    ${progressHtml}
                    ${(userTargetsEnabled && m > 0) ? `<div class="${saldoClass} font-bold border-t border-gray-100 mt-1 pt-1 text-[10px]">S: ${format(s)}</div>` : ''}
                </td>
            `;
        }).join('');

        const rowSaldo = rowVenda - rowMeta;
        const rowSaldoClass = rowSaldo >= 0 ? 'text-green-600' : 'text-red-600';
        let rowGrandProgressHtml = '';
        if (userTargetsEnabled && rowMeta > 0) {
            const pct = Math.min((rowVenda / rowMeta) * 100, 100).toFixed(0);
            const pcolor = rowVenda >= rowMeta ? 'bg-green-500' : 'bg-indigo-500';
            rowGrandProgressHtml = `
            <div class="w-full bg-gray-300 rounded-full h-2 mt-1 shadow-inner">
                <div class="${pcolor} h-2 rounded-full" style="width: ${pct}%"></div>
            </div>`;
        }

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
        const faturadoVal = t.faturado;
        const saldoVal = t.venda - metaVal;

        const sClass = saldoVal >= 0 ? 'text-green-600' : 'text-red-600';
        return `
            <td class="px-2 py-3 whitespace-nowrap text-xs text-right font-bold bg-gray-100 border-r border-gray-200">
                <div>${format(t.venda)}</div>
                ${faturadoVal > 0 ? `<div class="text-indigo-600 text-[10px]">F: ${format(faturadoVal)}</div>` : ''}
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

    // Row: Factory Total (Global)
    const factoryMetaMensal = parseFloat(group.meta_global_mensal) || 0;
    const factoryTotalCells = monthKeys.map(key => {
        const t = totals[key];
        const v = t.venda || 0;
        const f = t.faturado || 0;
        const m = factoryMetaMensal;
        const s = v - m;

        const sClass = s >= 0 ? 'text-green-600' : 'text-red-600';
        const bgClass = m > 0 ? (v >= m ? 'bg-green-50' : 'bg-red-50') : 'bg-blue-50';

        return `
            <td class="px-2 py-3 whitespace-nowrap text-xs text-right font-bold border-r border-gray-200 ${bgClass}">
                <div class="text-blue-900 text-sm" title="Total Vendas">${format(v)}</div>
                ${f > 0 ? `<div class="text-indigo-700 font-bold text-[10px]" title="Total Faturado">F: ${format(f)}</div>` : ''}
                ${m > 0 ? `<div class="text-gray-500 text-[10px]" title="Meta Fábrica">M: ${format(m)}</div>` : ''}
                ${m > 0 ? `<div class="${sClass} text-[10px] border-t border-gray-200 pt-1 mt-1" title="Saldo Vendas meta">S: ${format(s)}</div>` : ''}
            </td>
        `;
    }).join('');

    const factoryGrandVenda = Object.values(totals).reduce((a, b) => a + b.venda, 0);
    const factoryGrandFaturado = Object.values(totals).reduce((a, b) => a + b.faturado, 0);
    const factoryGrandMeta = factoryMetaMensal * numMonths;
    const factoryGrandSaldo = factoryGrandVenda - factoryGrandMeta;
    const factoryGrandSaldoClass = factoryGrandSaldo >= 0 ? 'text-green-600' : 'text-red-600';

    const factoryTotalRow = `
        <tr class="bg-blue-100 border-b-2 border-blue-200 shadow-sm">
            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-blue-900 border-r border-gray-200 sticky left-0 bg-blue-100 z-10">
                TOTAL ${(group.fornecedor_nome || 'FÁBRICA').toUpperCase()}
            </td>
            ${factoryTotalCells}
            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold bg-blue-200 border-l border-gray-200">
                <div class="text-blue-900 text-sm">${format(factoryGrandVenda)}</div>
                ${factoryGrandFaturado > 0 ? `<div class="text-indigo-700 text-[10px]">F: ${format(factoryGrandFaturado)}</div>` : ''}
                ${factoryGrandMeta > 0 ? `<div class="text-gray-600 text-[10px]">M: ${format(factoryGrandMeta)}</div>` : ''}
                ${factoryGrandMeta > 0 ? `<div class="${factoryGrandSaldoClass} text-[10px] border-t border-blue-300 pt-1 mt-1">S: ${format(factoryGrandSaldo)}</div>` : ''}
            </td>
        </tr>
    `;

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
                    ${factoryTotalRow}
                    ${tableBody}
                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 border-r border-gray-200 sticky left-0 bg-gray-100 z-10">TOTAIS</td>
                        ${totalsCells}
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold bg-gray-200 border-l border-gray-200">
                            <div>${format(grandVenda)}</div>
                            ${factoryGrandFaturado > 0 ? `<div class="text-indigo-700 text-[10px]">F: ${format(factoryGrandFaturado)}</div>` : ''}
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

    // Get only states that have defined goals for this supplier
    const states = Object.keys(stateGoals).sort();

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

        const maxSales = Math.max(...states.map(s => parseFloat(stateSales[s]) || 0));
        const heatPct = maxSales > 0 ? (sales / maxSales * 100) : 0;

        rowsHtml += `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-100 flex items-center justify-between">
                    <span>${uf}</span>
                    <i class="fas fa-map-marker-alt text-gray-300 ml-2"></i>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">
                    <div class="flex justify-end items-center space-x-3 w-full">
                       <span class="font-mono">${format(sales)}</span>
                       <div class="w-20 bg-gray-100 rounded-sm overflow-hidden border border-gray-200 flex h-3 mt-0.5">
                           <div class="bg-indigo-500 h-full shadow-md" style="width:${heatPct}%"></div>
                       </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 border-l border-gray-100 font-mono">${format(goal)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-bold ${balClass} border-l border-gray-100 font-mono">${format(balance)}</td>
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
    const container = document.getElementById('report-results');

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
                             <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ABC</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% Acumulado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${data.map((row, index) => {
        const val = parseFloat(row.valor_total) || 0;
        const percent = parseFloat(row.percentual_acumulado) || 0;
        let pColorClass = 'bg-gray-100 text-gray-800';
        if (row.classe === 'A') pColorClass = 'bg-green-100 text-green-800';
        else if (row.classe === 'B') pColorClass = 'bg-yellow-100 text-yellow-800';
        else if (row.classe === 'C') pColorClass = 'bg-red-100 text-red-800';
        return `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-center font-bold text-gray-500 border-r border-gray-100">${index + 1}</td>
                                    <td class="px-6 py-3 text-left font-medium text-gray-700">
                                        ${row.cliente_nome}
                                        ${row.classe === 'A' ? '<i class="fas fa-star text-yellow-400 ml-2"></i>' : ''}
                                    </td>
                                    <td class="px-6 py-3 text-center text-gray-600">${row.qtd_vendas}</td>
                                    <td class="px-6 py-3 text-center"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${pColorClass}">${row.classe || '-'}</span></td>
                                    <td class="px-6 py-3 text-right font-bold text-gray-800">${format(val)}</td>
                                    <td class="px-6 py-3 text-right text-gray-500">${percent.toFixed(1)}%</td>
                                </tr>
                            `;
    }).join('')}
                        <tr class="bg-gray-100 font-bold border-t-2 border-gray-200">
                            <td colspan="2" class="px-6 py-3 text-right text-gray-900">TOTAL</td>
                            <td class="px-6 py-3 text-center text-gray-900">${data.reduce((acc, r) => acc + parseInt(r.qtd_vendas), 0)}</td>
                            <td class="px-6 py-3 text-center"></td>
                            <td class="px-6 py-3 text-right text-gray-900">${format(totalRevenue)}</td>
                            <td class="px-6 py-3 text-right text-gray-900">100.0%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderFunnelTable(data, type = 'funnel') {
    const format = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
    const totalRevenue = data.reduce((acc, row) => acc + (parseFloat(row.valor_total) || 0), 0);
    const totalCount = data.reduce((acc, row) => acc + parseInt(row.qtd_oportunidades), 0);

    let title = 'Funil de Vendas (Conversão)';
    let unit = 'Oportunidades';
    if (type === 'licitacoes_funnel') {
        title = 'Funil de Licitações (Conversão)';
    } else if (type === 'contratos') {
        title = 'Funil Financeiro (Contratos)';
        unit = 'Contratos';
    }

    return `
        <div class="mb-8 bg-white shadow rounded-lg overflow-hidden break-inside-avoid">
            <div class="px-6 py-4 bg-teal-50 border-b border-teal-100 flex justify-between items-center">
                <h3 class="font-bold text-teal-700">${title}</h3>
                <span class="text-xs bg-teal-200 text-teal-800 px-2 py-1 rounded-full">${totalCount} ${unit}</span>
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

    // Determine year: passed arg > current real year > fallback
    if (!year) {
        year = new Date().getFullYear();
        // const startVal = document.getElementById('filter-start-date').value;
        // year = startVal ? startVal.split('-')[0] : new Date().getFullYear();
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
                    Swal.fire({
                        title: 'Tem certeza?',
                        text: 'Você tem certeza que deseja apagar esse registro!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Apagar',
                        cancelButtonText: 'Cancelar',
                        backdrop: `rgba(0,0,0,0.8)`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            div.remove();
                            tr.remove();
                            updateGrandTotal(container);
                        }
                    });
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

function updateFilterPills(type, start, end, supplierIds, userIds, clientIds, etapaIds, origemIds, ufIds, statusIds) {
    const container = document.getElementById('active-filters-pills');
    if (!container) return;
    container.innerHTML = '';

    let hasPills = false;

    const createPill = (label, filterId, isMulti = true) => {
        hasPills = true;
        return `
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 animate-fade-in shadow-sm border border-indigo-200">
                ${label}
                <button type="button" class="flex-shrink-0 ml-1.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white" onclick="removeFilterPill('${filterId}', ${isMulti})">
                    <span class="sr-only">Remover filtro</span>
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </span>
        `;
    };

    let innerHtml = '';

    const resolveLabels = (idBase) => {
        const checkboxes = document.querySelectorAll(`.${idBase}-checkbox:checked`);
        return Array.from(checkboxes).map(c => c.nextElementSibling.innerText).join(', ');
    };

    if (start && end) {
        innerHtml += `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 shadow-sm border border-gray-200">Período: ${start} a ${end}</span>`;
        hasPills = true;
    }

    if (supplierIds.length > 0) innerHtml += createPill('Fornecedores: ' + resolveLabels('supplier-select'), 'supplier-select');
    if (userIds.length > 0) innerHtml += createPill('Vendedores: ' + resolveLabels('user-select'), 'user-select');
    if (clientIds.length > 0) innerHtml += createPill('Clientes: ' + resolveLabels('client-select'), 'client-select');
    if (etapaIds.length > 0) innerHtml += createPill('Etapas: ' + resolveLabels('etapa-select'), 'etapa-select');
    if (origemIds.length > 0) innerHtml += createPill('Origens: ' + resolveLabels('origem-select'), 'origem-select');
    if (ufIds.length > 0) innerHtml += createPill('UF: ' + resolveLabels('uf-select'), 'uf-select');
    if (statusIds.length > 0) innerHtml += createPill('Status: ' + resolveLabels('status-select'), 'status-select');

    if (hasPills) {
        container.innerHTML = `<span class="text-xs font-bold text-gray-500 self-center mr-2"><i class="fas fa-tags mr-1"></i> Filtros Ativos:</span>` + innerHtml;
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

window.removeFilterPill = function (idBase, isMulti) {
    if (isMulti) {
        window.toggleAllMultiSelect(idBase, false);
    }
    document.getElementById('refresh-report-btn').click();
};

function exportToPDF(forcedType = null) {
    const type = forcedType || document.getElementById('report-type').value;

    let start = '';
    let end = '';

    if (type === 'performance') {
        const m = document.getElementById('perf-month-select')?.value;
        const y = document.getElementById('perf-year-select')?.value;
        if (m && y) {
            start = `${y}-${m.padStart(2, '0')}`;
            const lastDay = new Date(y, m, 0).getDate();
            end = `${start}-${lastDay}`;
        }
    } else {
        const startInput = document.getElementById('filter-start-date');
        const endInput = document.getElementById('filter-end-date');
        start = startInput ? startInput.value : '';
        const rawEnd = endInput ? endInput.value : '';

        if (rawEnd && rawEnd.length === 7) { // YYYY-MM
            const [y, m] = rawEnd.split('-');
            const lastDay = new Date(y, m, 0).getDate();
            end = `${rawEnd}-${lastDay}`;
        } else {
            end = rawEnd;
        }
    }

    const supplierIds = window.getMultiSelectValues('supplier-select');
    const userIds = window.getMultiSelectValues('user-select');
    const clientIds = window.getMultiSelectValues('client-select');
    const etapaIds = window.getMultiSelectValues('etapa-select');
    const origemIds = window.getMultiSelectValues('origem-select');
    const ufIds = window.getMultiSelectValues('uf-select');
    const statusIds = window.getMultiSelectValues('status-select');

    const qs = new URLSearchParams({
        report_type: type,
        start_date: type === 'performance' ? start + '-01' : (start ? start + '-01' : ''),
        end_date: end,
        supplier_id: supplierIds.join(','),
        user_id: userIds.join(','),
        cliente_id: clientIds.join(','),
        etapa_id: etapaIds.join(','),
        origem: origemIds.join(','),
        uf: ufIds.join(','),
        status: statusIds.join(',')
    }).toString();

    window.open(`/api.php?action=export_pdf&${qs}`, '_blank');
}

/**
 * Performance & Commission Logic
 */
async function loadPerformanceData(container, selectedMonth = null) {
    const output = document.getElementById('performance-output');
    if (!output) return;

    let month = selectedMonth;
    if (!month) {
        const m = document.getElementById('perf-month-select')?.value;
        const y = document.getElementById('perf-year-select')?.value;
        month = (m && y) ? `${y}-${m.padStart(2, '0')}` : new Date().toISOString().slice(0, 7);
    }

    // Calculate start/end date for the selected month
    const [year, mNum] = month.split('-');
    const startDate = `${month}-01`;
    const lastDay = new Date(year, mNum, 0).getDate();
    const endDate = `${month}-${lastDay}`;

    output.innerHTML = `
        <div class="p-20 text-center text-gray-400">
            <i class="fas fa-spinner fa-spin text-4xl mb-4 text-indigo-600"></i>
            <p class="animate-pulse">Calculando metas e comissões...</p>
        </div>
    `;

    try {
        const response = await apiCall('get_report_data', {
            params: {
                report_type: 'commission_analysis',
                start_date: startDate,
                end_date: endDate
            }
        });

        if (response && response.success) {
            if (!response.data || response.data.length === 0) {
                output.innerHTML = `<div class="p-20 text-center text-gray-500">Nenhum dado financeiro encontrado para o período ${month}.</div>`;
                return;
            }
            renderPerformanceTable(output, response.data);
        } else {
            throw new Error(response?.error || 'Falha na resposta do servidor');
        }
    } catch (e) {
        output.innerHTML = `
            <div class="p-20 text-center text-red-500 bg-red-50 m-6 rounded-2xl border border-red-100">
                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                <h4 class="font-bold">Erro ao Carregar Dados</h4>
                <p class="text-sm opacity-75">${e.message}</p>
                <button onclick="location.reload()" class="btn btn-primary mt-6">Tentar Novamente</button>
            </div>
        `;
    }
}

function renderPerformanceTable(container, data) {
    const format = (v) => formatCurrencyUtil(v || 0);

    container.innerHTML = `
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-left border-b border-gray-200">
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Colaborador</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Fixo (R$)</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Meta Mensal</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Vendas Período</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Atingimento (%)</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Comissão (%)</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Valor Comissão</th>
                        <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right bg-indigo-50">Total a Pagar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    ${data.map(row => {
        const atingimento = parseFloat(row.atingimento) || 0;
        const barColor = atingimento >= 100 ? 'bg-green-500' : (atingimento >= 70 ? 'bg-indigo-500' : 'bg-orange-500');
        const textClass = atingimento >= 100 ? 'text-green-600' : (atingimento >= 70 ? 'text-indigo-600' : 'text-orange-600');

        return `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-gray-800">${row.nome}</div>
                                </td>
                                <td class="p-4 text-right text-gray-600 font-mono">${format(row.valor_fixo)}</td>
                                <td class="p-4 text-right text-gray-600 font-mono">${format(row.meta_mensal)}</td>
                                <td class="p-4 text-right font-black text-gray-800 font-mono">${format(row.total_vendas)}</td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="font-bold ${textClass}">${atingimento.toFixed(1)}%</span>
                                        <div class="w-16 h-1 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                            <div class="h-full ${barColor}" style="width: ${Math.min(atingimento, 100)}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-right text-indigo-500 font-bold">${row.percentual_comissao}%</td>
                                <td class="p-4 text-right text-gray-600 font-mono">${format(row.comissao_valor)}</td>
                                <td class="p-4 text-right font-black text-indigo-900 bg-indigo-50/20 font-mono">${format(row.total_periodo)}</td>
                            </tr>
                        `;
    }).join('')}
                </tbody>
            </table>
        </div>
    `;
}