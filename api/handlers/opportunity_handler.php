<?php
// api/handlers/opportunity_handler.php

function handle_create_opportunity($pdo, $data) {
    // Validações básicas
    if (empty($data['titulo'])) {
        json_response(['success' => false, 'error' => 'Título é obrigatório.'], 400);
    }
    if (empty($data['items']) || !is_array($data['items'])) {
        json_response(['success' => false, 'error' => 'É necessário adicionar pelo menos um item à oportunidade.'], 400);
    }

    $pre_proposal_number = null;
    if (!empty($data['comercial_user_id'])) {
        $year = date('Y');
        $stmt_max = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(pre_proposal_number, '/', 1) AS UNSIGNED)) FROM oportunidades WHERE pre_proposal_number LIKE ?");
        $stmt_max->execute(["%/$year"]);
        $max_num = $stmt_max->fetchColumn();
        $next_num = ($max_num ? $max_num : 0) + 1;
        $pre_proposal_number = str_pad($next_num, 4, '0', STR_PAD_LEFT) . '/' . $year;
    }

    // --- CORREÇÃO: Calcula o valor total a partir de TODOS os itens ---
    $valor_total = 0;
    foreach ($data['items'] as $item) {
        $valor_unitario_base = (float)($item['valor_unitario'] ?? 0);
        $valor_parametros = 0;
        if (!empty($item['parametros']) && is_array($item['parametros'])) {
            foreach ($item['parametros'] as $param) {
                // O valor do parâmetro vem do JS como número (parseCurrency)
                $valor_parametros += (float)($param['valor'] ?? 0);
            }
        }
        $valor_unitario_total = $valor_unitario_base + $valor_parametros;
        $multiplicador = (strtoupper($item['status'] ?? 'VENDA') === 'LOCAÇÃO') ? 24 : 1;
        $valor_total += (($item['quantidade'] ?? 1) * $valor_unitario_total * $multiplicador);
    }
    // --- FIM DA CORREÇÃO ---
    
    // --- CORREÇÃO: SQL simplificado, usa nova estrutura de itens ---
    $sql = "INSERT INTO oportunidades (titulo, organizacao_id, contato_id, cliente_pf_id, etapa_id, usuario_id, comercial_user_id, pre_proposal_number, valor, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $pdo->beginTransaction();
    try {
        $success = $stmt->execute([
            $data['titulo'],
            $data['organizacao_id'] ?? null,
            $data['contato_id'] ?? null,
            $data['cliente_pf_id'] ?? null,
            $data['etapa_id'] ?? 1,
            $_SESSION['user_id'],
            $data['comercial_user_id'] ?? null,
            $pre_proposal_number,
            $valor_total, // Valor total calculado
            $data['notas'] ?? null
        ]);
        
        if (!$success) {
            throw new Exception("Falha ao criar a oportunidade principal.");
        }

        $lastId = $pdo->lastInsertId();

        // --- INÍCIO: Insere itens na nova tabela 'oportunidade_itens' ---
        $item_sql = "INSERT INTO oportunidade_itens (oportunidade_id, produto_id, descricao, descricao_detalhada, fabricante, modelo, imagem_url, quantidade, valor_unitario, status, unidade_medida, parametros) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $item_stmt = $pdo->prepare($item_sql);
        
        foreach ($data['items'] as $item) {
             $item_parametros_json = null;
             if (!empty($item['parametros']) && is_array($item['parametros'])) {
                // Salva o valor do parâmetro já como número
                 $item_parametros_json = json_encode($item['parametros']);
             }
             
             $item_stmt->execute([
                 $lastId,
                 $item['produto_id'] ?? null,
                 $item['descricao'],
                 $item['descricao_detalhada'] ?? null,
                 $item['fabricante'] ?? null,
                 $item['modelo'] ?? null,
                 $item['imagem_url'] ?? null,
                 $item['quantidade'] ?? 1,
                 $item['valor_unitario'] ?? 0, // Valor base
                 $item['status'] ?? 'VENDA',
                 $item['unidade_medida'] ?? 'Unidade',
                 $item_parametros_json
             ]);
        }
        // --- FIM: Inserção de itens ---

        if (!empty($data['lead_id'])) {
            $stmt_update_lead = $pdo->prepare("UPDATE leads SET oportunidade_id = ? WHERE id = ?");
            $stmt_update_lead->execute([$lastId, $data['lead_id']]);
        }
        
        $pdo->commit();

        // Busca dados completos da oportunidade criada (incluindo nomes via JOINs)
        $stmt_new = $pdo->prepare("
            SELECT o.*,
                   org.nome_fantasia as organizacao_nome,
                   cpf.nome as cliente_pf_nome,
                   c.nome as contato_nome, c.email as contato_email, c.telefone as contato_telefone,
                   u.nome as vendedor_nome
            FROM oportunidades o
            LEFT JOIN organizacoes org ON o.organizacao_id = org.id
            LEFT JOIN clientes_pf cpf ON o.cliente_pf_id = cpf.id
            LEFT JOIN contatos c ON o.contato_id = c.id
            LEFT JOIN usuarios u ON o.usuario_id = u.id
            WHERE o.id = ?");
        $stmt_new->execute([$lastId]);
        json_response(['success' => true, 'opportunity' => $stmt_new->fetch(PDO::FETCH_ASSOC)]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro DB (Create Opportunity): " . $e->getMessage() . " | Data: " . json_encode($data));
        json_response(['success' => false, 'error' => 'Falha ao criar oportunidade e seus itens.'], 500);
    }
}


function handle_update_opportunity($pdo, $data) {
    if (empty($data['id'])) {
         json_response(['success' => false, 'error' => 'ID da oportunidade é obrigatório.'], 400);
     }
    if (empty($data['titulo'])) {
        json_response(['success' => false, 'error' => 'Título é obrigatório.'], 400);
    }
    if (empty($data['items']) || !is_array($data['items'])) {
        json_response(['success' => false, 'error' => 'É necessário pelo menos um item na oportunidade.'], 400);
    }
    
    // --- CORREÇÃO: Calcula o valor total a partir de TODOS os itens ---
    $valor_total = 0;
    foreach ($data['items'] as $item) {
        $valor_unitario_base = (float)($item['valor_unitario'] ?? 0);
        $valor_parametros = 0;
        if (!empty($item['parametros']) && is_array($item['parametros'])) {
            foreach ($item['parametros'] as $param) {
                // O valor do parâmetro vem do JS como número (parseCurrency)
                $valor_parametros += (float)($param['valor'] ?? 0);
            }
        }
        $valor_unitario_total = $valor_unitario_base + $valor_parametros;
        $multiplicador = (strtoupper($item['status'] ?? 'VENDA') === 'LOCAÇÃO') ? 24 : 1;
        $valor_total += (($item['quantidade'] ?? 1) * $valor_unitario_total * $multiplicador);
    }
    // --- FIM DA CORREÇÃO ---

    // --- CORREÇÃO: SQL simplificado, remove colunas de item único ---
    $sql = "UPDATE oportunidades SET
                titulo = ?,
                organizacao_id = ?,
                contato_id = ?,
                cliente_pf_id = ?,
                valor = ?,
                notas = ?,
                comercial_user_id = ?
            WHERE id = ?";

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $data['titulo'],
            $data['organizacao_id'] ?? null,
            $data['contato_id'] ?? null,
            $data['cliente_pf_id'] ?? null,
            $valor_total, // Valor total calculado
            $data['notas'] ?? null,
            empty($data['comercial_user_id']) ? null : $data['comercial_user_id'],
            $data['id']
        ]);

        if (!$success) {
            throw new Exception("Falha ao atualizar a oportunidade principal.");
        }

        // --- INÍCIO: Atualiza itens na tabela 'oportunidade_itens' (DELETE/INSERT) ---
        $delete_stmt = $pdo->prepare("DELETE FROM oportunidade_itens WHERE oportunidade_id = ?");
        $delete_stmt->execute([$data['id']]);

        $item_sql = "INSERT INTO oportunidade_itens (oportunidade_id, produto_id, descricao, descricao_detalhada, fabricante, modelo, imagem_url, quantidade, valor_unitario, status, unidade_medida, parametros) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $item_stmt = $pdo->prepare($item_sql);
        
        foreach ($data['items'] as $item) {
             $item_parametros_json = null;
             if (!empty($item['parametros']) && is_array($item['parametros'])) {
                 $item_parametros_json = json_encode($item['parametros']);
             }
             
             $item_stmt->execute([
                 $data['id'],
                 $item['produto_id'] ?? null,
                 $item['descricao'],
                 $item['descricao_detalhada'] ?? null,
                 $item['fabricante'] ?? null,
                 $item['modelo'] ?? null,
                 $item['imagem_url'] ?? null,
                 $item['quantidade'] ?? 1,
                 $item['valor_unitario'] ?? 0, // Valor base
                 $item['status'] ?? 'VENDA',
                 $item['unidade_medida'] ?? 'Unidade',
                 $item_parametros_json
             ]);
        }
        // --- FIM: Atualização de itens ---
        
        $pdo->commit();

        // Busca dados completos da oportunidade atualizada
        $stmt_updated = $pdo->prepare("
            SELECT o.*,
                   org.nome_fantasia as organizacao_nome,
                   cpf.nome as cliente_pf_nome,
                   c.nome as contato_nome, c.email as contato_email, c.telefone as contato_telefone,
                   u.nome as vendedor_nome
            FROM oportunidades o
            LEFT JOIN organizacoes org ON o.organizacao_id = org.id
            LEFT JOIN clientes_pf cpf ON o.cliente_pf_id = cpf.id
            LEFT JOIN contatos c ON o.contato_id = c.id
            LEFT JOIN usuarios u ON o.usuario_id = u.id
            WHERE o.id = ?");
        $stmt_updated->execute([$data['id']]);
        json_response(['success' => true, 'opportunity' => $stmt_updated->fetch(PDO::FETCH_ASSOC)]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro DB (Update Opportunity): " . $e->getMessage() . " | Data: " . json_encode($data));
        json_response(['success' => false, 'error' => 'Falha ao atualizar oportunidade e seus itens.'], 500);
    }
}


