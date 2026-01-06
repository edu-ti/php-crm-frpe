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
    'name' => 'FR REPRESENTAÇÕES E COMERCIO DE PRODUTOS MÉDICOS LTDA',
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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Arial');
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f4f6;
        }
        .page {
            width: 21cm;
            min-height: 29.7cm;
            display: flex;
            flex-direction: column;
            margin: 1cm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 1cm;
            font-size: 8pt;
        }
        main {
            flex-grow: 1;
        }
        .bordered-table th, .bordered-table td {
            border: 0.5px solid #a1a1aa;
        }
        .text-header {
            font-size: 6.5pt;
            line-height: 1.2;
        }
        
        @media print {
            body { background: white; }
            .no-print { display: none; }
            .page {
                width: 100%;
                min-height: 29.7cm;
                margin: 0;
                box-shadow: none;
                padding: 1.5cm;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="no-print p-4 bg-gray-800 text-white text-center">
        <p>A janela de impressão deve abrir automaticamente. Caso contrário, pressione Ctrl+P.</p>
        <button onclick="window.print()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            Imprimir
        </button>
    </div>

    <div class="page">
        <header>
            <div class="flex items-center mb-2">
                <div class="w-1/4">
                    <img src="imagens/LOGO-FR.webp" alt="Logo FR" class="h-16">
                </div>
                <div class="w-3/4 text-center text-header">
                    <p class="font-bold text-sm"><?php echo htmlspecialchars($company_info['name']); ?></p>
                    <p>CNPJ: <?php echo htmlspecialchars($company_info['cnpj']); ?> / FONE: <?php echo htmlspecialchars($company_info['phone']); ?></p>
                    <p><?php echo htmlspecialchars($company_info['address']); ?></p>
                    <p><?php echo htmlspecialchars($company_info['site']); ?> / <?php echo htmlspecialchars($company_info['email']); ?> / <?php echo htmlspecialchars($company_info['social']); ?></p>
                </div>
            </div>
            <div class="text-center py-1">
                <h1 class="text-lg font-bold">Proposta Comercial</h1>
            </div>
             <div class="flex justify-end items-center">
                <div class="text-right">
                    <p class="text-xs">Página | 1</p>
                    <p class="font-bold">N° <?php echo htmlspecialchars($proposal['numero_proposta']); ?></p>
                    <p class="text-xs">Data Emissão: <?php echo format_date($proposal['data_criacao']); ?></p>
                    <p class="text-xs">Validade: <?php echo format_date($proposal['data_validade']); ?></p>
                </div>
            </div>
        </header>

        <main class="mt-4">
            <div class="border border-gray-400 p-2 text-xs rounded-lg">
                <p><span class="font-bold">Cliente:</span> <?php echo htmlspecialchars($proposal['organizacao_nome'] ?: $proposal['cliente_pf_nome']); ?></p>
                <p><span class="font-bold">CNPJ:</span> <?php echo htmlspecialchars($proposal['cnpj'] ?: $proposal['cpf']); ?></p>
                <p><span class="font-bold">Endereço:</span> <?php echo htmlspecialchars($proposal['logradouro'] . ', ' . $proposal['org_numero'] . ' - ' . $proposal['org_bairro'] . ' - ' . $proposal['org_cidade'] . '/' . $proposal['org_estado'] . ' CEP: ' . $proposal['org_cep']); ?></p>
                <p><span class="font-bold">Contato:</span> <?php echo htmlspecialchars($proposal['contato_nome'] ?: 'N/A'); ?></p>
                <p><span class="font-bold">Fone:</span> <?php echo htmlspecialchars($proposal['contato_telefone'] ?: 'N/A'); ?> | <span class="font-bold">E-mail:</span> <?php echo htmlspecialchars($proposal['contato_email'] ?: 'N/A'); ?></p>
            </div>

            <div class="mt-2 text-xs">
                <p>Prezados (as),</p>
                <p class="mt-1">A FR Produtos Médicos agradece seu interesse em nossos produtos e serviços. Sabemos da sua importância em sempre oferecer a mais alta tecnologia para a melhor e mais rápida recuperação do paciente e também em oferecer segurança aos profissionais da saúde.</p>
            </div>
            
            <div class="mt-2">
                <table class="w-full text-xs bordered-table rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-1 font-bold">Imagem</th>
                            <th class="p-1 font-bold">Descrição</th>
                            <th class="p-1 font-bold text-center">Estado</th> 
                            <th class="p-1 font-bold">Unid. de Medida</th>
                            <th class="p-1 font-bold">Qtd</th>
                            <th class="p-1 font-bold">Vlr. Unit.</th>
                            <th class="p-1 font-bold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($proposal['items'] as $item): 
                            $valor_unitario_base = (float) ($item['valor_unitario'] ?? 0);
                            $valor_parametros = 0;
                            
                            $visible_params = [];
                            $is_locacao = (strtoupper($item['status']) === 'LOCAÇÃO');
                            $meses_locacao = 1; 

                            // Use column value preferentially
                            if ($is_locacao && !empty($item['meses_locacao'])) {
                                $meses_locacao = (int) $item['meses_locacao'];
                            } elseif ($is_locacao) {
                                 // Fallback to 1 (or 12?) if not set. Default DB is 1. Previous JS default was 12.
                                 // Let's use 1 if column is null/zero, but mostly it should be set.
                                 if (empty($meses_locacao)) $meses_locacao = 1; 
                            }

                            if (!empty($item['parametros']) && is_array($item['parametros'])) {
                                foreach ($item['parametros'] as $param) {
                                    // Hide hidden params
                                    if (($param['nome'] ?? '') === '__meses_locacao') {
                                        // Legacy fallback if needed, but we trust column now
                                        continue; 
                                    }
                                    
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
                            
                            // Formata o status para exibição
                             $status_display = $is_locacao 
                                ? "LOCAÇÃO<br><span style='font-size: 7pt;'>{$meses_locacao} MESES</span>" 
                                : "Vendas";
                        ?>
                        <tr>
                            <td class="p-1 align-middle text-center"><img src="<?php echo htmlspecialchars($item['imagem_url'] ?: 'https://placehold.co/80x80/e2e8f0/64748b?text=Sem+Img'); ?>" class="w-16 h-16 object-contain inline-block" onerror="this.onerror=null;this.src='https://placehold.co/80x80/e2e8f0/64748b?text=Erro'"></td>
                            <td class="p-1 align-middle text-center">
                                <p class="font-bold"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                <p><?php echo htmlspecialchars($item['fabricante'] . ' - ' . $item['modelo']); ?></p>
                                <div class="mt-1"><?php echo nl2br(htmlspecialchars($item['descricao_detalhada'])); ?></div>

                                <!-- --- INÍCIO DA MODIFICAÇÃO: Exibir Parâmetros (Estilo da Imagem) --- -->
                                <?php if (!empty($visible_params)): ?>
                                    <div class="mt-2 p-1 max-w-xs mx-auto">
                                        <p class="font-bold text-center text-[7pt]">PARÂMETROS ADICIONAIS</p>
                                        <p class="text-center text-[7pt] font-medium">
                                            <?php
                                            $param_names = [];
                                            foreach ($visible_params as $param) {
                                                // Exibe apenas o nome, como na imagem "Layout.png"
                                                $param_names[] = htmlspecialchars($param['nome']);
                                            }
                                            echo implode(' | ', $param_names);
                                            ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <!-- --- FIM DA MODIFICAÇÃO --- -->
                            </td>
                            <td class="p-1 text-center align-middle"><?php echo $status_display; ?></td>
                            <td class="p-1 text-center align-middle"><?php echo htmlspecialchars($unidade_medida); ?></td>
                            <td class="p-1 text-center align-middle"><?php echo htmlspecialchars($quantidade); ?></td>
                            <td class="p-1 text-right align-middle whitespace-nowrap"><?php echo format_currency($valor_unitario_total); ?></td> <!-- Alterado para valor unitário total -->
                            <td class="p-1 text-right align-middle font-bold whitespace-nowrap"><?php echo format_currency($subtotal); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="6" class="p-1 text-right font-bold border-l-0 border-b-0">Valor Total Geral</td>
                            <td class="p-1 text-right font-bold bg-gray-200 whitespace-nowrap"><?php echo format_currency($total_geral); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                 <div class="p-2">
                    <h2 class="text-sm font-bold text-center">Condições Gerais de Fornecimento:</h2>
                    <div class="text-xs space-y-1 mt-2">
                        <p><strong>1. Faturamento:</strong> <?php echo htmlspecialchars($proposal['faturamento']); ?></p>
                        <p><strong>2. Treinamento:</strong> <?php echo htmlspecialchars($proposal['treinamento']); ?></p>
                        <p><strong>3. Condições de pagamento:</strong> <?php echo htmlspecialchars($proposal['condicoes_pagamento']); ?></p>
                        <p><strong>4. Prazo de entrega:</strong> <?php echo htmlspecialchars($proposal['prazo_entrega']); ?></p>
                        <p><strong>5. Garantia dos equipamentos:</strong> <?php echo htmlspecialchars($proposal['garantia_equipamentos']); ?></p>
                        <p><strong>6. Garantia dos acessórios:</strong> <?php echo htmlspecialchars($proposal['garantia_acessorios']); ?></p>
                        <p><strong>7. Instalação:</strong> <?php echo htmlspecialchars($proposal['instalacao']); ?></p>
                        <p><strong>8. Assistência técnica:</strong> <?php echo htmlspecialchars($proposal['assistencia_tecnica']); ?></p>
                    </div>
                    <div class="mt-2 text-xs">
                        <h3 class="font-bold">Observações:</h3>
                        <p><?php echo nl2br(htmlspecialchars($proposal['observacoes'])); ?></p>
                    </div>
                </div>
            </div>
             <div class="flex-grow"></div>
             <div class="pt-10 grid grid-cols-2 gap-8 text-center text-xs">
                <div class="pt-2">
                    <p class="border-t border-black pt-1 font-bold w-2/3 mx-auto"><?php echo htmlspecialchars($proposal['vendedor_nome']); ?></p>
                    <p><?php echo htmlspecialchars($proposal['vendedor_role']); ?></p>
                    <p>Fone: <?php echo htmlspecialchars($proposal['vendedor_telefone']); ?></p>
                    <p>E-mail: <?php echo htmlspecialchars($proposal['vendedor_email']); ?></p>
                </div>
                <div class="pt-2">
                    <p class="border-t border-black pt-1 w-2/3 mx-auto">De Acordo:</p>
                    <p class="mt-4">Data: ____/____/________</p>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        window.onload = () => {
            // Remove o delay, imprime imediatamente
            window.print();
        };
    </script>
</body>
</html>
