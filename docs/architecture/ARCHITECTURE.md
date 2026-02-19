# Multi-Tenant Enterprise ERP/CRM SaaS Platform

## Architecture Overview

This platform is built following Clean Architecture, Domain-Driven Design (DDD), and SOLID principles using **native Laravel 12.x and Vue features only**.

## Core Principles

### 1. Modular Architecture
- **Plugin-Style Modules**: Each module is independently installable, removable, and extendable
- **Loose Coupling**: Modules communicate only via explicit contracts, events, and APIs
- **No Circular Dependencies**: Strict module isolation prevents circular references
- **No Shared State**: Each module maintains its own state

### 2. Multi-Tenancy
- **Strict Tenant Isolation**: Complete data separation between tenants
- **Hierarchical Organizations**: Support for nested organizational structures
- **Tenant Context**: All operations are scoped to the current tenant and organization
- **Tenant-Scoped Trait**: Automatic tenant filtering on models

### 3. Authentication & Authorization
- **Stateless JWT Authentication**: No server-side sessions
- **Multi-Device Support**: Per user × device × organization tokens
- **RBAC/ABAC**: Role-Based and Attribute-Based Access Control via native Laravel policies
- **Secure Token Lifecycle**: Token generation, validation, refresh, and revocation

### 4. Data Integrity
- **Atomic Transactions**: All data modifications use database transactions
- **Foreign Key Constraints**: Referential integrity enforced at database level
- **Optimistic Locking**: Version-based concurrency control
- **Pessimistic Locking**: Database locks for critical sections
- **Audit Logging**: Comprehensive audit trails for all critical operations

### 5. Precision-Safe Calculations
- **BCMath**: All financial and quantity calculations use BCMath for precision
- **Deterministic**: Calculations are reproducible and auditable
- **Configurable Scale**: Decimal precision configurable per operation

## Module Structure

### Core Module (`modules/Core`)
- Module registry and lifecycle management
- Base contracts and interfaces (RepositoryInterface, ModuleInterface)
- **BaseRepository**: Abstract repository for data access layer
- Transaction and locking helpers
- Precision-safe math utilities
- Comprehensive exception hierarchy (DomainException, ValidationException, etc.)

### Tenant Module (`modules/Tenant`)
- Multi-tenant isolation
- Hierarchical organization structure
- Tenant context management
- Tenant-scoped queries
- **Repositories**: TenantRepository, OrganizationRepository
- **Exceptions**: TenantNotFoundException, InvalidTenantException, TenantIsolationException, CircularReferenceException

### Auth Module (`modules/Auth`)
- JWT token service (native PHP implementation)
- Multi-device authentication
- RBAC/ABAC models (Role, Permission, User)
- Token lifecycle management
- **Repositories**: UserRepository, RoleRepository, PermissionRepository, UserDeviceRepository, RevokedTokenRepository
- **Exceptions**: InvalidCredentialsException, TokenExpiredException, TokenInvalidException, UserNotFoundException, PermissionDeniedException

### Audit Module (`modules/Audit`)
- Comprehensive audit logging
- Auditable trait for models
- Event tracking and metadata
- **Event Listeners**: LogProductCreated, LogProductUpdated, LogUserCreated, LogUserUpdated, LogPriceCreated, LogPriceUpdated
- **Repository**: AuditLogRepository
- **Async Processing**: All audit events queued for background processing

### Product Module (`modules/Product`)
- Flexible product types: goods, services, bundles, composites
- Configurable buying/selling units
- Unit conversion system
- Hierarchical categories
- **Repositories**: ProductRepository, ProductCategoryRepository, UnitRepository, ProductUnitConversionRepository
- **Exceptions**: ProductNotFoundException, InvalidProductTypeException, UnitConversionException, CategoryNotFoundException
- **Events**: ProductCreated, ProductUpdated

### Pricing Module (`modules/Pricing`)
- Extensible pricing engines (6 strategies)
- Location-based pricing
- Multiple pricing strategies: flat, percentage, tiered, volume, time-based, rule-based
- Runtime-configurable pricing rules
- **Repository**: ProductPriceRepository
- **Exceptions**: PriceNotFoundException, InvalidPricingStrategyException, PricingCalculationException
- **Events**: PriceCreated, PriceUpdated

