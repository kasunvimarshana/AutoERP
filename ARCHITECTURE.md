# AutoERP - Architecture Documentation

## Table of Contents
1. [System Architecture Overview](#system-architecture-overview)
2. [Design Patterns](#design-patterns)
3. [Module Structure](#module-structure)
4. [Data Flow](#data-flow)
5. [Security Architecture](#security-architecture)
6. [Scalability & Performance](#scalability--performance)
7. [Database Design](#database-design)
8. [API Design](#api-design)

## System Architecture Overview

AutoERP follows a **Clean Architecture** approach with clear separation of concerns across multiple layers.

### Architectural Layers

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │  Controllers   │  │  API Routes    │  │  Middleware  │  │
│  └────────────────┘  └────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Business Logic Layer                      │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │   Services     │  │     DTOs       │  │    Events    │  │
│  └────────────────┘  └────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Data Access Layer                         │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │  Repositories  │  │    Models      │  │  Eloquent    │  │
│  └────────────────┘  └────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       Database Layer                         │
│             MySQL/PostgreSQL with Multi-Tenancy              │
└─────────────────────────────────────────────────────────────┘
```

### Key Architectural Principles

1. **Separation of Concerns**: Each layer has a distinct responsibility
2. **Dependency Inversion**: High-level modules don't depend on low-level modules
3. **Single Responsibility**: Each class has one reason to change
4. **Open/Closed**: Open for extension, closed for modification
5. **Interface Segregation**: Clients shouldn't depend on unused interfaces

## Design Patterns

### 1. Repository Pattern

**Purpose**: Abstract data access logic from business logic

**Implementation**:
```php
// Interface
interface RepositoryInterface {
    public function find(int $id): ?Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

// Base Implementation
abstract class BaseRepository implements RepositoryInterface {
    abstract protected function model(): string;
    
    protected function makeModel(): Model {
        return app($this->model());
    }
}

// Concrete Implementation
class ProductRepository extends BaseRepository {
    protected function model(): string {
        return Product::class;
    }
    
    public function findByCode(string $code): ?Product {
        return $this->model->where('code', $code)->first();
    }
}
```

**Benefits**:
- Centralized data access
- Easy to mock for testing
- Swappable implementations
- Consistent query patterns

### 2. Service Layer Pattern

**Purpose**: Encapsulate business logic and orchestrate operations

**Implementation**:
```php
class ProductService extends BaseService {
    protected ProductRepository $repository;
    protected StockLedgerRepository $stockLedgerRepository;
    
    public function createProduct(array $data): Product {
        DB::beginTransaction();
        try {
            $product = $this->repository->create($data);
            
            // Initialize stock ledger
            if ($product->type === ProductType::INVENTORY) {
                $this->stockLedgerRepository->create([
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'type' => StockMovementType::INITIAL,
                ]);
            }
            
            event(new ProductCreated($product));
            
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

**Benefits**:
- Transaction management
- Business rule enforcement
- Cross-repository orchestration
- Event dispatching

### 3. Data Transfer Object (DTO) Pattern

**Purpose**: Transfer data between layers without exposing models

**Implementation**:
```php
class StockMovementDTO {
    public function __construct(
        public int $productId,
        public int $quantity,
        public StockMovementType $type,
        public ?int $warehouseId = null,
        public ?string $reference = null,
        public ?array $metadata = null
    ) {}
    
    public static function fromArray(array $data): self {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            type: StockMovementType::from($data['type']),
            warehouseId: $data['warehouse_id'] ?? null,
            reference: $data['reference'] ?? null,
            metadata: $data['metadata'] ?? null
        );
    }
    
    public function toArray(): array {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'type' => $this->type->value,
            'warehouse_id' => $this->warehouseId,
            'reference' => $this->reference,
            'metadata' => $this->metadata,
        ];
    }
}
```

**Benefits**:
- Type safety
- Validation at boundaries
- Decoupling from models
- Clear API contracts

### 4. Event-Driven Architecture

**Purpose**: Decouple modules via asynchronous communication

**Implementation**:
```php
// Event
class StockMovementRecorded implements ShouldQueue {
    public function __construct(
        public StockLedger $stockLedger
    ) {}
}

// Listener
class CheckReorderLevel implements ShouldQueue {
    public function handle(StockMovementRecorded $event): void {
        $product = $event->stockLedger->product;
        
        if ($product->stock_quantity <= $product->reorder_level) {
            // Trigger reorder notification
            event(new LowStockAlert($product));
        }
    }
}
```

**Benefits**:
- Loose coupling
- Scalability
- Asynchronous processing
- Extensibility

### 5. Strategy Pattern

**Purpose**: Encapsulate algorithms and make them interchangeable

**Implementation**: Used in pricing calculations, tax calculations, and payment methods

## Module Structure

Each module follows a consistent structure:

```
app/Modules/{ModuleName}/
├── Http/
│   └── Controllers/      # Request handling
├── Services/            # Business logic
├── Repositories/        # Data access
├── Models/             # Domain models
├── DTOs/               # Data transfer objects
├── Events/             # Domain events
├── Listeners/          # Event handlers
├── Enums/              # Type-safe constants
└── Policies/           # Authorization rules
```

### Core Modules

#### 1. IAM (Identity & Access Management)
- User management
- Role-based access control (RBAC)
- Permission management
- Multi-tenancy support

#### 2. Inventory Management
- Product catalog (inventory, service, combo, bundle)
- Append-only stock ledger
- Multi-warehouse support
- Batch/lot/serial tracking
- Pricing engine with tiers and discounts

#### 3. CRM (Customer Relationship Management)
- Customer profiles
- Contact management
- Credit limits
- Customer segmentation

#### 4. Procurement
- Supplier management
- Purchase orders
- Goods receipt notes
- Invoice matching

#### 5. POS (Point of Sale)
- Quotations
- Sales orders
- Invoices
- Payment processing

#### 6. Manufacturing
- Bill of Materials (BOM)
- Production orders
- Work orders
- Material consumption

#### 7. Finance
- Chart of accounts
- Journal entries
- Financial reports (P&L, Balance Sheet)
- Fiscal year management

#### 8. Reporting & Analytics
- Customizable dashboards
- KPI tracking
- Report scheduling
- Data export

## Data Flow

### Request Flow Example: Create Product

```
1. HTTP Request
   POST /api/v1/inventory/products
   {
     "code": "PROD-001",
     "name": "Product Name",
     "type": "inventory",
     "buying_price": 100,
     "selling_price": 150
   }

2. Route Handler
   Route::post('/products', [ProductController::class, 'store']);

3. Controller (Validation & Routing)
   ProductController::store(Request $request)
   - Validates request data
   - Calls service method
   - Returns JSON response

4. Service (Business Logic)
   ProductService::createProduct(array $data)
   - Begins transaction
   - Creates product via repository
   - Initializes stock ledger
   - Dispatches ProductCreated event
   - Commits transaction

5. Repository (Data Access)
   ProductRepository::create(array $data)
   - Creates model instance
   - Saves to database
   - Returns product model

6. Response
   {
     "success": true,
     "message": "Product created successfully",
     "data": {
       "id": 1,
       "code": "PROD-001",
       "name": "Product Name",
       ...
     }
   }

7. Asynchronous (Event Handling)
   ProductCreated event dispatched to queue
   - Listeners process asynchronously
   - Update caches
   - Send notifications
   - Update analytics
```

## Security Architecture

### 1. Authentication
- **Laravel Sanctum** for API token authentication
- Stateless token-based authentication
- Token expiration and rotation
- Multi-device support

### 2. Authorization
- **Role-Based Access Control (RBAC)**
- Permission-based authorization
- Laravel Policies for model authorization
- Route middleware for endpoint protection

### 3. Multi-Tenancy
- **Complete tenant isolation** at database level
- Global query scopes on all models
- Automatic tenant context resolution
- Cross-tenant access prevention

```php
// Automatic tenant scoping
trait TenantScoped {
    protected static function bootTenantScoped(): void {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
        
        static::creating(function (Model $model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

### 4. Data Protection
- **SQL Injection**: Prevented via Eloquent parameterized queries
- **XSS**: Output escaping in views
- **CSRF**: Token verification on state-changing requests
- **Mass Assignment**: Fillable/guarded attributes
- **Encryption**: Sensitive data encrypted at rest

### 5. API Security
- Rate limiting per user/IP
- Input validation and sanitization
- CORS configuration
- HTTPS enforcement in production

## Scalability & Performance

### 1. Database Optimization
- **Indexes** on frequently queried columns
- **Eager loading** to prevent N+1 queries
- **Query optimization** via Eloquent scopes
- **Partitioning** for large tables (stock ledgers)

### 2. Caching Strategy
- **Application cache**: Configuration, routes, views
- **Data cache**: Frequently accessed data (products, customers)
- **Query cache**: Database query results
- **Cache tags**: For granular invalidation

### 3. Queue System
- **Background jobs** for long-running tasks
- **Event processing** via queue workers
- **Job retry logic** with exponential backoff
- **Failed job handling** with notifications

### 4. Horizontal Scalability
- **Stateless application** design
- **Load balancer** ready
- **Session storage** in database/Redis
- **File storage** on S3/shared storage

## Database Design

### Design Principles

1. **Normalization**: 3NF for most tables
2. **Denormalization**: Selective for performance (e.g., cached totals)
3. **Soft Deletes**: For audit trail
4. **Timestamps**: created_at, updated_at on all tables
5. **Foreign Keys**: With cascade rules
6. **Indexes**: On foreign keys and query columns

### Append-Only Pattern (Stock Ledger)

```sql
CREATE TABLE stock_ledgers (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    warehouse_id BIGINT,
    quantity INT NOT NULL,
    running_balance INT NOT NULL,
    type ENUM('purchase', 'sale', 'adjustment', 'transfer_in', 'transfer_out'),
    reference_type VARCHAR(255),
    reference_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_product_warehouse (tenant_id, product_id, warehouse_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

**Benefits**:
- Complete audit trail
- Immutable history
- Point-in-time queries
- Compliance ready

### Multi-Tenancy Implementation

Every tenant-scoped table includes:
```sql
tenant_id BIGINT NOT NULL,
INDEX idx_tenant (tenant_id),
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
```

## API Design

### RESTful Principles

1. **Resource-Based URLs**: `/api/v1/inventory/products`
2. **HTTP Methods**: GET, POST, PUT, PATCH, DELETE
3. **Status Codes**: 200, 201, 204, 400, 401, 403, 404, 409, 422, 500
4. **Versioning**: URL-based (`/api/v1/`)
5. **Pagination**: Consistent across all list endpoints

### Response Format

**Success Response**:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

### Pagination

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

## Testing Strategy

### Test Pyramid

```
       ┌─────────────┐
       │     E2E     │  (Few, slow, expensive)
       └─────────────┘
     ┌─────────────────┐
     │   Integration   │  (Some, medium speed)
     └─────────────────┘
   ┌─────────────────────┐
   │    Unit Tests       │  (Many, fast, cheap)
   └─────────────────────┘
```

### Test Coverage

- **Unit Tests**: Services, Repositories, Utilities
- **Feature Tests**: API endpoints, Controllers
- **Integration Tests**: Module interactions
- **Database Tests**: Migrations, Seeders

## Deployment Architecture

### Recommended Production Setup

```
┌──────────────┐
│ Load Balancer│
└──────────────┘
       │
   ┌───┴────┐
   │        │
┌──▼──┐  ┌──▼──┐
│ App │  │ App │  (Multiple instances)
│ Node│  │ Node│
└──┬──┘  └──┬──┘
   │        │
   └───┬────┘
       │
┌──────▼───────┐
│   Database   │  (MySQL/PostgreSQL with replication)
└──────────────┘
       │
┌──────▼───────┐
│    Redis     │  (Cache & Queue)
└──────────────┘
       │
┌──────▼───────┐
│   Storage    │  (S3/MinIO for files)
└──────────────┘
```

### Environment Configuration

- **Local**: SQLite, sync queues
- **Staging**: MySQL, Redis, async queues
- **Production**: PostgreSQL, Redis Cluster, multiple workers

## Monitoring & Observability

### Key Metrics

1. **Application Performance**
   - Response time
   - Throughput (requests/second)
   - Error rate

2. **Database Performance**
   - Query time
   - Connection pool usage
   - Slow query log

3. **Queue Performance**
   - Job processing time
   - Failed jobs
   - Queue depth

4. **Business Metrics**
   - Active users
   - Transactions/day
   - Module usage

## Conclusion

This architecture provides:
- ✅ **Maintainability**: Clear structure, consistent patterns
- ✅ **Scalability**: Horizontal scaling, caching, queues
- ✅ **Security**: Multi-layer protection, tenant isolation
- ✅ **Performance**: Optimized queries, caching, async processing
- ✅ **Testability**: Dependency injection, mocked repositories
- ✅ **Extensibility**: Plugin architecture, event-driven design

For more details, refer to:
- [README.md](./README.md) - Project overview and setup
- [IMPLEMENTATION_GUIDE.md](./IMPLEMENTATION_GUIDE.md) - Development guidelines
- [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) - API reference
