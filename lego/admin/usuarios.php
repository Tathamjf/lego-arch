<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$pdo = db();
$pageTitle = 'Admin · Usuários';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int)val($_POST, 'user_id', 0);
    $action = val($_POST, 'action');
    if ($uid === (int)$admin['id']) {
        flash('error', 'Você não pode alterar a sua própria conta por aqui.');
    } elseif ($action === 'toggle_active') {
        $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute(array($uid));
        flash('success', 'Situação da conta atualizada.');
    } elseif ($action === 'toggle_role') {
        $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'customer', 'admin') WHERE id = ?")->execute(array($uid));
        flash('success', 'Perfil atualizado.');
    }
    redirect('admin/usuarios.php');
}
$users = $pdo->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY id')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?php echo url('admin/index.php'); ?>">&larr; Painel</a></p>
<h1>Usuários</h1>
<table class="table">
  <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th>Criado em</th><th>Ações</th></tr></thead>
  <tbody>
  <?php foreach ($users as $x): $self = (int)$x['id'] === (int)$admin['id']; ?>
    <tr>
      <td><?php echo (int)$x['id']; ?></td>
      <td><?php echo e($x['name']); ?></td>
      <td><?php echo e($x['email']); ?></td>
      <td><?php echo $x['role'] === 'admin' ? 'Administrador' : 'Cliente'; ?></td>
      <td><?php echo $x['is_active'] ? 'Ativa' : 'Desativada'; ?></td>
      <td><?php echo e(date('d/m/Y', strtotime($x['created_at']))); ?></td>
      <td class="actions">
        <?php if ($self): ?><span class="muted">(você)</span><?php else: ?>
        <form method="post" class="inline">
          <?php echo csrf_field(); ?><input type="hidden" name="user_id" value="<?php echo (int)$x['id']; ?>">
          <button class="btn small" name="action" value="toggle_active" type="submit"><?php echo $x['is_active'] ? 'Desativar' : 'Ativar'; ?></button>
          <button class="btn small" name="action" value="toggle_role" type="submit"><?php echo $x['role'] === 'admin' ? 'Tornar cliente' : 'Tornar admin'; ?></button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
