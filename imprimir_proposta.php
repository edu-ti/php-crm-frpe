<?php
// imprimir_proposta.php

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/core/Database.php';

if (!isset($_SESSION['user_id'])) {
    die('Acesso negado. Por favor, faça login.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID da proposta inválido ou não fornecido.');
}

$proposal_id = (int)$_GET['id'];
$proposal = null;
$company_info = [
    'name' => 'FR PRODUTOS MÉDICOS',
    'cnpj' => '09.005.588/0001-40',
    'phone' => '(81) 3423-2022 | (81) 3423-7272',
    'address' => 'Rua Joaquim de Brito, 240, Boa Vista, Recife-PE, CEP: 50.070-280',
    'site' => 'www.fr.pe.com.br',
    'email' => 'frpe@frpe.com.br',
    'social' => '@frprodutosmedicospe'
];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $proposal_stmt = $pdo->prepare("
        SELECT p.*, 
               o.nome_fantasia as organizacao_nome, o.razao_social, o.cnpj, o.logradouro, o.numero as org_numero, o.complemento as org_complemento, o.bairro as org_bairro, o.cidade as org_cidade, o.estado as org_estado, o.cep as org_cep,
               pf.nome as cliente_pf_nome, pf.cpf, 
               ct.nome as contato_nome, ct.email as contato_email, ct.telefone as contato_telefone,
               u.nome as vendedor_nome, u.role as vendedor_role, u.email as vendedor_email, u.telefone as vendedor_telefone
        FROM propostas p 
        LEFT JOIN organizacoes o ON p.organizacao_id = o.id 
        LEFT JOIN clientes_pf pf ON p.cliente_pf_id = pf.id 
        LEFT JOIN contatos ct ON p.contato_id = ct.id 
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ?");
    $proposal_stmt->execute([$proposal_id]);
    $proposal = $proposal_stmt->fetch(PDO::FETCH_ASSOC);

    if ($proposal) {
        $items_stmt = $pdo->prepare("SELECT * FROM proposta_itens WHERE proposta_id = ?");
        $items_stmt->execute([$proposal_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- INÍCIO DA MODIFICAÇÃO: Decodificar Parâmetros ---
        foreach ($items as &$item) { // Passar por referência
            if (!empty($item['parametros'])) {
                try {
                    $item['parametros'] = json_decode($item['parametros'], true); // true para array associativo
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $item['parametros'] = []; // Define como array vazio em caso de JSON inválido
                    }
                } catch (Exception $e) {
                    $item['parametros'] = []; // Define como array vazio em caso de erro
                }
            } else {
                $item['parametros'] = []; // Define como array vazio se for nulo ou vazio
            }
        }
        unset($item); // Libera a referência
        $proposal['items'] = $items; // Atribui os itens processados de volta
        // --- FIM DA MODIFICAÇÃO ---
    }
} catch (Exception $e) {
    die('Erro ao conectar-se à base de dados: ' . $e->getMessage());
}

if (!$proposal) {
    die('Proposta não encontrada.');
}

$total_geral = 0;

function format_currency($value) {
    if (is_null($value) || !is_numeric($value)) return 'R$ 0,00';
    return 'R$ ' . number_format($value, 2, ',', '.');
}
function format_date($value, $format = 'd/m/Y') {
    if (!$value || $value === '0000-00-00') return 'N/A';
    // --- CORREÇÃO: Define o fuso horário para evitar problemas ---
    try {
        $date = new DateTime($value, new DateTimeZone('America/Recife'));
        return $date->format($format);
    } catch (Exception $e) {
        return 'Data Inválida';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposta <?php echo htmlspecialchars($proposal['numero_proposta']); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'print': {'raw': 'print'},
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Paged.js Config (Must be before Polyfill) -->
    <script>
        window.PagedConfig = {
            auto: true,
            after: (flow) => {
                console.log("Paged.js finish - Injecting Buttons safely");
                
                // Use setTimeout to ensure DOM is settled and override any Paged.js cleanup
                setTimeout(() => {
                    // Remove existing check
                    const existing = document.getElementById('print-controls');
                    if (existing) existing.remove();

                    // Re-inject the floating action buttons
                const printUi = document.createElement('div');
                printUi.id = 'print-controls';
                
                // NO INLINE DISPLAY STYLE to allow CSS override. 
                // We keep position and z-index here for safety, but display is handled by CSS.
                printUi.style.cssText = "position: fixed; top: 20px; right: 20px; z-index: 2147483647; pointer-events: auto;";
                
                printUi.innerHTML = `
                    <!-- Print Button -->
                    <button onclick="window.print()" style="width: 56px; height: 56px; border-radius: 50%; background-color: #4f46e5; color: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="Imprimir">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                    
                    <!-- Close Button -->
                    <button onclick="window.close()" style="width: 56px; height: 56px; border-radius: 50%; background-color: #334155; color: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;
                document.body.appendChild(printUi);
                
                // Add JS event listeners for printing as a failsafe
                const handleBeforePrint = () => {
                    const controls = document.getElementById('print-controls');
                    if (controls) controls.style.display = 'none';
                };
                const handleAfterPrint = () => {
                   const controls = document.getElementById('print-controls');
                   if (controls) controls.style.display = 'flex'; // Restore logic
                };
                
                window.addEventListener('beforeprint', handleBeforePrint);
                window.addEventListener('afterprint', handleAfterPrint);
            }, 500);
        }
    };
</script>

<!-- Explicit Print Hiding for the Controls -->
<style>
    /* Screen styles: Visible */
    #print-controls {
        display: flex; /* Removed !important to allow JS override */
        flex-direction: column; 
        gap: 12px;
    }
    
    /* Print styles: Hidden (backup) */
    @media print {
        #print-controls {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
    }
</style>

    <!-- Paged.js CDN -->
    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; 
            color: #333;
        }

        /* Paged.js CSS Rules */
        @page {
            size: A4;
            /* Increased top margin to 55mm to prevent clipping */
            margin: 37mm 10mm 10mm 10mm; 
            
            /* Place the running header in the top-center margin box */
            @top-center {
                content: element(headerInfo);
                width: 100%;
                vertical-align: bottom; /* Aligns content to the bottom of the margin header area */
            }
            
            /* Remove footer pagination as it moved to header */
            @bottom-right {
                content: none;
            }
        }
        
        /* Define the Header as a Running Element */
        .running-header {
            position: running(headerInfo);
            width: 100%; 
            /* Removed bottom margins to prevent pushing content up off the page */
            margin-bottom: 0 !important; 
            padding-bottom: 5px; /* Slight padding for the border */
        }
        
        /* Render Page Counter inside the HTML element */
        .page-counter::after {
            content: "Página " counter(page) " de " counter(pages);
        }

        /* Ensure major blocks don't split weirdly */
        .break-inside-avoid {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Styles for Paged.js Preview */
        .pagedjs_pages {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
        }
        .pagedjs_page {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            background-color: white;
            /* Debugging margins */
            /* --pagedjs-margin-top: 55mm !important; */
        }
        
        /* Table Styles */
        .ref-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .ref-table th {
            background-color: #2e2a78;
            color: #ffffff;
            font-weight: 500;
            text-transform: capitalize; 
            font-size: 8pt;
            padding: 8px 10px;
            text-align: left;
        }
        .ref-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 8pt;
            color: #1f2937;
        }

        /* Client Box - REDUCED FONT SIZE */
        .client-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 8pt; 
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white; margin: 0; }
            .pagedjs_pages { padding: 0; display: block; }
            .pagedjs_page { box-shadow: none; margin: 0; }
            
            /* Force background colors */
            .ref-table th {
                background-color: #2e2a78 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .client-box {
                background-color: #f9fafb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Static Header removed - Injected via JS after Paged.js render -->

    <!-- Spacer for fixed top bar -->
    <div class="h-24 no-print print:hidden"></div>

    <div id="content"> 
        
        <!-- Header - Moved to Running Element via CSS -->
        <header class="running-header border-b border-[#2e2a78] pb-4">
            <!-- Row 1: Title Only (Visual Alignment) -->
            <div class="text-right mb-2">
                 <h1 class="text-[11pt] font-black text-[#2e2a78] uppercase leading-none tracking-tight">PROPOSTA COMERCIAL</h1>
            </div>

            <div class="flex justify-between items-start">
                
                <!-- Left Side: Logo | Divider | Company Info -->
                <div class="flex items-start"> 
                    <!-- Logo -->
                    <div class="flex-shrink-0 mr-4 self-center">
                         <img src="imagens/LOGO-FR.webp" alt="Logo FR" class="h-12 w-auto object-contain">
                    </div>
                    
                    <!-- Vertical Divider -->
                    <div class="w-[0.5px] bg-[#2e2a78] self-stretch mr-4 opacity-100"></div>

                    <!-- Company Info -->
                    <div class="flex flex-col justify-center text-[6.5pt] text-slate-500 leading-snug text-left py-1">
                        <p class="font-bold text-[#2e2a78] text-[8pt] uppercase tracking-tight mb-0.5"><?php echo htmlspecialchars($company_info['name']); ?></p>
                        <p class="mb-px">CNPJ: <?php echo htmlspecialchars($company_info['cnpj']); ?></p>
                        <p class="mb-px"><?php echo htmlspecialchars($company_info['address']); ?></p>
                        <div class="flex flex-wrap gap-1 mb-px">
                            <span><?php echo htmlspecialchars($company_info['phone']); ?></span>
                            <span class="text-slate-400">|</span>
                            <span><?php echo htmlspecialchars($company_info['email']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($company_info['site']); ?></p>
                    </div>
                </div>

                <!-- Right Side: Number | Dates | Page -->
                <!-- Pt-0.5 ensures baseline alignment with Company Name if fonts match closely -->
                <div class="text-right flex flex-col pt-0.5">
                    
                    <!-- Number with precise color match and alignment to Company Name -->
                    <p class="text-[8pt] font-bold text-[#94a3b8] mb-1 leading-none">Nº <?php echo htmlspecialchars($proposal['numero_proposta']); ?></p>
                    
                    <!-- Dates & Page -->
                    <div class="text-[7pt] text-slate-600 leading-tight mt-1">
                        <p class="mb-0.5">Data de Emissão: <span class="font-bold text-[#2e2a78]"><?php echo format_date($proposal['data_criacao']); ?></span></p>
                        <p class="mb-0.5 font-bold text-red-600">Validade: <?php echo format_date($proposal['data_validade']); ?></p>
                        <p class="text-[#94a3b8] page-counter font-normal text-[7.5pt] mt-1"></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Spacer after header in flow -->
        <div class="h-6"></div>

        <!-- Client Info - Font reduced via .client-box CSS class -->
        <div class="client-box grid grid-cols-2 gap-8 break-inside-avoid shadow-sm">
            <div class="space-y-1.5">
                <p><span class="font-bold text-slate-700">Cliente:</span> <span class="uppercase font-semibold text-slate-600"><?php echo htmlspecialchars($proposal['cnpj'] ?: $proposal['cpf']); ?> <?php echo htmlspecialchars($proposal['organizacao_nome'] ?: $proposal['cliente_pf_nome']); ?></span></p>
                <p><span class="font-bold text-slate-700">CNPJ/CPF:</span> <span class="text-slate-600"><?php echo htmlspecialchars($proposal['cnpj'] ?: $proposal['cpf']); ?></span></p>
                <p class="flex items-start gap-1">
                    <span class="font-bold text-slate-700 whitespace-nowrap">Endereço:</span> 
                    <span class="text-slate-600"><?php echo htmlspecialchars($proposal['logradouro'] . ', ' . $proposal['org_numero'] . ($proposal['org_complemento'] ? ' (' . $proposal['org_complemento'] . ')' : '')); ?> - <?php echo htmlspecialchars($proposal['org_bairro']); ?></span>
                </p>
                <p><span class="font-bold text-slate-700">Cidade/UF:</span> <span class="uppercase text-slate-600"><?php echo htmlspecialchars($proposal['org_cidade'] . ' - ' . $proposal['org_estado']); ?> - CEP: <?php echo htmlspecialchars($proposal['org_cep']); ?></span></p>
            </div>
            <div class="space-y-1.5">
                <p><span class="font-bold text-slate-700">Contato:</span> <span class="text-slate-600"><?php echo htmlspecialchars($proposal['contato_nome'] ?: 'N/A'); ?></span></p>
                <p><span class="font-bold text-slate-700">Telefone:</span> <span class="text-slate-600"><?php echo htmlspecialchars($proposal['contato_telefone'] ?: 'N/A'); ?></span></p>
                <p><span class="font-bold text-slate-700">E-mail:</span> <span class="text-slate-600"><?php echo htmlspecialchars($proposal['contato_email'] ?: 'N/A'); ?></span></p>
            </div>
        </div>

        <!-- Intro -->
        <div class="mb-6 text-[8pt] text-slate-600 leading-relaxed text-justify break-inside-avoid">
            <p class="mb-2">Prezados (as),</p>
            <p>A <strong>FR Produtos Médicos</strong> agradece seu interesse em nossos produtos e serviços. Sabemos da sua importância em sempre oferecer a mais alta tecnologia para a melhor e mais rápida recuperação do paciente e também em oferecer segurança aos profissionais da saúde.</p>
        </div>

        <!-- Table -->
        <table class="ref-table">
            <thead>
                <tr>
                    <th width="70">Imagem</th>
                    <th>Descrição</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Unid.</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-right">Vlr. Unit.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($proposal['items'] as $item): 
                    $valor_unitario_base = (float) ($item['valor_unitario'] ?? 0);
                    $valor_parametros = 0;
                    $visible_params = [];
                    $is_locacao = (strtoupper($item['status']) === 'LOCAÇÃO');
                    $meses_locacao = 1; 

                    if ($is_locacao && !empty($item['meses_locacao'])) {
                        $meses_locacao = (int) $item['meses_locacao'];
                    } elseif ($is_locacao && empty($meses_locacao)) {
                        $meses_locacao = 1; 
                    }

                    if (!empty($item['parametros']) && is_array($item['parametros'])) {
                        foreach ($item['parametros'] as $param) {
                            if (($param['nome'] ?? '') === '__meses_locacao') continue; 
                            $valor_limpo = str_replace(',', '.', preg_replace('/[^\d,]/', '', $param['valor'] ?? '0'));
                            $valor_parametros += (float) $valor_limpo;
                            $visible_params[] = $param;
                        }
                    }
                    
                    $valor_unitario_total = $valor_unitario_base + $valor_parametros;
                    $quantidade = (int) ($item['quantidade'] ?? 1);
                    $multiplicador = $is_locacao ? $meses_locacao : 1;
                    $subtotal = $valor_unitario_total * $quantidade * $multiplicador;
                    $total_geral += $subtotal;
                    $unidade_medida = $is_locacao ? 'Mês' : ($item['unidade_medida'] ?: 'Unidade');
                ?>
                <tr class="break-inside-avoid">
                    <td class="text-center align-middle">
                        <img src="<?php echo htmlspecialchars($item['imagem_url'] ?: 'https://placehold.co/40x40/e2e8f0/64748b?text=IMG'); ?>" class="w-10 h-10 object-contain mx-auto" onerror="this.onerror=null;this.src='https://placehold.co/40x40/e2e8f0/64748b?text=IMG'">
                    </td>
                    <td>
                        <div class="font-bold text-[#2e2a78] uppercase text-[8.5pt]"><?php echo htmlspecialchars($item['descricao']); ?></div>
                        <div class="text-[7.5pt] uppercase text-slate-500 font-semibold"><?php echo htmlspecialchars($item['fabricante'] . ' - ' . $item['modelo']); ?></div>
                        <div class="text-[7.5pt] text-slate-500 italic mt-1">
                            <?php echo nl2br(htmlspecialchars($item['descricao_detalhada'])); ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="text-[7pt] font-semibold text-slate-600 uppercase"><?php echo $is_locacao ? 'LOCAÇÃO' : 'VENDA'; ?></div>
                        <?php if ($is_locacao): ?>
                            <div class="text-[7pt] text-slate-400"><?php echo $meses_locacao; ?> MESES</div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo htmlspecialchars($unidade_medida); ?></td>
                    <td class="text-center font-bold text-slate-700"><?php echo htmlspecialchars($quantidade); ?></td>
                    <!-- MAIN ROW: Base Price and Base Subtotal -->
                    <td class="text-right whitespace-nowrap text-slate-600"><?php echo format_currency($valor_unitario_base); ?></td>
                    <td class="text-right font-bold text-slate-600 whitespace-nowrap"><?php echo format_currency($valor_unitario_base * $quantidade * ($is_locacao ? $meses_locacao : 1)); ?></td>
                </tr>
                <?php if (!empty($visible_params)): ?>
                    <!-- ROW: Params Header -->
                    <tr class="bg-[#F3F4F6] print:bg-[#F3F4F6]" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <td class="py-1"></td> <!-- Img col spacing -->
                        <td colspan="4" class="py-1 pl-2 text-[8.5pt] font-bold text-gray-900 uppercase leading-none border-t border-l border-gray-300 rounded-tl-md">
                            PARAMETROS ADICIONAIS
                        </td>
                        <td class="py-1 border-t border-r border-gray-300 rounded-tr-md"></td> <!-- Price col -->
                        <td></td> <!-- Subtotal col (Outside box) -->
                    </tr>
                    <!-- ROWS: Params Items -->
                    <?php 
                    $total_params = count($visible_params);
                    $counter = 0;
                    foreach ($visible_params as $param): 
                        $counter++;
                        $val_str = $param['valor'] ?? '0';
                        $val_clean = str_replace(',', '.', preg_replace('/[^\d,]/', '', $val_str));
                        $val_float = (float) $val_clean;
                    ?>
                    <tr class="bg-[#F3F4F6] print:bg-[#F3F4F6]" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <td></td>
                        <td colspan="4" class="pl-2 pr-2 text-[8pt] text-gray-800 border-l border-gray-300">
                            <div class="flex items-end">
                                <span class="uppercase font-semibold leading-tight pr-1 whitespace-nowrap"><?php echo htmlspecialchars($param['nome']); ?></span>
                                <div class="flex-grow border-b-[1.5px] border-dotted border-gray-400 mb-[4px] opacity-70"></div>
                            </div>
                        </td>
                        <td class="text-right whitespace-nowrap text-[8pt] font-bold text-gray-800 align-bottom pb-1 border-r border-gray-300">
                            <?php echo format_currency($val_float); ?>
                        </td>
                        <td></td> <!-- Subtotal col (Outside box) -->
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- ROW: Footer (Total Final) -->
                    <tr class="bg-[#D1D5DB] print:bg-[#D1D5DB]" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
                         <td></td>
                         <td colspan="4" class="py-1 pl-2 text-right border-l border-b border-gray-400 rounded-bl-md">
                             <span class="text-[8.5pt] font-black text-gray-900 uppercase">VALOR UNITÁRIO FINAL (Base + Adicionais):</span>
                         </td>
                         <td class="py-1 text-right whitespace-nowrap text-[9pt] font-black text-gray-900 border-b border-gray-400">
                             <?php echo format_currency($valor_unitario_total); ?>
                         </td>
                         <td class="py-1 pr-2 text-right whitespace-nowrap text-[9pt] font-black text-red-600 border-t border-r border-b border-gray-400 rounded-br-md rounded-tr-md">
                             <?php  // Final Subtotal
                                $final_subtotal = $valor_unitario_total * $quantidade * ($is_locacao ? $meses_locacao : 1);
                                echo format_currency($final_subtotal); 
                             ?>
                         </td>
                    </tr>

                    <!-- Spacer Row to separate from next item -->
                    <tr><td colspan="7" class="h-2"></td></tr>
                <?php endif; ?>
                <?php endforeach; ?>
                <!-- Total -->
                <tr class="bg-gray-100 border-t border-gray-200 break-inside-avoid">
                        <td colspan="6" class="text-right py-2 px-4 text-[8.5pt] uppercase font-bold text-slate-600">Valor Total Geral</td>
                        <td class="text-right py-2 px-4 text-[10pt] font-bold text-[#2e2a78] whitespace-nowrap"><?php echo format_currency($total_geral); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Conditions -->
        <div class="mb-8 text-[7.5pt] text-slate-700 break-inside-avoid">
            <h3 class="font-bold text-[#2e2a78] text-[9pt] mb-3 border-b border-gray-200 pb-1">Condições Gerais de Fornecimento</h3>
            <ol class="list-decimal list-inside space-y-1 marker:text-slate-500">
                <li><span class="font-semibold">Faturamento:</span> <?php echo htmlspecialchars($proposal['faturamento'] ?: '-'); ?></li>
                <li><span class="font-semibold">Treinamento:</span> <?php echo htmlspecialchars($proposal['treinamento'] ?: '-'); ?></li>
                <li><span class="font-semibold">Condições de Pagamento:</span> <?php echo htmlspecialchars($proposal['condicoes_pagamento'] ?: '-'); ?></li>
                <li><span class="font-semibold">Prazo de Entrega:</span> <?php echo htmlspecialchars($proposal['prazo_entrega'] ?: '-'); ?></li>
                <li><span class="font-semibold">Garantia dos Equipamentos:</span> <?php echo htmlspecialchars($proposal['garantia_equipamentos'] ?: '-'); ?></li>
                <li><span class="font-semibold">Garantia dos Acessórios:</span> <?php echo htmlspecialchars($proposal['garantia_acessorios'] ?: '-'); ?></li>
                <li><span class="font-semibold">Instalação:</span> <?php echo htmlspecialchars($proposal['instalacao'] ?: '-'); ?></li>
                <li><span class="font-semibold">Assistência Técnica:</span> <?php echo htmlspecialchars($proposal['assistencia_tecnica'] ?: '-'); ?></li>
            </ol>
        </div>

        <!-- Observations -->
        <?php if (!empty($proposal['observacoes'])): ?>
            <div class="mb-8 text-[7.5pt] break-inside-avoid">
                <h3 class="font-bold text-[#2e2a78] text-[9pt] mb-2">Observações</h3>
                <p class="text-slate-600"><?php echo nl2br(htmlspecialchars($proposal['observacoes'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Signatures (Grid can be tricky, using Flex instead for better safety if needed, but break-inside-avoid helps) -->
        <div class="grid grid-cols-2 gap-10 mt-8 pt-4 break-inside-avoid text-[8pt]">
            <div>
                    <p class="font-bold text-[#2e2a78] text-[8pt] mb-1"><?php echo htmlspecialchars($proposal['vendedor_nome']); ?></p>
                    <p class="uppercase text-slate-500 text-[6pt] mb-1"><?php echo htmlspecialchars($proposal['vendedor_role']); ?></p>
                    <p class="text-slate-600">Fone: <?php echo htmlspecialchars($proposal['vendedor_telefone']); ?></p>
                    <p class="text-slate-600">E-mail: <?php echo htmlspecialchars($proposal['vendedor_email']); ?></p>
            </div>
            <div class="flex flex-col items-end">
                    <div class="border-b border-black w-full mb-2"></div>
                    <div class="w-full flex justify-between items-center text-slate-600">
                        <span>Data: ____/____/________</span>
                        <span class="font-bold text-slate-800">De Acordo</span>
                    </div>
            </div>
        </div>
    </div>

    <!-- Paged.js Auto-Runner (No custom script needed, purely declarative) -->
    <!-- We removed specific JS for preview() because the Polyfill auto-runs on DOMContentLoaded if not configured otherwise -->

</body>
</html>
