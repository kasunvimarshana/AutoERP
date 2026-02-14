# AutoERP

A production-ready, enterprise-grade ERP SaaS platform built with Laravel 10+ and Vue.js 3, following Clean Architecture and Modular Design patterns.

## 🏗️ Architecture

### Core Principles
- **Clean Architecture**: Clear separation between domain logic, application logic, and infrastructure
- **Modular Architecture**: Feature-based modules with independent concerns
- **Controller → Service → Repository Pattern**: Consistent data flow and business logic isolation
- **SOLID Principles**: Maintainable, extensible, and testable code
- **DRY & KISS**: Don't Repeat Yourself, Keep It Simple, Stupid

### Dynamic CRUD Framework ✨ NEW
A fully dynamic, configuration-driven CRUD framework providing:
- **Global and field-level search** - Search across multiple fields or specific columns
- **Advanced filtering** - Filter by exact match, partial match, ranges, or custom logic
- **Relation-based filters** - Filter based on related model data
- **Multi-field sorting** - Sort by multiple columns in ascending or descending order
- **Sparse field selection** - Return only requested fields to optimize payload size
- **Configurable eager loading** - Load related models on-demand with nested support
- **Pagination** - Configurable page size with full metadata
- **Tenant-aware** - Automatic tenant scoping through global scopes
- **Secure** - Built-in validation and consistent error handling
- **Scalable** - Configuration-driven, no hardcoded logic

📖 See [CRUD_FRAMEWORK.md](./CRUD_FRAMEWORK.md) for complete documentation.

### Multi-Tenancy
- Full tenant isolation with dedicated data scopes
- Subscription-based access control
- Multi-organization and multi-branch support
- Cross-branch data visibility with centralized histories

### Security
- Fine-grained RBAC (Role-Based Access Control) with Spatie Permission
- ABAC (Attribute-Based Access Control) support
- Tenant-aware authentication and authorization
- Immutable audit trails via append-only ledgers
- Structured logging for all operations

## 📦 Modules

### Core Modules
1. **IAM (Identity & Access Management)**
   - User management with tenant isolation
   - Roles and permissions (RBAC/ABAC)
   - Authentication and authorization
   - Session management

2. **Tenant Management**
   - Multi-tenant architecture
   - Subscription management
   - Organization and branch hierarchy
   - Tenant-specific configurations

3. **Master Data & Configuration**
   - Multi-currency support
   - Multi-language/i18n
   - Product categories and brands
   - System configuration

### Business Modules

4. **CRM (Customer Relationship Management)**
   - Customer management (B2B/B2C)
   - Vehicle management with centralized service history
   - Contact management
   - Customer preferences and metadata

5. **Inventory Management**
   - **Append-only stock ledger** for immutable audit trails
   - SKU/variant modeling with multi-attribute support
   - Batch/lot/serial number tracking
   - FIFO/FEFO inventory valuation
   - Expiry date handling and alerts
   - Warehouse and location management
   - Multi-branch stock visibility

6. **Procurement** (Planned)
   - Supplier management
   - Purchase orders and requisitions
   - Goods receipt and quality control
   - Procurement approval workflows

7. **Pricing Engine** (Planned)
   - Multiple price lists
   - Rule-based pricing logic
   - Customer-specific pricing
   - Promotional pricing

8. **Invoicing & Payments** (Planned)
   - Invoice generation and management
   - Payment processing
   - Multi-jurisdiction taxation
   - Payment gateway integrations

9. **POS (Point of Sale)** (Planned)
   - Quick sale interface
   - Cash register management
   - Shift management
   - Real-time inventory updates

10. **Appointments & Service** (Planned)
    - Appointment scheduling
    - Bay/resource scheduling
    - Job card workflow
    - Service tracking

11. **Fleet & Telematics** (Planned)
    - Vehicle fleet tracking
    - Preventive maintenance scheduling
    - Fuel tracking
    - Driver management

12. **Manufacturing** (Planned)
    - Bill of materials (BOM)
    - Production planning
    - Quality control

13. **Reporting & Analytics** (Planned)
    - KPI dashboards
    - Custom report builder
    - Scheduled reports
    - Data export (CSV, Excel, PDF)

## 🗄️ Database Schema

### System-Level Tables
- `tenants` - Multi-tenant isolation
- `subscription_plans` - Available subscription tiers
- `subscriptions` - Tenant subscriptions

### Tenant-Level Tables
- `organizations` - Companies within a tenant
- `branches` - Physical locations/branches
- `users` - Tenant-scoped users
- `roles` & `permissions` - RBAC tables
- `customers` & `customer_contacts` - CRM data
- `vehicles` & `vehicle_service_history` - Vehicle management
- `products`, `product_categories`, `brands` - Product catalog
- `stock_ledger` - **Append-only** inventory movements
- `warehouses` & `stock_locations` - Warehouse management

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 10+
- **PHP**: 8.3+
- **Database**: PostgreSQL (recommended), MySQL compatible
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **Multi-tenancy**: Spatie Laravel Multitenancy
- **API Documentation**: L5 Swagger (OpenAPI/Swagger)

### Frontend (Planned)
- **Framework**: Vue.js 3 + Vite
- **State Management**: Pinia
- **UI Framework**: Tailwind CSS + AdminLTE
- **Routing**: Vue Router
- **Internationalization**: Vue I18n
- **HTTP Client**: Axios

## 🚀 Installation

### Requirements
- PHP >= 8.3
- Composer >= 2.0
- Node.js >= 20.x
- NPM >= 10.x
- PostgreSQL >= 14 (or MySQL 8+)

### Setup

1. **Clone the repository**
```bash
git clone <repository-url>
cd AutoERP
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database** (edit `.env`)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=autoerp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations**
```bash
# System migrations (tenants, subscriptions)
php artisan migrate --path=database/migrations/system

