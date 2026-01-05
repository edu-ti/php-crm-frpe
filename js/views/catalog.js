// js/views/catalog.js

import { appState } from '../script.js';
import { apiCall } from '../api.js';
import { showToast, formatCurrency, parseCurrency, formatCurrencyForInput } from '../utils.js';
import { renderModal, closeModal } from '../ui.js';

let localState = {
    searchTerm: '',
    selectedFornecedor: '', // Estado para o filtro de fornecedor
    editingProduct: null
};

export function renderCatalogView() {
    const { permissions } = appState.currentUser;
    const container = document.getElementById('catalog-view');

    // --- Lógica para criar opções do filtro de fornecedor ---
    // Pega todos os fabricantes únicos e não nulos
    const fornecedores = [...new Set(
        (appState.products || [])
            .map(p => p.fabricante)
            .filter(Boolean) // Remove valores nulos ou vazios
    )].sort(); // Ordena alfabeticamente

    const fornecedorOptions = fornecedores.map(f => 
        `<option value="${f}" ${localState.selectedFornecedor === f ? 'selected' : ''}>${f}</option>`
    ).join('');
    // --- Fim da lógica do filtro ---

    container.innerHTML = `
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Catálogo de Produtos</h1>
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <div class="relative flex-grow">
                    <input type="text" id="product-search" placeholder="Pesquisar produtos..." class="form-input w-full" value="${localState.searchTerm || ''}">
                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <!-- --- Filtro de Fornecedor Adicionado --- -->
                <div class="flex-grow sm:flex-grow-0">
                     <select id="fornecedor-filter" class="form-input w-full sm:w-48">
                         <option value="">Todos Fornecedores</option>
                         ${fornecedorOptions}
                     </select>
                </div>
                 <!-- --- Fim do Filtro --- -->
                ${permissions.canCreate ? `
                <button id="add-product-btn" class="btn btn-primary flex-shrink-0"><i class="fas fa-plus mr-2"></i>Novo Produto</button>
                ` : ''}
            </div>
        </div>
        <div id="product-list-container" class="bg-white rounded-lg shadow-sm border responsive-table-container flex-1 overflow-y-auto"></div>
    `;

    addCatalogEventListeners();
    renderProductList();
}

function renderProductList() {
    const container = document.getElementById('product-list-container');
    const { products } = appState;
    // Pega os valores dos filtros do estado local
    const searchTerm = localState.searchTerm.toLowerCase();
    const selectedFornecedor = localState.selectedFornecedor;
    const { permissions } = appState.currentUser;

    const filteredProducts = (products || []).filter(p => {
        const matchesSearch = (
            (p.nome_produto && p.nome_produto.toLowerCase().includes(searchTerm)) ||
            (p.fabricante && p.fabricante.toLowerCase().includes(searchTerm)) ||
            (p.modelo && p.modelo.toLowerCase().includes(searchTerm))
        );
        // Se um fornecedor for selecionado, o produto DEVE ser desse fornecedor
        const matchesFornecedor = !selectedFornecedor || p.fabricante === selectedFornecedor;

        return matchesSearch && matchesFornecedor;
    });

    container.innerHTML = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead>
                    <tr>
                        <th class="table-header w-24">Imagem</th>
                        <th class="table-header">Produto</th>
                        <!-- --- Coluna Fabricante Adicionada --- -->
                        <th class="table-header">Fabricante</th>
                        <th class="table-header">Valor Unitário</th>
                        <th class="table-header">Unidade</th>
                        <th class="table-header text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${filteredProducts.map(p => `
                        <tr class="responsive-table-row">
                            <td data-label="Imagem" class="table-cell">
                                <img src="${p.imagem_url || 'https://placehold.co/100x100/e2e8f0/64748b?text=Sem+Img'}" 
                                     alt="${p.nome_produto}" 
                                     class="w-16 h-16 object-cover rounded border"
                                     onerror="this.onerror=null;this.src='https://placehold.co/100x100/e2e8f0/64748b?text=Erro'">
                            </td>
                            <td data-label="Produto" class="table-cell">
                                <p class="font-bold text-gray-900">${p.nome_produto || 'Nome não definido'}</p>
                                <p class="text-sm text-gray-500">${p.modelo || ''}</p>
                            </td>
                            <!-- --- Coluna Fabricante Adicionada --- -->
                            <td data-label="Fabricante" class="table-cell">${p.fabricante || 'N/A'}</td>
                            <td data-label="Valor" class="table-cell">${formatCurrency(p.valor_unitario)}</td>
                            <td data-label="Unidade" class="table-cell">${p.unidade_medida || 'Unidade'}</td>
                            <td data-label="Ações" class="table-cell text-right space-x-2 actions-cell">
                                ${permissions.canEdit ? `<button class="action-btn edit-product-btn" title="Editar" data-id="${p.id}"><i class="fas fa-pencil-alt"></i></button>` : ''}
                                ${permissions.canDelete ? `<button class="action-btn delete-product-btn" title="Excluir" data-id="${p.id}"><i class="fas fa-trash-alt text-red-500 hover:text-red-700"></i></button>` : ''}
                            </td>
                        </tr>
                    `).join('') || `<tr><td colspan="6" class="text-center py-4 text-gray-500">Nenhum produto encontrado.</td></tr>`}
                </tbody>
            </table>
        </div>
    `;

    addProductListEventListeners();
}

function addCatalogEventListeners() {
    document.getElementById('add-product-btn')?.addEventListener('click', () => openProductModal(null));
    
    // Atualiza estado local e re-renderiza ao pesquisar
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            localState.searchTerm = e.target.value;
            renderProductList();
        });
    }

    // Atualiza estado local e re-renderiza ao filtrar
    const fornecedorFilter = document.getElementById('fornecedor-filter');
    if (fornecedorFilter) {
        fornecedorFilter.addEventListener('change', (e) => {
            localState.selectedFornecedor = e.target.value;
            renderProductList();
        });
    }
}


