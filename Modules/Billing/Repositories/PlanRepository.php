<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Billing\Models\Plan;
use Modules\Core\Repositories\BaseRepository;

class PlanRepository extends BaseRepository
{
    protected function makeModel(): Model
    {
        return new Plan;
    }

    public function findByCode(string $code): ?Plan
    {
        return $this->model->where('code', $code)->first();
    }

    public function getActivePlans(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getPublicPlans(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function searchPlans(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sort_order')->paginate($perPage);
    }
}
