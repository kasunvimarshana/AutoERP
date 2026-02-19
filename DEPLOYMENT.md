# Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the ModularSaaS application to production environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [Server Requirements](#server-requirements)
4. [Deployment Steps](#deployment-steps)
5. [Database Setup](#database-setup)
6. [Queue Configuration](#queue-configuration)
7. [Cache Configuration](#cache-configuration)
8. [SSL/HTTPS Setup](#sslhttps-setup)
9. [Performance Optimization](#performance-optimization)
10. [Monitoring and Maintenance](#monitoring-and-maintenance)

## Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8+ or PostgreSQL 13+
- Redis (recommended for cache and queues)
- Web server (Nginx or Apache)
- Git

## Environment Setup

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/kasunvimarshana/ModularSaaS-LaravelVue.git
cd ModularSaaS-LaravelVue
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install JavaScript dependencies
npm ci --production
```

### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Update Environment Variables

Edit `.env` file with production settings:

```env
APP_NAME="ModularSaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=production_db
DB_USERNAME=prod_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Multi-Tenancy
TENANCY_CENTRAL_DB=central_database
TENANCY_TENANT_DB_PREFIX=tenant_

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

## Server Requirements

### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    root /var/www/ModularSaaS-LaravelVue/public;

    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### PHP-FPM Configuration

Edit `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Increase timeouts for heavy operations
request_terminate_timeout = 300
```

### File Permissions

```bash
# Set proper ownership
chown -R www-data:www-data /var/www/ModularSaaS-LaravelVue

# Set directory permissions
find /var/www/ModularSaaS-LaravelVue -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/ModularSaaS-LaravelVue -type f -exec chmod 644 {} \;

# Make storage and cache writable
chmod -R 775 storage bootstrap/cache
```

## Deployment Steps

### 1. Build Frontend Assets

```bash
npm run build
```

### 2. Run Database Migrations

```bash
php artisan migrate --force
```

### 3. Seed Initial Data

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

### 4. Cache Configuration

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### 5. Optimize Application

```bash
# Optimize autoloader
composer dump-autoload --optimize

# Clear and cache everything
php artisan optimize
```

### 6. Link Storage

```bash
php artisan storage:link
```

## Database Setup

### Multi-Tenancy Database Setup

```bash
# Run central database migrations
php artisan migrate --database=central --force

# Run tenant migrations
php artisan tenants:migrate --force
```

### Database Backups

Create a backup script `/usr/local/bin/backup-db.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mysql"
DB_NAME="production_db"
DB_USER="prod_user"
DB_PASS="secure_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

Schedule in crontab:

```bash
0 2 * * * /usr/local/bin/backup-db.sh
```

## Queue Configuration

### Using Supervisor for Queue Workers

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ModularSaaS-LaravelVue/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/ModularSaaS-LaravelVue/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Laravel Scheduler

Add to crontab (`crontab -e`):

```bash
* * * * * cd /var/www/ModularSaaS-LaravelVue && php artisan schedule:run >> /dev/null 2>&1
```

## Cache Configuration

### Redis Setup

```bash
# Install Redis
sudo apt-get install redis-server

# Enable Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Configure Redis (edit /etc/redis/redis.conf)
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### Application Cache Warming

```bash
# Warm up cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## SSL/HTTPS Setup

### Using Let's Encrypt

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already configured by certbot)
sudo certbot renew --dry-run
```

## Performance Optimization

### 1. OPcache Configuration

Edit `/etc/php/8.3/fpm/conf.d/10-opcache.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

### 2. Application Optimizations

```bash
# Use production optimizations
php artisan optimize

# Precompile views
php artisan view:cache

# Cache icons (if using Blade Icons)
php artisan icons:cache
```

### 3. Database Optimizations

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tenant_id ON users(tenant_id);
CREATE INDEX idx_products_status ON products(status);
```

### 4. CDN Setup (Optional)

Configure asset URLs in `.env`:

```env
ASSET_URL=https://cdn.yourdomain.com
```

## Monitoring and Maintenance

### Log Rotation

Create `/etc/logrotate.d/laravel`:

```
/var/www/ModularSaaS-LaravelVue/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        /usr/bin/supervisorctl restart laravel-worker:*
    endscript
}
```

### Health Check Endpoint

Create a health check route in `routes/api.php`:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});
```

### Monitoring with Laravel Telescope (Development Only)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Important**: Ensure Telescope is disabled in production by checking `config/telescope.php`:

```php
'enabled' => env('TELESCOPE_ENABLED', false),
```

### Application Monitoring

Consider using:
- **New Relic** - Application performance monitoring
- **Sentry** - Error tracking
- **DataDog** - Infrastructure monitoring
- **Laravel Forge** - Server management

## Deployment Checklist

- [ ] Environment configured correctly
- [ ] Database migrations run
- [ ] Initial data seeded
- [ ] Assets built and optimized
- [ ] Cache cleared and warmed
- [ ] File permissions set correctly
- [ ] SSL certificate installed
- [ ] Queue workers running
- [ ] Scheduler configured
- [ ] Backups configured
- [ ] Monitoring setup
- [ ] Log rotation configured
- [ ] Security headers configured
- [ ] Firewall rules set
- [ ] DNS records configured

## Rolling Back Deployments

If issues occur:

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Roll back migration
php artisan migrate:rollback --step=1

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo supervisorctl restart laravel-worker:*
```

## Zero-Downtime Deployment

For zero-downtime deployments, consider using:
- **Laravel Envoyer** - Automated deployment service
- **Deployer** - PHP deployment tool
- **GitHub Actions** - CI/CD pipeline

Example GitHub Actions workflow:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/ModularSaaS-LaravelVue
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci --production
            npm run build
            php artisan migrate --force
            php artisan optimize
            sudo supervisorctl restart laravel-worker:*
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check `.env` file exists and is configured
   - Check file permissions
   - Check error logs: `storage/logs/laravel.log`

2. **Queue not processing**
   - Check supervisor status: `sudo supervisorctl status`
   - Check Redis connection
   - View worker logs: `storage/logs/worker.log`

3. **Slow performance**
   - Enable OPcache
   - Use Redis for cache and sessions
   - Optimize database queries
   - Enable CDN for static assets

## Support

For issues or questions, please refer to:
- Documentation: `/docs`
- GitHub Issues: `https://github.com/kasunvimarshana/ModularSaaS-LaravelVue/issues`
