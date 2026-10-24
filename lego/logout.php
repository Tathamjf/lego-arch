<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = array();
    session_destroy();
    session_start();
    flash('success', 'Você saiu da conta.');
}
redirect('index.php');
