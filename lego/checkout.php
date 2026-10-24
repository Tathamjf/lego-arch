<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$pdo = db();
$cid = cart_id($u['id']);
$pageTitle = 'Finalizar compra';

function load_cart_items($pdo, $cid, $lock) {
    $st = $pdo->prepare('SELECT ci.product_id, ci.quantity, p.name, p.price, p.stock_quantity, p.is_published, p.deleted_at,
                                (SELECT url FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS img
                         FROM cart_items ci JOIN products p ON p.id = ci.product_id
                         WHERE ci.cart_id = ? ORDER BY ci.id' . ($lock ? ' FOR UPDATE' : ''));
    $st->execute(array($cid));
    return $st->fetchAll();
}

$f = array('ship_name' => $u['name'], 'ship_zip' => '', 'ship_street' => '', 'ship_number' => '',
           'ship_complement' => '', 'ship_district' => '', 'ship_city' => '', 'ship_state' => '');
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) { $f[$k] = trim(val($_POST, $k)); }
    $f['ship_state'] = strtoupper($f['ship_state']);

    if ($f['ship_name'] === '' || mb_strlen($f['ship_name'], 'UTF-8') > 120) { $errors[] = 'Informe o nome do destinatário.'; }
    if (!preg_match('/^\d{5}-?\d{3}$/', $f['ship_zip'])) { $errors[] = 'CEP inválido (use 00000-000).'; }
    if ($f['ship_street'] === '')   { $errors[] = 'Informe a rua.'; }
    if ($f['ship_number'] === '')   { $errors[] = 'Informe o número.'; }
    if ($f['ship_district'] === '') { $errors[] = 'Informe o bairro.'; }
    if ($f['ship_city'] === '')     { $errors[] = 'Informe a cidade.'; }
    if (!preg_match('/^[A-Z]{2}$/', $f['ship_state'])) { $errors[] = 'UF inválida (ex.: SP).'; }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            // trava as linhas dos produtos para ninguém comprar o mesmo estoque ao mesmo tempo
            $items = load_cart_items($pdo, $cid, true);
            if (!$items) { throw new Exception('Seu carrinho está vazio.'); }

            $subtotal = 0;
            foreach ($items as $i) {
                if (!$i['is_published'] || $i['deleted_at'] !== null) { throw new Exception('"' . $i['name'] . '" não está mais disponível.'); }
                if ((int)$i['quantity'] > (int)$i['stock_quantity']) {
                    throw new Exception('Estoque insuficiente para "' . $i['name'] . '" (disponível: ' . (int)$i['stock_quantity'] . ').');
                }
                $subtotal += round($i['price'] * $i['quantity'], 2);
            }
            $shipping = 0.00; // frete grátis neste projeto básico
            $total = $subtotal + $shipping;

            $pdo->prepare('INSERT INTO orders (user_id, status, subtotal, shipping_cost, total, ship_name, ship_zip, ship_street,
                                               ship_number, ship_complement, ship_district, ship_city, ship_state)
                           VALUES (?, \'pending_payment\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute(array($u['id'], $subtotal, $shipping, $total, $f['ship_name'], $f['ship_zip'], $f['ship_street'],
                                $f['ship_number'], $f['ship_complement'] === '' ? null : $f['ship_complement'],
                                $f['ship_district'], $f['ship_city'], $f['ship_state']));
            $orderId = (int)$pdo->lastInsertId();

            foreach ($items as $i) {
                $qty = (int)$i['quantity'];
                $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                               VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute(array($orderId, $i['product_id'], $i['name'], $i['price'], $qty, round($i['price'] * $qty, 2)));

                // baixa de estoque protegida: só atualiza se ainda houver saldo
                $upd = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
                $upd->execute(array($qty, $i['product_id'], $qty));
                if ($upd->rowCount() !== 1) { throw new Exception('Estoque insuficiente para "' . $i['name'] . '".'); }

                $after = (int)$i['stock_quantity'] - $qty;
                $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity_change, stock_after, order_id, user_id, reason)
                               VALUES (?, 'sale', ?, ?, ?, ?, ?)")
                    ->execute(array($i['product_id'], -$qty, $after, $orderId, $u['id'], 'Venda - pedido #' . $orderId));
            }
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute(array($cid));
            $pdo->commit();
            redirect('pedido_confirmado.php?id=' . $orderId);
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = $ex->getMessage();
        }
    }
}

$items = load_cart_items($pdo, $cid, false);
if (!$items) { flash('info', 'Seu carrinho está vazio.'); redirect('carrinho.php'); }
$total = 0;
foreach ($items as $i) { $total += round($i['price'] * $i['quantity'], 2); }

include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Finalizar compra</h1>
<?php foreach ($errors as $err): ?><div class="alert error"><?php echo e($err); ?></div><?php endforeach; ?>
<div class="cart-layout">
  <div class="box">
    <h2>1. Dados de entrega</h2>
    <form method="post" id="checkoutForm">
      <?php echo csrf_field(); ?>
      <label>Nome completo <input type="text" name="ship_name" value="<?php echo e($f['ship_name']); ?>" required></label>
      <div class="row">
        <label>CEP <input type="text" name="ship_zip" value="<?php echo e($f['ship_zip']); ?>" placeholder="00000-000" required></label>
        <label>Cidade <input type="text" name="ship_city" value="<?php echo e($f['ship_city']); ?>" required></label>
        <label style="max-width:90px">UF <input type="text" name="ship_state" value="<?php echo e($f['ship_state']); ?>" maxlength="2" placeholder="SP" required></label>
      </div>
      <label>Rua <input type="text" name="ship_street" value="<?php echo e($f['ship_street']); ?>" required></label>
      <div class="row">
        <label style="max-width:140px">Número <input type="text" name="ship_number" value="<?php echo e($f['ship_number']); ?>" required></label>
        <label>Complemento <input type="text" name="ship_complement" value="<?php echo e($f['ship_complement']); ?>"></label>
        <label>Bairro <input type="text" name="ship_district" value="<?php echo e($f['ship_district']); ?>" required></label>
      </div>
      <h2 style="margin-top:22px">2. Pagamento</h2>
      <div class="note">Projeto acadêmico: não há cobrança real. O pedido é criado como <strong>aguardando pagamento</strong>.</div>
    </form>
  </div>

  <aside class="summary-card">
    <h2>Resumo do pedido</h2>
    <?php foreach ($items as $i): ?>
      <div class="mini-item">
        <img src="<?php echo e(image_url($i['img'])); ?>" alt="">
        <div><?php echo e($i['name']); ?><br><small class="muted">Qtd.: <?php echo (int)$i['quantity']; ?></small></div>
        <strong><?php echo money(round($i['price'] * $i['quantity'], 2)); ?></strong>
      </div>
    <?php endforeach; ?>
    <div class="sum-row"><span>Subtotal</span><span><?php echo money($total); ?></span></div>
    <div class="sum-row"><span><?php echo icon('truck'); ?> Frete</span><span>Grátis</span></div>
    <div class="sum-row total"><span>Total</span><span><?php echo money($total); ?></span></div>
    <button class="btn block" type="submit" form="checkoutForm">Finalizar compra</button>
    <div class="secure"><?php echo icon('lock'); ?> Seus dados estão seguros</div>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
