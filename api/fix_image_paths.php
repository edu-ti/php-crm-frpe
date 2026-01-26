<?php
// api/fix_image_paths.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/core/Database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Correção de Caminhos de Imagem</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // 1. Corrigir Produtos
    echo "<h2>Produtos</h2>";
    $stmt = $pdo->query("SELECT id, imagem_url FROM produtos WHERE imagem_url IS NOT NULL AND imagem_url != ''");
    $products = $stmt->fetchAll();

    foreach ($products as $p) {
        $old_url = $p['imagem_url'];

        // Se já for relativo ou blob, ignora (ou ajusta conforme necessidade)
        // A lógica aqui é transformar URLs absolutas (http://...) em relativas (uploads/...)
        // se elas de fato apontarem para este servidor.

        // Exemplo: http://192.168.1.10/php-crm-frpe/uploads/products/prod_123.jpg
        // Vira: uploads/products/prod_123.jpg

        if (strpos($old_url, 'uploads/') !== false) {
            // Pega tudo a partir de 'uploads/'
            $new_url = substr($old_url, strpos($old_url, 'uploads/'));

            if ($old_url !== $new_url) {
                $update = $pdo->prepare("UPDATE produtos SET imagem_url = ? WHERE id = ?");
                $update->execute([$new_url, $p['id']]);
                echo "<p>Prod ID {$p['id']}: <br>De: $old_url <br>Para: <b>$new_url</b></p>";
            } else {
                echo "<p style='color:gray'>Prod ID {$p['id']}: Já relativo ou não alterado ($old_url)</p>";
            }
        }
    }

    // 2. Corrigir Itens de Proposta
    echo "<h2>Itens de Proposta</h2>";
    $stmt = $pdo->query("SELECT id, imagem_url FROM proposta_itens WHERE imagem_url IS NOT NULL AND imagem_url != ''");
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $old_url = $item['imagem_url'];

        if (strpos($old_url, 'uploads/') !== false) {
            $new_url = substr($old_url, strpos($old_url, 'uploads/'));

            if ($old_url !== $new_url) {
                $update = $pdo->prepare("UPDATE proposta_itens SET imagem_url = ? WHERE id = ?");
                $update->execute([$new_url, $item['id']]);
                echo "<p>Item ID {$item['id']}: <br>De: $old_url <br>Para: <b>$new_url</b></p>";
            }
        }
    }

    echo "<p style='color: green;'>Processo concluído.</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>