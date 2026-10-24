<?php
require_once __DIR__ . '/includes/functions.php';
echo 'PHP ' . PHP_VERSION . '<br>';
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'OK' : 'FALTANDO (usando mysqli)') . '<br>';
echo 'mysqli: ' . (extension_loaded('mysqli') ? 'OK' : 'FALTANDO') . '<br>';
echo 'mbstring: ' . (extension_loaded('mbstring') ? 'OK' : 'FALTANDO') . '<br>';
$n = db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
echo 'Conexão com o banco: OK — produtos cadastrados: ' . (int)$n;
