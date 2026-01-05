// js/views/email_marketing.js

import { appState } from '../script.js';
import { apiCall } from '../api.js';
import { showToast, showLoading } from '../utils.js';
import { renderModal, closeModal } from '../ui.js';

// Estado local específico para esta view
let localState = {
    searchTerm: '',
    searchResults: [],
    selectedLeads: [],
    subject: '',
    isEditorInitialized: false // Flag para controlar inicialização do editor
};

export function renderEmailMarketingView() {
    const container = document.getElementById('email-marketing-view');
    if (!container) return;

    // Preserva o conteúdo do editor se ele já existe
    let currentBodyContent = '';
    const editorInstance = tinymce.get('email-body-editor');
    if (localState.isEditorInitialized && editorInstance) {
        currentBodyContent = editorInstance.getContent();
    } else {
        currentBodyContent = appState.emailMarketingView?.body || ''; // Pega do estado global ou vazio
    }


    // Reinicia ou inicializa o estado local
    localState = {
        searchTerm: appState.emailMarketingView?.searchTerm || '',
        searchResults: [],
        selectedLeads: appState.emailMarketingView?.selectedLeads || [],
        subject: appState.emailMarketingView?.subject || '',
        body: currentBodyContent, // Usa o conteúdo preservado ou do estado global
        isEditorInitialized: localState.isEditorInitialized // Mantém o estado de inicialização
    };
    appState.emailMarketingView = localState; // Atualiza estado global

    container.innerHTML = `
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Campanha de E-mail Marketing</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Coluna de Seleção de Destinatários --!>
            <div class="md:col-span-1 bg-white p-6 rounded-lg shadow-sm border space-y-4 flex flex-col">
                <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">1. Selecione os Destinatários</h2>
                <div>
                    <label for="lead-search-input" class="form-label">Pesquisar Leads (Nome, E-mail, Interesse):</label>
                    <div class="relative">
                        <input type="text" id="lead-search-input" value="${localState.searchTerm}" placeholder="Digite para buscar..." class="form-input w-full pr-8">
                        <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div id="search-interaction-area" class="mt-2 text-sm ${localState.searchTerm.length >= 2 ? '' : 'hidden'}">
                         <button id="select-all-results-btn" class="btn btn-secondary btn-sm mb-1 text-xs ${localState.searchResults.length > 0 ? '' : 'hidden'}">Selecionar Todos (<span id="search-results-count">${localState.searchResults.length}</span>)</button>
                         <div id="search-results-container" class="border rounded-md max-h-48 overflow-y-auto bg-gray-50">
                            <div class="p-2 text-gray-500">Digite pelo menos 2 caracteres...</div>
                         </div>
                    </div>
                </div>

                <div class="pt-4 border-t flex-grow flex flex-col min-h-0">
                     <h3 class="text-md font-semibold text-gray-600 mb-2">Leads Selecionados (<span id="selected-count">${localState.selectedLeads.length}</span>):</h3>
                    <div id="selected-leads-list" class="flex-grow overflow-y-auto border rounded bg-gray-50 p-2 space-y-1 text-xs">
                         <p class="text-gray-500 italic text-center p-4">Nenhum lead selecionado.</p>
                    </div>
                     <button id="clear-selection-btn" class="text-xs text-red-600 hover:underline mt-2 ${localState.selectedLeads.length > 0 ? '' : 'hidden'}">Limpar Seleção</button>
                </div>
            </div>

            <!-- Coluna do Editor de Email --!>
            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border space-y-4">
                 <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">2. Componha o E-mail</h2>
                 <div>
                     <label for="email-subject" class="form-label">Assunto*</label>
                     <input type="text" id="email-subject" value="${localState.subject}" required class="form-input">
                 </div>
                 <div>
                     <label for="email-body-editor" class="form-label">Corpo do E-mail*</label>
                     <div id="email-body-editor" class="form-input border rounded-md shadow-sm" style="min-height: 350px; border: 1px solid #d1d5db;"></div> <!-- Estilizado para parecer um input --!>
                     <p class="text-xs text-gray-500 mt-1">Use o editor acima para formatar seu e-mail.</p>
                 </div>
                 <div class="flex justify-end pt-4 border-t">
                    <button id="send-email-btn" class="btn btn-primary" ${localState.selectedLeads.length === 0 ? 'disabled' : ''}>
                        <i class="fas fa-paper-plane mr-2"></i> Enviar Campanha (<span id="send-count">${localState.selectedLeads.length}</span>)
                    </button>
                 </div>
            </div>
        </div>
    `;

    renderSelectedLeadsList();
    addEmailMarketingEventListeners();
    initializeTinyMCE(); // Sempre tenta inicializar ou re-inicializar
}