### CRM Module (`modules/CRM`)
- Customer relationship management
- Lead tracking and conversion
- Sales opportunity pipeline
- Contact management
- **Models**: Customer, Contact, Lead, Opportunity
- **Repositories**: CustomerRepository, ContactRepository, LeadRepository, OpportunityRepository
- **Services**: LeadConversionService, OpportunityService
- **Policies**: CustomerPolicy, LeadPolicy, OpportunityPolicy
- **Enums**: CustomerType, CustomerStatus, LeadStatus, OpportunityStage, ContactType
- **Events**: CustomerCreated, LeadConverted, OpportunityStageChanged
- **Exceptions**: CustomerNotFoundException, ContactNotFoundException, LeadNotFoundException, OpportunityNotFoundException, InvalidLeadConversionException

## API Infrastructure

### Standardized Response Format
All API responses use the `ApiResponse` helper for consistent formatting:
- **Success responses**: `ApiResponse::success($data, $message, $statusCode, $meta)`
- **Paginated responses**: `ApiResponse::paginated($paginator, $message, $meta)`
- **Error responses**: `ApiResponse::error($message, $statusCode, $errorCode, $errors, $meta)`
- **Specialized responses**: `created()`, `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`, `serverError()`

### Rate Limiting
- **Middleware**: `RateLimitMiddleware` for API rate limiting
- **Configurable limits**: Per-user and per-IP rate limiting
- **Response headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`
- **Usage**: `->middleware('rate-limit:60,1')` for 60 requests per minute

## Database Schema

### Multi-Tenancy
- `tenants`: Tenant definitions
- `organizations`: Hierarchical organizational structure

### Authentication
- `users`: Multi-tenant users
- `roles`: RBAC roles
- `permissions`: RBAC permissions
- `user_roles`: User-role assignments
- `user_permissions`: Direct user permissions
- `user_devices`: Device tracking
- `revoked_tokens`: Token revocation list

### Products & Pricing
- `products`: Product catalog
- `product_categories`: Hierarchical categories
- `units`: Measurement units
- `product_unit_conversions`: Unit conversion rates
- `product_bundles`: Bundle compositions
- `product_composites`: Composite products
- `product_prices`: Location-based pricing

### CRM
- `customers`: Customer master data
- `contacts`: Customer contact persons
- `leads`: Sales leads with conversion tracking
- `opportunities`: Sales pipeline opportunities

### Audit
- `audit_logs`: Comprehensive audit trails

## Key Features

### 1. Extensible Pricing Engine
```php
// Register custom pricing engine
$pricingService->registerEngine(new CustomPricingEngine());

// Calculate price
$price = $pricingService->calculate(
    PricingStrategy::TIERED,
    $basePrice,
    $quantity,
    ['tiers' => [...]]
);
```

### 2. Precision-Safe Math
```php
use Modules\Core\Helpers\MathHelper;

// All calculations use BCMath
$total = MathHelper::multiply('10.50', '3');
$discount = MathHelper::percentage($total, '15');
$final = MathHelper::subtract($total, $discount);
```

### 3. Transaction Management
```php
use Modules\Core\Helpers\TransactionHelper;

// Execute with automatic retry on deadlock
TransactionHelper::execute(function () {
    // Your database operations
});

// Execute with pessimistic lock
TransactionHelper::withLock(function () {
    // Critical section
}, 'products', $productId);
```

### 4. Tenant Scoping
```php
// Automatic tenant filtering
use Modules\Tenant\Contracts\TenantScoped;

class Product extends Model
{
    use TenantScoped; // Auto-scopes all queries to current tenant
}
```

### 5. Audit Logging
```php
use Modules\Audit\Contracts\Auditable;

