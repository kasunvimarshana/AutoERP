-- Gateway database initialization
CREATE DATABASE IF NOT EXISTS saas_gateway;
USE saas_gateway;

-- Tenants table (multi-tenancy core)
CREATE TABLE IF NOT EXISTS tenants (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    domain VARCHAR(255) UNIQUE,
    plan VARCHAR(50) NOT NULL DEFAULT 'free',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant configurations (runtime config)
CREATE TABLE IF NOT EXISTS tenant_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    config_key VARCHAR(255) NOT NULL,
    config_value TEXT,
    config_group VARCHAR(100) NOT NULL DEFAULT 'general',
    is_encrypted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenant_config (tenant_id, config_key),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_config_group (config_group),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