// Função para inicializar o TinyMCE
function initializeTinyMCE() {
    // Remove a instância anterior se existir para evitar erros
    const existingEditor = tinymce.get('email-body-editor');
    if (existingEditor) {
        existingEditor.remove();
    }

    tinymce.init({
        selector: '#email-body-editor',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        height: 350,
        menubar: false,
        content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; }', // Estilo do conteúdo

        // --- Configuração de Upload de Imagem ---
        image_title: true,
        automatic_uploads: true, // Habilita o arrastar/colar e botão de upload
        file_picker_types: 'image',
        images_upload_url: 'api.php?action=upload_email_image', // Rota da API PHP

        // Handler customizado para pegar o blob e enviar via fetch (mais controle)
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            // TinyMCE espera que o nome do campo seja 'file'
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            showLoading(true); // Mostra spinner durante o upload

            fetch('api.php?action=upload_email_image', {
                method: 'POST',
                body: formData
             })
            .then(response => {
                if (!response.ok) {
                    // Tenta ler a mensagem de erro do JSON, senão usa o status text
                     return response.json().then(err => { throw new Error(err.error || response.statusText); });
                }
                return response.json();
            })
            .then(result => {
                if (result && result.location) {
                    resolve(result.location); // Retorna a URL da imagem salva
                    showToast('Imagem carregada com sucesso!');
                } else {
                     reject('Erro no upload: ' + (result.error || 'Resposta inválida do servidor.'));
                }
            })
            .catch(error => {
                 console.error("Erro no upload da imagem:", error);
                 // Se o erro for do tipo Error, pega a message, senão mostra o objeto erro
                 const errorMessage = error instanceof Error ? error.message : JSON.stringify(error);
                 reject('Falha no upload da imagem: ' + errorMessage);
                 showToast('Falha no upload da imagem: ' + errorMessage, 'error');
            })
            .finally(() => {
                showLoading(false); // Esconde o spinner
            });
        }),

        setup: (editor) => {
            editor.on('init', () => {
                editor.setContent(localState.body || ''); // Define conteúdo inicial
                localState.isEditorInitialized = true; // Marca como inicializado
                console.log("TinyMCE inicializado.");
            });
            // Atualiza o estado local quando o conteúdo muda
            editor.on('change input ExecCommand Paste', () => {
                if (localState.isEditorInitialized) { // Só atualiza se já foi inicializado
                    const newContent = editor.getContent();
                    localState.body = newContent;
                    appState.emailMarketingView.body = newContent;
                }
            });
            // Tratamento especial para desfazer/refazer
            editor.on('Undo Redo', () => {
                 if (localState.isEditorInitialized) {
                     const newContent = editor.getContent();
                     localState.body = newContent;
                     appState.emailMarketingView.body = newContent;
                 }
            });
        },
        // Remove branding/watermark
        promotion: false,
        branding: false
    });
}


function addEmailMarketingEventListeners() {
    const searchInput = document.getElementById('lead-search-input');
    const searchInteractionArea = document.getElementById('search-interaction-area');
    const clearSelectionBtn = document.getElementById('clear-selection-btn');
    const subjectInput = document.getElementById('email-subject');
    const sendBtn = document.getElementById('send-email-btn');

    if (searchInput) {
        searchInput.addEventListener('input', handleSearchInput);
        searchInput.addEventListener('focus', () => {
            if(searchInteractionArea) searchInteractionArea.classList.remove('hidden');
            handleSearchInput({ target: searchInput });
        });
    }

     document.addEventListener('click', (e) => {
        const searchArea = document.querySelector('.md\\:col-span-1');
        if (searchInteractionArea && searchArea && !searchArea.contains(e.target)) {
            // Verifica se o clique foi fora do editor TinyMCE também
            const editorContainer = document.querySelector('.tox.tox-tinymce');
             if (!editorContainer || !editorContainer.contains(e.target)) {
                 searchInteractionArea.classList.add('hidden');
             }
        }
    }, true); // Usa captura para pegar o clique antes


    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', () => {
            localState.selectedLeads = [];
            appState.emailMarketingView.selectedLeads = [];
            renderSelectedLeadsList();
            updateSendButtonState();
             document.querySelectorAll('#search-results-container .select-lead-checkbox').forEach(cb => cb.checked = false);
        });
    }

     if (subjectInput) {
        subjectInput.addEventListener('input', (e) => {
            localState.subject = e.target.value;
            appState.emailMarketingView.subject = e.target.value;
        });
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', handleSendEmailCampaign);
    }
}

