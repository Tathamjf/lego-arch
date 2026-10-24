<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$pdo = db();
$pageTitle = 'Admin · Pedidos';
$allowed = array('pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oid = (int)val($_POST, 'order_id', 0);
    $new = val($_POST, 'status');
    $st = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
    $st->execute(array($oid));
    $cur = $st->fetchColumn();
    if (!$cur || !in_array($new, $allowed, true)) {
        flash('error', 'Pedido ou status inválido.');
    } elseif ($cur === 'cancelled') {
        flash('error', 'Pedidos cancelados não podem ser reabertos.');
    } elseif ($new === 'cancelled') {
        $res = cancel_order($oid, $admin['id']);
        if ($res === true) { flash('success', 'Pedido #' . $oid . ' cancelado e estoque devolvido.'); } else { flash('error', $res); }
    } elseif ($new !== $cur) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute(array($new, $oid));
        flash('success', 'Status do pedido #' . $oid . ' atualizado.');
    }
    redirect('admin/pedidos.php');
}

$orders = $pdo->query('SELECT o.*, u.name AS user_name, u.email FROM orders o
                       JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC, o.id DESC LIMIT 100')->fetchAll();
$itemSt = $pdo->prepare('SELECT product_name, quantity FROM order_items WHERE order_id = ?');
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?php echo url('admin/index.php'); ?>">&larr; Painel</a></p>
<h1>Pedidos</h1>
<?php if (!$orders): ?><p class="muted">Nenhum pedido ainda.</p><?php else: ?>
<table class="table">
  <thead><tr><th>#</th><th>Cliente</th><th>Itens</th><th>Total</th><th>Data</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): $itemSt->execute(array($o['id'])); $its = $itemSt->fetchAll(); ?>
    <tr>
      <td><?php echo (int)$o['id']; ?></td>
      <td><?php echo e($o['user_name']); ?><br><small class="muted"><?php echo e($o['email']); ?></small></td>
      <td><?php foreach ($its as $it) { echo (int)$it['quantity'] . ' × ' . e($it['product_name']) . '<br>'; } ?></td>
      <td><?php echo money($o['total']); ?></td>
      <td><?php echo e(date('d/m/Y H:i', strtotime($o['created_at']))); ?></td>
      <td>
        <?php if ($o['status'] === 'cancelled'): ?>
          <span class="badge st-cancelled">Cancelado</span>
        <?php else: ?>
          <form method="post" class="row">
            <?php echo csrf_field(); ?><input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
            <select name="status">
              <?php foreach ($allowed as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $o['status'] === $s ? 'selected' : ''; ?>><?php echo e(status_label($s)); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn small" type="submit">Salvar</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
