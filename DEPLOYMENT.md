# Deployment Guide

## Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0+ or PostgreSQL 13+
- Node.js 18+ and npm
- BCMath PHP extension

## Installation Steps

### 1. Clone Repository
```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:
- Database connection
- JWT_SECRET (use `php artisan key:generate` output)
- Application URL
- Mail settings
- Queue driver (redis/database recommended for production)

### 4. Database Setup
```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
```

### 5. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 6. Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 7. Setup Queue Worker (Supervisor)
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Add:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 8. Web Server Configuration

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/public;

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

#### Apache
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/public

    <Directory /path/to/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Security Checklist

- [ ] Set strong `APP_KEY` and `JWT_SECRET`
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure rate limiting
- [ ] Set up database backups
- [ ] Configure log rotation
- [ ] Enable firewall (UFW/iptables)
- [ ] Disable directory listing
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Configure CORS properly
- [ ] Enable security headers

## Performance Optimization

### Database
- Enable query caching
- Create proper indexes
- Use connection pooling
- Regular optimization: `OPTIMIZE TABLE`

### Caching
```bash
# Use Redis for cache and sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### PHP-FPM
Optimize `php-fpm.conf`:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

## Monitoring

### Setup Laravel Horizon (for Redis queues)
```bash
composer require laravel/horizon
php artisan horizon:install
```

### Log Monitoring
- Configure centralized logging (Sentry, Papertrail)
- Set up application monitoring (New Relic, Datadog)
- Monitor queue workers
- Track error rates

## Backup Strategy

### Database Backup (Daily)
```bash
#!/bin/bash
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
tar -czf storage_$(date +%Y%m%d).tar.gz storage/
```

### Automated Backups
Use Laravel Backup package or cloud backup solutions

## Scaling

### Horizontal Scaling
- Load balancer (Nginx, HAProxy)
- Multiple application servers
- Shared storage (NFS, S3)
- Redis cluster for cache/sessions
- Database read replicas

### Vertical Scaling
- Increase PHP-FPM workers
- Optimize MySQL/PostgreSQL
- Add more RAM/CPU

## Troubleshooting

### Clear All Caches
```bash
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Permission Issues
```bash
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### Queue Not Processing
```bash
php artisan queue:restart
supervisorctl restart laravel-worker:*
```

## Health Checks

Test application health:
```bash
curl https://your-domain.com/up
curl https://your-domain.com/api/v1/health
```

## Maintenance Mode

Enable:
```bash
php artisan down --secret="maintenance-bypass-token"
```

Disable:
```bash
php artisan up
```

Access while in maintenance mode:
```
https://your-domain.com/maintenance-bypass-token
```

## Support

For issues, contact: support@your-domain.com
