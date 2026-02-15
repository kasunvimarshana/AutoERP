<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Taxation\Models\TaxExemption;
use Illuminate\Database\Eloquent\Collection;

class TaxExemptionRepository extends BaseRepository
{
    public function __construct(TaxExemption $model)
    {
        parent::__construct($model);
    }

    public function getActiveExemptions(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->with(['taxRate', 'taxGroup'])
            ->orderBy('name')
            ->get();
    }

    public function findByEntity(string $entityType, int $entityId): Collection
    {
        return $this->model
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->with(['taxRate', 'taxGroup'])
            ->get();
    }

    public function findByCustomer(int $customerId, $date = null): Collection
    {
        $date = $date ?? now();
        
        return $this->model
            ->where('entity_type', 'customer')
            ->where('entity_id', $customerId)
            ->where('is_active', true)
            ->where('valid_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $date);
            })
            ->with(['taxRate', 'taxGroup'])
            ->get();
    }

    public function findByProduct(int $productId, $date = null): Collection
    {
        $date = $date ?? now();
        
        return $this->model
            ->where('entity_type', 'product')
            ->where('entity_id', $productId)
            ->where('is_active', true)
            ->where('valid_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $date);
            })
            ->with(['taxRate', 'taxGroup'])
            ->get();
    }

    public function findValidExemptions(string $entityType, int $entityId, $date = null): Collection
    {
        $date = $date ?? now();
        
        return $this->model
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->where('valid_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $date);
            })
            ->with(['taxRate', 'taxGroup'])
            ->get();
    }
}
