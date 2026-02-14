## Common Patterns Across All Repositories

### 1. Controller → Service → Repository Pattern

All repositories consistently follow this layered architecture:

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
        DB::beginTransaction();
        try {
            $products = $this->repository->getAll($filters);
            DB::commit();
            return $products;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

// Repository (data access)
class ProductRepository
{
    public function getAll(array $filters): Collection
    {
        return Product::query()
            ->when(isset($filters['search']), 
                fn($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->get();
    }
}
```

### 2. Multi-Tenancy Implementation

Consistent tenant isolation across all repositories:

```php
// Global Scope
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', 
                auth()->user()->tenant_id);
        }
    }
}

// Model Trait
trait HasTenant
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
        
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

### 3. Event-Driven Architecture

Domain events for loose coupling:

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
        Mail::to($event->order->customer->email)
            ->send(new OrderConfirmation($event->order));
    }
}

// Service Layer
public function createOrder(array $data): Order
{
    DB::beginTransaction();
    try {
        $order = $this->repository->create($data);
        event(new OrderCreated($order));
        DB::commit();
        return $order;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### 4. API Resource Transformers

Consistent API response format:

```php
class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => [
                'amount' => $this->price,
                'currency' => $this->currency,
                'formatted' => $this->formatted_price,
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
```

### 5. Vue 3 Composition API with TypeScript

Modern frontend patterns from AutoERP and NexusERP:

```typescript
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useProductStore } from '@/stores/productStore'
import type { Product } from '@/types/models'

const productStore = useProductStore()
const products = ref<Product[]>([])
const loading = ref(false)

const totalProducts = computed(() => products.value.length)

onMounted(async () => {
  loading.value = true
  try {
    products.value = await productStore.fetchProducts()
  } finally {
    loading.value = false
  }
})
</script>
```

---
