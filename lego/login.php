<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { redirect('index.php'); }
$pageTitle = 'Entrar';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim(val($_POST, 'email'));
    $pass  = (string)val($_POST, 'password');
    $st = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = ? AND is_active = 1');
    $st->execute(array($email));
    $u = $st->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
        login_user($u['id']);
        redirect($u['role'] === 'admin' ? 'admin/index.php' : 'index.php');
    }
    flash('error', 'E-mail ou senha incorretos.');
}
$authPage = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth-art">
  <a class="brand" href="<?php echo url('index.php'); ?>"><?php echo brand_icon(); ?><span><?php echo e(SITE_NAME); ?></span></a>
</section>
<section class="auth-form dark">
  <div class="auth-box">
    <h1>Entrar na sua conta</h1>
    <p class="sub">Acesse seus pedidos, carrinho e muito mais.</p>
    <form method="post">
      <?php echo csrf_field(); ?>
      <div class="field"><?php echo icon('mail'); ?><input type="email" name="email" value="<?php echo e($email); ?>" placeholder="E-mail" aria-label="E-mail" required autofocus></div>
      <div class="field"><?php echo icon('lock'); ?><input type="password" name="password" placeholder="Senha" aria-label="Senha" required></div>
      <button class="btn block" type="submit">Entrar</button>
    </form>
    <p class="alt">Não tem uma conta? <a href="<?php echo url('registro.php'); ?>">Cadastre-se</a></p>
    <p class="alt"><a href="<?php echo url('index.php'); ?>">← Voltar para a loja</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