class Product extends Model
{
    use Auditable; // Auto-logs create, update, delete events
}
```

### 6. Repository Pattern
```php
use Modules\Product\Repositories\ProductRepository;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function createProduct(array $data): Product
    {
        return $this->productRepository->create($data);
    }

    public function searchProducts(string $term, int $perPage = 15)
    {
        return $this->productRepository->search($term, ['name', 'code', 'description'], $perPage);
    }
}
```

**Available Repositories:**
- **Core**: BaseRepository (abstract)
- **Product**: ProductRepository, ProductCategoryRepository, UnitRepository, ProductUnitConversionRepository
- **Auth**: UserRepository, RoleRepository, PermissionRepository, UserDeviceRepository, RevokedTokenRepository
- **Tenant**: TenantRepository, OrganizationRepository
- **Audit**: AuditLogRepository
- **Pricing**: ProductPriceRepository

### 7. Exception Handling
```php
use Modules\Product\Exceptions\ProductNotFoundException;
use Modules\Auth\Exceptions\InvalidCredentialsException;

try {
    $product = $productRepository->findOrFail($id);
} catch (ProductNotFoundException $e) {
    // Returns 404 with error code and message
    throw $e;
}
```

**Exception Hierarchy:**
- **Core**: DomainException (base), ValidationException, AuthorizationException, NotFoundException, ConflictException, BusinessRuleException
- **Module-Specific**: 21 domain exceptions across Tenant, Auth, Product, and Pricing modules

## Configuration

All configuration uses **enums and environment variables**—no hardcoded values.

### Centralized Module Configuration
Each module has its own configuration file in `config/`:
- `config/modules.php` - Module registry and lifecycle management
- `config/tenant.php` - Multi-tenancy settings
- `config/jwt.php` - JWT authentication settings
- `config/product.php` - Product catalog settings
- `config/pricing.php` - Pricing engine settings
- `config/audit.php` - Audit logging settings

### Environment Variables
```env
# Multi-Tenancy
MULTI_TENANCY_ENABLED=true
TENANCY_DATABASE_STRATEGY=single

# JWT Authentication
JWT_SECRET=your-secret-key
JWT_TTL=3600
JWT_REFRESH_TTL=86400

# Pricing
PRICING_DECIMAL_SCALE=6
PRICING_DISPLAY_DECIMALS=2
CURRENCY_CODE=USD
CURRENCY_SYMBOL=$
```

## Security

### 1. JWT Token Security
- Tokens signed with HMAC-SHA256
- Secure secret key required
- Token expiration enforced
- Revocation list for invalidated tokens

### 2. SQL Injection Prevention
- All queries use parameterized statements
- Native Laravel query builder
- Eloquent ORM protections

### 3. Authorization
- Policy-based authorization
- Middleware guards all routes
- Permission checks on every action

## Scalability

### 1. Stateless Design
- No server-side sessions
- Horizontal scaling supported
- Load balancer friendly

### 2. Database Optimization
- Proper indexing on all foreign keys
- Composite indexes for common queries
- Soft deletes for data retention

### 3. Caching Strategy
- Token revocation cached
- Tenant context cached per request

## API-First Design

All functionality is designed with API-first approach:
- RESTful resource endpoints
- Consistent response format
- Proper HTTP status codes
- Versioned APIs

## Testing Strategy

### Unit Tests
- Test individual components in isolation
- Mock dependencies
- Test edge cases

### Integration Tests
- Test module interactions
- Test database operations
- Test API endpoints

### Feature Tests
- Test complete workflows
- Test multi-tenant scenarios
- Test concurrent operations

## Deployment

### Requirements
- PHP 8.2+
- Laravel 12.x
- MySQL 8.0+ / PostgreSQL 13+
- BCMath extension enabled

### Production Checklist
- [ ] Set strong JWT_SECRET
- [ ] Enable HTTPS only
- [ ] Configure database connection pooling
- [ ] Set up automated backups
- [ ] Configure log rotation
- [ ] Set up monitoring and alerts
- [ ] Run security audit
- [ ] Enable query caching
- [ ] Configure queue workers
- [ ] Set up CI/CD pipeline

## Future Enhancements

### Planned Features
- Event sourcing for complete audit trail
- CQRS for read/write separation
- GraphQL API support
- Real-time notifications via WebSockets
- Advanced reporting and analytics
- Multi-currency support
- Workflow automation engine
- Document management system

## Contributing

All contributions must:
- Follow existing architecture patterns
- Use native Laravel/Vue features only
- Include comprehensive tests
- Maintain backward compatibility
- Update documentation
- Pass code review and security scan

## License

MIT License - See LICENSE file for details
