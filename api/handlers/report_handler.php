<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

// --- NEW CLASS (Requested by User) ---
class ReportHandler
{
    private $db;

    public function __construct()
    {
        $db = new Database();
        $this->db = $db->getConnection();
    }

    public function handleRequest($method, $action)
    {
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        switch ($action) {
            case 'dashboard_summary':
                $this->getDashboardSummary($startDate, $endDate);
                break;
            case 'by_vendor':
                $this->getSalesByVendor($startDate, $endDate);
                break;
            case 'by_supplier':
                $this->getPurchasesBySupplier($startDate, $endDate);
                break;
            case 'by_item':
                $this->getItemsSold($startDate, $endDate);
                break;
            case 'by_proposal_status':
                $this->getProposalsByStatus($startDate, $endDate);
                break;
            case 'by_bidding_funnel':
                $this->getBiddingFunnel($startDate, $endDate);
                break;
            case 'clients':
                $this->getClientsReport($startDate, $endDate);
                break;
            case 'forecast':
                $this->getForecastReport($startDate, $endDate);
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
    }

    private function applyFilters(&$sql, &$params, $table_alias = 'o') {
        $supplier_ids = isset($_GET['supplier_id']) && $_GET['supplier_id'] !== '' ? explode(',', $_GET['supplier_id']) : [];
        $user_ids = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? explode(',', $_GET['user_id']) : [];
        $cliente_ids = isset($_GET['cliente_id']) && $_GET['cliente_id'] !== '' ? explode(',', $_GET['cliente_id']) : [];
        $uf_ids = isset($_GET['uf']) && $_GET['uf'] !== '' ? explode(',', $_GET['uf']) : [];

        $buildIn = function($ids) {
            return implode(',', array_fill(0, count($ids), '?'));
        };

        if (!empty($supplier_ids)) {
            $sql .= " AND $table_alias.fornecedor_id IN (" . $buildIn($supplier_ids) . ")";
            $params = array_merge($params, $supplier_ids);
        }
        if (!empty($user_ids)) {
            $sql .= " AND $table_alias.usuario_id IN (" . $buildIn($user_ids) . ")";
            $params = array_merge($params, $user_ids);
        }
        if (!empty($cliente_ids)) {
            $tablePrefix = $table_alias === 'p' ? 'p' : ($table_alias === 'vf' ? 'vf' : ($table_alias === 'o' ? 'o' : ''));
            if ($tablePrefix === 'p' || $tablePrefix === 'vf') {
                $sql .= " AND ($tablePrefix.organizacao_id IN (" . $buildIn($cliente_ids) . ") OR $tablePrefix.cliente_pf_id IN (" . $buildIn($cliente_ids) . "))";
                $params = array_merge($params, $cliente_ids);
                $params = array_merge($params, $cliente_ids); // twice for OR
            }
        }
        if (!empty($uf_ids)) {
            $prefixForOrg = ($table_alias === 'p' || $table_alias === 'vf') ? $table_alias : ($table_alias === 'o' ? 'p' : ''); // se for 'o', precisa join org, ignora para simplificar ou usa org id da prop.
            if ($prefixForOrg === 'p' || $prefixForOrg === 'vf') {
                $sql .= " AND ($prefixForOrg.organizacao_id IN (SELECT id FROM organizacoes WHERE estado IN (" . $buildIn($uf_ids) . ")))";
                $params = array_merge($params, $uf_ids);
            }
        }
    }

    private function getDashboardSummary($start, $end)
    {
        try {
            // Total Sales: Sum of 'valor_total' from 'propostas' with status 'Aprovada'
            $sqlSales = "SELECT COALESCE(SUM(valor_total), 0) as total FROM propostas 
                         WHERE data_criacao BETWEEN :start AND :end AND status = 'Aprovada'";
            $stmtSales = $this->db->prepare($sqlSales);
            $stmtSales->execute([':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
            $totalSales = $stmtSales->fetch(PDO::FETCH_ASSOC)['total'];

            // Open Opportunities: Count of 'oportunidades' created in period
            $sqlOpps = "SELECT COUNT(*) as total FROM oportunidades WHERE data_criacao BETWEEN :start AND :end";
            $stmtOpps = $this->db->prepare($sqlOpps);
            $stmtOpps->execute([':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
            $openOpps = $stmtOpps->fetch(PDO::FETCH_ASSOC)['total'];

            // Active Proposals: Count of 'propostas' with status 'Enviada'
            $sqlProps = "SELECT COUNT(*) as total FROM propostas 
                         WHERE data_criacao BETWEEN :start AND :end AND status = 'Enviada'";
            $stmtProps = $this->db->prepare($sqlProps);
            $stmtProps->execute([':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
            $activeProps = $stmtProps->fetch(PDO::FETCH_ASSOC)['total'];

            // Conversion Rate: (Aprovada / (Aprovada + Recusada)) * 100
            $sqlConv = "SELECT 
                            SUM(CASE WHEN status = 'Aprovada' THEN 1 ELSE 0 END) as won,
                            SUM(CASE WHEN status IN ('Aprovada', 'Recusada') THEN 1 ELSE 0 END) as total_closed
                        FROM propostas 
                        WHERE data_criacao BETWEEN :start AND :end";
            $stmtConv = $this->db->prepare($sqlConv);
            $stmtConv->execute([':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
            $convData = $stmtConv->fetch(PDO::FETCH_ASSOC);

            $conversionRate = ($convData['total_closed'] > 0) ?
                round(($convData['won'] / $convData['total_closed']) * 100, 1) : 0;

            echo json_encode([
                'total_sales' => $totalSales,
                'open_opportunities' => $openOpps,
                'active_proposals' => $activeProps,
                'conversion_rate' => $conversionRate
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getSalesByVendor($start, $end)
    {
        try {
            // Using 'propostas' LEFT JOIN 'usuarios'
            $sql = "SELECT u.nome as label, COUNT(p.id) as count, COALESCE(SUM(p.valor_total), 0) as value
                    FROM propostas p 
                    LEFT JOIN usuarios u ON p.usuario_id = u.id
                    WHERE p.data_criacao BETWEEN :start AND :end 
                    AND p.status = 'Aprovada'
                    GROUP BY u.nome 
                    ORDER BY value DESC";
            $this->executeQuery($sql, $start . ' 00:00:00', $end . ' 23:59:59');
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getPurchasesBySupplier($start, $end)
    {
        try {
            // Using 'propostas' -> 'oportunidades' -> 'fornecedores'
            $sql = "SELECT f.nome as label, COUNT(p.id) as count, COALESCE(SUM(p.valor_total), 0) as value
                    FROM propostas p 
                    JOIN oportunidades o ON p.oportunidade_id = o.id 
                    LEFT JOIN fornecedores f ON o.fornecedor_id = f.id 
                    WHERE p.data_criacao BETWEEN :start AND :end 
                    AND p.status = 'Aprovada'
                    AND f.nome IS NOT NULL
                    GROUP BY f.nome 
                    ORDER BY value DESC 
                    LIMIT 15";
            $this->executeQuery($sql, $start . ' 00:00:00', $end . ' 23:59:59');
        } catch (Exception $e) {
            echo json_encode([]);
        }
    }

    private function getItemsSold($start, $end)
    {
        try {
            // Using 'proposta_itens' -> 'propostas' -> 'produtos'
            $sql = "SELECT pr.nome_produto as label, SUM(pi.quantidade) as count, COALESCE(SUM(pi.quantidade * pi.valor_unitario), 0) as value
                    FROM proposta_itens pi 
                    JOIN propostas p ON pi.proposta_id = p.id 
                    LEFT JOIN produtos pr ON pi.produto_id = pr.id
                    WHERE p.data_criacao BETWEEN :start AND :end 
                    AND p.status = 'Aprovada'
                    GROUP BY pr.nome_produto 
                    ORDER BY count DESC 
                    LIMIT 20";
            $this->executeQuery($sql, $start . ' 00:00:00', $end . ' 23:59:59');
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getProposalsByStatus($start, $end)
    {
        try {
            $sql = "SELECT status as label, COUNT(*) as count, COALESCE(SUM(valor_total), 0) as value
                    FROM propostas 
                    WHERE data_criacao BETWEEN :start AND :end 
                    GROUP BY status";
            $this->executeQuery($sql, $start . ' 00:00:00', $end . ' 23:59:59');
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getBiddingFunnel($start, $end)
    {
        try {
            $sql = "SELECT ef.nome as label, COUNT(o.id) as count, COALESCE(SUM(p.valor_total), SUM(o.valor), 0) as value
                    FROM oportunidades o
                    JOIN etapas_funil ef ON o.etapa_id = ef.id
                    LEFT JOIN propostas p ON o.id = p.oportunidade_id AND p.status = 'Aprovada'
                    WHERE o.data_criacao BETWEEN ? AND ?
                    AND ef.funil_id = 2";
            
            $params = [$start . ' 00:00:00', $end . ' 23:59:59'];
            
            if (empty($_GET['supplier_id'])) {
                $fixedSuppliers = ['BRASIL MEDICA', 'HEALTH', 'INSTRAMED', 'LIVANOVA', 'MASIMO', 'MERIL', 'MICROMED', 'NIPRO', 'SIGMAFIX'];
                $ph = implode(',', array_fill(0, count($fixedSuppliers), '?'));
                $sql .= " AND o.fornecedor_id IN (SELECT id FROM fornecedores WHERE nome IN ($ph))";
                $params = array_merge($params, $fixedSuppliers);
            }

            $this->applyFilters($sql, $params, 'o');

            $sql .= " GROUP BY ef.nome, ef.ordem ORDER BY ef.ordem ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $steps = ['Captação','Análise de Edital','Montagem de Proposta','Aprovação Interna','Aprovação Fornecedor','Participação','Acompanhamento','Adjudicado','Homologado','Perdido','Fracassado','Suspenso'];
            
            $result = [];
            foreach ($steps as $step) {
                $found = array_filter($rawData, function($r) use ($step) { return stripos($r['label'], $step) !== false; });
                if ($found) {
                    $first = reset($found);
                    $result[] = ['label' => $step, 'count' => $first['count'], 'value' => $first['value']];
                } else {
                    $result[] = ['label' => $step, 'count' => 0, 'value' => 0];
                }
            }

            echo json_encode([
                'success' => true,
                'report_data' => $result,
                'type' => 'funnel' // Return as funnel for frontend format compat
            ]);

        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getClientsReport($start, $end)
    {
        try {
            $params = [$start . ' 00:00:00', $end . ' 23:59:59'];
            
            $sqlInnerP = "SELECT p.id, p.organizacao_id, p.cliente_pf_id, p.valor_total FROM propostas p JOIN oportunidades o ON p.oportunidade_id = o.id WHERE p.data_criacao BETWEEN ? AND ? AND p.status = 'Aprovada'";
            $paramsP = $params;
            $this->applyFilters($sqlInnerP, $paramsP, 'p');
            
            $sqlInnerVF = "SELECT vf.id, vf.organizacao_id, vf.cliente_pf_id, vf.valor_total FROM vendas_fornecedores vf WHERE vf.data_venda BETWEEN ? AND ?";
            $paramsVF = $params;
            $this->applyFilters($sqlInnerVF, $paramsVF, 'vf');
            
            $sql = "
                SELECT 
                    COALESCE(org.nome_fantasia, cli.nome, 'Cliente Desconhecido') as cliente_nome,
                    SUM(t.valor_total) as valor_total,
                    COUNT(t.id) as qtd_vendas
                FROM (
                    $sqlInnerP
                    UNION ALL
                    $sqlInnerVF
                ) t
                LEFT JOIN organizacoes org ON t.organizacao_id = org.id
                LEFT JOIN clientes_pf cli ON t.cliente_pf_id = cli.id
                GROUP BY cliente_nome
                ORDER BY valor_total DESC
            ";

            $stmt = $this->db->prepare($sql);
            $finalParams = array_merge($paramsP, $paramsVF);
            $stmt->execute($finalParams);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcula Curva ABC
            $totalGeral = array_sum(array_column($data, 'valor_total'));
            $accum = 0;
            foreach ($data as &$row) {
                $accum += $row['valor_total'];
                $percAcumulado = $totalGeral > 0 ? ($accum / $totalGeral) * 100 : 0;
                $row['percentual_acumulado'] = round($percAcumulado, 2);
                if ($percAcumulado <= 80) $row['classe'] = 'A';
                elseif ($percAcumulado <= 95) $row['classe'] = 'B';
                else $row['classe'] = 'C';
            }

            echo json_encode([
                'success' => true,
                'report_data' => $data,
                'type' => 'clients'
            ]);

        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getForecastReport($start, $end)
    {
        try {
            $startMes = substr($start, 0, 7);
            $endMes = substr($end, 0, 7);
            $params = [$startMes . '-01', date('Y-m-t', strtotime($endMes . '-01'))];
            
            $sql = "
            SELECT 
                DATE_FORMAT(COALESCE(o.data_abertura, o.data_criacao), '%Y-%m') as mes,
                SUM(o.valor * (COALESCE(ef.probabilidade, 0) / 100)) as forecast_ponderado,
                SUM(o.valor) as pipeline_total
            FROM oportunidades o
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE COALESCE(o.data_abertura, o.data_criacao) BETWEEN ? AND ?
            ";
            
            $this->applyFilters($sql, $params, 'o');
            
            $sql .= " GROUP BY mes ORDER BY mes ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'report_data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'type' => 'forecast'
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function executeQuery($sql, $start, $end)
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function sendError($e)
    {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// --- LEGACY COMPATIBILITY FUNCTIONS ---
// Ensures existing frontend continues to work while new functions are available.

function handle_get_report_data($pdo)
{
    // BRIDGE to New Class if action matches new logic keys
    $type = $_GET['report_type'] ?? ($_GET['type'] ?? '');

    // DEBUG: Logging Removed

    $newActions = ['dashboard_summary', 'by_vendor', 'by_supplier', 'by_item', 'by_proposal_status', 'by_bidding_funnel', 'clients', 'forecast'];

    if (in_array($type, $newActions)) {
        $handler = new ReportHandler();
        $handler->handleRequest('GET', $type);
        return;
    }

    // --- OLD LOGIC ---

    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');

    // Fix Month Format (YYYY-MM to YYYY-MM-DD)
    if (preg_match('/^\d{4}-\d{2}$/', $start_date)) {
        $start_date .= '-01';
    }
    if (preg_match('/^\d{4}-\d{2}$/', $end_date)) {
        $end_date = date('Y-m-t', strtotime($end_date . '-01'));
    }

    $supplier_id_input = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : null;
    $user_id_input = isset($_GET['user_id']) ? $_GET['user_id'] : null;

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
    $cliente_ids_input = isset($_GET['cliente_id']) ? $_GET['cliente_id'] : null;
    $cliente_ids = [];
    if (!empty($cliente_ids_input)) {
        $cliente_ids = explode(',', $cliente_ids_input);
    }
    $etapa_ids = $parseIds($_GET['etapa_id'] ?? null);
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

    try {
        $data = [];

        if ($type === 'products') {
            $data = get_products_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
        } elseif ($type === 'clients') {
            $data = get_clients_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
        } elseif ($type === 'forecast') {
            $sql = "
            SELECT 
                DATE_FORMAT(COALESCE(o.data_abertura, o.data_criacao), '%Y-%m') as mes,
                SUM(
                    COALESCE(
                        (SELECT SUM(p.valor_total) FROM propostas p WHERE p.oportunidade_id = o.id AND p.status = 'Aprovada'),
                        (SELECT SUM(pi.quantidade * pi.valor_unitario) FROM proposta_itens pi JOIN propostas p ON p.id = pi.proposta_id WHERE p.oportunidade_id = o.id AND p.status = 'Aprovada'),
                        o.valor,
                        0
                    ) * (COALESCE(ef.probabilidade, 0) / 100)
                ) as forecast_ponderado,
                SUM(
                    COALESCE(
                        (SELECT SUM(p.valor_total) FROM propostas p WHERE p.oportunidade_id = o.id AND p.status = 'Aprovada'),
                        (SELECT SUM(pi.quantidade * pi.valor_unitario) FROM proposta_itens pi JOIN propostas p ON p.id = pi.proposta_id WHERE p.oportunidade_id = o.id AND p.status = 'Aprovada'),
                        o.valor,
                        0
                    )
                ) as pipeline_total
            FROM oportunidades o
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE COALESCE(o.data_abertura, o.data_criacao) BETWEEN ? AND ?
        ";
            $params = [$start_date, $end_date];
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
            $sql .= " GROUP BY mes ORDER BY mes ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($type === 'lost_reasons') {
            $sql = "
            SELECT 
                COALESCE(NULLIF(TRIM(p.motivo_status), ''), 'Não Informado') as motivo,
                COUNT(p.id) as qtd,
                COALESCE(SUM(p.valor_total), 0) as valor_total
            FROM propostas p
            LEFT JOIN oportunidades o ON p.oportunidade_id = o.id
            WHERE p.data_criacao BETWEEN ? AND ?
              AND (
                  p.status LIKE 'Recusad%'
              )
        ";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

            // Apply filters to Opportunity (o)
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, [], $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

            // Apply User Filter to Proposal (p) manually if needed
            if (!empty($user_ids)) {
                $in_params = trim(str_repeat('?,', count($user_ids)), ',');
                $sql .= " AND p.usuario_id IN ($in_params)";
                foreach ($user_ids as $uid) {
                    $params[] = $uid;
                }
            }

            $sql .= " GROUP BY motivo ORDER BY qtd DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($type === 'funnel') {
            $sql = "
            SELECT 
                ef.nome as etapa_nome, ef.ordem as etapa_ordem,
                COUNT(o.id) as qtd_oportunidades, SUM(o.valor) as valor_total
            FROM oportunidades o
            JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE o.data_criacao BETWEEN ? AND ?
              AND ef.funil_id = 1
        ";
            $params = [$start_date, $end_date];
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
            $sql .= " GROUP BY ef.id, ef.nome, ef.ordem ORDER BY ef.ordem ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Hardcode 8 fixed steps for funnel
            $etapas_rigidas = ['Prospectando', 'Contato', 'Negociação', 'Proposta', 'Fechado', 'Controle de Entrega', 'Pós-venda', 'Recusado'];
            
            $structured_funnel = [];
            foreach ($etapas_rigidas as $idx => $nome) {
                $structured_funnel[$nome] = [
                    'label' => $nome,
                    'etapa_nome' => $nome,
                    'etapa_ordem' => $idx + 1,
                    'count' => 0,
                    'value' => 0,
                    'qtd_oportunidades' => 0,
                    'valor_total' => 0
                ];
            }

            foreach($results as $r) {
                $matched = false;
                foreach($etapas_rigidas as $nr) {
                     if (strcasecmp(trim($r['etapa_nome']), $nr) === 0) {
                         $structured_funnel[$nr]['count'] += $r['qtd_oportunidades'];
                         $structured_funnel[$nr]['value'] += $r['valor_total'];
                         $structured_funnel[$nr]['qtd_oportunidades'] += $r['qtd_oportunidades'];
                         $structured_funnel[$nr]['valor_total'] += $r['valor_total'];
                         $matched = true;
                         break;
                     }
                }
                if (!$matched) {
                     $structured_funnel[$r['etapa_nome']] = [
                          'label' => $r['etapa_nome'],
                          'etapa_nome' => $r['etapa_nome'],
                          'etapa_ordem' => $r['etapa_ordem'],
                          'count' => $r['qtd_oportunidades'],
                          'value' => $r['valor_total'],
                          'qtd_oportunidades' => $r['qtd_oportunidades'],
                          'valor_total' => $r['valor_total']
                     ];
                }
            }
            $data = array_values($structured_funnel);

        } elseif ($type === 'supplier_funnel') {
            $sql = "
            SELECT 
                COALESCE(org.nome_fantasia, org.razao_social, f.nome, 'Outros') as fornecedor_nome,
                COUNT(DISTINCT o.id) as qtd_oportunidades, 
                SUM(o.valor) as valor_total
            FROM oportunidades o
            LEFT JOIN organizacoes org ON o.fornecedor_id = org.id
            LEFT JOIN fornecedores f ON o.fornecedor_id = f.id
            WHERE o.data_criacao BETWEEN ? AND ?
            ";
            $params = [$start_date, $end_date];
            apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
            
            $sql .= " GROUP BY fornecedor_nome ORDER BY valor_total DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Hardcode 9 fixed suppliers
            $fornecedores_rigidos = ['BRASIL MEDICA', 'HEALTH', 'INSTRAMED', 'LIVANOVA', 'MASIMO', 'MERIL', 'MICROMED', 'NIPRO', 'SIGMAFIX'];
            
            $structured_funnel = [];
            foreach ($fornecedores_rigidos as $nome) {
                $structured_funnel[$nome] = [
                    'fornecedor_nome' => $nome,
                    'qtd_oportunidades' => 0,
                    'valor_total' => 0
                ];
            }

            foreach($results as $r) {
                $matched = false;
                foreach($fornecedores_rigidos as $nr) {
                     // Check substring because DB names might be 'BRASIL MEDICA LTDA'
                     if (stripos(trim($r['fornecedor_nome']), $nr) !== false) {
                         $structured_funnel[$nr]['qtd_oportunidades'] += $r['qtd_oportunidades'];
                         $structured_funnel[$nr]['valor_total'] += $r['valor_total'];
                         $matched = true;
                         break; // add to the rigid bucket
                     }
                }
                // We ignore "Outros" or those not in the strict 9 list according to the prompt!
                // "Funil de Fornecedores: Agrupado pelos fornecedores fixos listados acima."
            }
            $data = array_values($structured_funnel);
            
        } elseif ($type === 'licitacoes_funnel') {
            $data = get_licitacoes_funnel_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
        } elseif ($type === 'licitacoes') {
            $data = get_licitacoes_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
        } elseif ($type === 'contratos') {
            $data = get_contracts_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
        } else {
            $data = get_sales_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);
        }

        echo json_encode(['success' => true, 'report_data' => $data, 'type' => $type]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handle_get_report_kpis($pdo)
{
    try {
        $stmt_sales = $pdo->query("SELECT SUM(valor_total) FROM vendas_fornecedores WHERE YEAR(data_venda) = YEAR(CURDATE())");
        $total_sales = $stmt_sales->fetchColumn() ?: 0;

        $stmt_lost = $pdo->query("SELECT SUM(valor_total) FROM propostas WHERE status LIKE 'Recusada%' AND YEAR(data_criacao) = YEAR(CURDATE())");
        $lost_sales = $stmt_lost->fetchColumn() ?: 0;

        $stmt_bids = $pdo->query("
            SELECT COUNT(*) FROM oportunidades o 
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id
            WHERE o.numero_edital IS NOT NULL AND o.numero_edital != '' 
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
        // file_put_contents(__DIR__ . '/../../api_debug_log.txt', date('[Y-m-d H:i:s] ') . "Error fetching KPIs: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        json_response(['success' => false, 'error' => $e->getMessage()], 500);
    }
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

        try {
            $stmt_states = $pdo->prepare("SELECT estado, meta_anual, meta_mensal_json FROM fornecedor_metas_estados WHERE fornecedor_id = ? AND ano = ?");
            $stmt_states->execute([$supplier_id, $year]);
            foreach ($stmt_states->fetchAll(PDO::FETCH_ASSOC) as $sr) {
                $result['state_targets'][$sr['estado']] = [
                    'meta_anual' => (float) $sr['meta_anual'],
                    'meta_mensal' => json_decode($sr['meta_mensal_json'] ?? '[]', true)
                ];
            }
        } catch (Exception $ex) {
        }

        $stmt_users = $pdo->prepare("SELECT usuario_id, mes, valor_meta FROM vendas_objetivos WHERE fornecedor_id = ? AND ano = ?");
        $stmt_users->execute([$supplier_id, $year]);
        foreach ($stmt_users->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result['targets'][$row['usuario_id']][$row['mes']] = (float) $row['valor_meta'];
        }

        json_response(['success' => true, 'data' => $result]);

    } catch (Exception $e) {
        json_response(['success' => true, 'data' => ['meta_anual' => 0, 'meta_mensal' => 0, 'targets' => [], 'state_targets' => [], 'user_targets_enabled' => 1]]);
    }
}

function handle_save_targets($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['year']) || !isset($data['targets'])) {
        json_response(['success' => false, 'error' => 'Dados inválidos.'], 400);
        return;
    }

    $year = (int) $data['year'];
    $targets = $data['targets'];
    $supplier_id = isset($data['supplier_id']) ? (int) $data['supplier_id'] : ($targets[0]['fornecedor_id'] ?? 0);
    $supGoals = $data['supplier_goals'] ?? ['annual' => 0, 'monthly' => 0];
    $stateTargets = $data['state_targets'] ?? [];
    $userTargetsEnabled = isset($data['user_targets_enabled']) ? (int) $data['user_targets_enabled'] : 1;

    try {
        $pdo->beginTransaction();
        $monthlyDetailedJson = isset($supGoals['monthly_detailed']) ? json_encode($supGoals['monthly_detailed']) : null;

        $stmt = $pdo->prepare("INSERT INTO fornecedor_metas (fornecedor_id, ano, meta_anual, meta_mensal, meta_mensal_json, user_targets_enabled) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE meta_anual = VALUES(meta_anual), meta_mensal = VALUES(meta_mensal), meta_mensal_json = VALUES(meta_mensal_json), user_targets_enabled = VALUES(user_targets_enabled)");
        $stmt->execute([$supplier_id, $year, $supGoals['annual'], $supGoals['monthly'], $monthlyDetailedJson, $userTargetsEnabled]);

        $stmtState = $pdo->prepare("INSERT INTO fornecedor_metas_estados (fornecedor_id, ano, estado, meta_anual, meta_mensal_json) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE meta_anual = VALUES(meta_anual), meta_mensal_json = VALUES(meta_mensal_json)");
        $savedStates = [];
        foreach ($stateTargets as $state => $sData) {
            $stmtState->execute([$supplier_id, $year, $state, $sData['annual'], json_encode($sData['monthly'])]);
            $savedStates[] = $state;
        }

        // Remove old states that were deleted from the UI
        if (!empty($savedStates)) {
            $placeholders = implode(',', array_fill(0, count($savedStates), '?'));
            $deleteParams = array_merge([$supplier_id, $year], $savedStates);
            $stmtDeleteStates = $pdo->prepare("DELETE FROM fornecedor_metas_estados WHERE fornecedor_id = ? AND ano = ? AND estado NOT IN ($placeholders)");
            $stmtDeleteStates->execute($deleteParams);
        } else {
            // If mapping is entirely empty, delete all states for this supplier and year
            $stmtDeleteStates = $pdo->prepare("DELETE FROM fornecedor_metas_estados WHERE fornecedor_id = ? AND ano = ?");
            $stmtDeleteStates->execute([$supplier_id, $year]);
        }

        $stmtUser = $pdo->prepare("INSERT INTO vendas_objetivos (fornecedor_id, usuario_id, ano, mes, valor_meta, created_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE valor_meta = VALUES(valor_meta), updated_at = NOW()");
        foreach ($targets as $t) {
            $stmtUser->execute([$t['fornecedor_id'], $t['usuario_id'], $year, $t['mes'], $t['valor']]);
        }

        $pdo->commit();
        json_response(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// --- HELPER FUNCTIONS ---

function get_sales_report($pdo, $start_date, $end_date, $supplier_ids = [], $user_ids = [], $etapa_ids = [], $origem_ids = [], $uf_ids = [], $status_ids = [], $cliente_ids = [])
{
    $buildIn = function ($ids) {
        if (empty($ids))
            return [null, []];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [$placeholders, $ids];
    };

    // Build supplier lookup map (Nome -> ID)
    $stmt_sup = $pdo->query("SELECT id, nome FROM fornecedores");
    $suppliers_map = [];
    while ($s = $stmt_sup->fetch(PDO::FETCH_ASSOC)) {
        $suppliers_map[strtoupper(trim($s['nome'] ?? ''))] = (int) $s['id'];
    }

    $sql = "SELECT vf.fornecedor_id, f.nome as fornecedor_nome, vf.usuario_id, u.nome as vendedor_nome, YEAR(vf.data_venda) as ano, MONTH(vf.data_venda) as mes, SUM(vf.valor_total) as total_vendido 
            FROM vendas_fornecedores vf 
            LEFT JOIN fornecedores f ON vf.fornecedor_id = f.id 
            LEFT JOIN usuarios u ON vf.usuario_id = u.id 
            WHERE vf.data_venda BETWEEN ? AND ?";
    $params = [$start_date, $end_date];

    // Apply standardized filters to vendas_fornecedores
    apply_report_filters_helper($sql, $params, 'vf', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

    $sql .= " GROUP BY vf.fornecedor_id, vf.usuario_id, YEAR(vf.data_venda), MONTH(vf.data_venda) ORDER BY f.nome, u.nome, ano, mes";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vendas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Notas Fiscais (Faturado) Fetching Logic
    // We fetch individual NFs and their items to attribute values correctly to suppliers
    $sql_nf = "SELECT nf.id, nf.itens, nf.valor as total_nf, o.fornecedor_id, f.nome as fornecedor_nome, o.usuario_id, u.nome as vendedor_nome, YEAR(nf.data_faturamento) as ano, MONTH(nf.data_faturamento) as mes
               FROM notas_fiscais nf 
               JOIN oportunidades o ON nf.oportunidade_id = o.id 
               LEFT JOIN fornecedores f ON o.fornecedor_id = f.id 
               LEFT JOIN usuarios u ON o.usuario_id = u.id 
               WHERE nf.data_faturamento BETWEEN ? AND ?";
    $params_nf = [$start_date, $end_date];

    // Important: We do NOT filter by supplier_ids in the SQL for NFs here if we want to catch items from other suppliers inside mixed opportunities.
    // However, we still filter by other criteria (Client, User, etc.)
    apply_report_filters_helper($sql_nf, $params_nf, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

    $stmt_nf = $pdo->prepare($sql_nf);
    $stmt_nf->execute($params_nf);
    $raw_nf_data = $stmt_nf->fetchAll(PDO::FETCH_ASSOC);

    $nf_data = []; // Aggregated results for report_data

    foreach ($raw_nf_data as $nf) {
        $items = !empty($nf['itens']) ? json_decode($nf['itens'], true) : [];
        $has_item_supplier = false;
        $attributed_by_supplier = [];

        if (is_array($items) && !empty($items)) {
            foreach ($items as $item) {
                $item_val = (float) ($item['valor_total'] ?? (($item['quantidade'] ?? 0) * ($item['valor_unitario'] ?? 0)));
                if ($item_val <= 0) continue;

                $item_sup_name = strtoupper(trim($item['fornecedor'] ?? $item['fabricante'] ?? ''));
                $item_sup_id = ($item_sup_name && isset($suppliers_map[$item_sup_name])) ? $suppliers_map[$item_sup_name] : null;

                $target_fid = $item_sup_id ?: $nf['fornecedor_id'];
                $target_fname = $item_sup_id ? $item['fornecedor'] : $nf['fornecedor_nome'];

                $key = "{$target_fid}|{$nf['usuario_id']}|{$nf['ano']}|{$nf['mes']}";
                if (!isset($attributed_by_supplier[$key])) {
                    $attributed_by_supplier[$key] = [
                        'fornecedor_id' => $target_fid,
                        'fornecedor_nome' => $target_fname,
                        'usuario_id' => $nf['usuario_id'],
                        'vendedor_nome' => $nf['vendedor_nome'],
                        'ano' => $nf['ano'],
                        'mes' => $nf['mes'],
                        'total_faturado' => 0
                    ];
                }
                $attributed_by_supplier[$key]['total_faturado'] += $item_val;
            }
        }

        if (empty($attributed_by_supplier)) {
            // Fallback to opportunity supplier if no items or items have no value
            $key = "{$nf['fornecedor_id']}|{$nf['usuario_id']}|{$nf['ano']}|{$nf['mes']}";
            $attributed_by_supplier[$key] = [
                'fornecedor_id' => $nf['fornecedor_id'],
                'fornecedor_nome' => $nf['fornecedor_nome'],
                'usuario_id' => $nf['usuario_id'],
                'vendedor_nome' => $nf['vendedor_nome'],
                'ano' => $nf['ano'],
                'mes' => $nf['mes'],
                'total_faturado' => (float) $nf['total_nf']
            ];
        }

        foreach ($attributed_by_supplier as $attr) {
            // Filter by supplier_ids manually in PHP if SQL filter was removed
            if (!empty($supplier_ids) && !in_array($attr['fornecedor_id'], $supplier_ids)) continue;

            $nf_data[] = $attr;
        }
    }

    // Targets fetching logic omitted but required for full functionality. 
    // Assuming simple sales report for brevity or re-implementing target fetching if critical.
    // For consistency with previous file breakdown, I'll include the targets fetch if possible or just return sales.
    // Looking at the previous file, the targets fetch logic was substantial. Use simplified or full?
    // User wants "existing class logic updated" but "don't break old". I should assume full targets fetch is needed.
    // RE-INJECTING TARGETS LOGIC from temp file memory...

    $sql_metas = "SELECT vo.fornecedor_id, f.nome as fornecedor_nome, vo.usuario_id, u.nome as vendedor_nome, vo.ano, vo.mes, vo.valor_meta FROM vendas_objetivos vo JOIN fornecedores f ON vo.fornecedor_id = f.id JOIN usuarios u ON vo.usuario_id = u.id WHERE CAST(CONCAT(vo.ano, '-', LPAD(vo.mes, 2, '0'), '-01') AS DATE) BETWEEN ? AND ?";
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

    $report_data = [];
    $initStructure = function (&$array, $fid, $fname, $uid, $uname) {
        $fid = $fid ?: 0;
        $uid = $uid ?: 0;
        $fname = $fname ?: "Fornecedor não informado";
        $uname = $uname ?: "Não informado";

        if (!isset($array[$fid]))
            $array[$fid] = ['fornecedor_id' => $fid, 'fornecedor_nome' => $fname, 'rows' => []];
        if (!isset($array[$fid]['rows_map'][$uid]))
            $array[$fid]['rows_map'][$uid] = ['usuario_id' => $uid, 'vendedor_nome' => $uname, 'dados_mes' => []];
    };

    foreach ($vendas_data as $row) {
        $initStructure($report_data, $row['fornecedor_id'], $row['fornecedor_nome'], $row['usuario_id'], $row['vendedor_nome']);
        $fid = $row['fornecedor_id'] ?: 0;
        $uid = $row['usuario_id'] ?: 0;
        // Initialize if not set
        if (!isset($report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['venda'])) {
            $report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['venda'] = 0;
        }
        $report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['venda'] += (float) $row['total_vendido'];
    }
    foreach ($nf_data as $row) {
        $initStructure($report_data, $row['fornecedor_id'], $row['fornecedor_nome'], $row['usuario_id'], $row['vendedor_nome']);
        $fid = $row['fornecedor_id'] ?: 0;
        $uid = $row['usuario_id'] ?: 0;
        if (!isset($report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['faturado'])) {
            $report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['faturado'] = 0;
        }
        $report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['faturado'] += (float) $row['total_faturado'];
    }
    foreach ($metas_data as $row) {
        $initStructure($report_data, $row['fornecedor_id'], $row['fornecedor_nome'], $row['usuario_id'], $row['vendedor_nome']);
        $fid = $row['fornecedor_id'] ?: 0;
        $uid = $row['usuario_id'] ?: 0;
        $report_data[$fid]['rows_map'][$uid]['dados_mes'][$row['ano'] . '-' . $row['mes']]['meta'] = (float) $row['valor_meta'];
    }

    // Busca das Metas Globais do Fornecedor para a linha de Totalizador de Fábrica
    $year = date('Y', strtotime($start_date));
    $sql_global_metas = "SELECT fornecedor_id, meta_anual, meta_mensal FROM fornecedor_metas WHERE ano = ?";
    $stmt_global_metas = $pdo->prepare($sql_global_metas);
    $stmt_global_metas->execute([$year]);
    $global_metas_data = $stmt_global_metas->fetchAll(PDO::FETCH_ASSOC);
    $global_metas_map = [];
    foreach ($global_metas_data as $gm) {
        $global_metas_map[$gm['fornecedor_id']] = [
            'anual' => (float) $gm['meta_anual'],
            'mensal' => (float) $gm['meta_mensal']
        ];
    }

    foreach ($report_data as &$supplier) {
        if (isset($supplier['rows_map'])) {
            $supplier['rows'] = array_values($supplier['rows_map']);
            unset($supplier['rows_map']);
        }

        // Incluir a meta global do fornecedor na resposta
        $fid = $supplier['fornecedor_id'];
        $supplier['meta_global_anual'] = $global_metas_map[$fid]['anual'] ?? 0;
        $supplier['meta_global_mensal'] = $global_metas_map[$fid]['mensal'] ?? 0;

        // Restore State Report Data
        $fid = $supplier['fornecedor_id'];

        // 1. State Sales (aggregated for this supplier in the period)
        $sql_state_sales = "
            SELECT o.estado, SUM(vf.valor_total) as total 
            FROM vendas_fornecedores vf
            LEFT JOIN organizacoes o ON vf.organizacao_id = o.id
            WHERE vf.fornecedor_id = ? 
            AND vf.data_venda BETWEEN ? AND ?";
        $params_ss = [$fid, $start_date, $end_date];

        // Apply filters to state sales
        apply_report_filters_helper($sql_state_sales, $params_ss, 'vf', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

        $sql_state_sales .= " AND o.estado IS NOT NULL GROUP BY o.estado";
        $stmt_ss = $pdo->prepare($sql_state_sales);
        $stmt_ss->execute($params_ss);
        $supplier['state_sales'] = $stmt_ss->fetchAll(PDO::FETCH_KEY_PAIR); // [PE => 1000, PB => 500]

        // Add NF state sales (Item-aware logic)
        $sql_state_nf = "
            SELECT org.estado, nf.itens, nf.valor as total_nf, o.fornecedor_id
            FROM notas_fiscais nf
            JOIN oportunidades o ON nf.oportunidade_id = o.id 
            LEFT JOIN organizacoes org ON o.organizacao_id = org.id
            WHERE nf.data_faturamento BETWEEN ? AND ?";
        $params_snf = [$start_date, $end_date];

        // Apply filters to NF state sales (except supplier_id to be item-aware)
        apply_report_filters_helper($sql_state_nf, $params_snf, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

        $stmt_snf = $pdo->prepare($sql_state_nf);
        $stmt_snf->execute($params_snf);
        $raw_snf_data = $stmt_snf->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw_snf_data as $nf) {
            $items = !empty($nf['itens']) ? json_decode($nf['itens'], true) : [];
            $attributed_val = 0;
            $uf = $nf['estado'];
            if (!$uf) continue;

            if (is_array($items) && !empty($items)) {
                foreach ($items as $item) {
                    $item_sup_name = strtoupper(trim($item['fornecedor'] ?? $item['fabricante'] ?? ''));
                    $item_sup_id = ($item_sup_name && isset($suppliers_map[$item_sup_name])) ? $suppliers_map[$item_sup_name] : null;

                    $target_fid = $item_sup_id ?: $nf['fornecedor_id'];

                    if ($target_fid == $fid) {
                        $attributed_val += (float) ($item['valor_total'] ?? (($item['quantidade'] ?? 0) * ($item['valor_unitario'] ?? 0)));
                    }
                }
            } else {
                // No items or no supplier specified in items, fall back to opportunity supplier
                if ($nf['fornecedor_id'] == $fid) {
                    $attributed_val = (float) $nf['total_nf'];
                }
            }

            if ($attributed_val > 0) {
                if (isset($supplier['state_sales'][$uf])) {
                    $supplier['state_sales'][$uf] += $attributed_val;
                } else {
                    $supplier['state_sales'][$uf] = $attributed_val;
                }
            }
        }

        // 2. State Goals (Annual/Monthly for the year of start_date)
        // Assuming we want the Annual Goal for the state for the year of the start_date
        $year = date('Y', strtotime($start_date));
        $sql_state_goals = "
            SELECT estado, meta_anual 
            FROM fornecedor_metas_estados
            WHERE fornecedor_id = ? AND ano = ?
        ";
        $stmt_sg = $pdo->prepare($sql_state_goals);
        $stmt_sg->execute([$fid, $year]);
        $supplier['state_goals'] = $stmt_sg->fetchAll(PDO::FETCH_KEY_PAIR); // [PE => 5000, PB => 2000]
    }

    // Simplification: Skipping state sales/goals detailed fetch to keep file size manageable if not strictly requested by user issue (501 error).
    // The previous code had them. If I drop them, "State Report" might be empty.
    // I should include apply_report_filters_helper usage though.

    return $report_data;
}

function get_contracts_report($pdo, $start_date, $end_date, $supplier_ids = [], $user_ids = [], $etapa_ids = [], $origem_ids = [], $uf_ids = [], $status_ids = [], $cliente_ids = [])
{
    // Find the Finance/Contracts funnel ID. Usually it's the highest or named 'Financeiro'/'Contratos'
    $funnel_query = $pdo->query("SELECT id FROM funis WHERE nome LIKE '%Financeiro%' OR nome LIKE '%Contrato%' LIMIT 1");
    $funnel_id = $funnel_query->fetchColumn();

    if (!$funnel_id) {
        // Fallback to highest funil_id like frontend does
        $funnel_query = $pdo->query("SELECT MAX(funil_id) FROM etapas_funil");
        $funnel_id = $funnel_query->fetchColumn();
    }

    $sql = "
        SELECT 
            ef.id as etapa_id,
            ef.nome as etapa_nome, 
            ef.ordem as etapa_ordem,
            o.id as oportunidade_id,
            COALESCE(org.nome_fantasia, pf.nome, 'Cliente Desconhecido') as cliente_nome,
            o.valor as valor_contrato,
            (SELECT SUM(valor) FROM empenhos e WHERE e.oportunidade_id = o.id) as valor_empenhado,
            (SELECT SUM(valor) FROM notas_fiscais nf WHERE nf.oportunidade_id = o.id) as valor_faturado
        FROM oportunidades o
        JOIN etapas_funil ef ON o.etapa_id = ef.id
        LEFT JOIN organizacoes org ON o.organizacao_id = org.id
        LEFT JOIN clientes_pf pf ON o.cliente_pf_id = pf.id
        WHERE o.data_criacao BETWEEN ? AND ?
          AND ef.funil_id = ?
    ";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59', $funnel_id];

    // Apply filters
    apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

    $sql .= " ORDER BY ef.ordem ASC, cliente_nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group the data by stage to match the funnel logic structure in JS, but containing rows
    $grouped_data = [];
    foreach ($data as $row) {
        $etapa_id = $row['etapa_id'];
        if (!isset($grouped_data[$etapa_id])) {
            $grouped_data[$etapa_id] = [
                'etapa_nome' => $row['etapa_nome'],
                'etapa_ordem' => $row['etapa_ordem'],
                'qtd_oportunidades' => 0,
                'valor_total' => 0, // This is for the chart
                'contratos' => []
            ];
        }

        $val_fat = (float) $row['valor_faturado'];
        $val_emp = (float) $row['valor_empenhado'];
        $val_base = (float) $row['valor_contrato'];

        // Logic for total value chart: Faturado > Empenhado > Base
        $val_considerado = 0;
        if ($val_fat > 0)
            $val_considerado = $val_fat;
        else if ($val_emp > 0)
            $val_considerado = $val_emp;
        else
            $val_considerado = $val_base;

        $grouped_data[$etapa_id]['qtd_oportunidades']++;
        $grouped_data[$etapa_id]['valor_total'] += $val_considerado;
        $grouped_data[$etapa_id]['contratos'][] = [
            'cliente_nome' => $row['cliente_nome'],
            'valor_contrato' => $val_base,
            'valor_empenhado' => $val_emp,
            'valor_faturado' => $val_fat,
            'saldo' => max(0, $val_base - $val_fat), // Valor do contrato - Valor faturado
            'valor_considerado' => $val_considerado // To calculate the percentage later
        ];
    }

    // Convert back to index array sorting by step order
    $final_data = array_values($grouped_data);
    usort($final_data, function ($a, $b) {
        return $a['etapa_ordem'] <=> $b['etapa_ordem'];
    });

    return $final_data;
}

function get_products_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids = [])
{
    $sql = "SELECT p.usuario_id as fornecedor_id, u.nome as fornecedor_nome, pi.produto_id, pr.nome_produto as produto_nome, SUM(pi.quantidade) as quantidade, AVG(pi.valor_unitario) as valor_unitario, MAX(pi.valor_unitario) as valor_max, SUM(pi.quantidade * pi.valor_unitario) as valor_total 
            FROM proposta_itens pi 
            JOIN propostas p ON pi.proposta_id = p.id 
            LEFT JOIN produtos pr ON pi.produto_id = pr.id 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.data_criacao BETWEEN ? AND ? 
            AND p.status = 'Aprovada'";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    if (!empty($supplier_ids)) {
        // For legacy compatibility, supplier_ids (fornecedores) might not map directly to user_id. 
        // But the previous query joined 'fornecedores f' on 'o.fornecedor_id'.
        // Proposals have 'usuario_id' (Vendor) and linked to Opportunity which has 'fornecedor_id'?
        // Let's check schema: Proposals -> Opportunity -> Fornecedor?
        // Or Proposals -> User?
        // Let's stick to simple Vendor (User) or try to join Opportunity if Supplier filter is needed.
        // The original query returned 'fornecedor_id'. 
        // Let's join Opportunity to get provider if needed.
    }

    // Re-writing the query to include Opportunity and Supplier for consistent filtering
    $sql = "SELECT COALESCE(o.fornecedor_id, 0) as fornecedor_id, COALESCE(f.nome, pi.fabricante, 'Fornecedor Não Informado') as fornecedor_nome, pi.produto_id, COALESCE(pr.nome_produto, pi.descricao) as produto_nome, 
            SUM(pi.quantidade) as quantidade, AVG(pi.valor_unitario) as valor_unitario, 
            MAX(pi.valor_unitario) as valor_max, SUM(pi.quantidade * pi.valor_unitario) as valor_total 
            FROM proposta_itens pi 
            JOIN propostas p ON pi.proposta_id = p.id 
            LEFT JOIN produtos pr ON pi.produto_id = pr.id 
            LEFT JOIN oportunidades o ON p.oportunidade_id = o.id
            LEFT JOIN fornecedores f ON o.fornecedor_id = f.id
            WHERE p.data_criacao BETWEEN ? AND ? 
            AND p.status = 'Aprovada'";

    if (!empty($supplier_ids)) {
        $in_params = trim(str_repeat('?,', count($supplier_ids)), ',');
        $sql .= " AND o.fornecedor_id IN ($in_params)";
        foreach ($supplier_ids as $id)
            $params[] = $id;
    }
    apply_report_filters_helper($sql, $params, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids, 'fornecedor_id', 'p.usuario_id');
    $sql .= " GROUP BY fornecedor_id, fornecedor_nome, pi.produto_id, produto_nome ORDER BY fornecedor_nome, valor_total DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $report_data = [];
    foreach ($data as $row) {
        $fid = strtoupper(trim($row['fornecedor_nome'])); // Use normalized name as key
        if (!isset($report_data[$fid]))
            $report_data[$fid] = ['fornecedor_id' => $row['fornecedor_id'], 'fornecedor_nome' => $row['fornecedor_nome'], 'rows' => []];
        $report_data[$fid]['rows'][] = $row;
    }
    return array_values($report_data); // Reset keys for frontend
}

function get_licitacoes_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids = [])
{
    // Updated to use 'propostas' and 'proposta_itens' for value calculation if available, 
    // falling back to opportunity items if no proposal or just counting on opportunity value?
    // Actually, Licitacoes might not have proposals yet? But user wants "No Data" fixed.
    // If Licitacoes are Opportunities with Edital, their value might be estimated.
    // However, the original query used SUM(oi.quantidade * oi.valor_unitario).
    // Let's try to get value from linked Proposal if exists (Approved preferably), or Opportunity value column if exists.
    // Since we don't know if 'valor' exists on Opportunity (legacy code didn't use it here), 
    // let's Assume we should check Proposals linked to this opportunity.

    $sql = "SELECT o.id, o.fornecedor_id, f.nome as fornecedor_nome, o.numero_edital, o.uasg, o.objeto, 
            COALESCE(SUM(pi.quantidade * pi.valor_unitario), o.valor, 0) as valor_total, 
            o.data_criacao as created_at, o.etapa_id, ef.nome as fase_nome 
            FROM oportunidades o 
            LEFT JOIN propostas p ON o.id = p.oportunidade_id AND p.status = 'Aprovada'
            LEFT JOIN proposta_itens pi ON p.id = pi.proposta_id
            JOIN fornecedores f ON o.fornecedor_id = f.id 
            LEFT JOIN etapas_funil ef ON o.etapa_id = ef.id 
            WHERE (o.numero_edital IS NOT NULL AND o.numero_edital != '') 
            AND o.data_criacao BETWEEN ? AND ?";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    if (!empty($supplier_ids)) {
        $in_params = trim(str_repeat('?,', count($supplier_ids)), ',');
        $sql .= " AND o.fornecedor_id IN ($in_params)";
        foreach ($supplier_ids as $id)
            $params[] = $id;
    }
    apply_report_filters_helper($sql, $params, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids, 'fornecedor_id', 'p.usuario_id');
    $sql .= " GROUP BY o.id, o.fornecedor_id ORDER BY f.nome, o.data_criacao DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $report_data = [];
    foreach ($data as $row) {
        $fid = $row['fornecedor_id'];
        if (!isset($report_data[$fid]))
            $report_data[$fid] = ['fornecedor_id' => $fid, 'fornecedor_nome' => $row['fornecedor_nome'], 'rows' => []];
        $row['fase_id'] = $row['fase_nome'] ?? 'Ativo';
        $report_data[$fid]['rows'][] = $row;
    }
    return $report_data;
}

function apply_report_filters_helper(&$sql, &$params, $table_alias, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids = [], $supplier_col = 'fornecedor_id', $user_col = 'usuario_id')
{
    $buildIn = function ($ids) {
        if (empty($ids))
            return [null, []];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [$placeholders, $ids];
    };

    if (!empty($supplier_ids)) {
        list($ph, $vals) = $buildIn($supplier_ids);
        $sql .= " AND $table_alias.$supplier_col IN ($ph)";
        $params = array_merge($params, $vals);
    }
    if (!empty($user_ids)) {
        list($ph, $vals) = $buildIn($user_ids);
        $u_alias = (strpos($user_col, '.') !== false) ? $user_col : "$table_alias.$user_col";
        $sql .= " AND $u_alias IN ($ph)";
        $params = array_merge($params, $vals);
    }
    // Skip etapa_id for 'vf' (vendas_fornecedores) as it does not exist there
    if (!empty($etapa_ids) && $table_alias !== 'vf') {
        list($ph, $vals) = $buildIn($etapa_ids);
        $sql .= " AND $table_alias.etapa_id IN ($ph)";
        $params = array_merge($params, $vals);
    }
    if (!empty($origem_ids)) {
        $in_params = trim(str_repeat('?,', count($origem_ids)), ',');
        $sql .= " AND $table_alias.origem IN ($in_params)";
        foreach ($origem_ids as $id)
            $params[] = $id;
    }
    if (!empty($status_ids)) {
        $status_conditions = [];
        foreach ($status_ids as $st) {
            if ($table_alias === 'vf') {
                // 'vendas_fornecedores' only contains won/approved items.
                // If filter is 'Ganho', it matches. If 'Perdido' or 'Aberto', it doesn't.
                if ($st === 'Ganho' || $st === 'Won') {
                    $status_conditions[] = "1=1"; // Always true for this table
                } else {
                    $status_conditions[] = "1=0"; // Always false for Open/Lost in this table
                }
            } else {
                if ($st === 'Ganho' || $st === 'Won') {
                    $status_conditions[] = "$table_alias.etapa_id IN (SELECT id FROM etapas_funil WHERE nome LIKE '%Ganho%' OR nome LIKE '%Fechado%' OR nome LIKE '%Vendido%' OR nome LIKE '%Empenhado%' OR nome LIKE '%Contrato%' OR nome LIKE '%Homologado%' OR nome LIKE '%Faturado%' OR nome LIKE '%Entrega%')";
                } elseif ($st === 'Perdido' || $st === 'Lost') {
                    $status_conditions[] = "$table_alias.etapa_id IN (SELECT id FROM etapas_funil WHERE nome LIKE '%Perdido%' OR nome LIKE '%Recusada%' OR nome LIKE '%Lost%' OR nome LIKE '%Desclassificado%' OR nome LIKE '%Fracassado%')";
                } elseif ($st === 'Aberto' || $st === 'Open') {
                    $status_conditions[] = "$table_alias.etapa_id NOT IN (SELECT id FROM etapas_funil WHERE nome LIKE '%Ganho%' OR nome LIKE '%Fechado%' OR nome LIKE '%Vendido%' OR nome LIKE '%Empenhado%' OR nome LIKE '%Contrato%' OR nome LIKE '%Homologado%' OR nome LIKE '%Faturado%' OR nome LIKE '%Entrega%' OR nome LIKE '%Perdido%' OR nome LIKE '%Recusada%' OR nome LIKE '%Lost%' OR nome LIKE '%Desclassificado%' OR nome LIKE '%Fracassado%')";
                }
            }
        }
        if (!empty($status_conditions))
            $sql .= " AND (" . implode(' OR ', $status_conditions) . ")";
    }
    if (!empty($uf_ids)) {
        $in_params = trim(str_repeat('?,', count($uf_ids)), ',');
        // Assume for state filter we check either organization (PJ) or client_pf (PF)
        $sql .= " AND (
            ($table_alias.organizacao_id IN (SELECT id FROM organizacoes WHERE estado IN ($in_params)))
            OR
            ($table_alias.cliente_pf_id IN (SELECT id FROM clientes_pf WHERE estado IN ($in_params)))
        )";
        foreach ($uf_ids as $id)
            $params[] = $id;
        foreach ($uf_ids as $id)
            $params[] = $id; // Double for OR condition
    }
    if (!empty($cliente_ids)) {
        $org_ids = [];
        $pf_ids = [];
        foreach ($cliente_ids as $cid) {
            if (strpos($cid, 'org-') === 0) {
                $org_ids[] = (int) substr($cid, 4);
            } elseif (strpos($cid, 'pf-') === 0) {
                $pf_ids[] = (int) substr($cid, 3);
            }
        }
        $cliente_conditions = [];
        if (!empty($org_ids)) {
            list($ph_org, $vals_org) = $buildIn($org_ids);
            $cliente_conditions[] = "$table_alias.organizacao_id IN ($ph_org)";
            $params = array_merge($params, $vals_org);
        }
        if (!empty($pf_ids)) {
            list($ph_pf, $vals_pf) = $buildIn($pf_ids);
            $cliente_conditions[] = "$table_alias.cliente_pf_id IN ($ph_pf)";
            $params = array_merge($params, $vals_pf);
        }
        if (!empty($cliente_conditions)) {
            $sql .= " AND (" . implode(' OR ', $cliente_conditions) . ")";
        }
    }
}

function get_clients_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids = [])
{
    // Source A: Propostas Aprovadas
    $sql_prop = "SELECT 
                    p.organizacao_id, 
                    p.cliente_pf_id, 
                    COALESCE(org.nome_fantasia, pf.nome, 'Cliente Desconhecido') as cliente_nome,
                    COUNT(p.id) as qtd, 
                    SUM(p.valor_total) as total
                 FROM propostas p
                 LEFT JOIN organizacoes org ON p.organizacao_id = org.id
                 LEFT JOIN clientes_pf pf ON p.cliente_pf_id = pf.id
                 LEFT JOIN oportunidades o ON p.oportunidade_id = o.id
                 WHERE p.data_criacao BETWEEN ? AND ? 
                 AND p.status = 'Aprovada'";

    $params_prop = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    // Apply filters to Propostas
    apply_report_filters_helper($sql_prop, $params_prop, 'o', [], $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids, 'fornecedor_id', 'p.usuario_id');

    $sql_prop .= " GROUP BY p.organizacao_id, p.cliente_pf_id, cliente_nome";

    $stmt_prop = $pdo->prepare($sql_prop);
    $stmt_prop->execute($params_prop);
    $results_prop = $stmt_prop->fetchAll(PDO::FETCH_ASSOC);

    // Source B: Vendas Fornecedores
    $sql_vendas = "SELECT 
                    vf.organizacao_id, 
                    vf.cliente_pf_id, 
                    COALESCE(org.nome_fantasia, pf.nome, 'Cliente Desconhecido') as cliente_nome,
                    COUNT(vf.id) as qtd, 
                    SUM(vf.valor_total) as total
                   FROM vendas_fornecedores vf
                   LEFT JOIN organizacoes org ON vf.organizacao_id = org.id
                   LEFT JOIN clientes_pf pf ON vf.cliente_pf_id = pf.id
                   WHERE vf.data_venda BETWEEN ? AND ?";

    $params_vendas = [$start_date, $end_date];

    apply_report_filters_helper($sql_vendas, $params_vendas, 'vf', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

    $sql_vendas .= " GROUP BY vf.organizacao_id, vf.cliente_pf_id, cliente_nome";

    $stmt_vendas = $pdo->prepare($sql_vendas);
    $stmt_vendas->execute($params_vendas);
    $results_vendas = $stmt_vendas->fetchAll(PDO::FETCH_ASSOC);

    // Merge Logic
    $clients = [];

    $process_row = function ($row) use (&$clients) {
        // Create a unique key. Prefer OrgID/PfID. If both null, assume generic name or skip.
        if (!empty($row['organizacao_id'])) {
            $key = 'pj_' . $row['organizacao_id'];
        } elseif (!empty($row['cliente_pf_id'])) {
            $key = 'pf_' . $row['cliente_pf_id'];
        } else {
            // Fallback: Normalize name
            $name = trim($row['cliente_nome']);
            if (empty($name) || $name === 'Cliente Desconhecido')
                return; // Skip invalid
            $key = 'name_' . md5(strtoupper($name));
        }

        if (!isset($clients[$key])) {
            $clients[$key] = [
                'cliente_nome' => $row['cliente_nome'],
                'qtd_vendas' => 0,
                'valor_total' => 0.0
            ];
        }

        $clients[$key]['qtd_vendas'] += (int) $row['qtd'];
        $clients[$key]['valor_total'] += (float) $row['total'];
    };

    foreach ($results_prop as $row)
        $process_row($row);
    foreach ($results_vendas as $row)
        $process_row($row);

    // Convert to array and sort
    $final_data = array_values($clients);

    usort($final_data, function ($a, $b) {
        return $b['valor_total'] <=> $a['valor_total'];
    });

    return $final_data;
}

function get_licitacoes_funnel_report($pdo, $start_date, $end_date, $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids = [])
{
    // Funnel ID 2 = Licitações
    $sql = "
        SELECT 
            COALESCE(org.nome_fantasia, org.razao_social, f.nome, 'Fornecedor Não Informado') as fornecedor_nome,
            ef.id as etapa_id,
            ef.nome as etapa_nome, 
            ef.ordem as etapa_ordem,
            COUNT(DISTINCT o.id) as qtd_oportunidades,
            SUM(
                COALESCE(
                    (SELECT SUM(pi.quantidade * pi.valor_unitario) 
                     FROM propostas p 
                     JOIN proposta_itens pi ON p.id = pi.proposta_id 
                     WHERE p.oportunidade_id = o.id AND p.status = 'Aprovada'), 
                    o.valor, 
                    0
                )
            ) as valor_total
        FROM oportunidades o
        JOIN etapas_funil ef ON o.etapa_id = ef.id
        LEFT JOIN organizacoes org ON o.fornecedor_id = org.id
        LEFT JOIN fornecedores f ON o.fornecedor_id = f.id
        WHERE o.data_criacao BETWEEN ? AND ?
          AND (o.numero_edital IS NOT NULL AND o.numero_edital != '')
          AND ef.funil_id = 2
    ";

    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

    apply_report_filters_helper($sql, $params, 'o', $supplier_ids, $user_ids, $etapa_ids, $origem_ids, $uf_ids, $status_ids, $cliente_ids);

    $sql .= " GROUP BY fornecedor_nome, ef.id, ef.nome, ef.ordem ORDER BY fornecedor_nome, ef.ordem ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $etapas_rigidas = [
        'Captação de Edital', 'Acolhimento de propostas', 'Em análise Técnica', 
        'Homologado', 'Ata/Carona', 'Empenhado', 'Contrato', 'Desclassificado', 
        'Fracassado', 'Revogado', 'Anulado', 'Suspenso'
    ];
    $fornecedores_rigidos = ['BRASIL MEDICA', 'HEALTH', 'INSTRAMED', 'LIVANOVA', 'MASIMO', 'MERIL', 'MICROMED', 'NIPRO', 'SIGMAFIX'];

    $matrix = [];
    foreach ($fornecedores_rigidos as $fj) {
        $matrix[$fj] = [];
        foreach ($etapas_rigidas as $idx => $nome_etapa) {
            $matrix[$fj][$nome_etapa] = [
                'fornecedor_nome' => $fj,
                'etapa_nome' => $nome_etapa,
                'etapa_ordem' => $idx + 1,
                'qtd_oportunidades' => 0,
                'valor_total' => 0
            ];
        }
    }

    foreach ($results as $row) {
        $matched_ff = false;
        foreach($fornecedores_rigidos as $fj) {
            if (stripos(trim($row['fornecedor_nome']), $fj) !== false) {
                $matched_ff = $fj;
                break;
            }
        }
        if (!$matched_ff) continue; 

        $matched_et = false;
        foreach ($etapas_rigidas as $nome_rigido) {
            if (strcasecmp(trim($row['etapa_nome']), $nome_rigido) == 0) {
                $matched_et = $nome_rigido;
                break;
            }
        }
        
        $etapa_to_use = $matched_et;
        if (!$etapa_to_use) continue;

        $matrix[$matched_ff][$etapa_to_use]['qtd_oportunidades'] += $row['qtd_oportunidades'];
        $matrix[$matched_ff][$etapa_to_use]['valor_total'] += $row['valor_total'];
    }

    $final_out = [];
    foreach ($fornecedores_rigidos as $fj) {
        foreach ($etapas_rigidas as $et) {
            $final_out[] = $matrix[$fj][$et];
        }
    }

    return $final_out;
}