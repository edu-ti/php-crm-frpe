// js/views/settings.js
import { apiCall } from '../api.js';
import { showToast } from '../utils.js';
import { renderModal, closeModal } from '../ui.js';

export function renderSettingsView(appState) {
    const { permissions } = appState.currentUser;
    const container = document.getElementById('settings-view');

    // Estado local da view para controlar a aba ativa (simples)
    let activeTab = 'users'; // 'users' or 'roles'

    // Função de renderização principal que decide o que mostrar
    const render = () => {
        container.innerHTML = `
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Administração</h1>
                <div class="flex space-x-2">
                    <button id="tab-users-btn" class="px-4 py-2 rounded-md ${activeTab === 'users' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50'}">
                        <i class="fas fa-users mr-2"></i> Usuários
                    </button>
                    ${permissions.canSeeSettings ? `
                    <button id="tab-roles-btn" class="px-4 py-2 rounded-md ${activeTab === 'roles' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50'}">
                        <i class="fas fa-shield-alt mr-2"></i> Perfis e Permissões
                    </button>
                    ` : ''}
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-4 min-h-[400px]">
                ${activeTab === 'users' ? renderUsersTab(appState) : '<div id="roles-tab-content">Carregando...</div>'}
            </div>
        `;

        addTabListeners();
        if (activeTab === 'users') {
            addUsersListeners(appState);
        } else {
            loadRolesTab(appState);
        }
    };

    // Renderiza inicialmente
    render();

    // Listeners das abas
    function addTabListeners() {
        document.getElementById('tab-users-btn').addEventListener('click', () => {
            activeTab = 'users';
            render();
        });
        document.getElementById('tab-roles-btn')?.addEventListener('click', () => {
            activeTab = 'roles';
            render();
        });
    }
}

// --- ABA DE USUÁRIOS (Lógica Existente Refatorada) ---

function renderUsersTab(appState) {
    const { permissions } = appState.currentUser;
    return `
        <div class="flex justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Gerenciamento de Usuários</h2>
            ${permissions.canCreate ? `
            <button id="add-user-btn" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-2"></i> Novo Usuário
            </button>
            ` : ''}
        </div>
        <div class="overflow-auto">
            ${renderUsersList(appState)}
        </div>
    `;
}

function renderUsersList(appState) {
    const users = appState.users;
    const { permissions } = appState.currentUser;

    if (users.length === 0) return `<p class="text-gray-500">Nenhum usuário encontrado.</p>`;

    const tableHeader = `
        <thead>
            <tr>
                <th class="table-header">Nome</th>
                <th class="table-header">Cargo</th>
                <th class="table-header">Email / Telefone</th>
                <th class="table-header">Perfil</th>
                <th class="table-header">Status</th>
                <th class="table-header text-right">Ações</th>
            </tr>
        </thead>
    `;

    const tableBody = users.map(user => `
        <tr>
            <td class="table-cell"><div class="font-medium">${user.nome}</div></td>
            <td class="table-cell"><div class="text-gray-600">${user.cargo || '-'}</div></td>
            <td class="table-cell"><div>${user.email}</div><div class="text-xs text-gray-500">${user.telefone || ''}</div></td>
            <td class="table-cell">${user.role}</td>
            <td class="table-cell">
                <span class="status-badge ${user.status === 'Ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${user.status}</span>
            </td>
            <td class="table-cell text-right">
                ${permissions.canEdit ? `<button class="action-btn edit-user-btn text-blue-600 hover:text-blue-800 mr-2" data-id="${user.id}"><i class="fas fa-edit"></i></button>` : ''}
                ${permissions.canDelete ? `<button class="action-btn delete-user-btn text-red-500 hover:text-red-700" data-id="${user.id}"><i class="fas fa-trash"></i></button>` : ''}
            </td>
        </tr>
    `).join('');

    return `<table class="w-full responsive-table">${tableHeader}<tbody>${tableBody}</tbody></table>`;
}

function addUsersListeners(appState) {
    document.getElementById('add-user-btn')?.addEventListener('click', () => openUserModal(null, appState));
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const user = appState.users.find(u => u.id == e.currentTarget.dataset.id);
            openUserModal(user, appState);
        });
    });
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const user = appState.users.find(u => u.id == e.currentTarget.dataset.id);
            if (confirm(`Excluir ${user.nome}?`)) {
                apiCall('delete_user', { method: 'POST', body: JSON.stringify({ id: user.id }) })
                    .then(() => {
                        appState.users = appState.users.filter(u => u.id != user.id);
                        renderSettingsView(appState); // Re-render logic would need to know the tab, simplifies to full re-render
                    });
            }
        });
    });
}

