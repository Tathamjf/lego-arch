<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$pdo = db();
$pageTitle = 'Meus pedidos';
$active = 'pedidos';

$st = $pdo->prepare('SELECT id, status, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$st->execute(array($u['id']));
$orders = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="account">
  <?php include __DIR__ . '/includes/account_nav.php'; ?>
  <div class="account-main">
    <h1>Meus pedidos</h1>
    <?php if (!$orders): ?>
      <p class="muted">Você ainda não fez pedidos.</p>
      <a class="btn" href="<?php echo url('index.php#catalogo'); ?>">Ver projetos</a>
    <?php else: ?>
    <table class="table">
      <thead><tr><th>Pedido</th><th>Data</th><th>Status</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?php echo e(order_no($o['id'])); ?></strong></td>
          <td><?php echo e(date('d/m/Y', strtotime($o['created_at']))); ?></td>
          <td><?php echo status_pill($o['status']); ?></td>
          <td><?php echo money($o['total']); ?></td>
          <td><a class="view-link" href="<?php echo url('pedido.php?id=' . (int)$o['id']); ?>">Ver detalhes <?php echo icon('arrow'); ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
