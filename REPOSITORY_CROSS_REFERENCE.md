# Repository Cross-Reference Guide

## Purpose

This document provides a detailed cross-reference guide for the seven related ERP repositories that informed the AutoERP design. Use this guide to quickly locate specific implementations, patterns, and features across the reference repositories.

---

## 1. AutoERP

**Repository**: https://github.com/kasunvimarshana/AutoERP

### Key Contributions to AutoERP

1. **TypeScript Adoption**
   - Fully typed Vue.js 3 frontend
   - Type-safe API client
   - Enhanced developer experience
   
2. **Domain Specialization**
   - Vehicle service center focus
   - Appointment and bay scheduling
   - Job card workflows
   - Service history tracking
   
3. **Granular RBAC**
   - 73 granular permissions
   - 4 default roles (Super Admin, Admin, Manager, Technician)
   - Permission-aware UI components
   
4. **Production Metrics**
   - 35 database tables
   - 85+ API endpoints
   - 15,000+ lines of code
   - 12 passing tests with 51 assertions

### Recommended for Learning

- ✅ **TypeScript patterns**: Best practices for Vue 3 + TypeScript
- ✅ **Permission system**: Granular RBAC implementation
- ✅ **Testing approach**: Comprehensive test coverage examples
- ✅ **Domain models**: Vehicle service center domain modeling

### File Paths to Explore

```
AutoERP/
├── resources/js/
│   ├── types/                   # TypeScript type definitions
│   ├── stores/                  # Pinia stores
│   ├── services/                # API services
│   └── components/              # Vue components
├── app/
│   ├── Models/
│   │   ├── Vehicle.php
│   │   ├── Appointment.php
│   │   └── JobCard.php
│   └── Permissions/             # Permission definitions
├── tests/
│   ├── Feature/                 # Feature tests
│   └── Unit/                    # Unit tests
└── database/
    └── seeders/
        └── PermissionSeeder.php # Granular permissions
```

## Common Patterns Across Repositories

### 1. Controller → Service → Repository Pattern

All repositories follow this pattern consistently:

```php
// Controller (thin, handles HTTP)
class ProductController extends Controller
{
    public function __construct(private ProductService $service) {}
    
    public function index(Request $request)
    {
        $products = $this->service->getAll($request->all());
        return ProductResource::collection($products);
    }
}

// Service (business logic, transactions)
class ProductService
{
    public function __construct(private ProductRepository $repository) {}
    
    public function getAll(array $filters): Collection
    {
        return $this->repository->getAll($filters);
    }
}

// Repository (data access)
class ProductRepository
{
    public function getAll(array $filters): Collection
    {
        return Product::query()
            ->when(isset($filters['search']), fn($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->get();
    }
}
```

### 2. Tenant-Aware Global Scopes

```php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    }
}

// In Model
protected static function booted()
{
    static::addGlobalScope(new TenantScope());
}
```

### 3. Event-Driven Architecture

```php
// Event
class OrderCreated
{
    public function __construct(public Order $order) {}
}

// Listener
class SendOrderConfirmationEmail
{
    public function handle(OrderCreated $event)
    {
        Mail::to($event->order->customer->email)->send(new OrderConfirmation($event->order));
    }
}

// Dispatch in Service
event(new OrderCreated($order));
```

### 4. API Resource Transformers

```php
class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'created_at' => $this->created_at->toISOString(),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
```

### 5. Vue Composition API with TypeScript

```typescript
// Component
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useProductStore } from '@/stores/productStore'
import type { Product } from '@/types'

const productStore = useProductStore()
const products = ref<Product[]>([])

onMounted(async () => {
  products.value = await productStore.fetchProducts()
})
</script>
```