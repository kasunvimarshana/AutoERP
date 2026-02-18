# Implementation Guide - AutoERP

This guide explains the implementation details and architectural decisions of the AutoERP platform.

## Architecture Overview

The platform follows **Clean Architecture** principles with strict separation of concerns:

```
┌─────────────────────────────────────────────┐
│            Presentation Layer               │
│  (Controllers, Views, API Routes)           │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│          Business Logic Layer               │
│         (Services, DTOs, Events)            │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│           Data Access Layer                 │
│       (Repositories, Models, DB)            │
└─────────────────────────────────────────────┘
```

## Design Patterns

### 1. Repository Pattern

**Purpose:** Abstract data access logic and provide a clean API for data operations.

**Implementation:**
```php
// Interface (Contract)
interface RepositoryInterface {
    public function all();
    public function find($id);
    public function create(array $data);
    // ...
}

// Base Repository
abstract class BaseRepository implements RepositoryInterface {
    protected function model(): string;
    // Common CRUD methods
}

// Specific Repository
class ProductRepository extends BaseRepository {
    protected function model(): string {
        return Product::class;
    }
    
    // Product-specific methods
}
```

### 2. Service Layer Pattern

**Purpose:** Contain business logic and orchestrate repository operations.

**Implementation:**
```php
class ProductService extends BaseService {
    public function __construct(
        protected ProductRepository $repository
    ) {}
    
    public function createProduct(array $data): Product {
        // Validation
        // Business logic
        // Call repository
        return $this->repository->create($data);
    }
}
```

### 3. DTO (Data Transfer Object) Pattern

**Purpose:** Transfer data between layers without coupling to models.

**Implementation:**
```php
class StockMovementDTO {
    public function __construct(
        public int $productId,
        public StockMovementType $movementType,
        public float $quantity,
        public ?int $warehouseId = null,
        // ...
    ) {}
}
```

### 4. Event-Driven Pattern

**Purpose:** Decouple business logic and enable asynchronous processing.

**Implementation:**
```php
// Event
class StockMovementRecorded {
    public function __construct(
        public StockLedger $stockLedger
    ) {}
}

// Listener
class CheckReorderLevel implements ShouldQueue {
    public function handle(StockMovementRecorded $event): void {
        // Check and notify if below reorder level
    }
}

// Registration
protected $listen = [
    StockMovementRecorded::class => [
        CheckReorderLevel::class,
    ],
];
```

## Multi-Tenancy Implementation

### Tenant Scoping

All tenant-specific models automatically filter by tenant:

```php
trait TenantScoped {
    protected static function bootTenantScoped() {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

### Usage

```php
class Product extends Model {
    use TenantScoped;
    // Automatically filters by tenant_id
}

// This query automatically includes WHERE tenant_id = ?
$products = Product::all();
```

## Stock Ledger - Append-Only Pattern

### Principle

Never delete or modify stock ledger entries. Always create new entries for corrections.

### Implementation

```php
class StockMovementService {
    public function recordMovement(StockMovementDTO $dto): StockLedger {
        return DB::transaction(function () use ($dto) {
            $quantity = $this->calculateQuantity($dto->movementType, $dto->quantity);
            $currentBalance = $this->getCurrentBalance($dto->productId);
            $newBalance = $currentBalance + $quantity;
            
            return StockLedger::create([
                'product_id' => $dto->productId,
                'quantity' => $quantity,
                'running_balance' => $newBalance,
                // ...
            ]);
        });
    }
}
```

### Benefits

- Complete audit trail
- Historical accuracy
- Never lose data
- Easy to trace discrepancies

## Pricing Engine

### Features

1. **Base Pricing:** Product buying and selling prices
2. **Tiered Pricing:** Quantity-based discounts
3. **Customer-Specific Pricing:** Custom prices per customer
4. **Tax Calculation:** Percentage or fixed tax
5. **Discount Management:** Product-level and transaction-level

### Implementation

```php
class PricingService {
    public function calculatePrice(
        int $productId,
        float $quantity,
        ?int $customerId = null
    ): array {
        // 1. Get base price
        $unitPrice = $product->selling_price;
        $subtotal = $unitPrice * $quantity;
        
        // 2. Apply discounts
        $discount = $this->calculateDiscounts($product, $quantity, $customerId);
        $afterDiscount = $subtotal - $discount;
        
        // 3. Calculate tax
        $tax = $this->calculateTax($product, $afterDiscount);
        
        // 4. Return breakdown
        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $afterDiscount + $tax,
        ];
    }
}
```

## Event-Driven Workflows

### Stock Movement Event Flow

```
Stock Movement Recorded
    ↓
