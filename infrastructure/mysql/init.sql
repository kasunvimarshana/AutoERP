-- ============================================================
-- Inventory Management System - MySQL Initialization Script
-- ============================================================

-- Create service databases
CREATE DATABASE IF NOT EXISTS product_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS inventory_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS order_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS user_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Grant privileges to app_user on all service databases
GRANT ALL PRIVILEGES ON product_db.*   TO 'app_user'@'%';
GRANT ALL PRIVILEGES ON inventory_db.* TO 'app_user'@'%';
GRANT ALL PRIVILEGES ON order_db.*     TO 'app_user'@'%';
GRANT ALL PRIVILEGES ON user_db.*      TO 'app_user'@'%';

FLUSH PRIVILEGES;

-- ============================================================
-- product_db schema
-- ============================================================
USE product_db;

CREATE TABLE IF NOT EXISTS categories (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150)    NOT NULL,
    slug        VARCHAR(150)    NOT NULL UNIQUE,
    description TEXT,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    deleted_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED,
    sku         VARCHAR(100)    NOT NULL UNIQUE,
    name        VARCHAR(255)    NOT NULL,
    description TEXT,
    price       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    weight      DECIMAL(8,3),
    status      ENUM('active','inactive','discontinued') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    deleted_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_products_category (category_id),
    KEY idx_products_status   (status),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- inventory_db schema
-- ============================================================
USE inventory_db;

CREATE TABLE IF NOT EXISTS warehouses (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(50)     NOT NULL UNIQUE,
    name       VARCHAR(150)    NOT NULL,
    location   VARCHAR(255),
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,
    created_at TIMESTAMP       NULL DEFAULT NULL,
    updated_at TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id   BIGINT UNSIGNED NOT NULL,
    quantity     INT             NOT NULL DEFAULT 0,
    reserved_qty INT             NOT NULL DEFAULT 0,
    reorder_point INT            NOT NULL DEFAULT 0,
    created_at   TIMESTAMP       NULL DEFAULT NULL,
    updated_at   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_warehouse_product (warehouse_id, product_id),
    KEY idx_inventory_product (product_id),
    CONSTRAINT fk_inventory_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id   BIGINT UNSIGNED NOT NULL,
    type         ENUM('in','out','adjustment','transfer') NOT NULL,
    quantity     INT             NOT NULL,
    reference    VARCHAR(100),
    notes        TEXT,
    created_by   BIGINT UNSIGNED,
    created_at   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_movements_warehouse (warehouse_id),
    KEY idx_movements_product   (product_id),
    KEY idx_movements_type      (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- order_db schema
-- ============================================================
USE order_db;

CREATE TABLE IF NOT EXISTS orders (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_number    VARCHAR(50)      NOT NULL UNIQUE,
    user_id         BIGINT UNSIGNED  NOT NULL,
    status          ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded')
                                     NOT NULL DEFAULT 'pending',
    subtotal        DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    tax_amount      DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    shipping_amount DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    total_amount    DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    notes           TEXT,
    shipped_at      TIMESTAMP        NULL DEFAULT NULL,
    delivered_at    TIMESTAMP        NULL DEFAULT NULL,
    created_at      TIMESTAMP        NULL DEFAULT NULL,
    updated_at      TIMESTAMP        NULL DEFAULT NULL,
    deleted_at      TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_orders_user   (user_id),
    KEY idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NOT NULL,
    product_sku VARCHAR(100)    NOT NULL,
    product_name VARCHAR(255)   NOT NULL,
    quantity    INT             NOT NULL,
    unit_price  DECIMAL(12,2)   NOT NULL,
    total_price DECIMAL(12,2)   NOT NULL,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order   (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- user_db schema
-- ============================================================
USE user_db;

CREATE TABLE IF NOT EXISTS users (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    keycloak_id    VARCHAR(36)     UNIQUE,
    name           VARCHAR(150)    NOT NULL,
    email          VARCHAR(191)    NOT NULL UNIQUE,
    email_verified TINYINT(1)      NOT NULL DEFAULT 0,
    phone          VARCHAR(30),
    role           ENUM('admin','manager','viewer') NOT NULL DEFAULT 'viewer',
    is_active      TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at  TIMESTAMP       NULL DEFAULT NULL,
    created_at     TIMESTAMP       NULL DEFAULT NULL,
    updated_at     TIMESTAMP       NULL DEFAULT NULL,
    deleted_at     TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_users_keycloak (keycloak_id),
    KEY idx_users_role     (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED NOT NULL UNIQUE,
    avatar_url VARCHAR(500),
    timezone   VARCHAR(50)     NOT NULL DEFAULT 'UTC',
    locale     VARCHAR(10)     NOT NULL DEFAULT 'en',
    created_at TIMESTAMP       NULL DEFAULT NULL,
    updated_at TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_profile_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a default admin user (password managed by Keycloak)
INSERT INTO users (keycloak_id, name, email, email_verified, role, is_active, created_at, updated_at)
VALUES ('00000000-0000-0000-0000-000000000001', 'System Admin', 'admin@inventory.local', 1, 'admin', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();
