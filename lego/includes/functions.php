<?php
require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) . '/db.php';

ini_set('session.cookie_httponly', '1');
if (session_id() === '') { session_start(); }

// ---------- caminhos ----------
// Funciona com o projeto em qualquer subpasta (ex.: /lego/) e dentro de /admin
$__dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if (substr($__dir, -6) === '/admin') { $__dir = substr($__dir, 0, -6); }
define('BASE_PATH', $__dir);

function url($p = '') { return BASE_PATH . '/' . ltrim($p, '/'); }
function redirect($p) { header('Location: ' . url($p)); exit; }

function image_url($u) {
    if (!$u) { return url('images/exemplo/placeholder.svg'); }
    if (preg_match('#^https?://#i', $u)) { return $u; }
    return url(ltrim($u, '/'));
}

// ---------- utilidades ----------
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function val($arr, $key, $default = '') { return isset($arr[$key]) ? $arr[$key] : $default; }

function slugify($text) {
    $text = trim($text);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($t !== false) { $text = $t; }
    }
    $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text));
    $text = trim($text, '-');
    return $text === '' ? 'produto' : $text;
}

function diff_label($d) {
    $m = array('beginner' => 'Iniciante', 'intermediate' => 'Intermediário',
               'advanced' => 'Avançado', 'expert' => 'Especialista');
    return isset($m[$d]) ? $m[$d] : $d;
}
function status_label($s) {
    $m = array('pending_payment' => 'Aguardando pagamento', 'paid' => 'Pago',
               'processing' => 'Em separação', 'shipped' => 'Enviado',
               'delivered' => 'Entregue', 'cancelled' => 'Cancelado');
    return isset($m[$s]) ? $m[$s] : $s;
}
function movement_label($t) {
    $m = array('entry' => 'Entrada', 'sale' => 'Venda', 'adjustment' => 'Ajuste',
               'cancellation_return' => 'Devolução (cancelamento)');
    return isset($m[$t]) ? $m[$t] : $t;
}

// ---------- mensagens (flash) ----------
function flash($type, $msg) { $_SESSION['flash'][] = array($type, $msg); }
function get_flashes() {
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(function_exists('random_bytes') ? random_bytes(16) : openssl_random_pseudo_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    $sent = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!hash_equals(csrf_token(), (string)$sent)) {
        http_response_code(400);
        die('Requisição inválida (token CSRF). Volte e tente novamente.');
    }
}

// ---------- autenticação ----------
function current_user() {
    static $user = false;
    if ($user !== false) { return $user; }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        $st = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND is_active = 1');
        $st->execute(array((int)$_SESSION['user_id']));
        $row = $st->fetch();
        if ($row) { $user = $row; } else { unset($_SESSION['user_id']); }
    }
    return $user;
}
function require_login() {
    $u = current_user();
    if (!$u) {
        flash('error', 'Entre na sua conta para continuar.');
        redirect('login.php');
    }
    return $u;
}
function require_admin() {
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        die('Acesso restrito a administradores.');
    }
    return $u;
}
function login_user($id) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$id;
}

// ---------- carrinho ----------
function cart_id($userId) {
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM carts WHERE user_id = ?');
    $st->execute(array($userId));
    $row = $st->fetch();
    if ($row) { return (int)$row['id']; }
    $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)')->execute(array($userId));
    return (int)$pdo->lastInsertId();
}
function cart_count($userId) {
    $st = db()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci
                         JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?');
    $st->execute(array($userId));
    return (int)$st->fetchColumn();
}

// ---------- pedidos ----------
// Cancela o pedido e devolve os itens ao estoque (transação).
// $onlyUserId: restringe ao dono do pedido (cliente). Retorna true ou mensagem de erro.
function cancel_order($orderId, $actorId, $onlyUserId = null) {
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT id, user_id, status FROM orders WHERE id = ? FOR UPDATE');
        $st->execute(array($orderId));
        $o = $st->fetch();
        if (!$o || ($onlyUserId !== null && (int)$o['user_id'] !== (int)$onlyUserId)) {
            $pdo->rollBack(); return 'Pedido não encontrado.';
        }
        if ($o['status'] === 'cancelled') { $pdo->rollBack(); return 'Este pedido já está cancelado.'; }
        if ($o['status'] === 'shipped' || $o['status'] === 'delivered') {
            $pdo->rollBack(); return 'Pedidos enviados ou entregues não podem ser cancelados.';
        }
        $it = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
        $it->execute(array($orderId));
        foreach ($it->fetchAll() as $row) {
            $lk = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
            $lk->execute(array($row['product_id']));
            $stock = (int)$lk->fetchColumn();
            $new = $stock + (int)$row['quantity'];
            $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?')->execute(array($new, $row['product_id']));
            $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity_change, stock_after, order_id, user_id, reason)
                           VALUES (?, 'cancellation_return', ?, ?, ?, ?, ?)")
                ->execute(array($row['product_id'], (int)$row['quantity'], $new, $orderId, $actorId, 'Cancelamento do pedido #' . $orderId));
        }
        $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?")->execute(array($orderId));
        $pdo->commit();
        return true;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return 'Erro ao cancelar o pedido.' . (DEBUG ? ' ' . $ex->getMessage() : '');
    }
}


// ---------- visual ----------
function icon($name) {
    $paths = array(
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'cart'   => '<path d="M3 4h2l2.4 11h10.2L20 8H6.2"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'trash'  => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'plus'   => '<path d="M12 5v14M5 12h14"/>',
        'minus'  => '<path d="M5 12h14"/>',
        'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrowl' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'check'  => '<path d="M5 12l5 5 9-10"/>',
        'lock'   => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
        'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'box'    => '<path d="M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10"/>',
        'logout' => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        'truck'  => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/>',
    );
    $p = isset($paths[$name]) ? $paths[$name] : '';
    return '<svg class="ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
function brand_icon() {
    return '<svg class="brand-ico" viewBox="0 0 32 32" width="30" height="30" aria-hidden="true">'
         . '<rect x="4" y="13" width="24" height="14" rx="3" fill="#f5f2eb"/>'
         . '<rect x="8" y="8" width="6" height="6" rx="2" fill="#f5f2eb"/><rect x="18" y="8" width="6" height="6" rx="2" fill="#f5f2eb"/>'
         . '<path d="M4 19h24" stroke="#0c111c" stroke-width="1.2" opacity=".25"/></svg>';
}
function order_no($id) { return '#' . str_pad((string)(int)$id, 6, '0', STR_PAD_LEFT); }
function status_pill($s) { return '<span class="pill st-' . e($s) . '">' . e(status_label($s)) . '</span>'; }
