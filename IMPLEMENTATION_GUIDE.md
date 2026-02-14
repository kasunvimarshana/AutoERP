# Implementation Guide - AutoERP

## Table of Contents
1. [Quick Start](#quick-start)
2. [Architecture Overview](#architecture-overview)
3. [Module Development](#module-development)
4. [API Development](#api-development)
5. [Frontend Development](#frontend-development)
6. [Database Design](#database-design)
7. [Testing Strategy](#testing-strategy)
8. [Deployment](#deployment)
9. [Best Practices](#best-practices)

## Quick Start

### Automated Setup
```bash
# Run the setup script
./setup.sh

# Configure database in backend/.env
# Run migrations
cd backend && php artisan migrate && php artisan db:seed

# Start backend (Terminal 1)
cd backend && php artisan serve

# Start frontend (Terminal 2)
cd frontend && npm run dev
```

### Manual Setup

#### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider" --tag=migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Configure .env with database credentials
php artisan migrate
php artisan db:seed
php artisan serve
```

#### Frontend
```bash
cd frontend
npm install
cp .env.example .env
# Edit .env: VITE_API_URL=http://localhost:8000/api
npm run dev
```

## Architecture Overview

### Clean Architecture Layers

```
┌─────────────────────────────────────┐
│     Presentation Layer              │
│  (Controllers, Requests, Resources) │
├─────────────────────────────────────┤
│       Application Layer             │
│    (Services, Use Cases, DTOs)      │
├─────────────────────────────────────┤
│        Domain Layer                 │
│      (Models, Events, Rules)        │
├─────────────────────────────────────┤
│     Infrastructure Layer            │
│  (Repositories, External Services)  │
└─────────────────────────────────────┘
```

### Request Flow

```
HTTP Request
    ↓
Route → Middleware
    ↓
Controller (validate, authorize)
    ↓
Service (business logic, transaction)
    ↓
Repository (database interaction)
    ↓
Model (Eloquent ORM)
    ↓
Database
```

### Multi-Tenancy Architecture

```
Request
    ↓
Tenant Identification (domain/subdomain)
    ↓
Tenant Database/Schema Selection
    ↓
Tenant Scoped Query (Global Scopes)
    ↓
Data (Tenant Isolated)
```

## Module Development

### Creating a New Backend Module

#### Step 1: Create Module Structure

```bash
cd backend/app/Modules
mkdir NewModule
cd NewModule
mkdir Models Repositories Services Http/Controllers Http/Requests Policies Events Listeners DTOs
touch Models/.gitkeep Repositories/.gitkeep Services/.gitkeep
```

#### Step 2: Create the Model

```php
// app/Modules/NewModule/Models/NewModel.php
<?php

namespace App\Modules\NewModule\Models;

use App\Base\BaseModel;

class NewModel extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

#### Step 3: Create the Repository

```php
// app/Modules/NewModule/Repositories/NewModelRepository.php
<?php

namespace App\Modules\NewModule\Repositories;

use App\Base\BaseRepository;
use App\Modules\NewModule\Models\NewModel;

class NewModelRepository extends BaseRepository
{
    public function __construct(NewModel $model)
    {
        parent::__construct($model);
    }

    // Custom repository methods
    public function findByStatus(bool $status)
    {
        return $this->model->where('status', $status)->get();
    }
}
```

#### Step 4: Create the Service

```php
// app/Modules/NewModule/Services/NewModelService.php
<?php

namespace App\Modules\NewModule\Services;

use App\Base\BaseService;
use App\Modules\NewModule\Repositories\NewModelRepository;
use App\Modules\NewModule\DTOs\NewModelDTO;
use Illuminate\Support\Facades\DB;

class NewModelService extends BaseService
{
    public function __construct(
        private NewModelRepository $repository
    ) {}

    public function create(NewModelDTO $dto): mixed
    {
        return DB::transaction(function () use ($dto) {
            $data = [
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $dto->name,
                'description' => $dto->description,
                'status' => $dto->status ?? true,
            ];

            $model = $this->repository->create($data);

            // Dispatch events
            event(new NewModelCreated($model));

            return $model;
        });
    }

    public function update(int $id, NewModelDTO $dto): mixed
    {
        return DB::transaction(function () use ($id, $dto) {
            $model = $this->repository->find($id);
            
            $data = array_filter([
                'name' => $dto->name,
                'description' => $dto->description,
                'status' => $dto->status,
            ], fn($value) => !is_null($value));

            $updated = $this->repository->update($id, $data);

            event(new NewModelUpdated($updated));

            return $updated;
        });
    }
}
```

#### Step 5: Create the DTO

```php
// app/Modules/NewModule/DTOs/NewModelDTO.php
<?php

namespace App\Modules\NewModule\DTOs;

class NewModelDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?bool $status = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
        );
    }
}
```

#### Step 6: Create the Controller

```php
// app/Modules/NewModule/Http/Controllers/NewModelController.php
<?php

namespace App\Modules\NewModule\Http\Controllers;

use App\Base\BaseController;
use App\Modules\NewModule\Services\NewModelService;
use App\Modules\NewModule\Http\Requests\StoreNewModelRequest;
use App\Modules\NewModule\Http\Requests\UpdateNewModelRequest;
use App\Modules\NewModule\DTOs\NewModelDTO;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class NewModelController extends BaseController
{
    public function __construct(
        private NewModelService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/new-models",
     *     summary="List all new models",
     *     tags={"NewModels"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(): JsonResponse
    {
        $models = $this->service->getAll();
        return $this->successResponse($models);
    }

    /**
     * @OA\Post(
     *     path="/api/new-models",
     *     summary="Create a new model",
     *     tags={"NewModels"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StoreNewModelRequest $request): JsonResponse
    {
        $dto = NewModelDTO::fromRequest($request->validated());
        $model = $this->service->create($dto);
        return $this->successResponse($model, 'Model created successfully', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/new-models/{id}",
     *     summary="Get a specific model",
     *     tags={"NewModels"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $model = $this->service->getById($id);
        return $this->successResponse($model);
    }

    /**
     * @OA\Put(
     *     path="/api/new-models/{id}",
     *     summary="Update a model",
     *     tags={"NewModels"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(UpdateNewModelRequest $request, int $id): JsonResponse
    {
        $dto = NewModelDTO::fromRequest($request->validated());
        $model = $this->service->update($id, $dto);
        return $this->successResponse($model, 'Model updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/new-models/{id}",
     *     summary="Delete a model",
     *     tags={"NewModels"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->successResponse(null, 'Model deleted successfully');
    }
}
```

#### Step 7: Create Form Requests

```php
// app/Modules/NewModule/Http/Requests/StoreNewModelRequest.php
<?php

namespace App\Modules\NewModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Use policies for authorization
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
        ];
    }
}
```

#### Step 8: Create Migration

```bash
php artisan make:migration create_new_models_table
```

```php
// database/migrations/xxxx_xx_xx_create_new_models_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_models');
    }
};
```

#### Step 9: Add Routes

```php
// routes/api.php
use App\Modules\NewModule\Http\Controllers\NewModelController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('new-models', NewModelController::class);
});
```

#### Step 10: Create Policy (Optional)

```php
// app/Modules/NewModule/Policies/NewModelPolicy.php
<?php

namespace App\Modules\NewModule\Policies;

use App\Modules\User\Models\User;
use App\Modules\NewModule\Models\NewModel;

class NewModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('new-models.view');
    }

    public function view(User $user, NewModel $model): bool
    {
        return $user->hasPermissionTo('new-models.view') 
            && $model->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('new-models.create');
    }

    public function update(User $user, NewModel $model): bool
    {
        return $user->hasPermissionTo('new-models.update')
            && $model->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, NewModel $model): bool
    {
        return $user->hasPermissionTo('new-models.delete')
            && $model->tenant_id === $user->tenant_id;
    }
}
```

#### Step 11: Register Policy

```php
// app/Providers/AuthServiceProvider.php
use App\Modules\NewModule\Models\NewModel;
use App\Modules\NewModule\Policies\NewModelPolicy;

protected $policies = [
    NewModel::class => NewModelPolicy::class,
];
```

### Creating a New Frontend Module

#### Step 1: Create Module Structure

```bash
cd frontend/src/modules
mkdir NewModule
cd NewModule
mkdir views components store api
```

#### Step 2: Create API Client

```javascript
// frontend/src/modules/NewModule/api/index.js
import axios from 'axios'

const BASE_URL = import.meta.env.VITE_API_URL

export default {
  async getAll(params = {}) {
    const response = await axios.get(`${BASE_URL}/new-models`, { params })
    return response.data
  },

  async getById(id) {
    const response = await axios.get(`${BASE_URL}/new-models/${id}`)
    return response.data
  },

  async create(data) {
    const response = await axios.post(`${BASE_URL}/new-models`, data)
    return response.data
  },

  async update(id, data) {
    const response = await axios.put(`${BASE_URL}/new-models/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await axios.delete(`${BASE_URL}/new-models/${id}`)
    return response.data
  },
}
```

#### Step 3: Create Pinia Store

```javascript
// frontend/src/modules/NewModule/store/index.js
import { defineStore } from 'pinia'
import api from '../api'

export const useNewModelStore = defineStore('newModel', {
  state: () => ({
    items: [],
    currentItem: null,
    loading: false,
    error: null,
  }),

  getters: {
    getItemById: (state) => (id) => {
      return state.items.find(item => item.id === id)
    },
    activeItems: (state) => {
      return state.items.filter(item => item.status)
    },
  },

  actions: {
    async fetchAll(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await api.getAll(params)
        this.items = response.data
        return response
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchById(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.getById(id)
        this.currentItem = response.data
        return response
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.create(data)
        this.items.push(response.data)
        return response
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async update(id, data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.update(id, data)
        const index = this.items.findIndex(item => item.id === id)
        if (index !== -1) {
          this.items[index] = response.data
        }
        if (this.currentItem?.id === id) {
          this.currentItem = response.data
        }
        return response
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async delete(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(id)
        this.items = this.items.filter(item => item.id !== id)
        if (this.currentItem?.id === id) {
          this.currentItem = null
        }
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
```

#### Step 4: Create List View

```vue
<!-- frontend/src/modules/NewModule/views/List.vue -->
<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">{{ $t('newModule.title') }}</h1>
      <router-link
        to="/new-models/create"
        class="btn btn-primary"
      >
        {{ $t('common.create') }}
      </router-link>
    </div>

    <div v-if="store.loading" class="text-center py-8">
      <div class="spinner"></div>
    </div>

    <div v-else-if="store.error" class="alert alert-error">
      {{ store.error }}
    </div>

    <div v-else class="bg-white rounded-lg shadow">
      <table class="min-w-full">
        <thead>
          <tr class="border-b">
            <th class="px-6 py-3 text-left">{{ $t('common.name') }}</th>
            <th class="px-6 py-3 text-left">{{ $t('common.description') }}</th>
            <th class="px-6 py-3 text-left">{{ $t('common.status') }}</th>
            <th class="px-6 py-3 text-right">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in store.items"
            :key="item.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="px-6 py-4">{{ item.name }}</td>
            <td class="px-6 py-4">{{ item.description }}</td>
            <td class="px-6 py-4">
              <span
                :class="item.status ? 'badge-success' : 'badge-danger'"
              >
                {{ item.status ? $t('common.active') : $t('common.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <router-link
                :to="`/new-models/${item.id}`"
                class="btn btn-sm btn-info mr-2"
              >
                {{ $t('common.view') }}
              </router-link>
              <router-link
                :to="`/new-models/${item.id}/edit`"
                class="btn btn-sm btn-warning mr-2"
              >
                {{ $t('common.edit') }}
              </router-link>
              <button
                @click="handleDelete(item.id)"
                class="btn btn-sm btn-danger"
              >
                {{ $t('common.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useNewModelStore } from '../store'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const store = useNewModelStore()
const router = useRouter()
const { t } = useI18n()

onMounted(() => {
  store.fetchAll()
})

const handleDelete = async (id) => {
  if (confirm(t('common.confirmDelete'))) {
    try {
      await store.delete(id)
      // Show success message
    } catch (error) {
      // Show error message
    }
  }
}
</script>
```

#### Step 5: Add Routes

```javascript
// frontend/src/router/index.js
import NewModelList from '@/modules/NewModule/views/List.vue'
import NewModelDetail from '@/modules/NewModule/views/Detail.vue'
import NewModelForm from '@/modules/NewModule/views/Form.vue'

const routes = [
  // ... existing routes
  {
    path: '/new-models',
    name: 'new-models',
    component: NewModelList,
    meta: { requiresAuth: true, permission: 'new-models.view' }
  },
  {
    path: '/new-models/create',
    name: 'new-models-create',
    component: NewModelForm,
    meta: { requiresAuth: true, permission: 'new-models.create' }
  },
  {
    path: '/new-models/:id',
    name: 'new-models-detail',
    component: NewModelDetail,
    meta: { requiresAuth: true, permission: 'new-models.view' }
  },
  {
    path: '/new-models/:id/edit',
    name: 'new-models-edit',
    component: NewModelForm,
    meta: { requiresAuth: true, permission: 'new-models.update' }
  },
]
```

## Testing Strategy

### Backend Testing

```php
// tests/Feature/NewModelTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\NewModule\Models\NewModel;
use Laravel\Sanctum\Sanctum;

class NewModelTest extends TestCase
{
    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_new_models()
    {
        NewModel::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/new-models');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_can_create_new_model()
    {
        $data = [
            'name' => 'Test Model',
            'description' => 'Test Description',
            'status' => true,
        ];

        $response = $this->postJson('/api/new-models', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Test Model']);

        $this->assertDatabaseHas('new_models', $data);
    }

    public function test_can_update_new_model()
    {
        $model = NewModel::factory()->create(['tenant_id' => $this->tenant->id]);

        $data = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/new-models/{$model->id}", $data);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_can_delete_new_model()
    {
        $model = NewModel::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/new-models/{$model->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('new_models', ['id' => $model->id]);
    }

    public function test_cannot_access_other_tenant_models()
    {
        $otherTenant = Tenant::factory()->create();
        $otherModel = NewModel::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->getJson("/api/new-models/{$otherModel->id}");

        $response->assertStatus(404);
    }
}
```

## Best Practices

### 1. Always Use Transactions
```php
use Illuminate\Support\Facades\DB;

public function complexOperation($data)
{
    return DB::transaction(function () use ($data) {
        // Multiple database operations
        $model = $this->repository->create($data);
        $this->relatedRepository->update($model->id, $relatedData);
        event(new ModelCreated($model));
        return $model;
    });
}
```

### 2. Use DTOs for Data Transfer
```php
// Instead of passing arrays
$dto = NewModelDTO::fromRequest($request->validated());
$result = $this->service->create($dto);
```

### 3. Implement Proper Error Handling
```php
try {
    $result = $this->service->create($dto);
    return $this->successResponse($result);
} catch (ValidationException $e) {
    return $this->errorResponse($e->errors(), 'Validation failed', 422);
} catch (\Exception $e) {
    Log::error('Error creating model: ' . $e->getMessage());
    return $this->errorResponse(null, 'Internal server error', 500);
}
```

### 4. Use Events for Side Effects
```php
// In service
event(new ModelCreated($model));

// In listener
public function handle(ModelCreated $event)
{
    // Send notification
    // Update cache
    // Log activity
}
```

### 5. Implement Caching
```php
use Illuminate\Support\Facades\Cache;

public function getAll()
{
    return Cache::remember('models.all', 3600, function () {
        return $this->repository->all();
    });
}
```

### 6. Use Resource Classes for API Responses
```php
use Illuminate\Http\Resources\Json\JsonResource;

class NewModelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### 7. Implement Rate Limiting
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('new-models', NewModelController::class);
});
```

### 8. Document APIs with Swagger
```php
/**
 * @OA\Post(
 *     path="/api/new-models",
 *     summary="Create a new model",
 *     tags={"NewModels"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="status", type="boolean")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Created")
 * )
 */
```

### 9. Use Query Scopes
```php
// In Model
public function scopeActive($query)
{
    return $query->where('status', true);
}

public function scopeForTenant($query, $tenantId)
{
    return $query->where('tenant_id', $tenantId);
}

// Usage
NewModel::active()->forTenant($tenantId)->get();
```

### 10. Implement Soft Deletes
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class NewModel extends Model
{
    use SoftDeletes;
}

// Restore soft-deleted record
$model->restore();

// Permanently delete
$model->forceDelete();
```

## Deployment

### Production Checklist

#### Backend
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Configure production database
- [ ] Set up queue workers
- [ ] Configure caching (Redis)
- [ ] Set up scheduled tasks
- [ ] Enable HTTPS
- [ ] Configure rate limiting
- [ ] Set up logging
- [ ] Run optimizations
- [ ] Set up monitoring

#### Frontend
- [ ] Set production API URL
- [ ] Build production assets
- [ ] Configure CDN
- [ ] Enable HTTPS
- [ ] Set up error tracking
- [ ] Configure monitoring
- [ ] Optimize images
- [ ] Enable compression

### Optimization Commands

```bash
# Backend
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Frontend
npm run build
```

## Support

For issues or questions, please refer to the documentation or contact the development team.
