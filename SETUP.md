# AutoERP - Setup Guide

## Quick Start

This guide will help you get the AutoERP platform up and running on your local machine.

## System Requirements

- **PHP**: 8.1 or higher
- **Node.js**: 18.x or higher
- **Composer**: Latest version
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Redis**: 6.x or higher (optional, for caching and queues)
- **Git**: For version control

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

```bash
# Copy the environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Configure Database

Edit the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. Run Database Migrations

```bash
# Run migrations
php artisan migrate

# (Optional) Seed the database with sample data
php artisan db:seed
```

### 7. Install Laravel Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 8. Install Spatie Permission

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### 9. Generate Swagger Documentation

```bash
php artisan vendor:publish --provider="Darkaonline\L5Swagger\L5SwaggerServiceProvider"
php artisan l5-swagger:generate
```

### 10. Build Frontend Assets

#### Development Mode (with hot reload)
```bash
npm run dev
```

#### Production Mode
```bash
npm run build
```

### 11. Start Development Server

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Vite development server
npm run dev
```

The application should now be accessible at `http://localhost:8000`

## Additional Configuration

### Redis Configuration (Optional)

If you want to use Redis for caching and queues:

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Queue Workers

For background job processing:

```bash
php artisan queue:work
```

Or use Laravel Horizon for advanced queue management:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

### Task Scheduling

Add this to your crontab for scheduled tasks:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Development Workflow

### Code Style

This project uses Laravel Pint for code formatting:

```bash
# Fix code style
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Generating API Documentation

After making changes to API endpoints:

```bash
php artisan l5-swagger:generate
```

Access API documentation at: `http://localhost:8000/api/documentation`

### Database Management

```bash
# Create a new migration
php artisan make:migration create_table_name

# Rollback last migration
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh

# Reset and seed
php artisan migrate:fresh --seed
```

## Module Development

### Creating a New Module

```bash
# Create module structure
mkdir -p app/Modules/ModuleName/{Controllers,Services,Repositories,Models,DTOs,Policies,Events,Listeners,Requests}

# Create controller
php artisan make:controller Modules/ModuleName/Controllers/ModuleNameController

# Create model
php artisan make:model Modules/ModuleName/Models/ModuleName

# Create migration
php artisan make:migration create_module_names_table
```

### Creating Frontend Components

```bash
# Create new Vue component
touch resources/js/modules/module-name/components/ComponentName.vue

# Create new view
touch resources/js/modules/module-name/views/ViewName.vue
```

## Troubleshooting

### Common Issues

#### 1. Permission Denied Errors

```bash
# Fix storage and cache permissions
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 2. Node Module Issues

```bash
# Clear npm cache and reinstall
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

#### 3. Composer Issues

```bash
# Clear composer cache
composer clear-cache
rm -rf vendor composer.lock
composer install
```

#### 4. Database Connection Issues

- Verify database credentials in `.env`
- Ensure database server is running
- Check if database exists
- Verify user permissions

#### 5. Vite Build Issues

```bash
# Clear Vite cache
rm -rf node_modules/.vite
npm run build
```

### Logs

Check application logs for errors:

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Clear logs
echo "" > storage/logs/laravel.log
```

## Production Deployment

### Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Build production assets
npm run build
```

### Environment Variables

Ensure production `.env` has:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com

# Use strong values
APP_KEY=base64:...

# Production database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-strong-password
```

### Security Checklist

- [ ] APP_DEBUG=false in production
- [ ] Strong APP_KEY generated
- [ ] HTTPS enforced
- [ ] Database credentials secure
- [ ] File permissions properly set
- [ ] .env file not accessible via web
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] Security headers configured

## Docker Deployment

```bash
# Build Docker image
docker build -t autoerp .

# Run with Docker Compose
docker-compose up -d

# View logs
docker-compose logs -f

# Stop containers
docker-compose down
```

## Maintenance

### Backup

```bash
# Backup database
php artisan backup:run

# Backup files
tar -czf backup-$(date +%Y%m%d).tar.gz storage/ public/uploads/
```

### Updates

```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Clear caches
php artisan optimize:clear

# Rebuild assets
npm run build
```

## Support

For issues, questions, or contributions:

- **Documentation**: See `ARCHITECTURE.md` for detailed architecture info
- **Issues**: Create an issue on GitHub
- **Email**: support@autoerp.com

## License

Proprietary - All rights reserved