function handle_delete_opportunity($pdo, $data) {
    if (!in_array($_SESSION['role'], ['Gestor', 'Analista', 'Comercial'])) {
        json_response(['success' => false, 'error' => 'Acesso negado para exclusão.'], 403);
        return; 
    }
    if (empty($data['id'])) {
        json_response(['success' => false, 'error' => 'ID da oportunidade não fornecido.'], 400);
    }

    $pdo->beginTransaction();
    try {
        $opp_id = $data['id'];

        // 1. Desvincula leads (DELETE CASCADE na tabela de itens já trata os itens)
        $stmt_unlink_lead = $pdo->prepare("UPDATE leads SET oportunidade_id = NULL WHERE oportunidade_id = ?");
        $stmt_unlink_lead->execute([$opp_id]);

        // 2. Excluir histórico de atribuição
         $stmt_delete_hist = $pdo->prepare("DELETE FROM historico_atribuicao WHERE oportunidade_id = ?");
         $stmt_delete_hist->execute([$opp_id]);

        // 3. Excluir a oportunidade principal (itens em 'oportunidade_itens' serão excluídos por CASCADE)
        $stmt = $pdo->prepare("DELETE FROM oportunidades WHERE id = ?");
        $success = $stmt->execute([$opp_id]);

        if ($success && $stmt->rowCount() > 0) {
            $pdo->commit();
            json_response(['success' => true]);
        } else {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Oportunidade não encontrada ou falha ao excluir.'], $success ? 404 : 500);
        }
    } catch (PDOException $e) {
         $pdo->rollBack();
         error_log("Erro DB (Delete Opportunity): " . $e->getMessage());
         json_response(['success' => false, 'error' => 'Erro no banco de dados ao excluir.'], 500);
    }
}


