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


define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u540193243_crmfr_db');
define('DB_USER', 'u540193243_crmFR');
define('DB_PASS', 'g3st@0crmFR'); // Verifique se esta senha está correta.
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