// --- ABA DE PERFIS (NOVA LÓGICA) ---

async function loadRolesTab(appState) {
    const container = document.getElementById('roles-tab-content');
    container.innerHTML = `<div class="p-8 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p>Carregando dados...</p></div>`;

    try {
        const [rolesData, permsData] = await Promise.all([
            apiCall('get_roles'),
            apiCall('get_permissions_catalog')
        ]);

        const roles = rolesData.roles;
        const catalog = permsData.catalog;

        renderRolesUI(container, roles, catalog, appState);

    } catch (error) {
        container.innerHTML = `<p class="text-red-500">Erro ao carregar dados: ${error.message}</p>`;
    }
}

function renderRolesUI(container, roles, catalog, appState) {
    // Agrupa catálogo por Recurso
    const resources = {};
    catalog.forEach(p => {
        if (!resources[p.resource]) resources[p.resource] = [];
        resources[p.resource].push(p);
    });

    container.innerHTML = `
        <div class="flex flex-col space-y-4">
            <div class="flex items-center space-x-4 border-b pb-4">
                <label class="font-semibold text-gray-700">Selecione o Perfil:</label>
                <select id="role-select" class="form-input w-64">
                    <option value="" disabled selected>-- Selecione --</option>
                    ${roles.map(r => `<option value="${r.name}">${r.name}</option>`).join('')}
                </select>
                <button id="save-perms-btn" class="btn btn-primary ml-auto hidden">
                    <i class="fas fa-save mr-2"></i> Salvar Alterações
                </button>
            </div>

            <div id="perms-matrix-container" class="mt-4">
                <p class="text-gray-500 italic">Selecione um perfil acima para editar as permissões.</p>
            </div>
        </div>
    `;

    const roleSelect = document.getElementById('role-select');
    const saveBtn = document.getElementById('save-perms-btn');
    const matrixContainer = document.getElementById('perms-matrix-container');

    roleSelect.addEventListener('change', async (e) => {
        const role = e.target.value;
        if (!role) return;

        matrixContainer.innerHTML = `<div class="text-center py-8"><i class="fas fa-spinner fa-spin"></i> Carregando permissões de ${role}...</div>`;
        saveBtn.classList.add('hidden');

        try {
            const result = await apiCall('get_role_permissions', { params: { role: role } });
            const userPerms = result.permissions || [];

            // Render Matrix
            renderPermissionsMatrix(matrixContainer, resources, userPerms, role);
            saveBtn.classList.remove('hidden');
        } catch (error) {
            matrixContainer.innerHTML = `<p class="text-red-500">Erro: ${error.message}</p>`;
        }
    });

    saveBtn.addEventListener('click', async () => {
        const role = roleSelect.value;
        const checkboxes = matrixContainer.querySelectorAll('input[type="checkbox"]');
        const permissions = [];

        checkboxes.forEach(cb => {
            permissions.push({
                resource: cb.dataset.resource,
                action: cb.dataset.action,
                allowed: cb.checked
            });
        });

        saveBtn.disabled = true;
        saveBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Salvando...`;

        try {
            await apiCall('save_role_permissions', {
                method: 'POST',
                body: JSON.stringify({ role, permissions })
            });
            showToast(`Permissões de ${role} salvas com sucesso!`);
        } catch (error) {
            showToast(`Erro ao salvar: ${error.message}`, 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="fas fa-save mr-2"></i> Salvar Alterações`;
        }
    });
}

function renderPermissionsMatrix(container, resources, userPerms, role) {
    if (role === 'SUPER_ADMIN') {
        container.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-yellow-700 font-medium">SUPER_ADMIN tem acesso total irrestrito (Bypass).</p>
                <p class="text-sm text-yellow-600">A edição de permissões para este perfil é desabilitada pois ele ignora checagens.</p>
            </div>
        `;
        return;
    }

    // Helper para checar se tem permissão
    const hasPerm = (resource, action) => {
        return userPerms.some(p => p.resource === resource && p.action === action && (p.allowed == 1 || p.allowed === true));
    };

    let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;

    for (const [resName, actions] of Object.entries(resources)) {
        html += `
            <div class="bg-gray-50 rounded-lg p-4 border block-resource" data-resource="${resName}">
                <h3 class="font-bold text-gray-800 mb-3 uppercase text-sm border-b pb-1">${formatResourceName(resName)}</h3>
                <div class="space-y-2">
                    ${actions.map(act => `
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-1 rounded">
                            <input type="checkbox" 
                                class="form-checkbox text-blue-600 h-4 w-4 perm-check" 
                                data-resource="${resName}" 
                                data-action="${act.action}"
                                ${hasPerm(resName, act.action) ? 'checked' : ''}
                            >
                            <span class="text-gray-700 text-sm">${act.label || act.action}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `;
    }
    html += `</div>`;
    container.innerHTML = html;

    // Lógica MOVE -> EDIT
    const checkboxes = container.querySelectorAll('.perm-check');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', (e) => {
            handlePermissionDependency(e.target, container);
        });
    });
}

