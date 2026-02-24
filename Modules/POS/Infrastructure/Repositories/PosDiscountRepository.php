<?php

namespace Modules\POS\Infrastructure\Repositories;

use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Infrastructure\Models\PosDiscountModel;

class PosDiscountRepository implements PosDiscountRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return PosDiscountModel::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return PosDiscountModel::where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->first();
    }

    public function create(array $data): object
    {
        return PosDiscountModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $discount = PosDiscountModel::findOrFail($id);
        $discount->update($data);
        return $discount->fresh();
    }

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = PosDiscountModel::where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function delete(string $id): void
    {
        PosDiscountModel::findOrFail($id)->delete();
    }

    public function incrementUsage(string $id): void
    {
        PosDiscountModel::where('id', $id)->increment('times_used');
    }
}