# Tenant migrations (organizations, users, etc.)
php artisan migrate --path=database/migrations/tenant
```

6. **Install frontend dependencies** (when frontend is implemented)
```bash
npm install
npm run dev
```

## 📚 Core Patterns

### Dynamic CRUD Framework
The platform includes a production-ready CRUD framework with advanced query capabilities:

```php
use App\Core\Http\Controllers\BaseCrudController;
use Spatie\QueryBuilder\AllowedFilter;

class YourController extends BaseCrudController
{
    protected function getQueryConfig(): array
    {
        return [
            'allowedFilters' => [
                'name',
                AllowedFilter::exact('status'),
                AllowedFilter::callback('has_items', fn($q, $v) => $q->has('items')),
            ],
            'allowedSorts' => ['name', 'created_at'],
            'allowedIncludes' => ['organization', 'branch'],
            'globalSearch' => ['name', 'email'],
        ];
    }
    
    protected function getValidationRules(string $action, $id = null): array
    {
        return ['name' => 'required|string|max:255'];
    }
}
```

Supports advanced queries:
```http
GET /api/v1/resources
  ?filter[status]=active        # Field-level filtering
  &search=john                  # Global search
  &sort=-created_at,name        # Multi-field sorting
  &include=organization,branch  # Eager loading
  &fields[resources]=id,name    # Sparse fieldsets
  &page[size]=20                # Pagination
```

📖 Complete documentation: [CRUD_FRAMEWORK.md](./CRUD_FRAMEWORK.md)

### Repository Pattern
All data access goes through repositories extending `BaseRepository`:

```php
use App\Core\Repositories\BaseRepository;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }
    
    // Add custom queries here
}
```

### Service Layer
Business logic resides in services extending `BaseService`:

```php
use App\Core\Services\BaseService;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }
    
    // Add business logic here
}
```

### DTOs (Data Transfer Objects)
Type-safe data transfer between layers:

```php
use App\Core\DTOs\BaseDTO;

class CreateCustomerDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null
    ) {}
    
    public function validate(): bool
    {
        // Validation logic
        return true;
    }
}
```

### Transaction Management
All service methods use automatic transaction management:

```php
public function createCustomer(array $data): Customer
{
    return $this->transaction(function () use ($data) {
        $customer = $this->repository->create($data);
        // Additional operations...
        return $customer;
    });
}
```

## 🔐 Security Features

### Append-Only Stock Ledger
- **Immutable audit trail**: All stock movements are recorded permanently
- **No updates or deletes**: Only INSERT operations allowed
- **FIFO/FEFO support**: First-In-First-Out and First-Expired-First-Out logic
- **Batch/lot/serial tracking**: Complete traceability
- **Expiry management**: Automated expiry alerts and FEFO rotation

### Tenant Isolation
- Automatic tenant scoping on all queries
- Global scopes prevent cross-tenant data leakage
- Tenant-specific roles and permissions

### RBAC/ABAC
- Fine-grained permissions at module and action levels
- Role hierarchy with inheritance
- Attribute-based access control for complex rules

## 📖 API Documentation

API documentation is auto-generated using Swagger/OpenAPI:

```bash
# Generate API documentation
php artisan l5-swagger:generate

# Access documentation
# Navigate to: http://localhost:8000/api/documentation
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 📝 Development Guidelines

### Module Structure
Each module follows this structure:
```
app/Modules/{ModuleName}/
├── Models/              # Eloquent models
├── Repositories/        # Data access layer
├── Services/           # Business logic layer
├── DTOs/               # Data transfer objects
├── Http/
│   ├── Controllers/    # HTTP controllers
│   └── Requests/       # Form request validation
├── Policies/           # Authorization policies
├── Events/             # Domain events
├── Listeners/          # Event listeners
├── Jobs/               # Background jobs
└── Notifications/      # Notification templates
```

### Coding Standards
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting: `./vendor/bin/pint`
- Type hint all method parameters and return types
- Document all public methods with PHPDoc

### Commit Guidelines
- Use conventional commits: `feat:`, `fix:`, `docs:`, `refactor:`, etc.
- Keep commits focused and atomic
- Write descriptive commit messages

## 🗺️ Roadmap

### Phase 1: Foundation ✅ (Current)
- [x] Laravel project setup
- [x] Core architecture (Repository, Service, DTO patterns)
- [x] Multi-tenancy implementation
- [x] IAM module (Users, Roles, Permissions)
- [x] Organization and Branch management
- [x] CRM module (Customers, Vehicles, Service History)
- [x] Inventory module (Products, Stock Ledger, Warehouses)

### Phase 2: Business Operations (Next)
- [ ] Procurement module
- [ ] Pricing engine
- [ ] Invoicing and payments
- [ ] POS system
- [ ] Appointments and scheduling

### Phase 3: Advanced Features
- [ ] Fleet management and telematics
- [ ] Manufacturing module
- [ ] eCommerce integration
- [ ] Advanced reporting and analytics

### Phase 4: Frontend & APIs
- [ ] Vue.js 3 + Vite frontend
- [ ] RESTful API endpoints
- [ ] API versioning
- [ ] WebSocket real-time updates

### Phase 5: DevOps & Production
- [ ] Docker containerization
- [ ] CI/CD pipeline
- [ ] Performance optimization
- [ ] Production deployment guides

## 📄 License

[Add your license here]

## 🤝 Contributing

Contributions are welcome! Please read the contributing guidelines before submitting PRs.

## 📧 Support

For support and questions, please [open an issue](../../issues) or contact the development team.

---

**Built with ❤️ using Laravel and Clean Architecture principles**
