<?php
$user = current_user();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Loja';
$authPage = !empty($authPage);
$cartN = $user ? cart_count($user['id']) : 0;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($pageTitle); ?> · <?php echo e(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo url('css/style.css'); ?>">
<script src="<?php echo url('js/app.js'); ?>" defer></script>
</head>
<body class="<?php echo $authPage ? 'is-auth' : ''; ?>">
<?php if (!$authPage): ?>
<header class="top">
  <div class="wrap nav">
    <a class="brand" href="<?php echo url('index.php'); ?>"><?php echo brand_icon(); ?><span><?php echo e(SITE_NAME); ?></span></a>
    <nav class="menu">
      <a href="<?php echo url('index.php'); ?>">Início</a>
      <a href="<?php echo url('index.php#catalogo'); ?>">Projetos</a>
      <?php if ($user): ?><a href="<?php echo url('pedidos.php'); ?>">Meus pedidos</a><?php endif; ?>
      <?php if ($user && $user['role'] === 'admin'): ?><a href="<?php echo url('admin/index.php'); ?>">Admin</a><?php endif; ?>
    </nav>
    <div class="tools">
      <a class="iconbtn" href="<?php echo url('index.php#catalogo'); ?>" title="Buscar"><?php echo icon('search'); ?></a>
      <a class="iconbtn" href="<?php echo url('carrinho.php'); ?>" title="Carrinho"><?php echo icon('cart'); ?><?php if ($cartN > 0): ?><span class="dot"><?php echo $cartN; ?></span><?php endif; ?></a>
      <?php if ($user): ?>
        <a class="iconbtn" href="<?php echo url('conta.php'); ?>" title="<?php echo e($user['name']); ?>"><?php echo icon('user'); ?></a>
        <form method="post" action="<?php echo url('logout.php'); ?>" class="inline">
          <?php echo csrf_field(); ?><button class="iconbtn" type="submit" title="Sair"><?php echo icon('logout'); ?></button>
        </form>
      <?php else: ?>
        <a class="iconbtn" href="<?php echo url('login.php'); ?>" title="Entrar"><?php echo icon('user'); ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="wrap page">
<?php else: ?>
<main class="auth-shell">
<?php endif; ?>
<?php foreach (get_flashes() as $f): ?>
  <div class="alert <?php echo e($f[0]); ?>"><?php echo e($f[1]); ?></div>
<?php endforeach; ?>
