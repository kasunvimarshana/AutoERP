# AutoERP Setup Guide

This guide will walk you through setting up AutoERP on your local development environment.

## Prerequisites

### Docker Setup (Recommended)
- Docker 24+ 
- Docker Compose 2+
- Git

### Manual Setup
- PHP 8.3 or higher
- Composer 2.x
- Node.js 20+ and npm 10+
- PostgreSQL 15+ or MySQL 8+
- Redis 7+
- Git

## Installation

### Option 1: Docker Setup (Recommended)

1. **Clone the repository**
   ```bash
   git clone https://github.com/kasunvimarshana/AutoERP.git
   cd AutoERP
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

4. **Install PHP dependencies**
   ```bash
   docker-compose exec app composer install
   ```

5. **Install Node.js dependencies**
   ```bash
   docker-compose exec app npm install
   ```

6. **Generate application key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

7. **Run database migrations**
   ```bash
   docker-compose exec app php artisan migrate --seed
   ```

8. **Build frontend assets**
   ```bash
   docker-compose exec app npm run build
   ```

9. **Access the application**
   - Web: http://localhost:8080
   - API: http://localhost:8080/api/v1
   - API Health: http://localhost:8080/api/v1/health

### Option 2: Manual Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/kasunvimarshana/AutoERP.git
   cd AutoERP
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file and configure**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=autoerp
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate --seed
   ```

7. **Build frontend assets**
   
   For development:
   ```bash
   npm run dev
   ```
   
   For production:
   ```bash
   npm run build
   ```

8. **Start development servers**
   
   Backend:
   ```bash
   php artisan serve
   ```
   
   Frontend (in separate terminal):
   ```bash
   npm run dev
   ```

9. **Access the application**
   - Backend: http://localhost:8000
   - Frontend: http://localhost:5173
   - API: http://localhost:8000/api/v1

## Configuration

### Database Configuration

The application supports both PostgreSQL and MySQL. PostgreSQL is recommended for production due to better JSON support and advanced features.

#### PostgreSQL (Recommended)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=autoerp
DB_USERNAME=autoerp_user
DB_PASSWORD=your_secure_password
```

#### MySQL
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=autoerp_user
DB_PASSWORD=your_secure_password
```

### Redis Configuration

Redis is used for caching, sessions, and queue management:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Multi-Tenancy Configuration

```env
TENANCY_ENABLED=true
TENANCY_DATABASE_AUTO_DELETE=false
```

## Development

### Running the Development Servers

#### With Docker
```bash
# All services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

#### Manual Setup
```bash
# Backend (Terminal 1)
php artisan serve

# Frontend (Terminal 2)
npm run dev

# Queue Worker (Terminal 3)
php artisan queue:work
```

### Database Operations

#### Running Migrations
```bash
# With Docker
docker-compose exec app php artisan migrate

# Manual
php artisan migrate
```

#### Seeding Database
```bash
# With Docker
docker-compose exec app php artisan db:seed

# Manual
php artisan db:seed
```

#### Rolling Back Migrations
```bash
# With Docker
docker-compose exec app php artisan migrate:rollback

# Manual
php artisan migrate:rollback
```

#### Fresh Migration (WARNING: Drops all tables)
```bash
# With Docker
docker-compose exec app php artisan migrate:fresh --seed

# Manual
php artisan migrate:fresh --seed
```

## Testing

### Running Tests

#### PHPUnit (Backend Tests)
```bash
# With Docker
docker-compose exec app php artisan test

# With coverage
docker-compose exec app php artisan test --coverage

# Manual
php artisan test
```

#### Vitest (Frontend Tests)
```bash
# With Docker
docker-compose exec app npm run test

# Manual
npm run test
```

### Writing Tests

Tests are located in:
- Backend: `tests/Unit/` and `tests/Feature/`
- Frontend: `resources/js/**/__tests__/`

Example test structure:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductApiTest extends TestCase
{
    public function test_can_list_products()
    {
        $response = $this->getJson('/api/v1/products');
        
        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }
}
```

## API Usage

### Example API Requests

#### List Products
```bash
GET /api/v1/products?fields=id,name,price&filter[status]=active&sort=-created_at&per_page=20
```

#### Create Product
```bash
POST /api/v1/products
Content-Type: application/json

{
  "name": "Product Name",
  "sku": "PRD-123",
  "description": "Product description",
  "price": 99.99,
  "status": "active",
  "category_id": 1
}
```

#### Update Product
```bash
PUT /api/v1/products/1
Content-Type: application/json

{
  "name": "Updated Product Name",
  "price": 149.99
}
```

#### Delete Product
```bash
DELETE /api/v1/products/1
```

### Query Parameters

The API supports advanced query parameters:

- `fields` - Sparse field selection (e.g., `fields=id,name,price`)
- `with` - Eager load relations (e.g., `with=category,inventoryItems`)
- `filter[field]` - Filter by field (e.g., `filter[status]=active`)
- `filter[field][operator]` - Advanced filtering (e.g., `filter[price][gte]=100`)
- `search` - Global search (e.g., `search=keyword`)
- `sort` - Multi-field sorting (e.g., `sort=-created_at,name`)
- `per_page` - Items per page (e.g., `per_page=50`)
- `page` - Page number (e.g., `page=2`)

## Troubleshooting

### Common Issues

#### "SQLSTATE[HY000] [2002] Connection refused"
- Ensure database service is running
- Check database credentials in `.env`
- For Docker: `docker-compose ps` to check service status

#### "Class not found" errors
- Run `composer dump-autoload`
- Clear config cache: `php artisan config:clear`

#### Permission errors
- Set proper permissions:
  ```bash
  chmod -R 755 storage bootstrap/cache
  ```

#### Frontend build errors
- Clear node_modules: `rm -rf node_modules package-lock.json`
- Reinstall: `npm install`

### Clearing Caches

```bash
# Clear all caches
php artisan optimize:clear

# Individual caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Production Deployment

### Environment Setup

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Set a strong `APP_KEY`
3. Configure production database
4. Set up Redis for caching and queues
5. Configure proper logging

### Optimization

```bash
# Optimize application
php artisan optimize

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### Queue Workers

Set up supervisor or systemd to keep queue workers running:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

### Scheduled Tasks

Add to crontab:
```
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

## Additional Resources

- [CRUD Framework Guide](CRUD_FRAMEWORK_GUIDE.md)
- [Architecture Documentation](ARCHITECTURE.md)
- [Requirements](REQUIREMENTS_CONSOLIDATED.md)
- [Contributing Guidelines](CONTRIBUTING.md)
- [Security Policy](SECURITY.md)

## Support

- **Issues**: [GitHub Issues](https://github.com/kasunvimarshana/AutoERP/issues)
- **Discussions**: [GitHub Discussions](https://github.com/kasunvimarshana/AutoERP/discussions)
- **Email**: support@autoerp.com

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
