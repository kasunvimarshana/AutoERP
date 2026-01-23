# AutoERP - Modular SaaS for Vehicle Service Centers

## Overview

AutoERP is a production-ready, enterprise-level modular SaaS application designed for vehicle service centers and auto repair garages. Built with Laravel 11 backend and Vue.js 3 frontend, it follows clean architecture principles with a strict Controller → Service → Repository pattern.

## Architecture

### Design Principles

- **Clean Architecture**: Clear separation of concerns with distinct layers
- **SOLID Principles**: Single responsibility, open-closed, Liskov substitution, interface segregation, dependency inversion
- **DRY (Don't Repeat Yourself)**: Reusable components and utilities
- **KISS (Keep It Simple, Stupid)**: Simple, maintainable code

### Pattern Structure

```
Controller → Service → Repository → Model → Database
```

- **Controllers**: Handle HTTP requests, validation, and responses
- **Services**: Contain business logic and orchestrate cross-module operations
- **Repositories**: Manage data access and database operations
- **Models**: Represent data entities and relationships
- **Events**: Enable event-driven communication between modules

## Key Features

### Multi-Tenancy Support
- Tenant isolation at database and application levels
- Secure data segregation
- Multi-vendor and multi-branch operations

### Core Modules

#### 1. Customer & Vehicle Management ✅ (Implemented)
- Customer profiles (individual and business)
- Vehicle registration and tracking
- Ownership transfer with complete history
- Meter readings and next-service tracking
- Customer lifetime value tracking

#### 2. Appointments & Bay Scheduling (Planned)
- Service bay management
- Appointment scheduling
- Resource allocation
- Calendar integration

#### 3. Job Cards & Workflows (Planned)
- Digital job cards
- Workflow automation
- Task management
- Status tracking

#### 4. Inventory & Procurement (Planned)
- Stock management
- Purchase orders
- Supplier management
- Dummy items support

#### 5. Invoicing & Payments (Planned)
- Invoice generation
- Payment processing
- Multiple payment methods
- Driver commissions

#### 6. CRM & Customer Engagement (Planned)
- Customer communication
- Automated notifications
- Marketing campaigns
- Loyalty programs

#### 7. Fleet, Telematics & Maintenance (Planned)
- Fleet tracking
- Telematics integration
- Maintenance schedules
- Service reminders

#### 8. Reporting & Analytics (Planned)
- KPI dashboards
- Custom reports
- Data visualization
- Business intelligence

### Security Features

- **Authentication**: Laravel Sanctum for API authentication
- **Authorization**: RBAC (Role-Based Access Control) and ABAC (Attribute-Based Access Control)
- **Tenant Isolation**: Strict data segregation between tenants
- **Audit Trails**: Complete activity logging with Spatie Activity Log
- **Encryption**: Sensitive data encryption
- **Validation**: Comprehensive input validation

### Event-Driven Architecture

- Asynchronous event processing
- Decoupled module communication
- Queue-based background jobs
- Notification system (email, SMS, in-app)

### Internationalization (i18n)

- Full backend and frontend localization
- Multiple language support
- Date/time localization
- Currency formatting

## Technology Stack

### Backend
- **Framework**: Laravel 11.x
- **PHP**: 8.3+
- **Database**: MySQL/PostgreSQL with SQLite for testing
- **Authentication**: Laravel Sanctum
- **Packages**:
  - `spatie/laravel-permission` - RBAC/ABAC
  - `spatie/laravel-multitenancy` - Multi-tenancy support
  - `spatie/laravel-activitylog` - Audit trails
  - `spatie/laravel-query-builder` - Advanced API filtering

### Frontend
- **Framework**: Vue.js 3 with TypeScript
- **Build Tool**: Vite
- **State Management**: Pinia
- **Routing**: Vue Router
- **HTTP Client**: Axios
- **UI Framework**: Tailwind CSS
- **i18n**: Vue I18n

## Installation

### Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- Node.js 20.x or higher
- npm 10.x or higher
- MySQL 8.0+ or PostgreSQL 14+

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=autoerp
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Publish vendor assets
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider"

# Start development server
php artisan serve
```

### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Configure API endpoint in .env
echo "VITE_API_URL=http://localhost:8000/api/v1" > .env

# Start development server
npm run dev
```

## Project Structure

### Backend Structure

```
backend/
├── app/
│   ├── Core/                          # Core application infrastructure
│   │   ├── Base/                      # Base classes (Repository, Service, Controller)
│   │   ├── Contracts/                 # Interfaces
│   │   ├── Traits/                    # Reusable traits
│   │   └── Exceptions/                # Custom exceptions
│   ├── Modules/                       # Feature modules
│   │   └── CustomerManagement/        # Customer Management module
│   │       ├── Models/                # Eloquent models
│   │       ├── Repositories/          # Data access layer
│   │       ├── Services/              # Business logic layer
│   │       ├── Http/
│   │       │   ├── Controllers/       # API controllers
│   │       │   └── Requests/          # Form requests
│   │       ├── Events/                # Domain events
│   │       ├── Listeners/             # Event listeners
│   │       ├── Policies/              # Authorization policies
│   │       └── Database/
│   │           └── Migrations/        # Database migrations
│   └── Models/                        # Core models (User, Tenant, etc.)
├── database/
│   ├── migrations/                    # Database migrations
│   ├── seeders/                       # Database seeders
│   └── factories/                     # Model factories
├── routes/
│   ├── api.php                        # API routes
│   ├── web.php                        # Web routes
│   └── modules/                       # Module-specific routes
│       └── customer-management.php
└── tests/                             # Tests
    ├── Feature/                       # Feature tests
    └── Unit/                          # Unit tests
```

### Frontend Structure

```
frontend/
├── src/
│   ├── assets/                        # Static assets
│   ├── components/                    # Reusable Vue components
│   │   ├── common/                    # Common components
│   │   └── modules/                   # Module-specific components
│   ├── views/                         # Page components
│   ├── router/                        # Vue Router configuration
│   ├── stores/                        # Pinia stores
│   ├── services/                      # API service layer
│   ├── utils/                         # Utility functions
│   ├── locales/                       # i18n translation files
│   └── App.vue                        # Root component
└── public/                            # Public assets
```

## API Documentation

### Customer Management API

#### Customers

**List Customers**
```http
GET /api/v1/customers
Query Parameters:
  - search: string (optional) - Search by name, email, or customer code
  - status: string (optional) - Filter by status (active, inactive, blocked)
  - customer_type: string (optional) - Filter by type (individual, business)
  - per_page: integer (optional) - Results per page (default: 15)
```

**Create Customer**
```http
POST /api/v1/customers
Content-Type: application/json

{
  "customer_type": "individual",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "address_line1": "123 Main St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "US"
}
```

**Get Customer**
```http
GET /api/v1/customers/{id}
```

**Update Customer**
```http
PUT /api/v1/customers/{id}
```

**Delete Customer**
```http
DELETE /api/v1/customers/{id}
```

#### Vehicles

**List Vehicles**
```http
GET /api/v1/vehicles
Query Parameters:
  - search: string (optional) - Search by VIN, registration, make, or model
  - status: string (optional) - Filter by status
  - customer_id: integer (optional) - Filter by customer
  - service_due: boolean (optional) - Filter vehicles due for service
```

**Create Vehicle**
```http
POST /api/v1/vehicles
Content-Type: application/json

{
  "current_customer_id": 1,
  "vin": "1HGBH41JXMN109186",
  "registration_number": "ABC123",
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "vehicle_type": "car",
  "current_mileage": 15000,
  "mileage_unit": "km"
}
```

**Transfer Ownership**
```http
POST /api/v1/vehicles/{id}/transfer-ownership
Content-Type: application/json

{
  "new_customer_id": 2,
  "reason": "sale",
  "notes": "Vehicle sold to new owner"
}
```

**Update Mileage**
```http
POST /api/v1/vehicles/{id}/update-mileage
Content-Type: application/json

{
  "mileage": 16500
}
```

## Database Schema

### Customers Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Unique identifier |
| tenant_id | bigint | Tenant reference (nullable) |
| customer_code | string | Unique customer code |
| customer_type | enum | individual or business |
| first_name | string | First name |
| last_name | string | Last name |
| company_name | string | Company name (nullable) |
| email | string | Email address (unique) |
| phone | string | Phone number |
| status | enum | active, inactive, or blocked |
| lifetime_value | decimal | Total spent by customer |
| total_services | integer | Number of services |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

### Vehicles Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Unique identifier |
| tenant_id | bigint | Tenant reference (nullable) |
| current_customer_id | bigint | Current owner reference |
| vin | string | Vehicle Identification Number (unique) |
| registration_number | string | Registration number (unique) |
| make | string | Vehicle make |
| model | string | Vehicle model |
| year | integer | Manufacturing year |
| vehicle_type | enum | car, truck, motorcycle, etc. |
| current_mileage | decimal | Current mileage |
| next_service_mileage | decimal | Next service mileage |
| next_service_date | datetime | Next service date |
| status | enum | active, inactive, sold, written_off |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

## Transaction Management

All service layer methods that modify data are wrapped in database transactions:

```php
public function create(array $data): Model
{
    try {
        DB::beginTransaction();
        
        $record = $this->repository->create($data);
        $this->afterCreate($record, $data);
        
        DB::commit();
        
        return $record;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### Transaction Best Practices

1. **Atomic Operations**: All database operations in a transaction complete or none do
2. **Exception Handling**: Automatic rollback on any exception
3. **Logging**: All operations are logged with context
4. **Event Dispatching**: Events are dispatched after successful transactions

## Event System

### Available Events

- `CustomerCreated` - Fired when a new customer is created
- `VehicleOwnershipTransferred` - Fired when vehicle ownership changes

### Creating Event Listeners

```bash
php artisan make:listener SendWelcomeEmail --event=CustomerCreated
```

### Registering Event Listeners

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    CustomerCreated::class => [
        SendWelcomeEmail::class,
        CreateCustomerPortalAccount::class,
    ],
];
```

## Testing

### Running Tests

```bash
# Backend tests
cd backend
php artisan test

# Frontend tests
cd frontend
npm run test:unit
```

### Writing Tests

Example repository test:

```php
public function test_can_create_customer()
{
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+1234567890',
    ];

    $customer = $this->customerService->create($data);

    $this->assertInstanceOf(Customer::class, $customer);
    $this->assertEquals('John', $customer->first_name);
    $this->assertDatabaseHas('customers', ['email' => 'john@example.com']);
}
```

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env
- [ ] Configure proper database credentials
- [ ] Set up queue workers
- [ ] Configure cache driver (Redis recommended)
- [ ] Set up scheduled tasks (cron)
- [ ] Configure email service
- [ ] Set up SSL certificates
- [ ] Configure CORS settings
- [ ] Enable rate limiting
- [ ] Set up monitoring and logging

### Queue Workers

```bash
# Start queue worker
php artisan queue:work

# For production, use supervisor
sudo supervisorctl start laravel-worker:*
```

### Scheduled Tasks

Add to crontab:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Performance Optimization

### Caching

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear all caches
php artisan optimize:clear
```

### Database Optimization

- Use database indexes on frequently queried columns
- Implement eager loading to avoid N+1 queries
- Use database query caching
- Implement database connection pooling

## Security

### Best Practices Implemented

1. **Input Validation**: All inputs validated using Form Requests
2. **SQL Injection Prevention**: Using Eloquent ORM and prepared statements
3. **XSS Prevention**: Output escaping in views
4. **CSRF Protection**: Laravel CSRF tokens
5. **Rate Limiting**: API rate limiting configured
6. **Authentication**: Sanctum token-based authentication
7. **Authorization**: Policy-based authorization
8. **Audit Logging**: Complete activity logging

## Contributing

### Code Style

- Follow PSR-12 coding standards
- Use Laravel conventions
- Write descriptive commit messages
- Add tests for new features

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit pull request with description

## License

This project is proprietary software. All rights reserved.

## Support

For support, email support@autoerp.com or create an issue in the repository.

## Roadmap

### Phase 1 (Current)
- [x] Core architecture setup
- [x] Customer & Vehicle Management module
- [x] Multi-tenancy support
- [x] Authentication & Authorization

### Phase 2 (Q1 2026)
- [ ] Appointments & Bay Scheduling module
- [ ] Job Cards & Workflows module
- [ ] Frontend dashboard implementation

### Phase 3 (Q2 2026)
- [ ] Inventory & Procurement module
- [ ] Invoicing & Payments module

### Phase 4 (Q3 2026)
- [ ] CRM & Customer Engagement module
- [ ] Notification system

### Phase 5 (Q4 2026)
- [ ] Fleet & Telematics module
- [ ] Reporting & Analytics module
- [ ] Mobile app development

## Changelog

### Version 1.0.0 (2026-01-23)
- Initial release
- Customer Management module
- Vehicle Management module
- Multi-tenancy support
- Clean architecture implementation
- Event-driven system
- Comprehensive API

---

**Built with ❤️ for the automotive service industry**
