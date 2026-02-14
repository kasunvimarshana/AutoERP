# AutoERP - Setup Guide

This guide will help you set up and run the AutoERP on your local development environment.

## Prerequisites

Ensure you have the following installed:

- **PHP**: >= 8.2 (8.3 recommended)
- **Composer**: >= 2.0
- **Node.js**: >= 20.x
- **NPM**: >= 10.x
- **Database**: PostgreSQL >= 14 (or MySQL >= 8.0)
- **Git**: Latest version

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

This will install all Laravel dependencies including:
- Laravel Framework 10.x
- Spatie Laravel Permission (RBAC)
- Spatie Laravel Multitenancy
- Spatie Laravel Query Builder
- Spatie Laravel Activity Log
- L5 Swagger (API Documentation)
- Laravel Sanctum (API Authentication)

### 3. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file with your database credentials:

**For PostgreSQL:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=autoerp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**For MySQL:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Create the database:

**PostgreSQL:**
```bash
createdb autoerp
```

**MySQL:**
```bash
mysql -u root -p -e "CREATE DATABASE autoerp;"
```

### 5. Run Migrations

The platform uses two types of migrations:

**System migrations** (tenants, subscriptions):
```bash
php artisan migrate --path=database/migrations/system
```

**Tenant migrations** (organizations, users, customers, inventory):
```bash
php artisan migrate --path=database/migrations/tenant
```

Or run all migrations:
```bash
php artisan migrate
```

### 6. Seed Database (Optional)

```bash
php artisan db:seed
```

### 7. Install Frontend Dependencies (Future)

```bash
npm install
```

### 8. Build Assets (Future)

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### 9. Create Storage Symlink

```bash
php artisan storage:link
```

### 10. Start Development Server

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

## API Documentation

The platform uses Swagger/OpenAPI for API documentation.

### Generate API Documentation

```bash
php artisan l5-swagger:generate
```

### Access API Documentation

Navigate to: `http://localhost:8000/api/documentation`

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Unit tests
php artisan test --testsuite=Unit

# Feature tests
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage
```

## Code Quality

### Run Laravel Pint (Code Formatter)

```bash
./vendor/bin/pint
```

### Check Code Style

```bash
./vendor/bin/pint --test
```

## Common Commands

### Clear Cache

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize for Production

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Create New Module Components

The platform follows modular architecture. Here's how to create components:

#### Create a Repository

```bash
# Create in app/Modules/{Module}/Repositories/
# Extend App\Core\Repositories\BaseRepository
```

#### Create a Service

```bash
# Create in app/Modules/{Module}/Services/
# Extend App\Core\Services\BaseService
```

#### Create a Model

```bash
php artisan make:model Modules/{Module}/Models/{ModelName}
```

#### Create a Controller

```bash
php artisan make:controller Modules/{Module}/Http/Controllers/{ControllerName}
```

#### Create a Request

```bash
php artisan make:request Modules/{Module}/Http/Requests/{RequestName}
```

## Project Structure

```
AutoERP/
├── app/
│   ├── Core/                      # Core architecture components
│   │   ├── DTOs/                  # Data Transfer Objects
│   │   ├── Exceptions/            # Custom exceptions
│   │   ├── Interfaces/            # Core interfaces
│   │   ├── Repositories/          # Base repository
│   │   └── Services/              # Base service
│   ├── Modules/                   # Feature modules
│   │   ├── Tenant/                # Multi-tenancy module
│   │   ├── IAM/                   # Identity & Access Management
│   │   ├── CRM/                   # Customer Relationship Management
│   │   ├── Inventory/             # Inventory management
│   │   └── ...                    # Other modules
│   ├── Models/                    # Shared models (User)
│   └── Providers/                 # Service providers
├── database/
│   ├── migrations/
│   │   ├── system/                # System-level migrations
│   │   └── tenant/                # Tenant-level migrations
│   └── seeders/
├── routes/
│   ├── api.php                    # API routes
│   └── web.php                    # Web routes
├── tests/                         # Test files
├── .env.example                   # Environment template
├── composer.json                  # PHP dependencies
├── package.json                   # Node dependencies
└── README.md                      # This file
```

## Module Architecture

Each module follows this structure:

```
app/Modules/{ModuleName}/
├── Models/                        # Eloquent models
├── Repositories/                  # Data access layer
├── Services/                      # Business logic layer
├── DTOs/                          # Data transfer objects
├── Http/
│   ├── Controllers/               # HTTP controllers
│   └── Requests/                  # Form request validation
├── Policies/                      # Authorization policies
├── Events/                        # Domain events
├── Listeners/                     # Event listeners
├── Jobs/                          # Background jobs
└── Notifications/                 # Notification templates
```

## Key Features Implemented

### ✅ Phase 1: Foundation & Core Architecture
- Laravel 10+ with clean architecture
- Repository pattern with BaseRepository
- Service layer with transaction management
- DTO infrastructure
- Custom exception handling
- Comprehensive documentation

### ✅ Phase 2: Multi-Tenancy
- Tenant isolation
- Subscription management
- Organization and branch hierarchy
- Multi-tenant models

### ✅ Phase 3: IAM Module
- User authentication with Sanctum
- RBAC with Spatie Permission
- Tenant-aware users
- Role and permission management

### ✅ Phase 4: CRM Module
- Customer management (B2B/B2C)
- Vehicle management
- Centralized service history
- Customer contacts

### ✅ Phase 5: Inventory Module
- **Append-only stock ledger**
- SKU/variant modeling
- Batch/lot/serial tracking
- FIFO/FEFO support
- Expiry date handling
- Warehouse management

## Troubleshooting

### Database Connection Issues

1. Verify database credentials in `.env`
2. Ensure database server is running
3. Check database exists: `php artisan db:show`

### Permission Issues

```bash
# Fix storage and cache permissions
chmod -R 775 storage bootstrap/cache
```

### Composer Dependency Issues

```bash
# Clear composer cache
composer clear-cache
composer install --no-cache
```

### Migration Issues

```bash
# Rollback and re-run
php artisan migrate:rollback
php artisan migrate
```

## Development Workflow

1. **Create feature branch**: `git checkout -b feature/your-feature`
2. **Make changes**: Follow the architecture patterns
3. **Run tests**: `php artisan test`
4. **Format code**: `./vendor/bin/pint`
5. **Commit changes**: `git commit -m "feat: your feature"`
6. **Push branch**: `git push origin feature/your-feature`
7. **Create PR**: Open pull request for review

## Production Deployment

### Environment Configuration

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Configure secure `APP_KEY`
4. Set up proper database credentials
5. Configure mail settings
6. Set up queue workers
7. Configure cache driver (Redis recommended)

### Optimization

```bash
# Optimize application
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm run build
```

### Queue Workers

```bash
# Run queue worker
php artisan queue:work --tries=3
```

## Security Considerations

1. **Never commit `.env` file**
2. Use strong `APP_KEY`
3. Enable HTTPS in production
4. Configure CORS properly
5. Set up rate limiting
6. Regular security updates
7. Use prepared statements (already handled by Eloquent)
8. Implement proper authentication
9. Validate all inputs
10. Sanitize outputs

## Support

For issues and questions:
- GitHub Issues: [Create an issue](https://github.com/kasunvimarshana/AutoERP/issues)
- Documentation: See IMPLEMENTATION.md
- API Docs: `http://localhost:8000/api/documentation`

## License

[Add your license here]

---

**Happy coding! 🚀**
