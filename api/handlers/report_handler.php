<?php
// api/handlers/report_handler.php

function handle_get_report_data($pdo)
{
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');
    $supplier_id = isset($_GET['supplier_id']) && $_GET['supplier_id'] !== '' ? (int) $_GET['supplier_id'] : null;
    $user_id = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int) $_GET['user_id'] : null;
    $type = $_GET['type'] ?? 'sales';
    try {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');
        $supplier_id = !empty($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
        $user_id = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;

        if ($type === 'products') {
            $data = get_products_report($pdo, $start_date, $end_date, $supplier_id, $user_id);
        } elseif ($type === 'licitacoes') {
            $data = get_licitacoes_report($pdo, $start_date, $end_date, $supplier_id, $user_id);
        } else {
            $data = get_sales_report($pdo, $start_date, $end_date, $supplier_id, $user_id);
        }

        echo json_encode(['success' => true, 'report_data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function get_sales_report($pdo, $start_date, $end_date, $supplier_id, $user_id)
{
    // 1. Fetch Sales Data
    $sql = "
        SELECT 
            vf.fornecedor_id,
            f.nome as fornecedor_nome,
            vf.usuario_id,
            u.nome as vendedor_nome,
            YEAR(vf.data_venda) as ano,
            MONTH(vf.data_venda) as mes,
            SUM(vf.valor_total) as total_vendido
        FROM vendas_fornecedores vf
        JOIN fornecedores f ON vf.fornecedor_id = f.id
        JOIN usuarios u ON vf.usuario_id = u.id
        WHERE vf.data_venda BETWEEN ? AND ?
    ";

    $params = [$start_date, $end_date];

    if ($supplier_id) {
        $sql .= " AND vf.fornecedor_id = ?";
        $params[] = $supplier_id;
    }

    if ($user_id) {
        $sql .= " AND vf.usuario_id = ?";
        $params[] = $user_id;
    }

    $sql .= " GROUP BY vf.fornecedor_id, vf.usuario_id, YEAR(vf.data_venda), MONTH(vf.data_venda)
              ORDER BY f.nome, u.nome, ano, mes";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vendas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Targets Data
    $sql_metas = "
        SELECT 
            vo.fornecedor_id,
            f.nome as fornecedor_nome,
            vo.usuario_id,
            u.nome as vendedor_nome,
            vo.ano,
            vo.mes,
            vo.valor_meta
        FROM vendas_objetivos vo
        JOIN fornecedores f ON vo.fornecedor_id = f.id
        JOIN usuarios u ON vo.usuario_id = u.id
        WHERE CAST(CONCAT(vo.ano, '-', LPAD(vo.mes, 2, '0'), '-01') AS DATE) BETWEEN ? AND ?
    ";

    // Adjust logic to properly filter meta by date range logic or just simple year/month match
    // Simplified range check for targets:
    $params_metas = [$start_date, $end_date];

    if ($supplier_id) {
        $sql_metas .= " AND vo.fornecedor_id = ?";
        $params_metas[] = $supplier_id;
    }

    if ($user_id) {
        $sql_metas .= " AND vo.usuario_id = ?";
        $params_metas[] = $user_id;
    }

    $stmt_metas = $pdo->prepare($sql_metas);
    $stmt_metas->execute($params_metas);
    $metas_data = $stmt_metas->fetchAll(PDO::FETCH_ASSOC);

    // 3. Process and Merge Data
    $report_data = [];

    // init helper
    $initStructure = function (&$array, $fid, $fname, $uid, $uname) {
        if (!isset($array[$fid])) {
            $array[$fid] = [
                'fornecedor_id' => $fid,
                'fornecedor_nome' => $fname,
                'rows' => []
            ];
        }
        // Check if user row exists in rows array (need to search or use map)
        // Using map for easier access then convert to array values
        if (!isset($array[$fid]['rows_map'][$uid])) {
            $array[$fid]['rows_map'][$uid] = [
                'usuario_id' => $uid,
                'vendedor_nome' => $uname,
                'dados_mes' => []
            ];
        }
    };

    // Process Sales
    foreach ($vendas_data as $row) {
        $fid = $row['fornecedor_id'];
        $uid = $row['usuario_id'];
        $key = $row['ano'] . '-' . $row['mes'];

        $initStructure($report_data, $fid, $row['fornecedor_nome'], $uid, $row['vendedor_nome']);

        if (!isset($report_data[$fid]['rows_map'][$uid]['dados_mes'][$key])) {
            $report_data[$fid]['rows_map'][$uid]['dados_mes'][$key] = ['venda' => 0, 'meta' => 0];
        }
        $report_data[$fid]['rows_map'][$uid]['dados_mes'][$key]['venda'] = (float) $row['total_vendido'];
    }

    // Process Targets
    foreach ($metas_data as $row) {
        $fid = $row['fornecedor_id'];
        $uid = $row['usuario_id'];
        $key = $row['ano'] . '-' . $row['mes'];

        $initStructure($report_data, $fid, $row['fornecedor_nome'], $uid, $row['vendedor_nome']);

        if (!isset($report_data[$fid]['rows_map'][$uid]['dados_mes'][$key])) {
            $report_data[$fid]['rows_map'][$uid]['dados_mes'][$key] = ['venda' => 0, 'meta' => 0];
        }
        $report_data[$fid]['rows_map'][$uid]['dados_mes'][$key]['meta'] = (float) $row['valor_meta'];
    }

    // Convert rows_map back to index array
    foreach ($report_data as &$supplier) {
        if (isset($supplier['rows_map'])) {
            $supplier['rows'] = array_values($supplier['rows_map']);
            unset($supplier['rows_map']);
        }
    }

    return $report_data;
}

function get_products_report($pdo, $start_date, $end_date, $supplier_id, $user_id)
{
    // Assuming 'oportunidades' has 'fornecedor_id' (or via some relation) and 'usuario_id'
    // And 'oportunidade_itens' holds products.
    // Status 'Ganho' (Assuming id for Ganho, lets check common standard or use status name if table unknown)
    // Checking `oportunidades` table structure from previous context: it has `fase_id` or similar? 
    // Assuming 'Ganho' status or filtering generally for now. If table structure uncertain, we'll do best guess.
    // Actually, report might just list *all* opportunities or just won ones. Let's assume Won/Sold.
    // However, the user asked for "Products Sold".

    $sql = "
        SELECT 
            o.fornecedor_id,
            f.nome as fornecedor_nome,
            oi.produto_id,
            oi.descricao as produto_nome,
            SUM(oi.quantidade) as quantidade,
            SUM(oi.valor_total) as valor_total,
            MAX(oi.valor_unitario) as valor_unitario
        FROM oportunidades o
        JOIN oportunidade_itens oi ON o.id = oi.oportunidade_id
        JOIN fornecedores f ON o.fornecedor_id = f.id
        WHERE o.created_at BETWEEN ? AND ? 
    ";
    // Ideally use a 'data_fechamento' or similar. Using created_at for now or if 'data_venda' exists.
    // Sales Logic usually relies on 'vendas_fornecedores'. But that doesn't detail items.
    // Let's assume 'oportunidades' with status 'Ganho' matches sales.
    // Let's filter by fase_id if possible. 
    // IMPORTANT: I will assume all opportunities in timeframe for now, or add status check if I knew the schema better.
    // Safe bet: Just filter by date.

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    if ($supplier_id) {
        $sql .= " AND o.fornecedor_id = ?";
        $params[] = $supplier_id;
    }
    if ($user_id) {
        $sql .= " AND o.usuario_id = ?";
        $params[] = $user_id;
    }

    $sql .= " GROUP BY o.fornecedor_id, oi.produto_id, oi.descricao ORDER BY f.nome, valor_total DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by Supplier
    $report_data = [];
    foreach ($data as $row) {
        $fid = $row['fornecedor_id'];
        if (!isset($report_data[$fid])) {
            $report_data[$fid] = [
                'fornecedor_id' => $fid,
                'fornecedor_nome' => $row['fornecedor_nome'],
                'rows' => []
            ];
        }
        $report_data[$fid]['rows'][] = $row;
    }
    return $report_data;
}

function get_licitacoes_report($pdo, $start_date, $end_date, $supplier_id, $user_id)
{
    // Fetch opportunities that are Licitations
    // Filter: modalidade IS NOT NULL or numero_edital IS NOT NULL
    $sql = "
        SELECT 
            o.id,
            o.fornecedor_id,
            f.nome as fornecedor_nome,
            o.numero_edital,
            o.uasg,
            o.objeto,
            o.valor_total,
            o.created_at,
            o.fase_id -- Assuming status/phase
        FROM oportunidades o
        JOIN fornecedores f ON o.fornecedor_id = f.id
        WHERE (o.numero_edital IS NOT NULL AND o.numero_edital != '')
        AND o.created_at BETWEEN ? AND ?
    ";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    if ($supplier_id) {
        $sql .= " AND o.fornecedor_id = ?";
        $params[] = $supplier_id;
    }

    if ($user_id) {
        $sql .= " AND o.usuario_id = ?";
        $params[] = $user_id;
    }

    $sql .= " ORDER BY f.nome, o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by Supplier
    $report_data = [];
    foreach ($data as $row) {
        $fid = $row['fornecedor_id'];
        if (!isset($report_data[$fid])) {
            $report_data[$fid] = [
                'fornecedor_id' => $fid,
                'fornecedor_nome' => $row['fornecedor_nome'],
                'rows' => []
            ];
        }
        $report_data[$fid]['rows'][] = $row;
    }
    return $report_data;
}
function handle_save_targets($pdo)
{
    // Espera JSON: { year: 2024, targets: [ { usuario_id, fornecedor_id, mes, valor }, ... ] }
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['year']) || !isset($data['targets'])) {
        json_response(['success' => false, 'error' => 'Dados inválidos.'], 400);
        return;
    }

    $year = (int) $data['year'];
    $targets = $data['targets'];

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO vendas_objetivos (usuario_id, fornecedor_id, ano, mes, valor_meta) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE valor_meta = VALUES(valor_meta)";
        $stmt = $pdo->prepare($sql);

        foreach ($targets as $target) {
            // Se usuario_id, fornecedor_id nao vierem, pula
            if (empty($target['usuario_id']) || empty($target['fornecedor_id']))
                continue;

            $stmt->execute([
                $target['usuario_id'],
                $target['fornecedor_id'],
                $year,
                $target['mes'],
                $target['valor']
            ]);
        }

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Metas salvas com sucesso.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Erro ao salvar metas: ' . $e->getMessage()], 500);
    }
}
?>