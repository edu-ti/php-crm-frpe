<?php
require_once __DIR__ . '/../../fpdf/fpdf.php';
require_once __DIR__ . '/report_handler.php';

class PDFReport extends FPDF {
    function Header() {
        if(file_exists(__DIR__ . '/../../imagens/LOGO-FR.png')) {
            $this->Image(__DIR__ . '/../../imagens/LOGO-FR.png', 10, 8, 33);
        }
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, utf8_decode('Resumo Executivo - Relatório de BI'), 0, 0, 'C');
        $this->Ln(20);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function BasicTable($header, $data) {
        $this->SetFillColor(79, 70, 229); // Indigo 600
        $this->SetTextColor(255);
        $this->SetDrawColor(209, 213, 219);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Count columns and dynamically set width to sum ~ 190 (A4 with 10mm margins = 210-20=190)
        $cols = count($header);
        $w = [];
        for ($i=0; $i<$cols; $i++) {
            $w[] = 190 / $cols; // Evenly distributed columns
        }
        
        for($i=0; $i<$cols; $i++)
            $this->Cell($w[$i], 7, utf8_decode($header[$i]), 1, 0, 'C', true);
        $this->Ln();
        
        // Data
        $this->SetFillColor(249, 250, 251); // Zebra color
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        foreach($data as $row) {
            $i = 0;
            foreach($row as $col) {
                if ($i >= count($w)) break;
                // Encode and truncate if too long
                $text = utf8_decode(is_string($col) ? substr($col, 0, 50) : $col);
                $this->Cell($w[$i], 6, $text, 'LR', 0, 'L', $fill);
                $i++;
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(10);
    }
}

$type = $_GET['report_type'] ?? ($_GET['type'] ?? 'sales');

// Interceptamos o endpoint original do handler para pegar os mesmos JSON arrays e renderizar
ob_start();
$db = new Database();
$pdo = $db->getConnection();
handle_get_report_data($pdo); 
$jsonResponse = ob_get_clean();

$response = json_decode($jsonResponse, true);
$data = [];
if (isset($response['report_data'])) {
    $data = $response['report_data'];
} elseif (is_array($response)) {
    // Para casos como by_vendor que retornavam um array simples
    $data = $response; 
}

$pdf = new PDFReport();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Resumo Executivo
$pdf->SetFillColor(243, 244, 246);
$pdf->Rect(10, 30, 190, 25, 'F');

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetY(32);
$pdf->Cell(0, 8, utf8_decode('Parâmetros do Filtro'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, utf8_decode('Data: ' . ($_GET['start_date'] ?? 'N/D') . ' a ' . ($_GET['end_date'] ?? 'N/D') . '     |     Tipo: ' . strtoupper($type)), 0, 1);
$pdf->Ln(15);

if (empty($data)) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, utf8_decode('Nenhum registro correspondente ao filtro foi encontrado.'), 0, 1, 'C');
} else {
    // Dynamic Table Generation based on Type
    if ($type === 'clients') {
        $header = ['Cliente Classificado', 'Qtd Vendas', 'Faturamento Bruto', 'Curva ABC'];
        $tableData = [];
        foreach ($data as $r) {
            $tableData[] = [
                $r['cliente_nome'], 
                $r['qtd_vendas'], 
                'R$ ' . number_format($r['valor_total'], 2, ',', '.'),
                $r['classe'] ?? '-'
            ];
        }
        $pdf->BasicTable($header, $tableData);
    } elseif ($type === 'funnel') {
        $header = ['Nome da Etapa do Funil', 'Total de Oportunidades', 'Valor em Pipeline Estimado'];
        $tableData = [];
        foreach ($data as $r) {
            $tableData[] = [
                $r['label'], 
                $r['count'], 
                'R$ ' . number_format($r['value'], 2, ',', '.')
            ];
        }
        $pdf->BasicTable($header, $tableData);
    } elseif ($type === 'forecast') {
        $header = ['Referência (Mês)', 'Pipeline Pleno (Sem Fator)', 'Forecast Projetado (Probabilidade Média)'];
        $tableData = [];
        $totalPipeline = 0;
        $totalForecast = 0;
        foreach ($data as $r) {
            $tableData[] = [
                $r['mes'], 
                'R$ ' . number_format($r['pipeline_total'], 2, ',', '.'),
                'R$ ' . number_format($r['forecast_ponderado'], 2, ',', '.')
            ];
            $totalPipeline += $r['pipeline_total'];
            $totalForecast += $r['forecast_ponderado'];
        }
        $tableData[] = ['TOTALIZADO', 'R$ ' . number_format($totalPipeline, 2, ',', '.'), 'R$ ' . number_format($totalForecast, 2, ',', '.')];
        $pdf->BasicTable($header, $tableData);
    } else {
        $pdf->Cell(0, 10, utf8_decode('Este formato de relatório ainda não possui template PDF detalhado. Verifique na plataforma.'), 0, 1);
    }
}

$pdf->Output('I', 'Relatorio_BI_FRPE.pdf');
