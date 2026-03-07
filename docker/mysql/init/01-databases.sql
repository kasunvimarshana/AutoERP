-- MySQL initialization script: create additional tenant schema databases
-- The application creates tenant-specific connections at runtime.

-- Create default databases
CREATE DATABASE IF NOT EXISTS inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS inventory_tenant_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant permissions
GRANT ALL PRIVILEGES ON `inventory%`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
