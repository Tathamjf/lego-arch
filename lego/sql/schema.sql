-- =====================================================================
-- lego-architecture-store / backend/sql/schema.sql
-- Compatível com MySQL 5.6+ (UsbWebServer 8.6) e MySQL 8.x.
--
-- Observações de compatibilidade:
--  * MySQL < 8.0.16 lê as cláusulas CHECK, mas NÃO as aplica. Elas estão
--    aqui para quando o projeto rodar em MySQL 8, e as regras também são
--    garantidas por colunas UNSIGNED + sql_mode estrito (configurado na
--    conexão do Node) + validação na camada de serviço.
--  * E-mail usa VARCHAR(191): no MySQL 5.6, índices em utf8mb4 são
--    limitados a 767 bytes (191 * 4 = 764).
--  * Todas as tabelas usam InnoDB (necessário para transações e FKs).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS lego_architecture_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lego_architecture_store;

-- Ordem de criação respeita as dependências entre tabelas.
-- Para recriar do zero (apaga tudo!), descomente o bloco abaixo.
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS stock_movements, order_items, orders, cart_items,
--   carts, product_images, products, categories, users;
-- SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,           -- hash bcrypt, nunca a senha
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,   -- desativação lógica
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL,
  description VARCHAR(500) NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
              ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id    INT UNSIGNED NOT NULL,
  name           VARCHAR(150) NOT NULL,
  slug           VARCHAR(160) NOT NULL,
  description    TEXT NOT NULL,
  price          DECIMAL(10,2) NOT NULL,
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,  -- UNSIGNED impede negativo
  piece_count    INT UNSIGNED NOT NULL,
  width_cm       DECIMAL(6,1) NULL,
  height_cm      DECIMAL(6,1) NULL,
  depth_cm       DECIMAL(6,1) NULL,
  difficulty     ENUM('beginner','intermediate','advanced','expert')
                 NOT NULL DEFAULT 'intermediate',
  min_age        TINYINT UNSIGNED NULL,
  architect_name VARCHAR(150) NULL,               -- só quando informado
  is_published   TINYINT(1) NOT NULL DEFAULT 0,
  is_sample      TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = dado de exemplo
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,
  deleted_at     TIMESTAMP NULL DEFAULT NULL,     -- exclusão lógica
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_category (category_id),
  KEY idx_products_listing (is_published, deleted_at),
  KEY idx_products_price (price),
  KEY idx_products_pieces (piece_count),
  KEY idx_products_difficulty (difficulty),
  KEY idx_products_created (created_at),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_products_price CHECK (price >= 0),
  CONSTRAINT chk_products_pieces CHECK (piece_count > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- product_images
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  url        VARCHAR(500) NOT NULL,
  alt_text   VARCHAR(200) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_product_images_product (product_id, sort_order),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- carts (um carrinho por usuário autenticado)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carts (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
             ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user (user_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cart_items (não guarda preço: o preço sempre vem de products)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
             ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id),
  KEY idx_cart_items_product (product_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id)
    REFERENCES carts (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_cart_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- orders (sem dados de cartão; payment_reference é para o gateway futuro)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           INT UNSIGNED NOT NULL,
  status            ENUM('pending_payment','paid','processing','shipped',
                         'delivered','cancelled')
                    NOT NULL DEFAULT 'pending_payment',
  subtotal          DECIMAL(10,2) NOT NULL,
  shipping_cost     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total             DECIMAL(10,2) NOT NULL,
  payment_reference VARCHAR(100) NULL,
  ship_name         VARCHAR(120) NOT NULL,
  ship_zip          VARCHAR(9)   NOT NULL,
  ship_street       VARCHAR(150) NOT NULL,
  ship_number       VARCHAR(20)  NOT NULL,
  ship_complement   VARCHAR(100) NULL,
  ship_district     VARCHAR(100) NOT NULL,
  ship_city         VARCHAR(100) NOT NULL,
  ship_state        CHAR(2)      NOT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  cancelled_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_orders_user (user_id, created_at),
  KEY idx_orders_status (status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_orders_totals CHECK
    (subtotal >= 0 AND shipping_cost >= 0 AND total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- order_items (guarda nome e preço no momento da compra)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,             -- cópia histórica
  unit_price   DECIMAL(10,2) NOT NULL,            -- preço histórico
  quantity     INT UNSIGNED NOT NULL,
  line_total   DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_order_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- stock_movements (histórico imutável; quantity_change tem sinal)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_movements (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id      INT UNSIGNED NOT NULL,
  movement_type   ENUM('entry','sale','adjustment','cancellation_return')
                  NOT NULL,
  quantity_change INT NOT NULL,                   -- ex.: +10, -2
  stock_after     INT UNSIGNED NOT NULL,          -- saldo após a movimentação
  order_id        INT UNSIGNED NULL,
  user_id         INT UNSIGNED NULL,              -- admin que fez o ajuste
  reason          VARCHAR(255) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stock_movements_product (product_id, created_at),
  KEY idx_stock_movements_order (order_id),
  KEY idx_stock_movements_user (user_id),
  CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_stock_movements_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_stock_movements_change CHECK (quantity_change <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
