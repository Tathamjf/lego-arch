<?php
// =====================================================================
// Criação dos usuários iniciais (admin e cliente de teste).
// Rode UMA vez no navegador (http://localhost:PORTA/lego/instalar.php)
// e DEPOIS APAGUE ESTE ARQUIVO. Só funciona a partir do próprio computador.
// =====================================================================
require_once __DIR__ . '/includes/functions.php';

$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($ip !== '127.0.0.1' && $ip !== '::1') { http_response_code(403); die('Disponível apenas em localhost.'); }

$pdo = db();
$users = array(
    array('Administrador', 'admin@lego.local',   'Admin@123',   'admin'),
    array('Cliente Teste', 'cliente@lego.local', 'Cliente@123', 'customer'),
);
echo '<meta charset="utf-8"><h1>Instalação</h1><ul>';
foreach ($users as $u) {
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute(array($u[1]));
    if ($st->fetch()) { echo '<li>' . htmlspecialchars($u[1]) . ': já existe (nada feito)</li>'; continue; }
    $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute(array($u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3]));
    echo '<li>' . htmlspecialchars($u[1]) . ' criado (senha: ' . htmlspecialchars($u[2]) . ')</li>';
}
echo '</ul><p><strong>Agora apague o arquivo instalar.php</strong> e troque a senha do admin em "Minha conta".</p>';
echo '<p><a href="login.php">Ir para o login</a></p>';
