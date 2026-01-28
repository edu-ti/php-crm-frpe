<?php
// api/handlers/report_handler.php

function handle_get_report_data($pdo)
{
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');

    // Fix Month Format (YYYY-MM to YYYY-MM-DD)
    if (preg_match('/^\d{4}-\d{2}$/', $start_date)) {
        $start_date .= '-01';
    }
    if (preg_match('/^\d{4}-\d{2}$/', $end_date)) {
        $end_date = date('Y-m-t', strtotime($end_date . '-01'));
    }

    // Handle multi-select: receive as comma-separated string or array
    $supplier_id_input = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : null;
    $user_id_input = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    // Helper to parse comma-separated or array to array of integers
    $parseIds = function ($input) {
        if (is_array($input))
            return array_map('intval', $input);
        if (is_string($input) && strlen($input) > 0)
            return array_map('intval', explode(',', $input));
        if (is_numeric($input))
            return [(int) $input];
        return [];
    };

    $supplier_ids = $parseIds($supplier_id_input);
    $user_ids = $parseIds($user_id_input);

    // New Filters
    $etapa_ids = $parseIds($_GET['etapa_id'] ?? null);
    $origem_input = isset($_GET['origei']) ? $_GET['origem'] : null; // "origei" typo fix? No, frontend sends 'origem'
    $origem_ids = [];
    if (isset($_GET['origem']) && !empty($_GET['origem'])) {
        $origem_ids = explode(',', $_GET['origem']);
    }

    $uf_ids = [];
    if (isset($_GET['uf']) && !empty($_GET['uf'])) {
        $uf_ids = explode(',', $_GET['uf']);
    }

    $status_ids = [];
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status_ids = explode(',', $_GET['status']);
    }

    // Common Filter Applier Helper Call
    // Logic moved to global function apply_report_filters_helper to be shared with external functions.


    $type = $_GET['type'] ?? 'sales';
    try {
        if ($type === 'products') {
            $data = get_products_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);
        } elseif ($type === 'forecast') {
            // --- RELATÓRIO DE FORECAST (PREVISÃO) ---

            // 1. Agrupar por Mês (Baseado na Data de Abertura/Fechamento)
            // Somar Valor Ponderado (Valor * Probabilidade / 100)
            // Somar Valor Total Pipeline

            $sql = "
            SELECT 
                DATE_FORMAT(COALESCE(o.data_abertura, o.data_criacao), '%Y-%m') as mes,
                SUM(o.valor * (COALESCE(ef.probabilidade, 0) / 100)) as forecast_ponderado,
                SUM(o.valor) as pipeline_total
            FROM oportunidades o
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE COALESCE(o.data_abertura, o.data_criacao) BETWEEN ? AND ?
        ";

            $params = [$start_date, $end_date];

            // Apply Filters
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);

            $sql .= "
            GROUP BY mes
            ORDER BY mes ASC
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Fetch Goals... (omitted)

            echo json_encode(['success' => true, 'data' => $rows, 'type' => 'forecast']);

        } elseif ($type === 'lost_reasons') {
            // --- RELATÓRIO DE MOTIVOS DE PERDA ---

            $sql = "
            SELECT 
                COALESCE(ef.nome, 'Não Informado') as motivo,
                COUNT(o.id) as qtd,
                SUM(o.valor) as valor_total
            FROM oportunidades o
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE o.data_criacao BETWEEN ? AND ?
              AND (
                  ef.nome LIKE '%Perdida%' OR ef.nome LIKE '%Recusada%' OR ef.nome LIKE '%Lost%'
              )
        ";

            $params = [$start_date, $end_date];

            // Apply Filters
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);

            $sql .= "
            GROUP BY motivo
            ORDER BY qtd DESC
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $rows, 'type' => 'lost_reasons']);

        } elseif ($type === 'funnel') {
            // --- RELATÓRIO DE FUNIL DE VENDAS ---

            $sql = "
            SELECT 
                ef.nome as etapa_nome,
                ef.ordem as etapa_ordem,
                COUNT(o.id) as qtd_oportunidades,
                SUM(o.valor) as valor_total
            FROM oportunidades o
            JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE o.data_criacao BETWEEN ? AND ?
        ";

            $params = [$start_date, $end_date];

            // Apply Filters
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);

            $sql .= "
            GROUP BY ef.id, ef.nome, ef.ordem
            ORDER BY ef.ordem ASC
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $rows, 'type' => 'funnel']);

        } elseif ($type === 'licitacoes') {
            $data = get_licitacoes_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);
        } else {
            $data = get_sales_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);
        }

        echo json_encode(['success' => true, 'report_data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function get_sales_report($pdo, $start_date, $end_date, $supplier_ids = [], $user_ids = [], $etapa_ids = [], $origem_ids = [], $uf_ids = [], $status_ids = [])
{
    // Helper to build IN clause
    $buildIn = function ($ids) {
        if (empty($ids))
            return [null, []];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [$placeholders, $ids];
    };

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

    // Apply common filters
    // Note: For sales report, supplier_id and user_id are directly on vendas_fornecedores (vf).
    // Other filters like etapa, origem, uf, status are typically for 'oportunidades'.
    // If these filters are needed for sales, a join to 'oportunidades' or 'organizacoes' might be required.
    // For now, only supplier and user filters are applied directly to vf.
    if (!empty($supplier_ids)) {
        list($ph, $vals) = $buildIn($supplier_ids);
        $sql .= " AND vf.fornecedor_id IN ($ph)";
        $params = array_merge($params, $vals);
    }

    if (!empty($user_ids)) {
        list($ph, $vals) = $buildIn($user_ids);
        $sql .= " AND vf.usuario_id IN ($ph)";
        $params = array_merge($params, $vals);
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

    $params_metas = [$start_date, $end_date];

    if (!empty($supplier_ids)) {
        list($ph, $vals) = $buildIn($supplier_ids);
        $sql_metas .= " AND vo.fornecedor_id IN ($ph)";
        $params_metas = array_merge($params_metas, $vals);
    }

    if (!empty($user_ids)) {
        list($ph, $vals) = $buildIn($user_ids);
        $sql_metas .= " AND vo.usuario_id IN ($ph)";
        $params_metas = array_merge($params_metas, $vals);
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

    // 4. Fetch Supplier Goals & User Targets Flag for ALL suppliers present in data
    $year = date('Y', strtotime($start_date));
    $supplier_keys = array_keys($report_data);

    if (!empty($supplier_keys)) {
        list($ph, $vals) = $buildIn($supplier_keys);

        // Fetch all metas for relevant suppliers
        $sql_sup = "SELECT fornecedor_id, meta_anual, meta_mensal, meta_mensal_json, user_targets_enabled FROM fornecedor_metas WHERE fornecedor_id IN ($ph) AND ano = ?";
        $params_sup = array_merge($vals, [$year]);

        $stmt_sup = $pdo->prepare($sql_sup);
        $stmt_sup->execute($params_sup);
        $sup_metas = $stmt_sup->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Group by fornecedor_id
        // $sup_metas format: [ 123 => [ [ 'meta_anual' => ..., 'user_targets_enabled' => ... ] ] ]

        foreach ($supplier_keys as $sid) {
            if (isset($sup_metas[$sid][0])) {
                $m = $sup_metas[$sid][0];
                $report_data[$sid]['meta_anual'] = (float) $m['meta_anual'];
                $report_data[$sid]['meta_mensal'] = (float) $m['meta_mensal'];
                $report_data[$sid]['meta_mensal_detailed'] = !empty($m['meta_mensal_json']) ? json_decode($m['meta_mensal_json'], true) : [];
                $report_data[$sid]['user_targets_enabled'] = (int) ($m['user_targets_enabled'] ?? 1);
            } else {
                $report_data[$sid]['meta_anual'] = 0;
                $report_data[$sid]['meta_mensal'] = 0;
                $report_data[$sid]['meta_mensal_detailed'] = [];
                $report_data[$sid]['user_targets_enabled'] = 1; // Default
            }
        }

        // 5. Fetch Sales by State for relevant suppliers
        $sql_states = "
            SELECT 
                vf.fornecedor_id,
                COALESCE(o.estado, c.estado, 'ND') as estado,
                SUM(vf.valor_total) as total_vendido
            FROM vendas_fornecedores vf
            LEFT JOIN organizacoes o ON vf.organizacao_id = o.id
            LEFT JOIN clientes_pf c ON vf.cliente_pf_id = c.id
            WHERE vf.data_venda BETWEEN ? AND ?
            AND vf.fornecedor_id IN ($ph)
        ";
        $params_st = array_merge([$start_date, $end_date], $vals);

        // Apply UF filter if present, joining with organizacoes or clientes_pf
        if (!empty($uf_ids)) {
            $in_params_uf = trim(str_repeat('?,', count($uf_ids)), ',');
            $sql_states .= " AND (o.estado IN ($in_params_uf) OR c.estado IN ($in_params_uf))";
            $params_st = array_merge($params_st, $uf_ids, $uf_ids); // Add UF params twice for OR condition
        }

        $sql_states .= " GROUP BY vf.fornecedor_id, estado";

        $stmt_st = $pdo->prepare($sql_states);
        $stmt_st->execute($params_st);
        $state_sales = $stmt_st->fetchAll(PDO::FETCH_ASSOC);

        // Map state sales to report_data
        foreach ($state_sales as $ss) {
            $sid = $ss['fornecedor_id'];
            if (!isset($report_data[$sid]['state_sales']))
                $report_data[$sid]['state_sales'] = [];

            $uf = strtoupper(trim($ss['estado']));
            if (strlen($uf) === 2) {
                $report_data[$sid]['state_sales'][$uf] = (float) $ss['total_vendido'];
            }
        }

        // 6. Fetch State Goals
        $sql_st_goals = "SELECT fornecedor_id, estado, meta_anual FROM fornecedor_metas_estados WHERE fornecedor_id IN ($ph) AND ano = ?";
        $params_st_goals = array_merge($vals, [$year]);

        $stmt_st_goals = $pdo->prepare($sql_st_goals);
        $stmt_st_goals->execute($params_st_goals);
        $state_goals = $stmt_st_goals->fetchAll(PDO::FETCH_ASSOC);

        foreach ($state_goals as $sg) {
            $sid = $sg['fornecedor_id'];
            if (!isset($report_data[$sid]['state_goals']))
                $report_data[$sid]['state_goals'] = [];
            $report_data[$sid]['state_goals'][$sg['estado']] = (float) $sg['meta_anual'];
        }
    }

    return $report_data;
}

function get_products_report($pdo, $start_date, $end_date, $supplier_ids = [], $user_ids = [], $etapa_ids = [], $origem_ids = [], $uf_ids = [], $status_ids = [])
{
    // Helper to build IN clause
    $buildIn = function ($ids) {
        if (empty($ids))
            return [null, []];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [$placeholders, $ids];
    };

    // Corrected Query: Link via Items -> Products -> Suppliers
    $sql = "
        SELECT 
            o.fornecedor_id,
            f.nome as fornecedor_nome,
            oi.produto_id,
            p.nome_produto as produto_nome,
            SUM(oi.quantidade) as quantidade,
            AVG(oi.valor_unitario) as valor_unitario,
            MAX(oi.valor_unitario) as valor_max,
            SUM(oi.quantidade * oi.valor_unitario) as valor_total
        FROM oportunidades o
        JOIN oportunidade_itens oi ON o.id = oi.oportunidade_id
        JOIN produtos p ON oi.produto_id = p.id
        JOIN fornecedores f ON o.fornecedor_id = f.id
        WHERE o.data_criacao BETWEEN ? AND ?
    ";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    // Apply Supplier Filter manually on 'o' (fixed table alias)
    if (!empty($supplier_ids)) {
        $in_params = trim(str_repeat('?,', count($supplier_ids)), ',');
        $sql .= " AND o.fornecedor_id IN ($in_params)";
        foreach ($supplier_ids as $id)
            $params[] = $id;
    }

    // Apply other filters on 'o'
    apply_report_filters_helper($sql, $params, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);

    $sql .= " GROUP BY o.fornecedor_id, oi.produto_id, p.nome_produto ORDER BY f.nome, valor_total DESC";

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

function get_licitacoes_report($pdo, $start_date, $end_date, $supplier_ids = [], $user_ids = [], $etapa_ids = [], $origem_ids = [], $uf_ids = [], $status_ids = [])
{
    // Helper to build IN clause
    $buildIn = function ($ids) {
        if (empty($ids))
            return [null, []];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [$placeholders, $ids];
    };

    // Corrected Query: Link via Items -> Products -> Suppliers
    $sql = "
        SELECT 
            o.id,
            o.fornecedor_id,
            f.nome as fornecedor_nome,
            o.numero_edital,
            o.uasg,
            o.objeto,
            SUM(oi.quantidade * oi.valor_unitario) as valor_total, 
            o.data_criacao as created_at,
            o.etapa_id,
            ef.nome as fase_nome
        FROM oportunidades o
        JOIN oportunidade_itens oi ON o.id = oi.oportunidade_id
        JOIN produtos p ON oi.produto_id = p.id
        JOIN fornecedores f ON o.fornecedor_id = f.id
        LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
        WHERE (o.numero_edital IS NOT NULL AND o.numero_edital != '')
        AND o.data_criacao BETWEEN ? AND ?
    ";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    // Apply Supplier Filter on 'p'
    if (!empty($supplier_ids)) {
        $in_params = trim(str_repeat('?,', count($supplier_ids)), ',');
        $sql .= " AND o.fornecedor_id IN ($in_params)";
        foreach ($supplier_ids as $id)
            $params[] = $id;
    }

    // Apply other filters on 'o'
    apply_report_filters_helper($sql, $params, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids);

    $sql .= " GROUP BY o.id, o.fornecedor_id ORDER BY f.nome, o.data_criacao DESC";

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
        $row['fase_id'] = $row['fase_nome'] ?? 'Ativo';
        $report_data[$fid]['rows'][] = $row;
    }
    return $report_data;
}

function handle_get_supplier_targets($pdo)
{
    $supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
    $year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');

    if (!$supplier_id) {
        json_response(['success' => false, 'error' => 'Fornecedor não informado.'], 400);
        return;
    }

    try {
        // 1. Fetch Supplier Goals
        $stmt_sup = $pdo->prepare("SELECT meta_anual, meta_mensal, meta_mensal_json, user_targets_enabled FROM fornecedor_metas WHERE fornecedor_id = ? AND ano = ?");
        $stmt_sup->execute([$supplier_id, $year]);
        $sup_meta = $stmt_sup->fetch(PDO::FETCH_ASSOC);

        $result = [
            'meta_anual' => $sup_meta ? (float) $sup_meta['meta_anual'] : 0,
            'meta_mensal' => $sup_meta ? (float) $sup_meta['meta_mensal'] : 0,
            'meta_mensal_detailed' => ($sup_meta && !empty($sup_meta['meta_mensal_json'])) ? json_decode($sup_meta['meta_mensal_json'], true) : [],
            'user_targets_enabled' => $sup_meta ? (int) ($sup_meta['user_targets_enabled'] ?? 1) : 1,
            'state_targets' => [],
            'targets' => []
        ];

        // 2. Fetch State Targets
        // Check if table exists first? Or just try/catch
        try {
            $stmt_states = $pdo->prepare("SELECT estado, meta_anual, meta_mensal_json FROM fornecedor_metas_estados WHERE fornecedor_id = ? AND ano = ?");
            $stmt_states->execute([$supplier_id, $year]);
            $state_rows = $stmt_states->fetchAll(PDO::FETCH_ASSOC);
            foreach ($state_rows as $sr) {
                $result['state_targets'][$sr['estado']] = [
                    'meta_anual' => (float) $sr['meta_anual'],
                    'meta_mensal' => json_decode($sr['meta_mensal_json'] ?? '[]', true)
                ];
            }
        } catch (Exception $ex) {
            // Table might not exist, ignore
        }

        // 3. Fetch User Targets for that year
        $stmt_users = $pdo->prepare("
            SELECT usuario_id, mes, valor_meta 
            FROM vendas_objetivos 
            WHERE fornecedor_id = ? AND ano = ?
        ");
        $stmt_users->execute([$supplier_id, $year]);
        $rows = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

        // Format for easy frontend consumption: map[userId][month] = val
        foreach ($rows as $row) {
            $uid = $row['usuario_id'];
            $m = $row['mes'];
            if (!isset($result['targets'][$uid])) {
                $result['targets'][$uid] = [];
            }
            $result['targets'][$uid][$m] = (float) $row['valor_meta'];
        }

        json_response(['success' => true, 'data' => $result]);

    } catch (Exception $e) {
        // Log error for debugging
        $logFile = __DIR__ . '/../../api_debug_log.txt';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Error fetching targets: " . $e->getMessage() . PHP_EOL, FILE_APPEND);

        // ALWAYS return empty structure on error to prevent UI blockage
        json_response(['success' => true, 'data' => ['meta_anual' => 0, 'meta_mensal' => 0, 'targets' => [], 'state_targets' => [], 'user_targets_enabled' => 1]]);
    }
}


function handle_save_targets($pdo)
{
    // Espera JSON: { year: 2024, supplier_id: 1, supplier_goals: { annual: X, monthly: Y }, state_targets: { 'PE': {...}, ... }, targets: [ ... ], user_targets_enabled: true/false }
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['year']) || !isset($data['targets'])) {
        json_response(['success' => false, 'error' => 'Dados inválidos.'], 400);
        return;
    }

    $year = (int) $data['year'];
    $targets = $data['targets'];
    $supplier_id = isset($data['supplier_id']) ? (int) $data['supplier_id'] : (isset($targets[0]['fornecedor_id']) ? $targets[0]['fornecedor_id'] : 0);
    $supGoals = $data['supplier_goals'] ?? ['annual' => 0, 'monthly' => 0];
    $stateTargets = $data['state_targets'] ?? [];
    $userTargetsEnabled = isset($data['user_targets_enabled']) ? (int) $data['user_targets_enabled'] : 1;

    if (!$supplier_id) {
        json_response(['success' => false, 'error' => 'Fornecedor ID não encontrado.'], 400);
        return;
    }

    // 1. Ensure Tables Exist (DDL causes implicit commit, so run before transaction)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS fornecedor_metas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fornecedor_id INT NOT NULL,
            ano INT NOT NULL,
            meta_anual DECIMAL(15,2) DEFAULT 0,
            meta_mensal DECIMAL(15,2) DEFAULT 0,
            user_targets_enabled TINYINT(1) DEFAULT 1,
            UNIQUE KEY uq_forn_ano (fornecedor_id, ano)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS fornecedor_metas_estados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fornecedor_id INT NOT NULL,
            ano INT NOT NULL,
            estado VARCHAR(2) NOT NULL,
            meta_anual DECIMAL(15,2) DEFAULT 0,
            meta_mensal_json TEXT, -- JSON {1: val, 2: val...}
            UNIQUE KEY uq_forn_ano_est (fornecedor_id, ano, estado)
        )");

        // Add column user_targets_enabled if missing
        try {
            $pdo->query("SELECT user_targets_enabled FROM fornecedor_metas LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE fornecedor_metas ADD COLUMN user_targets_enabled TINYINT(1) DEFAULT 1");
        }

        // Add column meta_mensal_json if missing (New for Seasonality)
        try {
            $pdo->query("SELECT meta_mensal_json FROM fornecedor_metas LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE fornecedor_metas ADD COLUMN meta_mensal_json TEXT DEFAULT NULL");
        }

    } catch (Exception $e) {
        // Continue, might failed if table exists or permission issue, but let proper queries fail if so.
    }

    try {
        $pdo->beginTransaction();

        // 2. Save Supplier Goals
        $monthlyDetailedJson = isset($supGoals['monthly_detailed']) ? json_encode($supGoals['monthly_detailed']) : null;

        $stmt = $pdo->prepare("
            INSERT INTO fornecedor_metas (fornecedor_id, ano, meta_anual, meta_mensal, meta_mensal_json, user_targets_enabled)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                meta_anual = VALUES(meta_anual),
                meta_mensal = VALUES(meta_mensal),
                meta_mensal_json = VALUES(meta_mensal_json),
                user_targets_enabled = VALUES(user_targets_enabled)
        ");
        $stmt->execute([$supplier_id, $year, $supGoals['annual'], $supGoals['monthly'], $monthlyDetailedJson, $userTargetsEnabled]);


        // 3. Save State Goals
        $stmtState = $pdo->prepare("
            INSERT INTO fornecedor_metas_estados (fornecedor_id, ano, estado, meta_anual, meta_mensal_json)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                meta_anual = VALUES(meta_anual),
                meta_mensal_json = VALUES(meta_mensal_json)
        ");
        foreach ($stateTargets as $state => $sData) {
            $stmtState->execute([$supplier_id, $year, $state, $sData['annual'], json_encode($sData['monthly'])]);
        }

        // 4. Save User Targets
        $stmtUser = $pdo->prepare("
            INSERT INTO vendas_objetivos (fornecedor_id, usuario_id, ano, mes, valor_meta, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                valor_meta = VALUES(valor_meta),
                updated_at = NOW()
        ");

        foreach ($targets as $t) {
            $stmtUser->execute([
                $t['fornecedor_id'],
                $t['usuario_id'],
                $year,
                $t['mes'],
                $t['valor']
            ]);
        }

        $pdo->commit();
        json_response(['success' => true]);

    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();

        // Log error
        file_put_contents(__DIR__ . '/../../api_debug_log.txt', date('[Y-m-d H:i:s] ') . "Error saving targets: " . $e->getMessage() . PHP_EOL, FILE_APPEND);

    }
}

function handle_get_report_kpis($pdo)
{
    try {
        // 1. Total Sales (Current Year)
        $stmt_sales = $pdo->query("SELECT SUM(valor_total) FROM vendas_fornecedores WHERE YEAR(data_venda) = YEAR(CURDATE())");
        $total_sales = $stmt_sales->fetchColumn() ?: 0;

        // 2. Lost Sales (Current Year) - Proposals with status 'Recusada'
        // Adjust status string if needed based on your DB (e.g., 'Recusada', 'Perdido')
        $stmt_lost = $pdo->query("SELECT SUM(valor_total) FROM propostas WHERE status LIKE 'Recusada%' AND YEAR(data_criacao) = YEAR(CURDATE())");
        $lost_sales = $stmt_lost->fetchColumn() ?: 0;

        // 3. Active Bids (Opportunities that are Bids/Licitacao and not Closed)
        // Assuming 'Fechado', 'Perdido', 'Fracassado' are the closing stages. Adjust names if needed.
        $stmt_bids = $pdo->query("
            SELECT COUNT(*) FROM oportunidades o 
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE o.numero_edital IS NOT NULL 
            AND o.numero_edital != '' 
            AND ef.nome NOT IN ('Fechado', 'Perdido', 'Fracassado')
        ");
        $active_bids = $stmt_bids->fetchColumn() ?: 0;

        json_response([
            'success' => true,
            'kpis' => [
                'total_sales_year' => (float) $total_sales,
                'lost_sales_year' => (float) $lost_sales,
                'active_bids' => (int) $active_bids
            ]
        ]);

    } catch (Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/../../api_debug_log.txt', date('[Y-m-d H:i:s] ') . "Error fetching KPIs: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        json_response(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

function apply_report_filters_helper(&$sql, &$params, $table_alias, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $supplier_col = 'fornecedor_id', $user_col = 'usuario_id')
{
    // Helper to build IN clause
    $buildIn = function ($ids) {
        if (empty($ids))
            return [null, []];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [$placeholders, $ids];
    };

    // Supplier
    if (!empty($supplier_ids)) {
        list($ph, $vals) = $buildIn($supplier_ids);
        $sql .= " AND $table_alias.$supplier_col IN ($ph)";
        $params = array_merge($params, $vals);
    }
    // User
    if (!empty($user_ids)) {
        list($ph, $vals) = $buildIn($user_ids);
        $sql .= " AND $table_alias.$user_col IN ($ph)";
        $params = array_merge($params, $vals);
    }
    // Etapa
    if (!empty($etapa_ids)) {
        list($ph, $vals) = $buildIn($etapa_ids);
        $sql .= " AND $table_alias.etapa_id IN ($ph)";
        $params = array_merge($params, $vals);
    }
    // Origem
    if (!empty($origem_ids)) {
        $in_params = trim(str_repeat('?,', count($origem_ids)), ',');
        $sql .= " AND $table_alias.origem IN ($in_params)";
        foreach ($origem_ids as $id)
            $params[] = $id;
    }
    // Status
    if (!empty($status_ids)) {
        $status_conditions = [];
        foreach ($status_ids as $st) {
            if ($st === 'Ganho' || $st === 'Won') {
                $status_conditions[] = "$table_alias.etapa_id IN (SELECT id FROM etapas_funil WHERE nome LIKE '%Ganho%' OR nome LIKE '%Fechado%')";
            } elseif ($st === 'Perdido' || $st === 'Lost') {
                $status_conditions[] = "$table_alias.etapa_id IN (SELECT id FROM etapas_funil WHERE nome LIKE '%Perdido%' OR nome LIKE '%Recusada%' OR nome LIKE '%Lost%')";
            } elseif ($st === 'Aberto' || $st === 'Open') {
                $status_conditions[] = "$table_alias.etapa_id NOT IN (SELECT id FROM etapas_funil WHERE nome LIKE '%Ganho%' OR nome LIKE '%Fechado%' OR nome LIKE '%Perdido%' OR nome LIKE '%Recusada%' OR nome LIKE '%Lost%')";
            }
        }

        if (!empty($status_conditions)) {
            $sql .= " AND (" . implode(' OR ', $status_conditions) . ")";
        }
    }

    // UF (State)
    if (!empty($uf_ids)) {
        $in_params = trim(str_repeat('?,', count($uf_ids)), ',');
        $sql .= " AND (
            $table_alias.organizacao_id IN (SELECT id FROM organizacoes WHERE estado IN ($in_params))
         )";
        foreach ($uf_ids as $id)
            $params[] = $id;
    }
}
?>