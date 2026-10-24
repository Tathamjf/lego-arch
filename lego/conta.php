<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$pdo = db();
$pageTitle = 'Minha conta';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = val($_POST, 'action');

    if ($action === 'profile') {
        $name = trim(val($_POST, 'name'));
        if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 120) {
            flash('error', 'Informe um nome válido (2 a 120 caracteres).');
        } else {
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute(array($name, $u['id']));
            flash('success', 'Nome atualizado.');
        }
    } elseif ($action === 'password') {
        $cur = (string)val($_POST, 'current');
        $new = (string)val($_POST, 'new');
        $new2 = (string)val($_POST, 'new2');
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute(array($u['id']));
        $hash = $st->fetchColumn();
        if (!password_verify($cur, $hash)) { flash('error', 'Senha atual incorreta.'); }
        elseif (strlen($new) < 8)          { flash('error', 'A nova senha deve ter pelo menos 8 caracteres.'); }
        elseif ($new !== $new2)            { flash('error', 'As novas senhas não conferem.'); }
        else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute(array(password_hash($new, PASSWORD_DEFAULT), $u['id']));
            session_regenerate_id(true);
            flash('success', 'Senha alterada.');
        }
    }
    redirect('conta.php');
}
$active = 'dados';
include __DIR__ . '/includes/header.php';
?>
<div class="account">
  <?php include __DIR__ . '/includes/account_nav.php'; ?>
  <div class="account-main">
    <h1>Meus dados</h1>
    <div class="two">
      <div class="box">
        <h2>Dados</h2>
        <form method="post">
          <?php echo csrf_field(); ?><input type="hidden" name="action" value="profile">
          <label>Nome <input type="text" name="name" value="<?php echo e($u['name']); ?>" required></label>
          <label>E-mail <input type="email" value="<?php echo e($u['email']); ?>" disabled></label>
          <button class="btn" type="submit">Salvar</button>
        </form>
      </div>
      <div class="box">
        <h2>Alterar senha</h2>
        <form method="post">
          <?php echo csrf_field(); ?><input type="hidden" name="action" value="password">
          <label>Senha atual <input type="password" name="current" required></label>
          <label>Nova senha <input type="password" name="new" required></label>
          <label>Repita a nova senha <input type="password" name="new2" required></label>
          <button class="btn" type="submit">Alterar senha</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
