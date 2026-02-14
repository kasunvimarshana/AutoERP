# AutoERP: Setup and Installation Guide

## Prerequisites
- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0+ or PostgreSQL 13+
- Node.js 18+ and npm (for frontend)
- Redis (recommended for caching and queues)

## Backend Setup

### 1. Clone the Repository
```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

### 2. Install Backend Dependencies
```bash
cd backend
composer install
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Migrations
```bash
php artisan migrate
```

### 5. Seed Initial Data (Optional)
```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=TenantSeeder
```

### 6. Start Development Server
```bash
php artisan serve
```

The backend API will be available at `http://localhost:8000`

## Database Schema Overview

### Core Tables
1. **tenants**: Multi-tenant isolation with subscription management
2. **organizations**: Organizations within tenants (vendors/customers)
3. **branches**: Physical locations for organizations
4. **users**: System users with tenant awareness
5. **roles**: Role definitions for RBAC
6. **permissions**: System-wide permissions
7. **role_permission**: Role-permission pivot
8. **user_role**: User-role pivot

### Inventory Tables
9. **products**: Product master data
10. **product_variants**: SKU/variant management
11. **batches**: Batch/lot/serial tracking
12. **stock_ledger**: Append-only inventory transactions

### Pricing Tables
13. **price_lists**: Price list definitions
14. **price_list_items**: Product-specific pricing

## Architecture Overview

### Clean Architecture Layers

```
┌─────────────────────────────────────────┐
│          Controllers (HTTP)              │
│  - Request validation                    │
│  - Response formatting                   │
│  - Thin, no business logic              │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│          Services (Business Logic)       │
│  - Orchestration                         │
│  - Transaction management                │
│  - Business rules                        │
│  - Cross-cutting concerns                │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Repositories (Data Access)          │
│  - Database queries                      │
│  - ORM abstraction                       │
│  - No business logic                     │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│            Models (Entities)             │
│  - Database mapping                      │
│  - Relationships                         │
│  - Accessors/Mutators                    │
└─────────────────────────────────────────┘
```

### Module Structure

Each module follows this structure:
```
app/Modules/{ModuleName}/
├── Controllers/      # HTTP controllers
├── Services/         # Business logic
├── Repositories/     # Data access
├── Models/           # Eloquent models
├── DTOs/             # Data transfer objects
├── Requests/         # Form requests
├── Policies/         # Authorization policies
├── Events/           # Domain events
├── Listeners/        # Event listeners
├── Jobs/             # Background jobs
└── Rules/            # Custom validation rules
```

## Key Features Implemented

### ✅ Multi-Tenancy
- Tenant isolation with global scopes
- Automatic tenant ID injection
- Support for multi-organization, multi-branch operations
- Subscription management

### ✅ RBAC/ABAC
- Role-based access control
- Permission-based authorization
- Flexible permission assignment
- Tenant-aware roles

### ✅ Inventory Management
- Product and variant tracking
- Append-only stock ledger (immutable)
- Batch/lot/serial number tracking
- FIFO/FEFO support (ready for implementation)
- Multi-location inventory

### ✅ Pricing Engine
- Multiple price lists
- Tiered pricing support
- Time-based pricing validity
- Customer-specific pricing (ready)

## API Endpoints (Coming Soon)

### Authentication
- POST `/api/v1/auth/login`
- POST `/api/v1/auth/register`
- POST `/api/v1/auth/logout`
- GET `/api/v1/auth/me`

### Users
- GET `/api/v1/users`
- POST `/api/v1/users`
- GET `/api/v1/users/{id}`
- PUT `/api/v1/users/{id}`
- DELETE `/api/v1/users/{id}`

### Products
- GET `/api/v1/products`
- POST `/api/v1/products`
- GET `/api/v1/products/{id}`
- PUT `/api/v1/products/{id}`
- DELETE `/api/v1/products/{id}`

### Stock
- GET `/api/v1/stock/ledger`
- POST `/api/v1/stock/adjustment`
- POST `/api/v1/stock/transfer`
- GET `/api/v1/stock/balance`

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run with Coverage
```bash
php artisan test --coverage
```

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up Redis for caching and queues
- [ ] Configure mail settings
- [ ] Set up SSL/TLS certificates
- [ ] Configure CORS settings
- [ ] Set up proper logging
- [ ] Configure backup strategy
- [ ] Set up monitoring and alerts
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`

### Queue Workers
```bash
php artisan queue:work --queue=high,default,low
```

### Scheduler
Add to crontab:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Frontend Setup (Coming Soon)

### Install Frontend Dependencies
```bash
cd frontend
npm install
```

### Run Development Server
```bash
npm run dev
```

### Build for Production
```bash
npm run build
```

## Development Guidelines

### Code Style
- Follow PSR-12 coding standards
- Use type hints for all method parameters and return types
- Write meaningful PHPDoc comments
- Keep methods small and focused (Single Responsibility Principle)

### Naming Conventions
- Controllers: `{Resource}Controller` (e.g., `UserController`)
- Services: `{Resource}Service` (e.g., `UserService`)
- Repositories: `{Resource}Repository` (e.g., `UserRepository`)
- Models: Singular form (e.g., `User`, `Product`)
- Tables: Plural form (e.g., `users`, `products`)

### Commit Messages
Follow conventional commits:
```
feat: Add user authentication
fix: Resolve stock calculation bug
docs: Update API documentation
refactor: Simplify product service
test: Add unit tests for inventory
```

## Troubleshooting

### Database Connection Issues
```bash
php artisan config:clear
php artisan cache:clear
```

### Migration Issues
```bash
php artisan migrate:fresh --seed
```

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Support & Documentation

- **Documentation**: Coming soon
- **API Docs**: Will be available at `/api/documentation`
- **Issues**: [GitHub Issues](https://github.com/kasunvimarshana/AutoERP/issues)
- **Discussions**: [GitHub Discussions](https://github.com/kasunvimarshana/AutoERP/discussions)

## License

This project is proprietary and confidential.

---

**Version**: 0.1.0-alpha  
**Last Updated**: 2026-02-02  
**Status**: Active Development
