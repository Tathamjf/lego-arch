<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { redirect('index.php'); }
$pageTitle = 'Criar conta';
$name = ''; $email = '';
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim(val($_POST, 'name'));
    $email = trim(val($_POST, 'email'));
    $pass  = (string)val($_POST, 'password');
    $pass2 = (string)val($_POST, 'password2');

    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 120) { $errors[] = 'Informe seu nome (2 a 120 caracteres).'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) { $errors[] = 'Informe um e-mail válido.'; }
    if (strlen($pass) < 8) { $errors[] = 'A senha deve ter pelo menos 8 caracteres.'; }
    if ($pass !== $pass2) { $errors[] = 'As senhas não conferem.'; }

    if (!$errors) {
        $pdo = db();
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute(array($email));
        if ($st->fetch()) {
            $errors[] = 'Já existe uma conta com este e-mail.';
        } else {
            try {
                $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')")
                    ->execute(array($name, $email, password_hash($pass, PASSWORD_DEFAULT)));
                login_user($pdo->lastInsertId());
                flash('success', 'Conta criada com sucesso. Bem-vinda(o)!');
                redirect('index.php');
            } catch (Exception $ex) {
                $errors[] = 'Não foi possível criar a conta.' . (DEBUG ? ' ' . $ex->getMessage() : '');
            }
        }
    }
}
$authPage = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth-art">
  <a class="brand" href="<?php echo url('index.php'); ?>"><?php echo brand_icon(); ?><span><?php echo e(SITE_NAME); ?></span></a>
  <h1>Crie sua conta</h1>
  <p>Cadastre-se para ter uma experiência completa e aproveitar todas as vantagens.</p>
</section>
<section class="auth-form">
  <div class="auth-box">
    <?php foreach ($errors as $err): ?><div class="alert error"><?php echo e($err); ?></div><?php endforeach; ?>
    <form method="post">
      <?php echo csrf_field(); ?>
      <label>Nome completo <input type="text" name="name" value="<?php echo e($name); ?>" placeholder="Digite seu nome" required></label>
      <label>E-mail <input type="email" name="email" value="<?php echo e($email); ?>" placeholder="Digite seu e-mail" required></label>
      <label>Senha <input type="password" name="password" placeholder="Mínimo de 8 caracteres" required></label>
      <label>Confirmar senha <input type="password" name="password2" placeholder="Confirme sua senha" required></label>
      <button class="btn block" type="submit">Criar conta</button>
    </form>
    <p class="alt">Já tem conta? <a href="<?php echo url('login.php'); ?>">Entrar</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