// Lida com a digitação na barra de pesquisa
function handleSearchInput(event) {
    const searchTerm = event.target.value.toLowerCase().trim();
    localState.searchTerm = searchTerm;
    appState.emailMarketingView.searchTerm = searchTerm;
    const searchInteractionArea = document.getElementById('search-interaction-area');
    const resultsContainer = document.getElementById('search-results-container');
    const selectAllBtn = document.getElementById('select-all-results-btn');
    const resultsCountSpan = document.getElementById('search-results-count');

    if (!searchInteractionArea || !resultsContainer || !resultsCountSpan) return;

    searchInteractionArea.classList.remove('hidden');

    if (searchTerm.length < 2) {
        localState.searchResults = [];
        resultsContainer.innerHTML = '<div class="p-2 text-gray-500">Digite pelo menos 2 caracteres...</div>';
        resultsCountSpan.textContent = '0';
        if (selectAllBtn) selectAllBtn.classList.add('hidden');
        return;
    }

    localState.searchResults = (appState.leads || []).filter(lead =>
        lead.email && lead.email.includes('@') &&
        (
            (lead.nome && lead.nome.toLowerCase().includes(searchTerm)) ||
            (lead.email && lead.email.toLowerCase().includes(searchTerm)) ||
            (lead.produto_interesse && lead.produto_interesse.toLowerCase().includes(searchTerm))
        )
    );

    renderSearchResults();
}

// Renderiza os resultados da pesquisa e o botão "Selecionar Todos"
function renderSearchResults() {
    const resultsContainer = document.getElementById('search-results-container');
    const selectAllBtn = document.getElementById('select-all-results-btn');
    const resultsCountSpan = document.getElementById('search-results-count');

    if (!resultsContainer || !selectAllBtn || !resultsCountSpan) return;

    const resultsCount = localState.searchResults.length;
    resultsCountSpan.textContent = resultsCount;

    if (resultsCount === 0) {
        resultsContainer.innerHTML = '<div class="p-2 text-gray-500">Nenhum lead encontrado.</div>';
        selectAllBtn.classList.add('hidden');
    } else {
        resultsContainer.innerHTML = localState.searchResults.map(lead => {
            const isSelected = localState.selectedLeads.some(sel => sel.id === lead.id);
            return `
                <div class="p-2 border-b last:border-b-0 hover:bg-indigo-100 flex items-center justify-between cursor-pointer search-result-item" data-lead-id="${lead.id}">
                    <div>
                        <p class="font-medium">${lead.nome}</p>
                        <p class="text-xs text-gray-600">${lead.email}</p>
                        <p class="text-xs text-gray-500">Interesse: ${lead.produto_interesse || 'N/A'}</p>
                    </div>
                     <input type="checkbox" data-lead-id="${lead.id}" ${isSelected ? 'checked' : ''} class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 select-lead-checkbox pointer-events-none">
                </div>
            `;
        }).join('');

        resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', (e) => {
                 e.stopPropagation();
                 const leadId = e.currentTarget.dataset.leadId;
                 const checkbox = e.currentTarget.querySelector('.select-lead-checkbox');
                 toggleLeadSelection(leadId, !checkbox.checked);
            });
        });

        selectAllBtn.classList.remove('hidden');

        const oldBtn = document.getElementById('select-all-results-btn');
        const newBtn = oldBtn.cloneNode(true);
        oldBtn.parentNode.replaceChild(newBtn, oldBtn);
        newBtn.addEventListener('click', (e) => {
             e.stopPropagation();
             localState.searchResults.forEach(lead => {
                if (!localState.selectedLeads.some(sel => sel.id === lead.id)) {
                    toggleLeadSelection(lead.id, true);
                }
            });
             document.querySelectorAll('#search-results-container .select-lead-checkbox').forEach(cb => cb.checked = true);
        });

    }
}


// Adiciona ou remove um lead da lista de selecionados
function toggleLeadSelection(leadId, shouldBeSelected) {
    const leadToAddOrRemove = appState.leads.find(l => l.id == leadId);
    if (!leadToAddOrRemove) return;

    const index = localState.selectedLeads.findIndex(l => l.id == leadId);

    if (shouldBeSelected && index === -1) {
         localState.selectedLeads.push({...leadToAddOrRemove});
    } else if (!shouldBeSelected && index !== -1) {
         localState.selectedLeads.splice(index, 1);
    }

    appState.emailMarketingView.selectedLeads = localState.selectedLeads;
    renderSelectedLeadsList();
    updateSendButtonState();
    const checkboxInResults = document.querySelector(`#search-results-container .select-lead-checkbox[data-lead-id="${leadId}"]`);
    if(checkboxInResults) checkboxInResults.checked = shouldBeSelected;
}


