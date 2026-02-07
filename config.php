<?php
// config.php




/**
 * Arquivo de configuração do banco de dados.
 * Define as constantes usadas para a conexão com o MySQL.
 */

/**
 * Cria um hash seguro de uma senha usando o algoritmo padrão do PHP.
 * Esta é a maneira mais recomendada e segura de armazenar senhas.
 *
 * @param string $password A senha em texto simples.
 * @return string A senha criptografada (hash).
 */
function hashPassword($password)
{
    // PASSWORD_DEFAULT usa o algoritmo mais forte disponível na sua versão do PHP
    // e é atualizado automaticamente em futuras versões.
    return password_hash($password, PASSWORD_DEFAULT);
}


/**
 * Carrega variáveis de ambiente de um arquivo .env
 *
 * @param string $path Caminho para o arquivo .env
 */
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Carrega o arquivo .env da raiz
loadEnv(__DIR__ . '/.env');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'u540193243_crmfr_db');
define('DB_USER', getenv('DB_USER') ?: 'u540193243_crmFR');
define('DB_PASS', getenv('DB_PASS'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
