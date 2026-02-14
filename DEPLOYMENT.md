# AutoERP - Deployment Guide

## Table of Contents
1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Environment Setup](#environment-setup)
3. [Database Configuration](#database-configuration)
4. [Deployment Methods](#deployment-methods)
5. [Post-Deployment Tasks](#post-deployment-tasks)
6. [Monitoring & Maintenance](#monitoring--maintenance)
7. [Troubleshooting](#troubleshooting)

## Pre-Deployment Checklist

### Requirements
- [ ] PHP 8.1 or higher installed
- [ ] Composer installed
- [ ] Node.js 18.x or higher
- [ ] MySQL 8.0+ or PostgreSQL 13+
- [ ] Redis 6.x (optional but recommended)
- [ ] Web server (Nginx/Apache)
- [ ] SSL certificate (for HTTPS)
- [ ] Domain name configured
- [ ] Backup strategy in place

### Application Readiness
- [ ] All tests passing (`php artisan test`)
- [ ] Code style checked (`./vendor/bin/pint`)
- [ ] Assets built (`npm run build`)
- [ ] Database migrations ready
- [ ] Seeders configured (if needed)
- [ ] Environment variables set
- [ ] Security configurations reviewed

## Environment Setup

### 1. Production Environment Variables

Create a `.env` file with production settings:

```env
# Application
APP_NAME="AutoERP"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-mail-username
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=your-domain.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 2. Generate Application Key

```bash
php artisan key:generate --force
```

## Database Configuration

### 1. Create Database

```sql
CREATE DATABASE autoerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'autoerp'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON autoerp.* TO 'autoerp'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Run Migrations

```bash
php artisan migrate --force
```

### 3. Seed Database (Optional)

```bash
php artisan db:seed --force
```

## Deployment Methods

### Method 1: Traditional Server Deployment

#### Step 1: Clone Repository
```bash
cd /var/www
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

#### Step 2: Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

#### Step 3: Set Permissions
```bash
chown -R www-data:www-data /var/www/AutoERP
chmod -R 775 storage bootstrap/cache
```

#### Step 4: Configure Web Server

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/AutoERP/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Step 5: SSL Setup
```bash
# Using Certbot for Let's Encrypt
sudo certbot --nginx -d your-domain.com
```

#### Step 6: Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 7: Setup Supervisor for Queue Workers
```ini
[program:autoerp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/AutoERP/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/AutoERP/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start autoerp-worker:*
```

### Method 2: Docker Deployment

#### Step 1: Build and Start Containers
```bash
docker-compose up -d --build
```

#### Step 2: Run Migrations
```bash
docker-compose exec app php artisan migrate --force
```

#### Step 3: Seed Database (Optional)
```bash
docker-compose exec app php artisan db:seed --force
```

#### Step 4: Optimize
```bash
docker-compose exec app php artisan optimize
```

### Method 3: Platform as a Service (e.g., Laravel Forge, Envoyer)

Follow platform-specific deployment guides. Generally:

1. Connect repository
2. Configure environment
3. Run deployment script
4. Enable zero-downtime deployments

## Post-Deployment Tasks

### 1. Verify Deployment
```bash
# Check application status
curl https://your-domain.com/up

# Test API endpoint
curl https://your-domain.com/api/v1/auth/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@demo.com","password":"password"}'
```

### 2. Setup Scheduled Tasks

Add to crontab:
```bash
* * * * * cd /var/www/AutoERP && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Configure Backups

**Database Backup:**
```bash
#!/bin/bash
# backup-database.sh
mysqldump -u username -p password autoerp | gzip > backup-$(date +%Y%m%d).sql.gz
```

**Application Backup:**
```bash
#!/bin/bash
# backup-app.sh
tar -czf autoerp-backup-$(date +%Y%m%d).tar.gz /var/www/AutoERP \
    --exclude=vendor \
    --exclude=node_modules \
    --exclude=storage/logs
```

Schedule with cron:
```bash
0 2 * * * /path/to/backup-database.sh
0 3 * * * /path/to/backup-app.sh
```

### 4. Setup Monitoring

**Application Health:**
- Configure uptime monitoring (Pingdom, UptimeRobot)
- Setup application monitoring (New Relic, Datadog)
- Configure error tracking (Sentry, Bugsnag)

**Server Monitoring:**
- CPU, Memory, Disk usage
- Database performance
- Queue processing
- Log monitoring

## Monitoring & Maintenance

### Daily Tasks
- [ ] Check error logs
- [ ] Monitor queue workers
- [ ] Verify backups completed
- [ ] Check application uptime

### Weekly Tasks
- [ ] Review performance metrics
- [ ] Check disk space
- [ ] Review security logs
- [ ] Test backup restoration

### Monthly Tasks
- [ ] Update dependencies (security patches)
- [ ] Review and rotate logs
- [ ] Optimize database
- [ ] Review user feedback

### Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Optimize application
php artisan optimize

# Clear expired sessions
php artisan session:flush

# Prune old records
php artisan model:prune

# Rotate logs
php artisan log:clear --keep=30
```

## Troubleshooting

### Issue: 500 Internal Server Error

**Check:**
1. Verify `.env` file exists and is readable
2. Check file permissions (775 for storage, 775 for bootstrap/cache)
3. Review error logs: `storage/logs/laravel.log`
4. Ensure all environment variables are set

```bash
php artisan config:clear
php artisan cache:clear
```

### Issue: Database Connection Failed

**Check:**
1. Database credentials in `.env`
2. Database server is running
3. Firewall rules allow connection
4. User has proper permissions

```bash
# Test connection
mysql -h hostname -u username -p database_name
```

### Issue: Queue Jobs Not Processing

**Check:**
1. Queue workers are running
2. Redis connection (if using Redis queue)
3. Worker logs for errors

```bash
# Restart workers
php artisan queue:restart

# Check supervisor status
sudo supervisorctl status autoerp-worker:*
```

### Issue: High Memory Usage

**Solutions:**
1. Optimize queries (add eager loading)
2. Implement pagination
3. Enable caching
4. Scale horizontally

### Issue: Slow Response Times

**Check:**
1. Database query performance
2. Cache configuration
3. Asset optimization
4. CDN configuration

```bash
# Enable query logging
DB_LOG_QUERIES=true

# Profile application
php artisan route:list --sort=name
```

## Security Best Practices

### 1. Regular Updates
```bash
# Update Composer dependencies
composer update --no-dev

# Update npm packages
npm update
npm audit fix
```

### 2. Security Headers

Add to Nginx config:
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
```

### 3. Rate Limiting

Configure in `config/sanctum.php` and middleware

### 4. Regular Audits
- Review user permissions
- Check access logs
- Audit API usage
- Review security patches

## Rollback Procedure

If deployment fails:

1. **Restore Previous Version:**
```bash
git checkout previous-tag-or-commit
composer install --no-dev
npm ci && npm run build
php artisan migrate:rollback
php artisan optimize
```

2. **Restore Database:**
```bash
mysql -u username -p database_name < backup-file.sql
```

3. **Clear Caches:**
```bash
php artisan optimize:clear
```

4. **Verify Application:**
```bash
curl https://your-domain.com/up
```

## Support

For deployment issues:
- Email: devops@autoerp.com
- Documentation: See SETUP.md and ARCHITECTURE.md
- GitHub Issues: https://github.com/kasunvimarshana/AutoERP/issues

---

**Last Updated:** 2026-01-29
**Version:** 1.0.0
