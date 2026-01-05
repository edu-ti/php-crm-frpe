<?php
// api/handlers/product_handler.php

/**
 * Função para lidar com o upload da imagem de um produto.
 */
function handle_upload_product_image() {
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array(strtolower($filetype), $allowed)) {
            json_response(['success' => false, 'error' => 'Tipo de ficheiro inválido.'], 400);
            return;
        }

        $upload_dir = 'uploads/products/';
        $base_path = dirname(__DIR__, 2); // Raiz do projeto
        $destination_dir = $base_path . '/' . $upload_dir;

        if (!is_dir($destination_dir)) {
            if (!mkdir($destination_dir, 0777, true)) { // Tenta criar recursivamente
                json_response(['success' => false, 'error' => 'Falha ao criar o diretório de uploads. Verifique as permissões.'], 500);
                return;
            }
        }

        $new_filename = uniqid('prod_') . '.' . strtolower($filetype);
        $destination_path = $destination_dir . $new_filename;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination_path)) {
             $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
             // Obtém o diretório base do script atual (api.php) e remove o '/api'
             $script_dir = dirname($_SERVER['SCRIPT_NAME']);
             $base_url_path = rtrim(str_replace('/api', '', $script_dir), '/'); // Remove /api se existir
             // Constrói a URL completa
             $url = $protocol . $_SERVER['HTTP_HOST'] . $base_url_path . '/' . $upload_dir . $new_filename;
             
             json_response(['success' => true, 'url' => $url]);
             return;
        } else {
            json_response(['success' => false, 'error' => 'Falha ao mover o ficheiro.'], 500);
            return;
        }
    }
    json_response(['success' => false, 'error' => 'Nenhum ficheiro enviado ou erro no upload.'], 400);
}

/**
 * Função para criar um novo produto no catálogo.
 * REMOVIDA a lógica de 'parametros'.
 */
function handle_create_product($pdo, $data) {
    $required = ['nome_produto'];
    foreach($required as $field) {
        if(!isset($data[$field])) {
            json_response(['success' => false, 'error' => "Campo obrigatório ausente: {$field}"], 400);
            return;
        }
    }
    
    // --- CORREÇÃO: Permite valor 0.00 ---
    if(!isset($data['valor_unitario']) || !is_numeric($data['valor_unitario'])) {
         json_response(['success' => false, 'error' => "Valor unitário é obrigatório e deve ser numérico (pode ser 0)."], 400);
         return;
    }
    // --- FIM CORREÇÃO ---

    $sql = "INSERT INTO produtos (nome_produto, fabricante, modelo, descricao_detalhada, valor_unitario, unidade_medida, imagem_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $success = $stmt->execute([
        $data['nome_produto'],
        empty($data['fabricante']) ? null : $data['fabricante'],
        empty($data['modelo']) ? null : $data['modelo'],
        empty($data['descricao_detalhada']) ? null : $data['descricao_detalhada'],
        $data['valor_unitario'], // Agora aceita 0
        empty($data['unidade_medida']) ? 'Unidade' : $data['unidade_medida'],
        empty($data['imagem_url']) ? null : $data['imagem_url'],
    ]);

    if($success) {
        $lastId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$lastId]);
        json_response(['success' => true, 'product' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Erro DB (Create Produto): " . print_r($errorInfo, true));
        json_response(['success' => false, 'error' => 'Falha ao criar o produto.', 'db_error' => $errorInfo[2]], 500);
    }
}

/**
 * Função para atualizar um produto existente no catálogo.
 * REMOVIDA a lógica de 'parametros'.
 */
function handle_update_product($pdo, $data) {
    if(empty($data['id'])) {
        json_response(['success' => false, 'error' => 'ID do produto é obrigatório para atualização.'], 400);
        return;
    }
    
    // --- CORREÇÃO: Permite valor 0.00 ---
    if(!isset($data['valor_unitario']) || !is_numeric($data['valor_unitario'])) {
         json_response(['success' => false, 'error' => "Valor unitário é obrigatório e deve ser numérico (pode ser 0)."], 400);
         return;
    }
    // --- FIM CORREÇÃO ---

    $sql = "UPDATE produtos SET 
                nome_produto = ?, 
                fabricante = ?, 
                modelo = ?, 
                descricao_detalhada = ?, 
                valor_unitario = ?, 
                unidade_medida = ?, 
                imagem_url = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        $data['nome_produto'],
        empty($data['fabricante']) ? null : $data['fabricante'],
        empty($data['modelo']) ? null : $data['modelo'],
        empty($data['descricao_detalhada']) ? null : $data['descricao_detalhada'],
        $data['valor_unitario'], // Agora aceita 0
        empty($data['unidade_medida']) ? 'Unidade' : $data['unidade_medida'],
        empty($data['imagem_url']) ? null : $data['imagem_url'],
        $data['id']
    ]);
    
    if($success) {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$data['id']]);
        json_response(['success' => true, 'product' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Erro DB (Update Produto): " . print_r($errorInfo, true));
        json_response(['success' => false, 'error' => 'Falha ao atualizar o produto.', 'db_error' => $errorInfo[2]], 500);
    }
}

/**
 * Função para excluir um produto do catálogo.
 */
function handle_delete_product($pdo, $data) {
    if(empty($data['id'])) {
        json_response(['success' => false, 'error' => 'ID do produto é obrigatório para exclusão.'], 400);
        return;
    }

    // Primeiro, busca o caminho da imagem para poder excluí-la do servidor.
    $stmt = $pdo->prepare("SELECT imagem_url FROM produtos WHERE id = ?");
    $stmt->execute([$data['id']]);
    $image_url = $stmt->fetchColumn();

    // Depois, exclui o registo do produto da base de dados.
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $success = $stmt->execute([$data['id']]);

    if($success) {
        // Se o produto foi excluído e tinha uma imagem, tenta apagar o ficheiro.
        if ($image_url) {
            // Constrói o caminho local do ficheiro a partir da URL.
             $base_path = dirname(__DIR__, 2); // Raiz do projeto
             $url_path = parse_url($image_url, PHP_URL_PATH);
            
            // Remove o caminho base do script (que pode ser /api ou /)
             $script_dir = dirname($_SERVER['SCRIPT_NAME']);
             $base_url_path = rtrim(str_replace('/api', '', $script_dir), '/');
             
             // Remove o caminho base da URL para obter o caminho relativo ao sistema de arquivos
             $relative_path = ltrim(str_replace($base_url_path, '', $url_path), '/');
             $full_path = $base_path . '/' . $relative_path;
            
            if (file_exists($full_path)) {
                @unlink($full_path); // O "@" suprime erros caso o ficheiro não possa ser apagado.
            } else {
                 error_log("Tentativa de apagar imagem do produto falhou: Arquivo não encontrado em " . $full_path);
            }
        }
        json_response(['success' => true]);
    } else {
        json_response(['success' => false, 'error' => 'Falha ao excluir o produto.'], 500);
    }
}


?>

