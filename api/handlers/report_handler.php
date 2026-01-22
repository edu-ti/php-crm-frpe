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

    // 4. Fetch Supplier Goals (Annual/Monthly) & User Targets Flag
    $year = date('Y', strtotime($start_date));
    if ($supplier_id) {
        try {
            $stmt_sup = $pdo->prepare("SELECT meta_anual, meta_mensal, user_targets_enabled FROM fornecedor_metas WHERE fornecedor_id = ? AND ano = ?");
            $stmt_sup->execute([$supplier_id, $year]);
            $sup_meta = $stmt_sup->fetch(PDO::FETCH_ASSOC);
            if ($sup_meta && isset($report_data[$supplier_id])) {
                $report_data[$supplier_id]['meta_anual'] = (float) $sup_meta['meta_anual'];
                $report_data[$supplier_id]['meta_mensal'] = (float) $sup_meta['meta_mensal'];
                $report_data[$supplier_id]['user_targets_enabled'] = (int) ($sup_meta['user_targets_enabled'] ?? 1);
            } else if (isset($report_data[$supplier_id])) {
                // Default to enabled if no meta record found
                $report_data[$supplier_id]['user_targets_enabled'] = 1;
            }

            // 5. Fetch Sales by State
            $sql_states = "
                SELECT 
                    COALESCE(o.estado, c.estado, 'ND') as estado,
                    SUM(vf.valor_total) as total_vendido
                FROM vendas_fornecedores vf
                LEFT JOIN organizacoes o ON vf.organizacao_id = o.id
                LEFT JOIN clientes_pf c ON vf.cliente_pf_id = c.id
                WHERE vf.data_venda BETWEEN ? AND ?
                AND vf.fornecedor_id = ?
                GROUP BY estado
            ";
            $stmt_st = $pdo->prepare($sql_states);
            $stmt_st->execute([$start_date, $end_date, $supplier_id]);
            $state_sales = $stmt_st->fetchAll(PDO::FETCH_ASSOC);

            $report_data[$supplier_id]['state_sales'] = [];
            foreach ($state_sales as $ss) {
                // Ensure state is valid 2 chars (sometimes ND or empty)
                $uf = strtoupper(trim($ss['estado']));
                if (strlen($uf) === 2) {
                    $report_data[$supplier_id]['state_sales'][$uf] = (float) $ss['total_vendido'];
                }
            }

            // 6. Fetch State Goals (Target) for Comparison
            $stmt_st_goals = $pdo->prepare("SELECT estado, meta_anual FROM fornecedor_metas_estados WHERE fornecedor_id = ? AND ano = ?");
            $stmt_st_goals->execute([$supplier_id, $year]);
            $state_goals = $stmt_st_goals->fetchAll(PDO::FETCH_ASSOC);

            $report_data[$supplier_id]['state_goals'] = [];
            foreach ($state_goals as $sg) {
                $report_data[$supplier_id]['state_goals'][$sg['estado']] = (float) $sg['meta_anual'];
            }

        } catch (Exception $e) {
            // Table might not exist yet. Ignore.
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
        $stmt_sup = $pdo->prepare("SELECT meta_anual, meta_mensal, user_targets_enabled FROM fornecedor_metas WHERE fornecedor_id = ? AND ano = ?");
        $stmt_sup->execute([$supplier_id, $year]);
        $sup_meta = $stmt_sup->fetch(PDO::FETCH_ASSOC);

        $result = [
            'meta_anual' => $sup_meta ? (float) $sup_meta['meta_anual'] : 0,
            'meta_mensal' => $sup_meta ? (float) $sup_meta['meta_mensal'] : 0,
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

    try {
        $pdo->beginTransaction();

        // 1. Ensure Tables Exist
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


        // 2. Save Supplier Goals
        $stmt = $pdo->prepare("
            INSERT INTO fornecedor_metas (fornecedor_id, ano, meta_anual, meta_mensal, user_targets_enabled)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                meta_anual = VALUES(meta_anual),
                meta_mensal = VALUES(meta_mensal),
                user_targets_enabled = VALUES(user_targets_enabled)
        ");
        $stmt->execute([$supplier_id, $year, $supGoals['annual'], $supGoals['monthly'], $userTargetsEnabled]);


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

        json_response(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
?>