<?php
// Conexão única. Usa PDO quando o driver pdo_mysql existe;
// senão usa a camada mysqli (includes/mysqli_compat.php).
function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            if (extension_loaded('pdo_mysql')) {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ));
                $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
            } else {
                require_once dirname(__FILE__) . '/mysqli_compat.php';
                $pdo = new MyDb(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            }
        } catch (Exception $e) {
            http_response_code(500);
            die('Não foi possível conectar ao banco. Confira o config.php (porta, usuário e senha).'
                . (DEBUG ? '<br><small>' . htmlspecialchars($e->getMessage()) . '</small>' : ''));
        }
    }
    return $pdo;
}