function handlePermissionDependency(changedCheckbox, container) {
    const resource = changedCheckbox.dataset.resource;
    const action = changedCheckbox.dataset.action;
    const isChecked = changedCheckbox.checked;

    if (action === 'move' && isChecked) {
        // Se ativou MOVE, deve ativar EDIT
        const editCb = container.querySelector(`input[data-resource="${resource}"][data-action="edit"]`);
        if (editCb && !editCb.checked) {
            editCb.checked = true;
            // Opcional: mostrar pequeno feedback visual
        }
    }

    if (action === 'edit' && !isChecked) {
        // Se desativou EDIT, deve desativar MOVE
        const moveCb = container.querySelector(`input[data-resource="${resource}"][data-action="move"]`);
        if (moveCb && moveCb.checked) {
            moveCb.checked = false;
        }
    }
}

function formatResourceName(name) {
    const map = {
        'leads': 'Leads / Funil',
        'settings': 'Configurações',
        'products': 'Catálogo de Produtos',
        'clients': 'Clientes',
        'reports': 'Relatórios',
        'marketing_module': 'Módulo de Marketing',
        'opportunities': 'Oportunidades',
        'users': 'Gerenciamento de Usuários'
    };
    return map[name] || name;
}


// --- Modal de Usuário Recriado para usar API de Roles dinâmica ---

async function openUserModal(user, appState) {
    const isEditing = user !== null;

    // Busca roles dinamicamente
    let roles = [];
    try {
        const res = await apiCall('get_roles');
        roles = res.roles.map(r => r.name);
    } catch {
        roles = ['Gestor', 'Comercial', 'Vendedor', 'Marketing']; // Fallback
    }

    const title = isEditing ? 'Editar Usuário' : 'Adicionar Novo Usuário';
    const roleOptions = roles.map(role =>
        `<option value="${role}" ${isEditing && user.role === role ? 'selected' : ''}>${role}</option>`
    ).join('');

    const content = `
        <form id="modal-form">
            <input type="hidden" name="id" value="${isEditing ? user.id : ''}">
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label">Nome*</label>
                        <input type="text" name="nome" required value="${isEditing ? user.nome : ''}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Email*</label>
                        <input type="email" name="email" required value="${isEditing ? user.email : ''}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Telefone</label>
                        <input type="tel" name="telefone" value="${isEditing && user.telefone ? user.telefone : ''}" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Cargo</label>
                    <input type="text" name="cargo" value="${isEditing && user.cargo ? user.cargo : ''}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Senha*</label>
                    <input type="password" name="senha" ${isEditing ? '' : 'required'} class="form-input" placeholder="${isEditing ? 'Deixe em branco para não alterar' : ''}">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Perfil*</label>
                        <select name="role" required class="form-input">${roleOptions}</select>
                    </div>
                    <div>
                        <label class="form-label">Status*</label>
                        <select name="status" required class="form-input">
                            <option value="Ativo" ${isEditing && user.status === 'Ativo' ? 'selected' : ''}>Ativo</option>
                            <option value="Inativo" ${isEditing && user.status === 'Inativo' ? 'selected' : ''}>Inativo</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    `;

    renderModal(title, content, async (form) => {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const result = await apiCall(isEditing ? 'update_user' : 'create_user', { method: 'POST', body: JSON.stringify(data) });
            // Atualiza AppState localmente ou recarrega? A função original atualizava local.
            if (isEditing) {
                const index = appState.users.findIndex(u => u.id == result.user.id);
                if (index !== -1) appState.users[index] = result.user;
                showToast('Usuário atualizado com sucesso!');
            } else {
                appState.users.push(result.user);
                showToast('Usuário criado com sucesso!');
            }
            renderSettingsView(appState);
            closeModal();
        } catch (error) {
            // Toast já tratada no utils ou apiCall geralmente, mas aqui o original tinha catch vazio
            showToast(error.message || 'Erro ao salvar', 'error');
        }
    });
}
