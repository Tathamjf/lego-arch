<?php
// =====================================================================
// CONFIGURAÇÃO — ajuste conforme o seu ambiente
//
//   UsbWebServer: DB_PORT 3307, usuário root, senha usbw
//   XAMPP:        DB_PORT 3306, usuário root, senha vazia ('')
// =====================================================================
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'lego_architecture_store');
define('DB_USER', 'root');
define('DB_PASS', 'usbw');

define('LOW_STOCK', 5);   // estoque <= isso aparece como "baixo" no admin
define('DEBUG', true);    // true mostra detalhes de erro de conexão (desligue em produção)

define('SITE_NAME', 'LEGO Arch'); // nome exibido no topo e no título das páginas
