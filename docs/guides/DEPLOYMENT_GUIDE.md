# Production Deployment Guide

## Enterprise ERP/CRM SaaS Platform

Complete guide for deploying the platform to production environments.

---

## Table of Contents

1. [Pre-Deployment Requirements](#pre-deployment-requirements)
2. [Server Setup](#server-setup)
3. [Application Deployment](#application-deployment)
4. [Database Configuration](#database-configuration)
5. [Security Hardening](#security-hardening)
6. [Performance Optimization](#performance-optimization)
7. [Monitoring & Logging](#monitoring--logging)
8. [Backup & Recovery](#backup--recovery)
9. [Post-Deployment Validation](#post-deployment-validation)
10. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Requirements

### Infrastructure

**Minimum Requirements:**
- **Web Server**: Nginx 1.20+ or Apache 2.4+
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite (dev only)
- **Memory**: 2GB RAM minimum (4GB+ recommended)
- **Storage**: 20GB minimum
- **SSL Certificate**: Valid TLS/SSL certificate

**PHP Extensions Required:**
```bash
php -m | grep -E 'bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml'
```

Required extensions:
- bcmath (for precision calculations)
- ctype
- fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO (+ driver for your database)
- Tokenizer
- XML

### DNS Configuration

**Required DNS Records:**
```
# Main domain
example.com         A       123.45.67.89

# API subdomain
api.example.com     A       123.45.67.89

# Tenant subdomains (wildcard)
*.example.com       A       123.45.67.89
```

---

## Server Setup

### 1. Install Dependencies

**Ubuntu/Debian:**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-bcmath php8.2-curl \
    php8.2-mbstring php8.2-mysql php8.2-xml php8.2-zip \
    php8.2-sqlite3 php8.2-redis php8.2-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Redis (optional, for caching)
sudo apt install -y redis-server

# Install Node.js (for frontend builds)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Create Application User

```bash
# Create dedicated user
sudo useradd -m -s /bin/bash -G www-data erp-app

# Set up directory
sudo mkdir -p /var/www/erp-crm
sudo chown erp-app:www-data /var/www/erp-crm
```

### 3. Configure Firewall

```bash
# Allow HTTP, HTTPS, SSH
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
```

---

## Application Deployment

### 1. Clone & Install

```bash
# Switch to application user
sudo su - erp-app

# Clone repository
cd /var/www/erp-crm
git clone https://github.com/kasunvimarshana/AutoERP.git .

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm ci --only=production

# Build frontend assets
npm run build
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

**Critical .env Settings:**
```bash
# Application
APP_NAME="Enterprise ERP/CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_TIMEZONE=UTC

# Security
JWT_SECRET=<generate-strong-secret>
BCRYPT_ROUNDS=12

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_production
DB_USERNAME=erp_user
DB_PASSWORD=<strong-password>

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=<mail-password>
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Multi-Tenancy
MULTI_TENANCY_ENABLED=true
TENANT_USE_SUBDOMAIN=true
TENANT_STRICT_MODE=true

# JWT
JWT_TTL=3600
JWT_REFRESH_TTL=86400
JWT_REQUIRE_HTTPS=true
```

**Generate JWT Secret:**
```bash
php -r "echo base64_encode(random_bytes(32));"
```

### 3. Validate Configuration

```bash
# Validate all required configs
php artisan config:validate --production --warnings

# Should show: ✅ Configuration validation passed!
```

### 4. Set Permissions

```bash
# Storage and cache directories
chmod -R 775 storage bootstrap/cache
chown -R erp-app:www-data storage bootstrap/cache

# Ensure proper permissions
find storage -type f -exec chmod 664 {} \;
find storage -type d -exec chmod 775 {} \;
```

---

## Database Configuration

### 1. Create Database

```bash
# Login to MySQL
sudo mysql

# Create database and user
CREATE DATABASE erp_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erp_user'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON erp_production.* TO 'erp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Run Migrations

```bash
# Run all migrations
php artisan migrate --force

# Verify migration status
php artisan migrate:status
```

### 3. Seed Initial Data (Optional)

**⚠️ WARNING: Only run in development!**
```bash
# Development only
php artisan db:seed --force
```

For production, create admin users manually:
```bash
php artisan tinker

# Create admin user
$admin = \Modules\Auth\Models\User::create([
    'tenant_id' => '<tenant-id>',
    'organization_id' => '<org-id>',
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('<secure-password>'),
    'is_active' => true,
]);
```

---

## Security Hardening

### 1. Web Server Configuration

**Nginx Configuration** (`/etc/nginx/sites-available/erp-crm`):
```nginx
server {
    listen 80;
    server_name example.com *.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com *.example.com;
    root /var/www/erp-crm/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/example.com.crt;
    ssl_certificate_key /etc/ssl/private/example.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Hide Nginx version
    server_tokens off;

    # Logging
    access_log /var/log/nginx/erp-crm-access.log;
    error_log /var/log/nginx/erp-crm-error.log;

    # PHP-FPM
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ \.(env|log|git|json|lock)$ {
        deny all;
    }
}
```

### 2. PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php-fpm/error.log
```

### 3. Database Security

**MySQL Hardening:**
```bash
# Run MySQL secure installation
sudo mysql_secure_installation

# Configure MySQL
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add:
```ini
# Networking
bind-address = 127.0.0.1
skip-name-resolve

# Security
local-infile = 0

# Performance
max_connections = 200
innodb_buffer_pool_size = 1G
```

---

## Performance Optimization

### 1. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload -o
```

### 2. Queue Workers

**Setup Supervisor** (`/etc/supervisor/conf.d/erp-queue.conf`):
```ini
[program:erp-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/erp-crm/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=erp-app
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/erp-crm/storage/logs/queue-worker.log
```

**Start workers:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start erp-queue-worker:*
```

### 3. Task Scheduling

**Add to crontab:**
```bash
sudo crontab -e -u erp-app

# Add:
* * * * * cd /var/www/erp-crm && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Enable OPcache

Edit `/etc/php/8.2/fpm/conf.d/10-opcache.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

---

## Monitoring & Logging

### 1. Application Logging

**Configure logging** (`config/logging.php`):
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 30,
    ],
];
```

### 2. Log Rotation

**Configure logrotate** (`/etc/logrotate.d/erp-crm`):
```
/var/www/erp-crm/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 erp-app www-data
    sharedscripts
}
```

### 3. Health Checks

Create health check endpoint for monitoring:
```bash
# Test health check
curl https://example.com/api/health

# Expected response:
{
  "status": "healthy",
  "database": "connected",
  "cache": "operational"
}
```

---

## Backup & Recovery

### 1. Database Backups

**Automated backup script** (`/usr/local/bin/erp-backup.sh`):
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/erp-crm"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="erp_production"
DB_USER="erp_user"
DB_PASS="<password>"

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Encrypt backup (optional)
gpg --encrypt --recipient admin@example.com $BACKUP_DIR/db_$DATE.sql.gz

# Remove old backups (keep 30 days)
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

# Upload to S3 (optional)
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://erp-backups/
```

**Schedule backup:**
```bash
sudo crontab -e

# Daily at 2 AM
0 2 * * * /usr/local/bin/erp-backup.sh
```

### 2. Application Backups

```bash
# Backup storage directory
tar -czf /var/backups/erp-storage-$(date +%Y%m%d).tar.gz \
    /var/www/erp-crm/storage/app

# Backup .env
cp /var/www/erp-crm/.env /var/backups/erp-env-$(date +%Y%m%d).bak
```

### 3. Recovery Procedure

```bash
# Restore database
gunzip < db_20260212.sql.gz | mysql -u erp_user -p erp_production

# Restore storage
tar -xzf erp-storage-20260212.tar.gz -C /var/www/erp-crm/

# Restore .env
cp /var/backups/erp-env-20260212.bak /var/www/erp-crm/.env

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Post-Deployment Validation

### 1. Configuration Check

```bash
# Validate configuration
php artisan config:validate --production

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### 2. Smoke Tests

```bash
# Test authentication
curl -X POST https://example.com/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@example.com","password":"<password>"}'

# Test tenant isolation
curl https://example.com/api/v1/health \
    -H "X-Tenant-ID: <tenant-id>"
```

### 3. Performance Tests

```bash
# Install Apache Bench (if not installed)
sudo apt install apache2-utils

# Load test
ab -n 1000 -c 10 https://example.com/api/v1/health
```

### 4. Security Scan

```bash
# Run vulnerability scan
composer audit

# Check for outdated dependencies
composer outdated --direct
```

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Check:**
```bash
# PHP error log
tail -f /var/log/php8.2-fpm.log

# Laravel log
tail -f /var/www/erp-crm/storage/logs/laravel.log

# Nginx error log
tail -f /var/log/nginx/erp-crm-error.log
```

**Fix:**
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Fix permissions
sudo chown -R erp-app:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 2. Database Connection Failed

**Check:**
```bash
# Test connection
mysql -u erp_user -p erp_production

# Verify .env settings
cat .env | grep DB_
```

**Fix:**
```bash
# Ensure MySQL is running
sudo systemctl status mysql

# Grant permissions
mysql -u root -p
GRANT ALL ON erp_production.* TO 'erp_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3. Queue Not Processing

**Check:**
```bash
# Check supervisor status
sudo supervisorctl status erp-queue-worker:*

# Check queue jobs
php artisan queue:failed
```

**Fix:**
```bash
# Restart workers
sudo supervisorctl restart erp-queue-worker:*

# Retry failed jobs
php artisan queue:retry all
```

#### 4. Slow Performance

**Check:**
```bash
# Enable query logging
php artisan debugbar:enable

# Check slow query log
sudo mysql -e "SHOW VARIABLES LIKE 'slow_query%';"
```

**Fix:**
```bash
# Optimize database
php artisan optimize

# Add missing indexes
php artisan migrate --force

# Clear old sessions
php artisan session:gc
```

---

## Maintenance

### Routine Tasks

**Daily:**
- Monitor error logs
- Check disk space
- Review failed queues

**Weekly:**
- Review performance metrics
- Update dependencies (security patches)
- Test backups

**Monthly:**
- Security audit
- Performance optimization
- Database optimization

### Update Procedure

```bash
# Backup first!
/usr/local/bin/erp-backup.sh

# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev
npm ci --only=production

# Run migrations
php artisan migrate --force

# Build assets
npm run build

# Clear caches
php artisan optimize:clear
php artisan optimize

# Restart services
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart erp-queue-worker:*
```

---

## Rollback Plan

**If deployment fails:**

```bash
# 1. Revert code
git reset --hard <previous-commit>

# 2. Restore database
gunzip < db_backup.sql.gz | mysql -u erp_user -p erp_production

# 3. Restore .env
cp /var/backups/erp-env-backup.bak .env

# 4. Clear caches
php artisan optimize:clear

# 5. Restart services
sudo systemctl restart php8.2-fpm nginx
sudo supervisorctl restart erp-queue-worker:*
```

---

## Support

**Documentation:**
- [Architecture Guide](./ARCHITECTURE.md)
- [Security Best Practices](./SECURITY_BEST_PRACTICES.md)
- [API Documentation](./API_DOCUMENTATION.md)

**Contact:**
- Email: support@example.com
- Issue Tracker: https://github.com/kasunvimarshana/AutoERP/issues