CheckReorderLevel Listener
    ↓
If below reorder level:
    → Create Notification
    → Dispatch LowStockAlert Event
    → Auto-create Purchase Order (optional)
```

### Purchase Order Event Flow

```
Purchase Order Created
    ↓
PurchaseOrderCreated Event
    ↓
Log Activity
    ↓
Purchase Order Approved
    ↓
PurchaseOrderApproved Event
    ↓
NotifySupplier Listener
    → Send Email to Supplier
    → Update Inventory Expected
```

## Authentication & Authorization

### Authentication Flow

1. User sends credentials to `/api/v1/auth/login`
2. Backend validates credentials
3. Creates Sanctum token
4. Returns token to client
5. Client stores token (localStorage/cookies)
6. Client includes token in subsequent requests

### Authorization

Uses **Role-Based Access Control (RBAC)**:

```php
// Check if user has role
if ($user->hasRole('admin')) {
    // Allow access
}

// Check if user has permission
if ($user->hasPermission('products.create')) {
    // Allow action
}
```

## Database Schema Design

### Key Principles

1. **Normalized Design:** Minimize data redundancy
2. **Foreign Keys:** Enforce referential integrity
3. **Indexes:** Optimize query performance
4. **Soft Deletes:** Never hard delete data
5. **Timestamps:** Track creation and updates

### Example: Products Table

```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    sku VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    product_type VARCHAR(50),
    buying_price DECIMAL(15,2),
    selling_price DECIMAL(15,2),
    current_stock DECIMAL(15,2),
    reorder_level DECIMAL(15,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    INDEX idx_tenant (tenant_id),
    INDEX idx_sku (sku),
    INDEX idx_type (product_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

## Frontend Architecture

### State Management with Pinia

```javascript
// Store definition
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),
  
  actions: {
    async login(email, password) {
      const response = await authService.login(email, password)
      this.user = response.data.user
      this.token = response.data.token
    }
  }
})
```

### API Service Layer

```javascript
// Centralized API calls
export const productService = {
  async getAll() {
    return await apiClient.get('/inventory/products')
  },
  
  async create(data) {
    return await apiClient.post('/inventory/products', data)
  }
}
```

## Testing Strategy

### Unit Tests

Test individual components in isolation:

```php
public function test_product_repository_creates_product() {
    $repository = new ProductRepository();
    $product = $repository->create([
        'sku' => 'TEST-001',
        'name' => 'Test Product',
    ]);
    
    $this->assertNotNull($product->id);
}
```

### Feature Tests

Test complete workflows:

```php
public function test_user_can_create_product_via_api() {
    $response = $this->actingAs($user)
        ->postJson('/api/v1/inventory/products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
        ]);
    
    $response->assertStatus(201);
}
```

## Performance Optimization

### 1. Eager Loading

Prevent N+1 queries:

```php
// Bad
$products = Product::all();
foreach ($products as $product) {
    echo $product->category->name; // N+1 queries
}

// Good
$products = Product::with('category')->get();
foreach ($products as $product) {
    echo $product->category->name; // 2 queries total
}
```

### 2. Caching

Cache frequently accessed data:

```php
$categories = Cache::remember('categories', 3600, function () {
    return Category::all();
});
```

### 3. Database Indexing

Index frequently queried columns:

```php
$table->index('tenant_id');
$table->index(['tenant_id', 'is_active']);
```

## Security Best Practices

1. **Input Validation:** Validate all user input
2. **SQL Injection Prevention:** Use Eloquent/Query Builder
3. **XSS Prevention:** Escape output
4. **CSRF Protection:** Enable CSRF tokens
5. **Rate Limiting:** Limit API requests
6. **Authentication:** Use Sanctum tokens
7. **Authorization:** Check permissions
8. **Encryption:** Encrypt sensitive data

## Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure database credentials
- [ ] Run migrations
- [ ] Seed initial data
- [ ] Configure queue workers
- [ ] Set up HTTPS
- [ ] Configure CORS
- [ ] Set up backups
- [ ] Configure logging
- [ ] Set up monitoring

## Troubleshooting

### Common Issues

**Issue:** 401 Unauthorized
**Solution:** Check token in Authorization header

**Issue:** 422 Validation Error
**Solution:** Review validation rules and request data

**Issue:** 500 Server Error
**Solution:** Check logs in `storage/logs/laravel.log`

**Issue:** CORS errors
**Solution:** Configure CORS in `config/cors.php`

## Contributing

1. Follow the established patterns
2. Write tests for new features
3. Update documentation
4. Use meaningful commit messages
5. Create pull requests for review

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Guide](https://vuejs.org/guide/)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
