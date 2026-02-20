<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Brand;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Brand::where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): Brand
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']).'-'.Str::random(6);

            $brand = Brand::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Brand::class,
                auditableId: $brand->id,
                newValues: $data
            );

            return $brand;
        });
    }

    public function update(string $id, array $data): Brand
    {
        return DB::transaction(function () use ($id, $data) {
            $brand = Brand::findOrFail($id);
            $oldValues = $brand->toArray();
            $brand->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Brand::class,
                auditableId: $brand->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $brand->fresh();
        });
    }

    public function delete(string $id): void
    {
        Brand::findOrFail($id)->delete();
    }
}
