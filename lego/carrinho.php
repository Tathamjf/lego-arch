<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$pdo = db();
$cid = cart_id($u['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = val($_POST, 'action');

    if ($action === 'add') {
        $pid = (int)val($_POST, 'product_id', 0);
        $qty = max(1, (int)val($_POST, 'quantity', 1));
        $st = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? AND is_published = 1 AND deleted_at IS NULL');
        $st->execute(array($pid));
        $p = $st->fetch();
        if (!$p) {
            flash('error', 'Produto indisponível.');
        } elseif ((int)$p['stock_quantity'] < 1) {
            flash('error', 'Produto esgotado.');
        } else {
            $stock = (int)$p['stock_quantity'];
            $st = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
            $st->execute(array($cid, $pid));
            $cur = $st->fetch();
            $wanted = ($cur ? (int)$cur['quantity'] : 0) + $qty;
            $new = min($stock, $wanted);
            if ($cur) {
                $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute(array($new, $cur['id']));
            } else {
                $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)')
                    ->execute(array($cid, $pid, $new));
            }
            flash('success', 'Produto adicionado ao carrinho.');
            if ($new < $wanted) { flash('info', 'A quantidade foi limitada ao estoque disponível (' . $stock . ').'); }
        }
    } elseif ($action === 'update') {
        $iid = (int)val($_POST, 'item_id', 0);
        $qty = (int)val($_POST, 'quantity', 0);
        if ($qty <= 0) {
            $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')->execute(array($iid, $cid));
            flash('success', 'Item removido.');
        } else {
            $st = $pdo->prepare('SELECT p.stock_quantity FROM cart_items ci JOIN products p ON p.id = ci.product_id
                                 WHERE ci.id = ? AND ci.cart_id = ?');
            $st->execute(array($iid, $cid));
            $row = $st->fetch();
            if ($row) {
                $stock = (int)$row['stock_quantity'];
                $new = min($qty, $stock);
                $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?')->execute(array(max(1, $new), $iid, $cid));
                if ($new < $qty) { flash('info', 'Quantidade limitada ao estoque disponível (' . $stock . ').'); }
                else { flash('success', 'Quantidade atualizada.'); }
            }
        }
    } elseif ($action === 'remove') {
        $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')->execute(array((int)val($_POST, 'item_id', 0), $cid));
        flash('success', 'Item removido.');
    } elseif ($action === 'clear') {
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute(array($cid));
        flash('success', 'Carrinho esvaziado.');
    }
    redirect('carrinho.php');
}

$st = $pdo->prepare('SELECT ci.id AS item_id, ci.quantity, p.id AS product_id, p.name, p.price, p.stock_quantity,
                            p.is_published, p.deleted_at,
                            (SELECT url FROM product_images i WHERE i.product_id = p.id
                             ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS img
                     FROM cart_items ci JOIN products p ON p.id = ci.product_id
                     WHERE ci.cart_id = ? ORDER BY ci.id');
$st->execute(array($cid));
$items = $st->fetchAll();

$total = 0; $problem = false;
foreach ($items as $i) {
    $total += round($i['price'] * $i['quantity'], 2);
    if ((int)$i['quantity'] > (int)$i['stock_quantity'] || !$i['is_published'] || $i['deleted_at'] !== null) { $problem = true; }
}
$pageTitle = 'Carrinho';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Seu carrinho</h1>
<?php if (!$items): ?>
  <div class="box"><p class="muted">Seu carrinho está vazio.</p><a class="btn" href="<?php echo url('index.php#catalogo'); ?>">Ver projetos</a></div>
<?php else: ?>
<div class="cart-layout">
  <div class="cart-list">
    <?php foreach ($items as $i): $bad = (int)$i['quantity'] > (int)$i['stock_quantity']; ?>
      <div class="cart-item">
        <img src="<?php echo e(image_url($i['img'])); ?>" alt="">
        <div>
          <a class="name" href="<?php echo url('produto.php?id=' . (int)$i['product_id']); ?>"><?php echo e($i['name']); ?></a>
          <div class="price"><?php echo money($i['price']); ?></div>
          <?php if ($bad): ?><span class="badge out">Estoque disponível: <?php echo (int)$i['stock_quantity']; ?></span><?php endif; ?>
        </div>
        <form method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="item_id" value="<?php echo (int)$i['item_id']; ?>">
          <div class="stepper" data-autosubmit>
            <button type="button" data-step="-1" aria-label="Diminuir"><?php echo icon('minus'); ?></button>
            <input type="number" name="quantity" value="<?php echo (int)$i['quantity']; ?>" min="1" max="<?php echo max(1, (int)$i['stock_quantity']); ?>">
            <button type="button" data-step="1" aria-label="Aumentar"><?php echo icon('plus'); ?></button>
          </div>
          <noscript><button class="btn small" type="submit">OK</button></noscript>
        </form>
        <form method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="remove">
          <input type="hidden" name="item_id" value="<?php echo (int)$i['item_id']; ?>">
          <button class="iconbtn dark" type="submit" title="Remover"><?php echo icon('trash'); ?></button>
        </form>
      </div>
    <?php endforeach; ?>
    <form method="post">
      <?php echo csrf_field(); ?><input type="hidden" name="action" value="clear">
      <button class="linklike muted" type="submit">Esvaziar carrinho</button>
    </form>
  </div>

  <aside class="summary-card">
    <h2>Resumo do pedido</h2>
    <div class="sum-row"><span>Subtotal</span><span><?php echo money($total); ?></span></div>
    <div class="sum-row"><span><?php echo icon('truck'); ?> Frete</span><span>Grátis</span></div>
    <div class="sum-row total"><span>Total</span><span><?php echo money($total); ?></span></div>
    <?php if ($problem): ?>
      <p class="muted">Ajuste os itens sem estoque suficiente para finalizar a compra.</p>
    <?php else: ?>
      <a class="btn block" href="<?php echo url('checkout.php'); ?>">Finalizar compra</a>
    <?php endif; ?>
    <a class="center-link" href="<?php echo url('index.php#catalogo'); ?>">Continuar comprando →</a>
  </aside>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
