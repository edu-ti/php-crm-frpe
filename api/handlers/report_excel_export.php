<?php
require_once __DIR__ . '/report_handler.php';

function handle_export_excel($pdo, $request_data)
{
    if (ob_get_length()) ob_clean();

    $type = $_GET['report_type'] ?? ($_GET['type'] ?? 'sales');
    $start_date = $_GET['start_date'] ?? date('Y-01-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    ob_start();
    handle_get_report_data($pdo);
    $jsonResponse = ob_get_clean();

    $response = json_decode($jsonResponse, true);
    $data = [];

    if (isset($response['report_data'])) {
        $data = $response['report_data'];
    } elseif (isset($response['data'])) {
        $data = $response['data'];
    } elseif (is_array($response)) {
        $data = $response;
    }

    $reportTitle = getReportTitle($type);
    $fileName = getFileName($type);

    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        exportWithPhpSpreadsheet($type, $data, $response, $reportTitle, $fileName, $start_date, $end_date, $pdo);
    } else {
        exportAsHtmlExcel($type, $data, $response, $reportTitle, $fileName, $start_date, $end_date, $pdo);
    }
}

function exportWithPhpSpreadsheet($type, $data, $response, $title, $fileName, $start, $end, $pdo)
{
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Font;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($title, 0, 31));

    $sheet->setCellValue('A1', 'FR Produtos Médicos - ' . $title);
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1e40af'));

    $sheet->setCellValue('A2', 'Gerado em: ' . date('d/m/Y H:i'));
    $sheet->setCellValue('A3', 'Período: ' . $start . ' a ' . $end);

    $headers = getHeadersForType($type);
    $rows = formatDataForType($type, $data, $response);

    $col = 'A';
    $headerRow = 5;
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $headerRow, $header);
        $col++;
    }

    $headerRange = 'A' . $headerRow . ':' . chr(64 + count($headers)) . $headerRow;
    $headerStyle = $sheet->getStyle($headerRange);
    $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('ffffff'));
    $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('4f46e5');
    $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $rowNum = $headerRow + 1;
    foreach ($rows as $row) {
        $col = 'A';
        foreach ($row as $value) {
            $cell = $sheet->setCellValue($col . $rowNum, $value);
            $sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $col++;
        }
        $rowNum++;
    }

    $dataRange = 'A' . ($headerRow + 1) . ':' . chr(64 + count($headers)) . ($rowNum - 1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    foreach (range('A', chr(64 + count($headers) - 1)) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $sheet->setAutoFilter('A' . $headerRow . ':' . chr(64 + count($headers) - 1) . ($rowNum - 1));

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function exportAsHtmlExcel($type, $data, $response, $title, $fileName, $start, $end, $pdo)
{
    $headers = getHeadersForType($type);
    $rows = formatDataForType($type, $data, $response);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    $html .= '<style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { color: #1e40af; margin-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; margin-bottom: 12px; }
        table { border-collapse: collapse; width: 100%; }
        thead tr { background: #4f46e5; color: #fff; }
        th { padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .number { text-align: right; }
        .currency { text-align: right; }
    </style></head><body>';

    $html .= '<h2>FR Produtos Médicos - ' . htmlspecialchars($title) . '</h2>';
    $html .= '<p class="meta">Gerado em: ' . date('d/m/Y H:i') . ' | Período: ' . htmlspecialchars($start) . ' a ' . htmlspecialchars($end) . '</p>';

    $filters = getAppliedFilters();
    if (!empty($filters)) {
        $html .= '<p class="meta">Filtros: ' . htmlspecialchars(implode(' | ', $filters)) . '</p>';
    }

    $html .= '<table><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . htmlspecialchars($h) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        $i = 0;
        foreach ($row as $value) {
            $cls = ($i > 0) ? ' class="number"' : '';
            $html .= '<td' . $cls . '>' . htmlspecialchars((string)$value) . '</td>';
            $i++;
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></body></html>';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
    header('Cache-Control: max-age=0');
    echo $html;
    exit;
}

function getReportTitle($type)
{
    $titles = [
        'bi_kpis' => 'Dashboard BI - Indicadores',
        'sales' => 'Vendas por Vendedor',
        'by_vendor' => 'Vendas por Vendedor',
        'by_supplier' => 'Vendas por Fornecedor',
        'clients' => 'Ranking de Clientes',
        'bids_result' => 'Resultado de Licitações',
        'commission_analysis' => 'Análise de Comissões',
        'revenue_forecast' => 'Forecast de Receita',
        'conversion_by_vendor' => 'Conversão por Vendedor',
        'sales_by_state' => 'Vendas por Estado (UF)',
        'by_item' => 'Produtos Vendidos',
        'funnel' => 'Funil de Vendas',
        'supplier_funnel' => 'Performance por Fornecedor',
    ];
    return $titles[$type] ?? 'Relatório';
}

function getFileName($type)
{
    $names = [
        'bi_kpis' => 'Dashboard_BI',
        'sales' => 'Vendas_Vendedor',
        'by_vendor' => 'Vendas_Vendedor',
        'by_supplier' => 'Vendas_Fornecedor',
        'clients' => 'Ranking_Clientes',
        'bids_result' => 'Resultado_Licitacoes',
        'commission_analysis' => 'Analise_Comissoes',
        'revenue_forecast' => 'Forecast_Receita',
        'conversion_by_vendor' => 'Conversao_Vendedor',
        'sales_by_state' => 'Vendas_Estado',
        'by_item' => 'Produtos_Vendidos',
        'funnel' => 'Funil_Vendas',
        'supplier_funnel' => 'Performance_Fornecedor',
    ];
    $base = $names[$type] ?? 'Relatorio';
    return $base . '_' . date('Y-m-d');
}

function getAppliedFilters()
{
    $filters = [];
    if (!empty($_GET['supplier_id'])) $filters[] = 'Fornecedor: ' . $_GET['supplier_id'];
    if (!empty($_GET['user_id'])) $filters[] = 'Vendedor: ' . $_GET['user_id'];
    if (!empty($_GET['cliente_id'])) $filters[] = 'Cliente: ' . $_GET['cliente_id'];
    if (!empty($_GET['uf'])) $filters[] = 'UF: ' . $_GET['uf'];
    return $filters;
}

function getHeadersForType($type)
{
    $map = [
        'bi_kpis' => ['Indicador', 'Valor'],
        'sales' => ['Vendedor', 'Faturamento'],
        'by_vendor' => ['Vendedor', 'Faturamento', 'Qtd Vendas', 'Ticket Médio'],
        'by_supplier' => ['Fornecedor', 'Faturamento', 'Qtd Vendas'],
        'clients' => ['Cliente', 'Faturamento', 'Qtd Vendas', 'Ticket Médio'],
        'bids_result' => ['Categoria', 'Quantidade'],
        'commission_analysis' => ['Vendedor', 'Meta', 'Vendas', 'Salário Fixo', '% Comissão', 'Comissão', 'Total Período', 'Atingimento %'],
        'revenue_forecast' => ['Métrica', 'Valor'],
        'conversion_by_vendor' => ['Vendedor', 'Propostas', 'Aprovadas', 'Recusadas', 'Conversão %'],
        'sales_by_state' => ['UF', 'Faturamento', 'Qtd Vendas'],
        'by_item' => ['Produto', 'Faturamento', 'Qtd'],
        'funnel' => ['Etapa', 'Quantidade', 'Valor'],
        'supplier_funnel' => ['Fornecedor', 'Meta Anual', 'Realizado', 'Atingimento %'],
    ];
    return $map[$type] ?? ['Campo', 'Valor'];
}

function formatDataForType($type, $data, $response)
{
    switch ($type) {
        case 'bi_kpis':
            return [
                ['Total Vendido', 'R$ ' . number_format($response['total_sales'] ?? 0, 2, ',', '.')],
                ['Aprovado no Mês', 'R$ ' . number_format($response['month_sales'] ?? 0, 2, ',', '.')],
                ['Perdas de Oportunidades', 'R$ ' . number_format($response['lost_sales'] ?? 0, 2, ',', '.')],
                ['Ticket Médio (Ano)', 'R$ ' . number_format($response['avg_ticket'] ?? 0, 2, ',', '.')],
                ['Ticket Médio (Mês)', 'R$ ' . number_format($response['avg_ticket_month'] ?? 0, 2, ',', '.')],
                ['Licitações Ativas', (int)($response['active_bids'] ?? 0)],
            ];

        case 'sales':
        case 'by_vendor':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (isset($item['vendedor'])) {
                        $name = $item['vendedor'];
                    } elseif (isset($item['nome'])) {
                        $name = $item['nome'];
                    } else {
                        $name = $item[0] ?? '';
                    }
                    $total = $item['total'] ?? $item['faturamento'] ?? ($item[1] ?? 0);
                    $qtd = $item['qtd'] ?? $item['qtd_vendas'] ?? '';
                    $rows[] = [$name, 'R$ ' . number_format((float)$total, 2, ',', '.'), $qtd, ''];
                }
            }
            return $rows;

        case 'by_supplier':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $name = $item['label'] ?? $item['fornecedor'] ?? '';
                    $val = $item['value'] ?? $item['faturamento'] ?? 0;
                    $cnt = $item['count'] ?? $item['qtd_vendas'] ?? '';
                    $rows[] = [$name, 'R$ ' . number_format((float)$val, 2, ',', '.'), $cnt];
                }
            }
            return $rows;

        case 'clients':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $name = $item['cliente'] ?? $item['cliente_nome'] ?? '';
                    $val = $item['faturamento'] ?? $item['valor_total'] ?? 0;
                    $qtd = $item['qtd_vendas'] ?? '';
                    $ticket = $item['ticket_medio'] ?? '';
                    $rows[] = [
                        $name,
                        'R$ ' . number_format((float)$val, 2, ',', '.'),
                        $qtd,
                        $ticket ? 'R$ ' . number_format((float)$ticket, 2, ',', '.') : ''
                    ];
                }
            }
            return $rows;

        case 'bids_result':
            $d = is_array($data) ? $data : [];
            return [
                ['Ganhas', (int)($d['ganhas'] ?? 0)],
                ['Perdidas', (int)($d['perdidas'] ?? 0)],
                ['Fracassadas', (int)($d['fracassadas'] ?? 0)],
                ['Revogadas', (int)($d['revogadas'] ?? 0)],
                ['Suspensas', (int)($d['suspensas'] ?? 0)],
                ['Taxa de Sucesso', number_format($d['success_rate'] ?? 0, 1, ',', '.') . '%'],
            ];

        case 'commission_analysis':
            $rows = [];
            $list = $response['data'] ?? $data;
            if (is_array($list)) {
                foreach ($list as $item) {
                    $rows[] = [
                        $item['nome'] ?? '',
                        'R$ ' . number_format((float)($item['meta_mensal'] ?? 0), 2, ',', '.'),
                        'R$ ' . number_format((float)($item['total_vendas'] ?? 0), 2, ',', '.'),
                        'R$ ' . number_format((float)($item['valor_fixo'] ?? 0), 2, ',', '.'),
                        number_format((float)($item['percentual_comissao'] ?? 0), 1, ',', '.') . '%',
                        'R$ ' . number_format((float)($item['comissao_valor'] ?? 0), 2, ',', '.'),
                        'R$ ' . number_format((float)($item['total_periodo'] ?? 0), 2, ',', '.'),
                        number_format((float)($item['atingimento'] ?? 0), 1, ',', '.') . '%',
                    ];
                }
            }
            return $rows;

        case 'revenue_forecast':
            $d = is_array($data) ? $data : ($response['data'] ?? []);
            return [
                ['Realizado (YTD)', 'R$ ' . number_format((float)($d['realized'] ?? 0), 2, ',', '.')],
                ['Média Mensal', 'R$ ' . number_format((float)($d['monthly_avg'] ?? 0), 2, ',', '.')],
                ['Meses considerados', (int)($d['current_month'] ?? 0)],
                ['Projetado (Forecast 12m)', 'R$ ' . number_format((float)($d['forecast'] ?? 0), 2, ',', '.')],
                ['Meta Anual', 'R$ ' . number_format((float)($d['meta_anual'] ?? 0), 2, ',', '.')],
                ['Diferença', 'R$ ' . number_format((float)($d['difference'] ?? 0), 2, ',', '.')],
                ['Atingimento Esperado', number_format((float)($d['achievement'] ?? 0), 1, ',', '.') . '%'],
            ];

        case 'conversion_by_vendor':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $rows[] = [
                        $item['vendedor'] ?? '',
                        (int)($item['propostas'] ?? 0),
                        (int)($item['aprovadas'] ?? 0),
                        (int)($item['recusadas'] ?? 0),
                        number_format((float)($item['conversao'] ?? 0), 1, ',', '.') . '%',
                    ];
                }
            }
            return $rows;

        case 'sales_by_state':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $rows[] = [
                        $item['uf'] ?? '',
                        'R$ ' . number_format((float)($item['faturamento'] ?? 0), 2, ',', '.'),
                        (int)($item['qtd_vendas'] ?? 0),
                    ];
                }
            }
            return $rows;

        case 'by_item':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $name = $item['label'] ?? $item['produto'] ?? ($item[0] ?? '');
                    $val = $item['value'] ?? $item['faturamento'] ?? ($item[1] ?? 0);
                    $cnt = $item['count'] ?? $item['qtd'] ?? ($item[2] ?? '');
                    $rows[] = [$name, 'R$ ' . number_format((float)$val, 2, ',', '.'), $cnt];
                }
            }
            return $rows;

        case 'funnel':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $rows[] = [
                        $item['etapa'] ?? $item['label'] ?? ($item[0] ?? ''),
                        (int)($item['quantidade'] ?? $item['count'] ?? ($item[1] ?? 0)),
                        'R$ ' . number_format((float)($item['valor'] ?? $item['total'] ?? ($item[2] ?? 0)), 2, ',', '.'),
                    ];
                }
            }
            return $rows;

        case 'supplier_funnel':
            $rows = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $rows[] = [
                        $item['name'] ?? $item['fornecedor'] ?? ($item[0] ?? ''),
                        'R$ ' . number_format((float)($item['annual_goal'] ?? $item['meta'] ?? 0), 2, ',', '.'),
                        'R$ ' . number_format((float)($item['period_total'] ?? $item['realizado'] ?? 0), 2, ',', '.'),
                        number_format((float)($item['progress'] ?? $item['atingimento'] ?? 0), 1, ',', '.') . '%',
                    ];
                }
            }
            return $rows;

        default:
            if (is_array($data)) {
                return array_map(function($row) {
                    if (is_array($row)) return array_values($row);
                    return [$row];
                }, $data);
            }
            return [];
    }
}
