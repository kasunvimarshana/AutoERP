# AutoERP - Deployment Guide

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Local Development Setup](#local-development-setup)
4. [Staging Environment Setup](#staging-environment-setup)
5. [Production Deployment](#production-deployment)
6. [Post-Deployment Tasks](#post-deployment-tasks)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)
9. [Scaling Guide](#scaling-guide)

## System Requirements

### Minimum Requirements

#### Backend Server
- **OS**: Ubuntu 22.04 LTS / RHEL 9 / Debian 12
- **CPU**: 2 cores
- **RAM**: 4 GB
- **Storage**: 20 GB SSD
- **PHP**: 8.3 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Process Manager**: Supervisor (for queue workers)

#### Frontend Server (Optional - can be same as backend)
- **Node.js**: 20.x LTS
- **npm**: 10.x

### Recommended Production Requirements

#### Application Servers (2+ instances for high availability)
- **CPU**: 4 cores
- **RAM**: 8 GB
- **Storage**: 50 GB SSD

#### Database Server (Primary + Replica)
- **CPU**: 4 cores
- **RAM**: 16 GB
- **Storage**: 100 GB SSD (RAID 10)

#### Cache & Queue Server
- **Redis**: Latest stable
- **CPU**: 2 cores
- **RAM**: 4 GB

#### Load Balancer
- Nginx or HAProxy
- SSL termination
- HTTP/2 support

## Pre-Deployment Checklist

### Code Preparation
- [ ] All code merged to `main` branch
- [ ] All tests passing
- [ ] Code review completed
- [ ] Security scan completed
- [ ] Database migrations tested
- [ ] Environment-specific configurations ready

### Infrastructure
- [ ] Servers provisioned
- [ ] Domain name configured
- [ ] SSL certificates obtained (Let's Encrypt or commercial)
- [ ] Database server setup
- [ ] Redis server setup
- [ ] File storage configured (S3 or equivalent)
- [ ] Backup system configured
- [ ] Monitoring tools installed

### Credentials
- [ ] Database credentials generated
- [ ] Application key generated
- [ ] API keys for third-party services
- [ ] SSL certificates configured
- [ ] SSH keys for deployment

## Local Development Setup

### 1. Clone Repository

```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

### 2. Backend Setup

```bash
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
nano .env
# Set:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=autoerp
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed --class=InitialDataSeeder

# Start development server
php artisan serve
```

### 3. Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

### 4. Queue Worker (Separate Terminal)

```bash
cd backend
php artisan queue:work --tries=3
```

### Access Application
- Backend API: `http://localhost:8000`
- Frontend: `http://localhost:5173`

## Staging Environment Setup

### 1. Server Preparation (Ubuntu 22.04)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml \
    php8.3-curl php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-gd \
    php8.3-redis php8.3-intl

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Supervisor
sudo apt install -y supervisor
```

### 2. Database Setup

```bash
# Create database
sudo mysql -u root -p

CREATE DATABASE autoerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erp_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';
GRANT ALL PRIVILEGES ON autoerp.* TO 'erp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Application Deployment

```bash
# Create application directory
sudo mkdir -p /var/www/autoerp
sudo chown -R $USER:www-data /var/www/autoerp

# Clone repository
cd /var/www/autoerp
git clone https://github.com/kasunvimarshana/AutoERP.git .

# Backend setup
cd backend
composer install --optimize-autoloader --no-dev
cp .env.example .env

# Edit .env file with production values
nano .env
# Set:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://staging.autoerp.com
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=autoerp
# DB_USERNAME=erp_user
# DB_PASSWORD=SecurePassword123!
# CACHE_DRIVER=redis
# QUEUE_CONNECTION=redis
# SESSION_DRIVER=redis

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Frontend build
cd ../frontend
npm install
npm run build

# Copy built files to public directory
sudo cp -r dist/* /var/www/autoerp/backend/public/
```

### 4. Nginx Configuration

Create `/etc/nginx/sites-available/autoerp`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name staging.autoerp.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name staging.autoerp.com;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/staging.autoerp.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/staging.autoerp.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/autoerp/backend/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';" always;

    # Logging
    access_log /var/log/nginx/autoerp-access.log;
    error_log /var/log/nginx/autoerp-error.log;

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Laravel specific
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # File upload size
    client_max_body_size 20M;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/autoerp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d staging.autoerp.com

# Auto-renewal
sudo certbot renew --dry-run
```

### 6. Queue Worker Setup (Supervisor)

Create `/etc/supervisor/conf.d/autoerp-worker.conf`:

```ini
[program:autoerp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/autoerp/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/autoerp-worker.log
stopwaitsecs=3600
```

Start workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start autoerp-worker:*
```

### 7. Cron Jobs

```bash
sudo crontab -e -u www-data

# Add Laravel scheduler
* * * * * cd /var/www/autoerp/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Production Deployment

### Multi-Server Architecture

```
┌─────────────────┐
│  Load Balancer  │ (Nginx/HAProxy)
│   + SSL Cert    │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
┌───▼──┐  ┌──▼───┐
│ App1 │  │ App2 │  (Application Servers)
└───┬──┘  └──┬───┘
    │         │
    └────┬────┘
         │
    ┌────▼─────┐
    │ Database │  (MySQL Primary + Replica)
    └────┬─────┘
         │
    ┌────▼─────┐
    │  Redis   │  (Cache & Queue)
    └──────────┘
```

### Load Balancer Configuration (Nginx)

```nginx
upstream autoerp_backend {
    least_conn;
    server app1.internal:80 max_fails=3 fail_timeout=30s;
    server app2.internal:80 max_fails=3 fail_timeout=30s;
}

server {
    listen 443 ssl http2;
    server_name erp.example.com;

    ssl_certificate /etc/ssl/certs/erp.example.com.crt;
    ssl_certificate_key /etc/ssl/private/erp.example.com.key;

    location / {
        proxy_pass http://autoerp_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # WebSocket support (if needed)
    location /ws {
        proxy_pass http://autoerp_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

### Database Replication

#### Primary Server

```sql
-- Configure primary
-- Edit /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
binlog_do_db = autoerp

-- Create replication user
CREATE USER 'replicator'@'%' IDENTIFIED BY 'ReplicationPassword123!';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
FLUSH PRIVILEGES;
```

#### Replica Server

```sql
-- Configure replica
-- Edit /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
server-id = 2
relay-log = /var/log/mysql/mysql-relay-bin.log
log_bin = /var/log/mysql/mysql-bin.log
binlog_do_db = autoerp
read_only = 1

-- Setup replication
CHANGE MASTER TO
    MASTER_HOST='primary-db-ip',
    MASTER_USER='replicator',
    MASTER_PASSWORD='ReplicationPassword123!',
    MASTER_LOG_FILE='mysql-bin.000001',
    MASTER_LOG_POS=107;

START SLAVE;
SHOW SLAVE STATUS\G
```

### File Storage (S3 or MinIO)

Configure in `.env`:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=autoerp-production
AWS_URL=https://autoerp-production.s3.amazonaws.com
```

## Post-Deployment Tasks

### 1. Verify Deployment

```bash
# Check application health
curl https://erp.example.com/api/v1/health

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue workers
sudo supervisorctl status

# Check cron jobs
sudo tail -f /var/log/syslog | grep artisan
```

### 2. Create Initial Admin User

```bash
php artisan tinker

>>> $user = App\Models\User::create([
    'name' => 'System Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('SecurePassword123!'),
    'tenant_id' => 1,
]);
>>> $user->assignRole('super-admin');
```

### 3. Configure Backup

```bash
# Install backup package
composer require spatie/laravel-backup

# Configure in config/backup.php
# Add to cron:
0 2 * * * cd /var/www/autoerp/backend && php artisan backup:run >> /var/log/backup.log 2>&1
```

## Monitoring & Maintenance

### Application Monitoring

#### Laravel Telescope (Development/Staging)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

#### Error Tracking (Sentry)
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn-here
```

### Server Monitoring

#### Install Monitoring Agent
```bash
# Prometheus Node Exporter
wget https://github.com/prometheus/node_exporter/releases/download/v1.6.1/node_exporter-1.6.1.linux-amd64.tar.gz
tar xvfz node_exporter-1.6.1.linux-amd64.tar.gz
sudo mv node_exporter-1.6.1.linux-amd64/node_exporter /usr/local/bin/
```

### Log Management

#### Centralized Logging
```bash
# Install Filebeat
curl -L -O https://artifacts.elastic.co/downloads/beats/filebeat/filebeat-8.11.0-amd64.deb
sudo dpkg -i filebeat-8.11.0-amd64.deb

# Configure to send logs to ELK stack or similar
```

### Performance Monitoring

#### PHP-FPM Status
```nginx
location ~ ^/(status|ping)$ {
    access_log off;
    allow 127.0.0.1;
    deny all;
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Check status:
```bash
curl http://localhost/status?full
```

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/autoerp-error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 2. Database Connection Failed
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check credentials in .env
# Check MySQL service
sudo systemctl status mysql
```

#### 3. Queue Jobs Not Processing
```bash
# Check queue workers
sudo supervisorctl status autoerp-worker:*

# Restart workers
sudo supervisorctl restart autoerp-worker:*

# Check failed jobs
php artisan queue:failed
```

#### 4. High Memory Usage
```bash
# Check PHP-FPM pool settings
sudo nano /etc/php/8.3/fpm/pool.d/www.conf

# Adjust:
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

## Scaling Guide

### Vertical Scaling
1. Increase server resources (CPU, RAM)
2. Optimize database queries
3. Enable OPcache
4. Increase PHP-FPM workers

### Horizontal Scaling
1. Add more application servers
2. Configure load balancer
3. Use centralized session storage (Redis)
4. Use centralized cache (Redis Cluster)
5. Use external file storage (S3)

### Database Scaling
1. Read replicas for SELECT queries
2. Connection pooling
3. Query optimization
4. Indexing strategy
5. Partitioning for large tables

### Cache Optimization
```bash
# Redis cluster for high availability
# Use cache tags for granular invalidation
# Implement cache warming for critical data
```

## Security Checklist

- [ ] SSL/TLS enabled (A+ rating on SSL Labs)
- [ ] Firewall configured (UFW/iptables)
- [ ] SSH key-only authentication
- [ ] Database access restricted to application servers
- [ ] Regular security updates applied
- [ ] Fail2ban configured
- [ ] DDoS protection enabled (Cloudflare/AWS Shield)
- [ ] Regular backups tested
- [ ] Security headers configured
- [ ] Rate limiting enabled
- [ ] CORS properly configured
- [ ] Environment variables secured
- [ ] File upload validation
- [ ] SQL injection prevention (using Eloquent)
- [ ] XSS prevention (output escaping)
- [ ] CSRF protection enabled

## Maintenance Tasks

### Daily
- [ ] Monitor error logs
- [ ] Check queue workers status
- [ ] Review failed jobs
- [ ] Check disk space

### Weekly
- [ ] Review application logs
- [ ] Check database performance
- [ ] Review slow query log
- [ ] Test backups

### Monthly
- [ ] Security updates
- [ ] Dependency updates
- [ ] Performance optimization
- [ ] Database maintenance
- [ ] Review monitoring metrics

### Quarterly
- [ ] Security audit
- [ ] Penetration testing
- [ ] Disaster recovery drill
- [ ] Architecture review

## Rollback Procedure

### Quick Rollback
```bash
# Revert to previous version
cd /var/www/autoerp
git fetch
git checkout previous-stable-tag

# Rerun deployment steps
composer install --optimize-autoloader --no-dev
php artisan migrate:rollback
php artisan cache:clear
php artisan config:cache

# Restart services
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart autoerp-worker:*
```

## Support & Resources

- **Documentation**: https://github.com/kasunvimarshana/AutoERP
- **Issue Tracker**: https://github.com/kasunvimarshana/AutoERP/issues
- **Community Forum**: TBD
- **Email Support**: TBD

---

**Last Updated**: 2026-02-03
**Version**: 1.0.0
