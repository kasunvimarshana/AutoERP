# AutoERP Setup Guide

This guide provides step-by-step instructions for setting up AutoERP in different environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Local Development Setup](#local-development-setup)
3. [Docker Setup (Recommended)](#docker-setup-recommended)
4. [Manual Installation](#manual-installation)
5. [Database Configuration](#database-configuration)
6. [Frontend Setup](#frontend-setup)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

## Prerequisites

### For Docker Setup (Recommended)
- Docker 20.10 or higher
- Docker Compose 2.0 or higher
- Git

### For Manual Setup
- PHP 8.1 or higher with extensions:
  - PDO
  - pdo_pgsql (or pdo_mysql)
  - mbstring
  - xml
  - ctype
  - json
  - bcmath
  - fileinfo
  - tokenizer
  - redis
- Composer 2.x
- Node.js 18.x or higher
- NPM 9.x or higher
- PostgreSQL 15+ OR MySQL 8.0+
- Redis 7.x
- Git

## Local Development Setup

### Option 1: Docker Setup (Recommended)

This is the easiest way to get started with AutoERP.

#### Step 1: Clone the Repository

```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

#### Step 2: Configure Environment

```bash
# Copy the example environment file
cp .env.example .env

# Edit .env if needed (optional for Docker setup)
# The default values work out of the box with Docker
```

#### Step 3: Start Docker Containers

```bash
# Start all services in detached mode
docker-compose up -d

# Check that all containers are running
docker-compose ps
```

You should see the following containers running:
- `autoerp-app` - PHP application
- `autoerp-nginx` - Web server
- `autoerp-db` - PostgreSQL database
- `autoerp-redis` - Redis cache/queue
- `autoerp-queue` - Queue worker
- `autoerp-mailhog` - Email testing

#### Step 4: Install Dependencies

```bash
# Install PHP dependencies
docker-compose exec app composer install

# Install Node dependencies
docker-compose exec app npm install
```

#### Step 5: Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

#### Step 6: Run Database Migrations

```bash
# Run migrations
docker-compose exec app php artisan migrate

# (Optional) Seed database with sample data
docker-compose exec app php artisan db:seed
```

#### Step 7: Build Frontend Assets

```bash
# Build for development
docker-compose exec app npm run dev

# OR build for production
docker-compose exec app npm run build
```

#### Step 8: Generate API Documentation

```bash
docker-compose exec app php artisan l5-swagger:generate
```

#### Step 9: Access the Application

- **Web Application**: http://localhost:8080
- **API**: http://localhost:8080/api/v1
- **Swagger Documentation**: http://localhost:8080/api/documentation
- **MailHog (Email Testing)**: http://localhost:8025
- **Database**: localhost:5432 (user: postgres, password: secret)
- **Redis**: localhost:6379

### Option 2: Manual Installation

For those who prefer not to use Docker or need more control over the setup.

#### Step 1: Clone the Repository

```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

#### Step 2: Install PHP Dependencies

```bash
composer install
```

#### Step 3: Install Node Dependencies

```bash
npm install
```

#### Step 4: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and configure the following:

```env
APP_NAME="AutoERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=autoerp
DB_USERNAME=postgres
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### Step 5: Generate Application Key

```bash
php artisan key:generate
```

#### Step 6: Create Database

```bash
# For PostgreSQL
psql -U postgres -c "CREATE DATABASE autoerp;"

# For MySQL
mysql -u root -p -e "CREATE DATABASE autoerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

#### Step 7: Run Migrations

```bash
php artisan migrate

# (Optional) Seed database
php artisan db:seed
```

#### Step 8: Build Frontend Assets

```bash
# For development (with hot reload)
npm run dev

# For production
npm run build
```

#### Step 9: Generate API Documentation

```bash
php artisan l5-swagger:generate
```

#### Step 10: Start Development Servers

In separate terminal windows:

```bash
# Terminal 1: PHP development server
php artisan serve

# Terminal 2: Queue worker (optional)
php artisan queue:work

# Terminal 3: Vite dev server (for hot reload)
npm run dev
```

Access the application at http://localhost:8000

## Database Configuration

### PostgreSQL (Recommended)

PostgreSQL is recommended for production due to:
- Better support for JSON/JSONB columns
- Row-level security
- Advanced indexing capabilities
- ACID compliance

#### Installation

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

**macOS:**
```bash
brew install postgresql@15
brew services start postgresql@15
```

**Windows:**
Download from https://www.postgresql.org/download/windows/

#### Configuration

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE autoerp;
CREATE USER autoerp_user WITH ENCRYPTED PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE autoerp TO autoerp_user;
\q
```

Update `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=autoerp
DB_USERNAME=autoerp_user
DB_PASSWORD=your_secure_password
```

### MySQL (Alternative)

If you prefer MySQL:

#### Installation

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql
sudo mysql_secure_installation
```

**macOS:**
```bash
brew install mysql@8.0
brew services start mysql@8.0
```

#### Configuration

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE autoerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'autoerp_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON autoerp.* TO 'autoerp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=autoerp_user
DB_PASSWORD=your_secure_password
```

## Redis Configuration

Redis is used for caching, sessions, and queue management.

### Installation

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

**macOS:**
```bash
brew install redis
brew services start redis
```

**Windows:**
Use WSL2 or download from https://github.com/microsoftarchive/redis/releases

### Configuration

```bash
# Test Redis connection
redis-cli ping
# Should return: PONG
```

Update `.env`:
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Frontend Setup

### Development Mode

For active development with hot module replacement (HMR):

```bash
npm run dev
```

This starts Vite dev server on http://localhost:5173 with hot reload.

### Production Build

For production deployment:

```bash
npm run build
```

This creates optimized, minified assets in `public/build/`.

### Preview Production Build

To preview the production build locally:

```bash
npm run preview
```

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/CustomerTest.php

# Run specific test method
php artisan test --filter test_can_create_customer
```

### Setting Up Test Database

Create a separate database for testing:

```bash
# PostgreSQL
psql -U postgres -c "CREATE DATABASE autoerp_test;"

# MySQL
mysql -u root -p -e "CREATE DATABASE autoerp_test;"
```

Update `phpunit.xml`:
```xml
<env name="DB_DATABASE" value="autoerp_test"/>
```

## Code Quality Tools

### PHP Code Sniffer (Linting)

```bash
# Check code style
./vendor/bin/pint

# Dry run (check without fixing)
./vendor/bin/pint --test
```

### PHPStan (Static Analysis)

```bash
./vendor/bin/phpstan analyse
```

## Queue Workers

For background job processing:

### Development

```bash
php artisan queue:work
```

### Production (with Supervisor)

Install Supervisor:
```bash
sudo apt install supervisor
```

Create configuration file `/etc/supervisor/conf.d/autoerp-queue.conf`:
```ini
[program:autoerp-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/autoerp/artisan queue:work --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/autoerp/storage/logs/queue.log
```

Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start autoerp-queue:*
```

## Scheduled Tasks (Cron)

Add to crontab (`crontab -e`):

```bash
* * * * * cd /path/to/autoerp && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Common Issues

#### 1. Permission Issues

```bash
# Fix storage and cache permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 2. Database Connection Error

- Check database is running: `sudo systemctl status postgresql`
- Verify credentials in `.env`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

#### 3. Redis Connection Error

- Check Redis is running: `redis-cli ping`
- Verify REDIS_HOST and REDIS_PORT in `.env`

#### 4. Composer Install Fails

```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Install with verbose output
composer install -vvv
```

#### 5. NPM Install Fails

```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules and package-lock.json
rm -rf node_modules package-lock.json

# Reinstall
npm install
```

#### 6. Migration Fails

```bash
# Rollback and retry
php artisan migrate:rollback
php artisan migrate

# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh
```

#### 7. Queue Jobs Not Processing

```bash
# Check queue worker is running
ps aux | grep queue:work

# Restart queue worker
php artisan queue:restart
```

#### 8. API Documentation Not Generated

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Regenerate documentation
php artisan l5-swagger:generate
```

### Docker-Specific Issues

#### Container Not Starting

```bash
# Check container logs
docker-compose logs app

# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

#### Database Connection in Docker

If you get "Connection refused" errors:
- Ensure `DB_HOST` is set to `db` (container name) not `127.0.0.1`
- Check containers are on the same network: `docker-compose ps`

#### Permission Issues in Docker

```bash
# Fix permissions inside container
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Getting Help

If you encounter issues not covered here:

1. Check the logs:
   - Laravel: `storage/logs/laravel.log`
   - Nginx: `docker-compose logs nginx`
   - PHP: `docker-compose logs app`

2. Enable debug mode in `.env`:
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

3. Open an issue on GitHub with:
   - Error message
   - Steps to reproduce
   - Environment details (OS, PHP version, etc.)

## Next Steps

After successful setup:

1. **Configure Multi-Tenancy**: Set up your first tenant
2. **Create Users**: Add users with appropriate roles
3. **Explore API**: Visit `/api/documentation` for interactive API docs
4. **Customize**: Modify modules to fit your business needs
5. **Deploy**: Follow the deployment guide for production setup

## Additional Resources

- [Architecture Documentation](ARCHITECTURE.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [API Documentation](http://localhost:8080/api/documentation)
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide)

---

**Last Updated**: 2026-01-31
**Version**: 1.0.0