function handle_move_opportunity($pdo, $data) {
    if (empty($data['opportunityId']) || empty($data['newStageId'])) {
        json_response(['success' => false, 'error' => 'Dados insuficientes para mover oportunidade.'], 400);
    }
     if (!in_array($_SESSION['role'], ['Gestor', 'Analista', 'Comercial'])) {
         json_response(['success' => false, 'error' => 'Acesso negado para mover oportunidades.'], 403);
         return;
     }

    $stmt = $pdo->prepare("UPDATE oportunidades SET etapa_id = ?, data_ultima_movimentacao = NOW() WHERE id = ?");
    $success = $stmt->execute([$data['newStageId'], $data['opportunityId']]);
    if ($success && $stmt->rowCount() > 0) {
        json_response(['success' => true]);
    } elseif ($success) {
        json_response(['success' => true, 'message' => 'Nenhuma alteração necessária.']);
    } else {
        json_response(['success' => false, 'error' => 'Falha ao mover a oportunidade.'], 500);
    }
}

function handle_get_opportunity_details($pdo, $get_data) {
    $id = isset($get_data['id']) ? (int)$get_data['id'] : 0;
    if (empty($id)) {
        json_response(['success' => false, 'error' => 'ID da oportunidade não fornecido.'], 400);
    }
    
    // 1. Busca a oportunidade principal e dados da proposta (se houver)
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            org.nome_fantasia as organizacao_nome,
            cpf.nome as cliente_pf_nome,
            c.nome as contato_nome, c.email as contato_email, c.telefone as contato_telefone,
            u.nome as vendedor_nome,
            p.id as proposta_id, -- ID da proposta vinculada
            p.numero_proposta   -- Número da proposta
        FROM oportunidades o
        LEFT JOIN organizacoes org ON o.organizacao_id = org.id
        LEFT JOIN clientes_pf cpf ON o.cliente_pf_id = cpf.id
        LEFT JOIN contatos c ON o.contato_id = c.id
        LEFT JOIN usuarios u ON o.usuario_id = u.id
        LEFT JOIN propostas p ON p.oportunidade_id = o.id -- Junta com propostas
        WHERE o.id = ?");
    $stmt->execute([$id]);
    $opportunity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunity) {
        json_response(['success' => false, 'error' => 'Oportunidade não encontrada.'], 404);
        return;
    }

    $items = [];
    // 2. Decide de onde buscar os itens
    if (!empty($opportunity['proposta_id'])) {
        // --- Se tem proposta, busca os itens da PROPOSTA ---
        $stmt_items = $pdo->prepare("SELECT * FROM proposta_itens WHERE proposta_id = ?");
        $stmt_items->execute([$opportunity['proposta_id']]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // --- Se NÃO tem proposta, busca os itens da OPORTUNIDADE ---
        $stmt_items = $pdo->prepare("SELECT * FROM oportunidade_itens WHERE oportunidade_id = ?");
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. Decodifica o JSON de parâmetros para cada item
    $opportunity['items'] = []; // Inicializa
    if ($items) {
        foreach ($items as $item) {
            if (!empty($item['parametros'])) {
                try {
                    $decoded_params = json_decode($item['parametros'], true);
                    // Garante que é um array, mesmo se o JSON for inválido
                    if (is_array($decoded_params)) {
                        $item['parametros'] = $decoded_params;
                    } else {
                         $item['parametros'] = [];
                    }
                } catch (Exception $e) {
                    $item['parametros'] = []; 
                }
            } else {
                $item['parametros'] = []; // Define como array vazio se for nulo
            }
            $opportunity['items'][] = $item;
        }
    }

    json_response(['success' => true, 'opportunity' => $opportunity]);
}


function handle_transfer_opportunity($pdo, $data) {
    if (!in_array($_SESSION['role'], ['Gestor', 'Analista'])) {
        json_response(['success' => false, 'error' => 'Acesso negado para transferir oportunidades.'], 403);
        return;
    }

    if (empty($data['opportunityId']) || empty($data['newUserId'])) {
        json_response(['success' => false, 'error' => 'Dados insuficientes para transferir.'], 400);
    }
    $stmt_opp = $pdo->prepare("SELECT usuario_id FROM oportunidades WHERE id = ?");
    $stmt_opp->execute([$data['opportunityId']]);
    $current_owner = $stmt_opp->fetchColumn();

    $stmt_user_check = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND status = 'Ativo'");
    $stmt_user_check->execute([$data['newUserId']]);
    if(!$stmt_user_check->fetchColumn()){
         json_response(['success' => false, 'error' => 'Utilizador de destino inválido ou inativo.'], 400);
         return;
    }

    $pdo->beginTransaction();
    try {
        $stmt_update = $pdo->prepare("UPDATE oportunidades SET usuario_id = ? WHERE id = ?");
        $stmt_update->execute([$data['newUserId'], $data['opportunityId']]);

        $stmt_log = $pdo->prepare("INSERT INTO historico_atribuicao (oportunidade_id, usuario_anterior_id, usuario_novo_id, usuario_transferencia_id) VALUES (?, ?, ?, ?)");
        $stmt_log->execute([$data['opportunityId'], $current_owner, $data['newUserId'], $_SESSION['user_id']]);
        
        $pdo->commit();
        json_response(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Erro na transação: ' . $e->getMessage()], 500);
    }
}

?>