<?php

namespace App\Services;

use App\Contracts\Services\ProductServiceInterface;
use App\Enums\AuditAction;
use App\Events\ProductCreated;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::where('tenant_id', $tenantId)
            ->with(['category', 'buyUnit', 'sellUnit']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('sku', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']).'-'.Str::random(6);

            $product = Product::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Product::class,
                auditableId: $product->id,
                newValues: $data
            );

            $fresh = $product->fresh(['category', 'buyUnit', 'sellUnit']);
            Event::dispatch(new ProductCreated($fresh));

            return $fresh;
        });
    }

    public function update(string $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = Product::lockForUpdate()->findOrFail($id);
            $oldValues = $product->toArray();

            // Optimistic locking check
            if (isset($data['lock_version']) && (int) $data['lock_version'] !== $product->lock_version) {
                throw new \RuntimeException('Concurrent modification detected. Please refresh and retry.');
            }

            $data['lock_version'] = $product->lock_version + 1;
            $product->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Product::class,
                auditableId: $product->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $product->fresh(['category', 'buyUnit', 'sellUnit']);
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $product = Product::findOrFail($id);
            $product->delete();

            $this->auditService->log(
                action: AuditAction::Deleted,
                auditableType: Product::class,
                auditableId: $id
            );
        });
    }
}
