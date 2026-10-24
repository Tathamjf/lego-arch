<?php
// Menu lateral da área da conta. Defina $active = 'pedidos' | 'dados' antes de incluir.
$initials = '';
foreach (array_slice(explode(' ', trim($u['name'])), 0, 2) as $part) { $initials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8'); }
?>
<aside class="side">
  <div class="side-user">
    <span class="avatar"><?php echo e($initials); ?></span>
    <div><strong><?php echo e($u['name']); ?></strong><small><?php echo e($u['email']); ?></small></div>
  </div>
  <a class="<?php echo $active === 'pedidos' ? 'active' : ''; ?>" href="<?php echo url('pedidos.php'); ?>"><?php echo icon('box'); ?> Meus pedidos</a>
  <a class="<?php echo $active === 'dados' ? 'active' : ''; ?>" href="<?php echo url('conta.php'); ?>"><?php echo icon('user'); ?> Meus dados</a>
  <a href="<?php echo url('carrinho.php'); ?>"><?php echo icon('cart'); ?> Carrinho</a>
  <form method="post" action="<?php echo url('logout.php'); ?>">
    <?php echo csrf_field(); ?><button type="submit"><?php echo icon('logout'); ?> Sair</button>
  </form>
</aside>