// Renderiza a lista de leads selecionados
function renderSelectedLeadsList() {
    const listContainer = document.getElementById('selected-leads-list');
    const countElement = document.getElementById('selected-count');
     const clearBtn = document.getElementById('clear-selection-btn');

    if (!listContainer || !countElement) return;

    if (localState.selectedLeads.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-500 italic text-center p-4">Nenhum lead selecionado.</p>';
        if (clearBtn) clearBtn.classList.add('hidden');
    } else {
        listContainer.innerHTML = localState.selectedLeads.map(lead => `
            <div class="bg-indigo-50 p-1.5 rounded border border-indigo-200 flex justify-between items-center">
                <span>
                    <span class="font-medium">${lead.nome}</span> <span class="text-gray-600">&lt;${lead.email}&gt;</span>
                </span>
                <button class="remove-selected-lead text-red-500 hover:text-red-700 text-xs ml-2" data-lead-id="${lead.id}" title="Remover">&times;</button>
            </div>
        `).join('');
         if (clearBtn) clearBtn.classList.remove('hidden');

        listContainer.querySelectorAll('.remove-selected-lead').forEach(button => {
            button.addEventListener('click', (e) => {
                const leadId = e.currentTarget.dataset.leadId;
                toggleLeadSelection(leadId, false);
            });
        });
    }
    countElement.textContent = localState.selectedLeads.length;
}

// Atualiza o estado e o texto do botão de envio
function updateSendButtonState() {
    const sendBtn = document.getElementById('send-email-btn');
    const sendCount = document.getElementById('send-count');
    const count = localState.selectedLeads.length;

    if (sendBtn && sendCount) {
        sendBtn.disabled = count === 0;
        sendCount.textContent = count;
    }
}

// Função chamada ao clicar no botão "Enviar Campanha"
async function handleSendEmailCampaign() {
    const { selectedLeads, subject } = localState;
    let body = ''; // Inicializa o body
    const editorInstance = tinymce.get('email-body-editor');
    if (localState.isEditorInitialized && editorInstance) {
        body = editorInstance.getContent(); // Pega o conteúdo atualizado
    } else {
         // Fallback se o editor não estiver pronto (improvável, mas seguro)
         body = appState.emailMarketingView?.body || '';
    }

    const recipientEmails = selectedLeads.map(lead => lead.email);

    if (recipientEmails.length === 0) {
        showToast("Nenhum destinatário selecionado.", 'error');
        return;
    }
    if (!subject.trim() || !body.trim()) {
         showToast("Assunto e Corpo do e-mail são obrigatórios.", 'error');
        return;
    }

    const confirmationMessage = `<p>Tem certeza que deseja enviar esta campanha para <strong>${recipientEmails.length}</strong> lead(s)?</p>`;

    renderModal('Confirmar Envio', confirmationMessage, async () => {
        closeModal();
        showLoading(true);
        const sendBtn = document.getElementById('send-email-btn');
        if(sendBtn) sendBtn.disabled = true;

        try {
            const result = await apiCall('send_bulk_email_leads', {
                method: 'POST',
                body: JSON.stringify({
                    emails: recipientEmails,
                    subject: subject,
                    body: body
                })
            });

            if (result.success) {
                showToast(`Campanha enviada (ou simulada) para ${result.sentCount || 0} destinatário(s).`, 'success');
                 // Limpar estado local e global
                 localState = {
                    searchTerm: '', searchResults: [], selectedLeads: [], subject: '', body: '', isEditorInitialized: true // Mantém editor inicializado
                 };
                 appState.emailMarketingView = {...localState}; // Atualiza estado global
                 // Limpa campos na UI e re-renderiza parcialmente
                 if (tinymce.get('email-body-editor')) {
                    tinymce.get('email-body-editor').setContent('');
                 }
                 document.getElementById('email-subject').value = '';
                 renderSelectedLeadsList(); // Limpa lista de selecionados
                 updateSendButtonState(); // Desabilita botão
            }
            // Erro já tratado pelo apiCall

        } catch (error) {
            console.error("Falha ao enviar campanha:", error);
        } finally {
            showLoading(false);
            // Reabilita o botão se ainda houver selecionados (improvável após limpar)
            if(sendBtn) sendBtn.disabled = localState.selectedLeads.length === 0;
        }
    }, 'Enviar', 'btn-primary');
}

