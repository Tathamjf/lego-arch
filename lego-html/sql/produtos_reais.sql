-- =====================================================================
-- Troca os 8 produtos de exemplo pelos projetos com fotos reais.
-- Rode no phpMyAdmin: selecione o banco lego_architecture_store >
-- aba SQL > cole este arquivo > Executar.
-- O estoque e o histórico de movimentações NÃO são alterados.
-- Valores de preço, peças e medidas são fictícios (projeto acadêmico).
-- =====================================================================
USE lego_architecture_store;

UPDATE categories SET description = 'Torres e edifícios altos.' WHERE id = 1;
UPDATE categories SET description = 'Réplicas de monumentos e construções clássicas.' WHERE id = 2;
UPDATE categories SET description = 'Pontes, viadutos e obras de engenharia.' WHERE id = 3;
UPDATE categories SET description = 'Casas, apartamentos e conjuntos habitacionais.' WHERE id = 4;
UPDATE categories SET description = 'Projetos experimentais e conceituais.' WHERE id = 5;
-- Nenhuma foto de ponte por enquanto: para esconder a categoria do filtro, descomente:
-- UPDATE categories SET is_active = 0 WHERE id = 3;

UPDATE products SET category_id=1, name='Torre Jardim Vertical', slug='torre-jardim-vertical',
  description='Torre residencial com varandas cobertas de vegetação em vários tons de verde. Cada pavimento tem jardineiras brancas com plantas em alturas diferentes, e o terraço superior traz um jardim protegido por gradil transparente. A base compacta de exposição combina com estantes e mesas de centro.',
  price=329.90, piece_count=1420, width_cm=16.0, height_cm=45.0, depth_cm=16.0, difficulty='advanced', min_age=14,
  architect_name=NULL, is_sample=0 WHERE id=1;

UPDATE products SET category_id=1, name='Torre dos Pavimentos Rotacionados', slug='torre-pavimentos-rotacionados',
  description='Torre em tons de cinza com pavimentos em caixas de vidro fumê, girados uns em relação aos outros em torno de um núcleo central, o que cria uma silhueta em espiral. A cobertura em placas e a base de rua completam o modelo, que funciona bem em dioramas de cidade.',
  price=299.90, piece_count=1100, width_cm=18.0, height_cm=40.0, depth_cm=18.0, difficulty='advanced', min_age=14,
  architect_name=NULL, is_sample=0 WHERE id=2;

UPDATE products SET category_id=2, name='Ópera de Sydney', slug='opera-de-sydney',
  description='Réplica em tons de areia e cinza das grandes velas em forma de concha. As camadas de peças criam o efeito de azulejo das cascas, e a base escalonada, com escadarias e minifiguras, dá escala ao conjunto. Um modelo para quem gosta de curvas e de desafios de montagem.',
  price=449.90, piece_count=2650, width_cm=50.0, height_cm=22.0, depth_cm=38.0, difficulty='expert', min_age=16,
  architect_name=NULL, is_sample=0 WHERE id=3;

UPDATE products SET category_id=2, name='Coliseu de Roma', slug='coliseu-de-roma',
  description='Anfiteatro oval em tons de areia, com arcadas em vários níveis, arquibancadas internas visíveis e o trecho de parede em ruínas que marca o monumento. Montado sobre uma base circular cinza com árvores e postes de luz, é uma peça de exposição imponente.',
  price=549.90, piece_count=3200, width_cm=52.0, height_cm=24.0, depth_cm=48.0, difficulty='expert', min_age=18,
  architect_name=NULL, is_sample=0 WHERE id=4;

UPDATE products SET category_id=5, name='Edifício Sports Bolhas', slug='edificio-sports-bolhas',
  description='Torre conceitual de fachada escura e lisa, perfurada por janelas circulares e ovais de tamanhos variados com vidros translúcidos azul-esverdeados. No térreo há entrada envidraçada, letreiro "SPORTS", árvores e postes de luz, com minifiguras passeando pela calçada.',
  price=359.90, piece_count=1980, width_cm=22.0, height_cm=40.0, depth_cm=22.0, difficulty='advanced', min_age=14,
  architect_name=NULL, is_sample=0 WHERE id=5;

UPDATE products SET category_id=4, name='Casa Sunset Neon', slug='casa-sunset-neon',
  description='Casa moderna de dois andares com estrutura em colunas pretas e faixas em degradê de roxo e laranja, como um pôr do sol retrô. O térreo curvo, as palmeiras e o espelho d''água criam um clima de verão. Um bom modelo para quem está começando em arquitetura com peças.',
  price=189.90, piece_count=540, width_cm=22.0, height_cm=20.0, depth_cm=20.0, difficulty='intermediate', min_age=12,
  architect_name=NULL, is_sample=0 WHERE id=6;

UPDATE products SET category_id=4, name='Residencial Onda', slug='residencial-onda',
  description='Conjunto residencial com silhueta em onda: uma grande cobertura curva em cinza abraça cinco pavimentos de apartamentos com varandas de guarda-corpo branco e esquadrias claras. Jardins, postes de iluminação, calçada em mosaico e minifiguras completam a cena urbana.',
  price=389.90, piece_count=1850, width_cm=40.0, height_cm=44.0, depth_cm=26.0, difficulty='advanced', min_age=14,
  architect_name=NULL, is_sample=0 WHERE id=7;

UPDATE products SET category_id=4, name='Apartamentos Cubos Coloridos', slug='apartamentos-cubos-coloridos',
  description='Edifício modular formado por cubos coloridos em vermelho, azul, verde-limão, amarelo e branco, encaixados em volumes deslocados ao redor de um núcleo escuro. Cada módulo tem janelas e uma pequena varanda, e uma árvore na base completa o conjunto.',
  price=249.90, piece_count=760, width_cm=24.0, height_cm=36.0, depth_cm=24.0, difficulty='intermediate', min_age=12,
  architect_name=NULL, is_sample=0 WHERE id=8;

-- Fotos (uma principal por produto)
UPDATE product_images SET url='images/produtos/torre-jardim-vertical.jpg',        alt_text='Torre Jardim Vertical' WHERE product_id=1;
UPDATE product_images SET url='images/produtos/torre-pavimentos-rotacionados.jpg', alt_text='Torre dos Pavimentos Rotacionados' WHERE product_id=2;
UPDATE product_images SET url='images/produtos/opera-de-sydney.jpg',               alt_text='Ópera de Sydney' WHERE product_id=3;
UPDATE product_images SET url='images/produtos/coliseu-de-roma.jpg',               alt_text='Coliseu de Roma' WHERE product_id=4;
UPDATE product_images SET url='images/produtos/edificio-sports-bolhas.jpg',        alt_text='Edifício Sports Bolhas' WHERE product_id=5;
UPDATE product_images SET url='images/produtos/casa-sunset-neon.jpg',              alt_text='Casa Sunset Neon' WHERE product_id=6;
UPDATE product_images SET url='images/produtos/residencial-onda.jpg',              alt_text='Residencial Onda' WHERE product_id=7;
UPDATE product_images SET url='images/produtos/apartamentos-cubos-coloridos.jpg',  alt_text='Apartamentos Cubos Coloridos' WHERE product_id=8;
