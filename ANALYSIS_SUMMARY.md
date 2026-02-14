## Common Patterns Identified

### 1. Architecture Patterns

#### Controller → Service → Repository Pattern
All repositories follow this clean architecture pattern:

```php
// Controller Layer - HTTP handling
class CustomerController {
    public function store(Request $request) {
        $data = $request->validated();
        return $this->customerService->create($data);
    }
}

// Service Layer - Business logic
class CustomerService {
    public function create(array $data): Customer {
        DB::beginTransaction();
        try {
            $customer = $this->customerRepository->create($data);
            event(new CustomerCreated($customer));
            DB::commit();
            return $customer;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

// Repository Layer - Data access
class CustomerRepository {
    public function create(array $data): Customer {
        return Customer::create($data);
    }
}
```

#### Multi-Tenancy Implementation
Common approach across repositories:

1. **Column-Based Tenancy** (Default):
   - `tenant_id` column in all tables
   - Global scope for automatic filtering
   - Middleware for tenant context

2. **Subdomain Routing**:
   - `tenant1.app.com`, `tenant2.app.com`
   - Tenant identification from subdomain
   - Optional custom domain support

3. **Database Strategies**:
   - Shared database (default, cost-effective)
   - Database per tenant (enterprise, maximum isolation)

### 2. Technology Stack Consensus

#### Backend
- **Framework**: Laravel 10+ or Laravel 11 (latest)
- **PHP Version**: 8.1+ to 8.3+
- **Database**: PostgreSQL (preferred) or MySQL 8+
- **Cache**: Redis 7+
- **Queue**: Redis or RabbitMQ
- **Authentication**: Laravel Sanctum (API tokens)
- **Permissions**: Spatie Laravel Permission
- **Activity Log**: Spatie Laravel Activity Log
- **API Docs**: L5-Swagger (OpenAPI/Swagger)

#### Frontend
- **Framework**: Vue.js 3 with Composition API
- **Build Tool**: Vite (fast, modern)
- **Language**: TypeScript (AutoERP, preferred) or JavaScript
- **Styling**: Tailwind CSS (utility-first)
- **State Management**: Pinia (Vue.js official)
- **HTTP Client**: Axios
- **Routing**: Vue Router
- **i18n**: Vue I18n

#### DevOps
- **Containerization**: Docker & Docker Compose
- **Orchestration**: Kubernetes (production)
- **CI/CD**: GitHub Actions
- **Monitoring**: Prometheus + Grafana
- **Logging**: ELK Stack
- **Error Tracking**: Sentry

### 3. Security Patterns

#### Authentication & Authorization
- Token-based authentication (Sanctum)
- RBAC with hierarchical roles:
  - `super_admin` - System-wide
  - `admin` - Tenant-level
  - `manager` - Department/branch
  - `user` - Standard access
  - Custom roles per tenant
- Permission-based route protection
- API token expiration and rotation

#### Data Security
- Encryption at rest (database)
- Encryption in transit (TLS/SSL)
- Password hashing (bcrypt)
- SQL injection prevention (Eloquent ORM)
- XSS protection (input sanitization)
- CSRF protection (Laravel middleware)

#### Audit & Compliance
- Activity logging (all CRUD operations)
- Audit trail with user, timestamp, changes
- GDPR compliance capabilities
- Data export and deletion support

### 4. Database Patterns

#### Common Tables Across Repositories
- `tenants` - Tenant master data
- `users` - User accounts
- `roles` - User roles
- `permissions` - Access permissions
- `role_has_permissions` - Role-permission mapping
- `user_has_roles` - User-role mapping
- `customers` - Customer master
- `products` - Product catalog
- `invoices` - Invoice header
- `invoice_items` - Invoice lines
- `payments` - Payment transactions

#### Naming Conventions
- Plural table names (`customers`, not `customer`)
- Snake case for table and column names
- Soft deletes with `deleted_at`
- Timestamps with `created_at`, `updated_at`
- Foreign keys: `{table}_id` (e.g., `tenant_id`)

### 5. API Design Patterns

### 2. Modular Design

**Learning**: All repositories emphasize modularity with clear boundaries.

**Modules to Implement**:
1. Multi-Tenancy (foundation)
2. IAM (authentication & authorization)
3. CRM (customer management)
4. Inventory (product & stock management)
5. Billing & Payments (invoicing & payments)
6. Fleet (optional, from AutoERP)
7. Analytics (reporting & dashboards)
8. Settings (configuration)
