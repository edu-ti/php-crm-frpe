<?php
require_once 'api/core/Database.php';

$db = new Database();
$pdo = $db->getConnection();
try {
    $pdo->beginTransaction();

    // 1. Encontrar todas as oportunidades em "Negociação" sem nenhuma proposta
    $sqlNegociacao = "SELECT o.id, o.titulo 
                      FROM oportunidades o 
                      JOIN etapas_funil ef ON o.etapa_id = ef.id 
                      WHERE LOWER(ef.nome) IN ('negociação', 'em negociação', 'análise') 
                      AND (SELECT COUNT(*) FROM propostas WHERE oportunidade_id = o.id) = 0";
    $stmtNeg = $pdo->query($sqlNegociacao);
    $oppsNegociacao = $stmtNeg->fetchAll(PDO::FETCH_ASSOC);

    foreach ($oppsNegociacao as $opp) {
        $insertProp = $pdo->prepare("INSERT INTO propostas (oportunidade_id, status, data_criacao) VALUES (?, 'Negociando', NOW())");
        $insertProp->execute([$opp['id']]);
    }

    // 2. Encontrar todas as oportunidades "Fechadas" sem nenhuma proposta
    $sqlFechado = "SELECT o.* 
                   FROM oportunidades o 
                   JOIN etapas_funil ef ON o.etapa_id = ef.id 
                   WHERE LOWER(ef.nome) IN ('fechado', 'contrato', 'homologado', 'empenhado', 'ganho', 'vendido') 
                   AND (SELECT COUNT(*) FROM propostas WHERE oportunidade_id = o.id) = 0";
    $stmtFec = $pdo->query($sqlFechado);
    $oppsFechado = $stmtFec->fetchAll(PDO::FETCH_ASSOC);

    foreach ($oppsFechado as $opp) {
        // Criar a proposta aprovada
        $insertProp = $pdo->prepare("INSERT INTO propostas (oportunidade_id, status, data_criacao, data_aprovacao, comercial_user_id) VALUES (?, 'Aprovada', NOW(), NOW(), ?)");
        $insertProp->execute([$opp['id'], $opp['comercial_user_id'] ?? $opp['usuario_id']]);
        $propId = $pdo->lastInsertId();

        // Como ela é fechada, precisamos gerar o registro em vendas_fornecedores para corrigir os relatórios
        // Buscar os itens da oportunidade
        $stmtItems = $pdo->prepare("SELECT * FROM oportunidade_itens WHERE oportunidade_id = ?");
        $stmtItems->execute([$opp['id']]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $sql_insert_venda = "INSERT INTO vendas_fornecedores (fornecedor_id, organizacao_id, cliente_pf_id, usuario_id, titulo, data_venda, origem, descricao_produto, fabricante_marca, modelo, quantidade, valor_unitario, valor_total, notas, proposta_ref_id) VALUES (?, ?, ?, ?, ?, COALESCE(?, CURDATE()), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_venda = $pdo->prepare($sql_insert_venda);

        foreach ($items as $item) {
            $fornecedor_id_venda = $opp['fornecedor_id'] ?? null;
            
            // Tenta achar o fornecedor pelo fabricante se não estiver na oportunidade
            if (!$fornecedor_id_venda && !empty($item['fabricante'])) {
                $stmt_find_forn = $pdo->prepare("SELECT id FROM fornecedores WHERE nome = ? LIMIT 1");
                $stmt_find_forn->execute([trim($item['fabricante'])]);
                $forn_id_db = $stmt_find_forn->fetchColumn();
                if ($forn_id_db) {
                    $fornecedor_id_venda = $forn_id_db;
                }
            }

            // A tabela vendas_fornecedores OBRIGA ter o fornecedor_id. Se não houver, pulamos este item.
            if ($fornecedor_id_venda) {
                $multiplicador = (strtoupper($item['status'] ?? 'VENDA') === 'LOCAÇÃO') ? (int)($item['meses_locacao'] ?? 1) : 1;
                
                // Re-calcular valor unitário com os parâmetros (igual no opp_handler)
                $valor_parametros = 0;
                if (!empty($item['parametros'])) {
                    $params = json_decode($item['parametros'], true);
                    if (is_array($params)) {
                        foreach ($params as $param) {
                            if (in_array(strtolower($param['nome'] ?? ''), ['lote', 'item_num', 'item'])) continue;
                            $valor_parametros += (float)($param['valor'] ?? 0);
                        }
                    }
                }
                $valor_unit_final = ((float)($item['valor_unitario'] ?? 0)) + $valor_parametros;
                $valor_total_item = ((float)($item['quantidade'] ?? 1)) * $valor_unit_final * $multiplicador;

                $titulo_venda = 'Venda via Proposta #' . $propId . ' - ' . $opp['titulo'];
                $stmt_venda->execute([
                    $fornecedor_id_venda,
                    $opp['organizacao_id'] ?? null,
                    $opp['cliente_pf_id'] ?? null,
                    $opp['comercial_user_id'] ?? $opp['usuario_id'],
                    $titulo_venda,
                    $opp['data_ultima_movimentacao'], // usar a data que foi movida
                    'Oportunidade Migration',
                    $item['descricao'] . ($item['descricao_detalhada'] ? ' - ' . $item['descricao_detalhada'] : ''),
                    $item['fabricante'] ?? null,
                    $item['modelo'] ?? null,
                    $item['quantidade'] ?? 1,
                    $valor_unit_final,
                    $valor_total_item,
                    $opp['notas'] ?? null,
                    $propId
                ]);
            }
        }
    }

    // --- CORREÇÃO DE VENDAS JÁ MIGRADAS ---
    // Atualiza o título das vendas migradas anteriormente para o formato correto,
    // evitando que o sistema as conte duas vezes (como proposta aprovada e como venda direta).
    $pdo->exec("UPDATE vendas_fornecedores SET titulo = CONCAT('Venda via Proposta #', proposta_ref_id, ' - ', titulo) WHERE origem = 'Oportunidade Migration' AND titulo NOT LIKE 'Venda via Proposta #%'");
    
    $pdo->commit();
    echo "<h1>Migração Concluída com Sucesso!</h1>";
    echo "<p>Foram corrigidas " . count($oppsNegociacao) . " oportunidades em Negociação.</p>";
    echo "<p>Foram corrigidas " . count($oppsFechado) . " oportunidades Fechadas (Vendas contabilizadas).</p>";
    echo "<p>Você já pode voltar para o sistema e os relatórios estarão normalizados.</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h1>Erro na Migração</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
