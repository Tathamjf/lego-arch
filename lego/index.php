<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Catálogo';
$pdo = db();

$q    = trim(val($_GET, 'q'));
$cat  = (int)val($_GET, 'cat', 0);
$ord  = val($_GET, 'ord', 'new');
$only = val($_GET, 'disp') === '1';

$orders = array(
    'new'        => 'p.created_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'pieces'     => 'p.piece_count DESC',
);
$orderSql = isset($orders[$ord]) ? $orders[$ord] : $orders['new'];

$where  = array('p.is_published = 1', 'p.deleted_at IS NULL', 'c.is_active = 1');
$params = array();
if ($q !== '')   { $where[] = 'p.name LIKE ?'; $params[] = '%' . $q . '%'; }
if ($cat > 0)    { $where[] = 'p.category_id = ?'; $params[] = $cat; }
if ($only)       { $where[] = 'p.stock_quantity > 0'; }

$sql = 'SELECT p.id, p.name, p.price, p.stock_quantity, p.piece_count, p.difficulty, c.name AS category,
               (SELECT url FROM product_images i WHERE i.product_id = p.id
                ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS img
        FROM products p JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderSql;
$st = $pdo->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

$filtering = ($q !== '' || $cat > 0 || $only || $ord !== 'new');
$loggedIn = (bool)current_user();
include __DIR__ . '/includes/header.php';
?>
<?php if (!$filtering): ?>
<section class="hero">
  <h1>Transforme arquitetura em peças de LEGO</h1>
  <p>Modelos arquitetônicos para você montar, colecionar e se inspirar.</p>
  <a class="btn light" href="#catalogo">Ver projetos <?php echo icon('arrow'); ?></a>
</section>
<?php endif; ?>

<div class="section-head" id="catalogo">
  <h2><?php echo $filtering ? 'Resultados' : 'Projetos em destaque'; ?></h2>
  <span class="muted"><?php echo count($products); ?> <?php echo count($products) === 1 ? 'projeto' : 'projetos'; ?></span>
</div>
<form method="get" class="filters" action="#catalogo">
  <input type="text" name="q" placeholder="Buscar pelo nome" value="<?php echo e($q); ?>">
  <select name="cat">
    <option value="0">Todas as categorias</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?php echo (int)$c['id']; ?>" <?php echo $cat === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
    <?php endforeach; ?>
  </select>
  <select name="ord">
    <option value="new" <?php echo $ord === 'new' ? 'selected' : ''; ?>>Mais recentes</option>
    <option value="price_asc" <?php echo $ord === 'price_asc' ? 'selected' : ''; ?>>Menor preço</option>
    <option value="price_desc" <?php echo $ord === 'price_desc' ? 'selected' : ''; ?>>Maior preço</option>
    <option value="pieces" <?php echo $ord === 'pieces' ? 'selected' : ''; ?>>Mais peças</option>
  </select>
  <label class="check"><input type="checkbox" name="disp" value="1" <?php echo $only ? 'checked' : ''; ?>> Só disponíveis</label>
  <button class="btn small" type="submit">Filtrar</button>
  <?php if ($filtering): ?><a class="btn small ghost" href="<?php echo url('index.php'); ?>">Limpar</a><?php endif; ?>
</form>

<?php if (!$products): ?>
  <p class="muted">Nenhum projeto encontrado.</p>
<?php else: ?>
<div class="grid">
  <?php foreach ($products as $p): $s = (int)$p['stock_quantity']; $link = url('produto.php?id=' . (int)$p['id']); ?>
    <article class="card">
      <a class="thumb" href="<?php echo $link; ?>"><img src="<?php echo e(image_url($p['img'])); ?>" alt="<?php echo e($p['name']); ?>"></a>
      <div class="card-body">
        <small><?php echo e($p['category']); ?> · <?php echo (int)$p['piece_count']; ?> peças · <?php echo e(diff_label($p['difficulty'])); ?></small>
        <h3><a href="<?php echo $link; ?>"><?php echo e($p['name']); ?></a></h3>
        <div class="card-foot">
          <div>
            <div class="price"><?php echo money($p['price']); ?></div>
            <?php if ($s === 0): ?><span class="badge out">Esgotado</span>
            <?php elseif ($s <= LOW_STOCK): ?><span class="badge low">Últimas <?php echo $s; ?></span><?php endif; ?>
          </div>
          <?php if ($s === 0): ?>
            <button class="cart-btn" disabled title="Esgotado"><?php echo icon('cart'); ?></button>
          <?php elseif (!$loggedIn): ?>
            <a class="cart-btn" href="<?php echo url('login.php'); ?>" title="Entre para comprar"><?php echo icon('cart'); ?></a>
          <?php else: ?>
            <form method="post" action="<?php echo url('carrinho.php'); ?>" class="inline">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
              <input type="hidden" name="quantity" value="1">
              <button class="cart-btn" type="submit" title="Adicionar ao carrinho"><?php echo icon('cart'); ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
