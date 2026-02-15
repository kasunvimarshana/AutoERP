<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Taxation\Models\TaxCalculation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaxCalculationRepository extends BaseRepository
{
    public function __construct(TaxCalculation $model)
    {
        parent::__construct($model);
    }

    public function findByEntity(string $entityType, int $entityId): Collection
    {
        return $this->model
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with(['taxJurisdiction'])
            ->orderByDesc('calculated_at')
            ->get();
    }

    public function findByCustomer(int $customerId, $startDate = null, $endDate = null): Collection
    {
        $query = $this->model
            ->where('customer_id', $customerId)
            ->with(['taxJurisdiction']);

        if ($startDate) {
            $query->where('calculated_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('calculated_at', '<=', $endDate);
        }

        return $query->orderByDesc('calculated_at')->get();
    }

    public function getTaxSummary($startDate = null, $endDate = null): array
    {
        $query = $this->model->query();

        if ($startDate) {
            $query->where('calculated_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('calculated_at', '<=', $endDate);
        }

        return [
            'total_base_amount' => $query->sum('base_amount'),
            'total_tax_amount' => $query->sum('tax_amount'),
            'total_amount' => $query->sum('total_amount'),
            'calculation_count' => $query->count(),
        ];
    }

    public function getTaxBreakdown($startDate = null, $endDate = null): Collection
    {
        $query = $this->model->query();

        if ($startDate) {
            $query->where('calculated_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('calculated_at', '<=', $endDate);
        }

        return $query->with(['taxJurisdiction'])
            ->orderByDesc('calculated_at')
            ->get();
    }

    public function paginateCalculations(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('calculated_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('calculated_at', '<=', $filters['end_date']);
        }

        return $query->with(['taxJurisdiction'])
            ->orderByDesc('calculated_at')
            ->paginate($perPage);
    }
}