function addProductListEventListeners() {
    document.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const productId = e.currentTarget.dataset.id;
            const product = appState.products.find(p => p.id == productId);
            if (product) openProductModal(product);
        });
    });

    document.querySelectorAll('.delete-product-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const productId = e.currentTarget.dataset.id;
            openDeleteProductModal(productId);
        });
    });
}

function openProductModal(product) {
    const isEditing = !!product;
    localState.editingProduct = product ? { ...product } : { unidade_medida: 'Unidade' };
    const data = localState.editingProduct;

    const title = isEditing ? 'Editar Produto' : 'Novo Produto no Catálogo';
    const imageUrl = data.imagem_url || 'https://placehold.co/150x150/e2e8f0/64748b?text=Imagem';

    // --- REMOVIDA a seção de Parâmetros ---
    const content = `
        <form id="modal-form" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 space-y-4">
                    <div><label class="form-label">Nome do Produto*</label><input type="text" name="nome_produto" required class="form-input" value="${data.nome_produto || ''}"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="form-label">Fabricante</label><input type="text" name="fabricante" class="form-input" value="${data.fabricante || ''}"></div>
                        <div><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-input" value="${data.modelo || ''}"></div>
                    </div>
                </div>
                <div class="text-center">
                    <label class="form-label">Imagem</label>
                    <img id="product-image-preview" src="${imageUrl}" class="w-28 h-28 object-cover mx-auto rounded border mb-2" onerror="this.onerror=null;this.src='https://placehold.co/150x150/e2e8f0/64748b?text=Erro'">
                    <input type="file" class="hidden" id="product-image-upload" accept="image/*">
                    <label for="product-image-upload" class="btn btn-secondary btn-sm cursor-pointer w-full text-xs">Escolher</label>
                </div>
            </div>
            <div><label class="form-label">Descrição Detalhada</label><textarea name="descricao_detalhada" rows="4" class="form-input">${data.descricao_detalhada || ''}</textarea></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="form-label">Valor Unitário*</label><input type="text" name="valor_unitario" required class="form-input" value="${formatCurrencyForInput(data.valor_unitario)}"></div>
                <div><label class="form-label">Unidade de Medida</label><input type="text" name="unidade_medida" class="form-input" value="${data.unidade_medida || 'Unidade'}"></div>
            </div>
        </form>
    `;

    renderModal(title, content, saveProduct);

    document.getElementById('product-image-upload').addEventListener('change', handleProductImageUpload);

    // Adiciona listener para formatar o valor monetário ao perder o foco
    const valorInput = document.querySelector('input[name="valor_unitario"]');
    if(valorInput) {
        valorInput.addEventListener('blur', (e) => {
             e.target.value = formatCurrencyForInput(parseCurrency(e.target.value));
        });
    }
}

async function handleProductImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('product_image', file);
    showToast('Enviando imagem...', 'info');

    try {
        const result = await apiCall('upload_product_image', { method: 'POST', body: formData });
        showToast('Imagem enviada com sucesso!');
        document.getElementById('product-image-preview').src = result.url;
        localState.editingProduct.imagem_url = result.url;
    } catch (error) {
        document.getElementById('product-image-preview').src = 'https://placehold.co/150x150/e2e8f0/64748b?text=Erro';
    }
}

async function saveProduct(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Pega o ID e a imagem do estado local
    data.id = localState.editingProduct.id;
    data.imagem_url = localState.editingProduct.imagem_url;
    
    // --- CORREÇÃO: Converte valor monetário para número antes de enviar ---
    data.valor_unitario = parseCurrency(data.valor_unitario);
    
    // Validação para permitir valor 0
    if (data.valor_unitario === null || data.valor_unitario === undefined || data.valor_unitario < 0) {
        showToast('Por favor, insira um Valor Unitário válido (pode ser 0).', 'error');
        return;
    }
    // --- FIM DA CORREÇÃO ---


    const action = data.id ? 'update_product' : 'create_product';
    const successMessage = data.id ? 'Produto atualizado com sucesso!' : 'Produto criado com sucesso!';

    try {
        const result = await apiCall(action, { method: 'POST', body: JSON.stringify(data) });
        
        // Atualiza o estado da aplicação com o produto salvo
        const savedProduct = result.product;
        if (data.id) {
            const index = appState.products.findIndex(p => p.id == savedProduct.id);
            if (index !== -1) {
                appState.products[index] = savedProduct;
            }
        } else {
            appState.products.push(savedProduct);
        }

        showToast(successMessage);
        closeModal();
        renderCatalogView(); // Re-renderiza a view inteira para atualizar o filtro de fornecedores
    } catch (error) {
        // O erro já é exibido pelo apiCall
    }
}

function openDeleteProductModal(productId) {
    const product = appState.products.find(p => p.id == productId);
    if (!product) return;

    const content = `<p>Você tem certeza que deseja excluir o produto <strong>${product.nome_produto}</strong>? Esta ação não pode ser desfeita.</p>`;
    
    renderModal('Confirmar Exclusão', content, async () => {
        try {
            await apiCall('delete_product', { method: 'POST', body: JSON.stringify({ id: productId }) });
            appState.products = appState.products.filter(p => p.id != productId);
            showToast('Produto excluído com sucesso!');
            closeModal();
            renderCatalogView(); // Re-renderiza a view inteira para atualizar o filtro
        } catch(error) {}
    }, 'Excluir', 'btn-danger');
}

