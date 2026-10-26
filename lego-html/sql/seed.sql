-- =====================================================================
-- lego-architecture-store / backend/sql/seed.sql
-- DADOS DE EXEMPLO para demonstração (products.is_sample = 1).
-- Nomes, medidas e preços são fictícios; nenhum autor ou marca real.
-- As imagens apontam para placeholders locais que você criará em
-- frontend/public/images/exemplo/ (ou trocará por URLs próprias).
--
-- Usuários NÃO são criados aqui: o hash bcrypt do admin será gerado
-- por um script TypeScript (npm run seed:admin), na etapa de autenticação.
-- =====================================================================

USE lego_architecture_store;

INSERT INTO categories (id, name, slug, description) VALUES
  (1, 'Arranha-céus',           'arranha-ceus',      'Torres e edifícios altos (dados de exemplo).'),
  (2, 'Monumentos históricos',  'monumentos',        'Réplicas de monumentos e construções clássicas (dados de exemplo).'),
  (3, 'Pontes e infraestrutura','pontes',            'Pontes, viadutos e obras de engenharia (dados de exemplo).'),
  (4, 'Arquitetura residencial','residencial',       'Casas e conjuntos habitacionais (dados de exemplo).'),
  (5, 'Modelos conceituais',    'conceituais',       'Projetos experimentais e conceituais (dados de exemplo).');

INSERT INTO products
  (id, category_id, name, slug, description, price, stock_quantity,
   piece_count, width_cm, height_cm, depth_cm, difficulty, min_age,
   architect_name, is_published, is_sample)
VALUES
  (1, 1, 'Torre de Vidro Modelo A (exemplo)', 'torre-de-vidro-modelo-a',
   'Modelo de exemplo de uma torre comercial com fachada em camadas. Dado fictício para demonstração.',
   349.90, 25, 1450, 14.0, 52.0, 14.0, 'advanced', 16, NULL, 1, 1),
  (2, 1, 'Edifício Corporativo Modelo B (exemplo)', 'edificio-corporativo-modelo-b',
   'Edifício de exemplo com térreo comercial e pavimentos-tipo. Dado fictício para demonstração.',
   219.90, 40, 820, 12.0, 34.0, 12.0, 'intermediate', 14, NULL, 1, 1),
  (3, 2, 'Arco Monumental Modelo C (exemplo)', 'arco-monumental-modelo-c',
   'Réplica simplificada de exemplo de um arco monumental. Dado fictício para demonstração.',
   179.90, 3, 560, 18.0, 20.0, 9.0, 'intermediate', 12, NULL, 1, 1),
  (4, 2, 'Catedral Gótica Modelo D (exemplo)', 'catedral-gotica-modelo-d',
   'Modelo de exemplo com naves, torres e arcobotantes. Dado fictício para demonstração.',
   489.90, 12, 2300, 32.0, 36.0, 22.0, 'expert', 18, NULL, 1, 1),
  (5, 3, 'Ponte Pênsil Modelo E (exemplo)', 'ponte-pensil-modelo-e',
   'Ponte pênsil de exemplo com cabos e torres. Dado fictício para demonstração.',
   299.90, 18, 1100, 60.0, 22.0, 8.0, 'advanced', 14, NULL, 1, 1),
  (6, 4, 'Casa Modernista Modelo F (exemplo)', 'casa-modernista-modelo-f',
   'Casa de exemplo com planta livre e grandes aberturas. Dado fictício para demonstração.',
   149.90, 60, 380, 20.0, 10.0, 16.0, 'beginner', 10, NULL, 1, 1),
  (7, 5, 'Pavilhão Conceitual Modelo G (exemplo)', 'pavilhao-conceitual-modelo-g',
   'Pavilhão experimental de exemplo com cobertura em curvas. Dado fictício para demonstração.',
   259.90, 0, 940, 26.0, 14.0, 26.0, 'advanced', 14, NULL, 1, 1),
  (8, 4, 'Vila de Casas Modelo H (exemplo)', 'vila-de-casas-modelo-h',
   'Conjunto de pequenas casas de exemplo. Dado fictício para demonstração.',
   199.90, 35, 640, 24.0, 9.0, 18.0, 'beginner', 8, NULL, 1, 1);

-- Uma imagem principal por produto (placeholders locais de exemplo)
INSERT INTO product_images (product_id, url, alt_text, sort_order, is_primary) VALUES
  (1, '/images/exemplo/torre-a.svg',       'Imagem de exemplo: torre de vidro',      0, 1),
  (2, '/images/exemplo/corporativo-b.svg', 'Imagem de exemplo: edifício corporativo',0, 1),
  (3, '/images/exemplo/arco-c.svg',        'Imagem de exemplo: arco monumental',     0, 1),
  (4, '/images/exemplo/catedral-d.svg',    'Imagem de exemplo: catedral gótica',     0, 1),
  (5, '/images/exemplo/ponte-e.svg',       'Imagem de exemplo: ponte pênsil',        0, 1),
  (6, '/images/exemplo/casa-f.svg',        'Imagem de exemplo: casa modernista',     0, 1),
  (7, '/images/exemplo/pavilhao-g.svg',    'Imagem de exemplo: pavilhão conceitual', 0, 1),
  (8, '/images/exemplo/vila-h.svg',        'Imagem de exemplo: vila de casas',       0, 1);

-- Movimentação inicial: o estoque de cada produto nasce registrado
INSERT INTO stock_movements
  (product_id, movement_type, quantity_change, stock_after, reason)
SELECT id, 'entry', stock_quantity, stock_quantity,
       'Estoque inicial (dados de exemplo)'
FROM products
WHERE stock_quantity > 0;
