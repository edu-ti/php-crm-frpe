<?php
// debug_db_connection.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Tenta carregar .env manualmente para ver se o path está correto
echo "DIR: " . __DIR__ . "\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo ".env encontrado.\n";
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        echo "ENV: $name = " . substr($value, 0, 3) . "***\n"; // Mostrar parcial
    }
} else {
    echo ".env NÃO encontrado.\n";
}

try {
    require_once 'config.php';
    if (isset($pdo)) {
        echo "Conexão bem sucedida via config.php!\n";
    }
} catch (Throwable $e) {
    echo "Erro capturado: " . $e->getMessage() . "\n";
}
?>