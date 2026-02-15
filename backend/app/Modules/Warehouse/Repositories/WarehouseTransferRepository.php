<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Warehouse\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Collection;

class WarehouseTransferRepository extends BaseRepository implements WarehouseTransferRepositoryInterface
{
    public function __construct(WarehouseTransfer $model)
    {
        parent::__construct($model);
    }

    public function findByNumber(string $transferNumber): ?WarehouseTransfer
    {
        return $this->model->where('transfer_number', $transferNumber)->first();
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model->status($status)
            ->with(['items', 'sourceBranch', 'destinationBranch'])
            ->orderBy('transfer_date', 'desc')
            ->get();
    }

    public function getByBranch(int $branchId, string $direction = 'source'): Collection
    {
        return $this->model->byBranch($branchId, $direction)
            ->with(['items', 'sourceBranch', 'destinationBranch'])
            ->orderBy('transfer_date', 'desc')
            ->get();
    }

    public function getPendingTransfers(?int $branchId = null): Collection
    {
        $query = $this->model->pending()
            ->with(['items', 'sourceBranch', 'destinationBranch'])
            ->orderBy('priority', 'desc')
            ->orderBy('transfer_date', 'asc');

        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('source_branch_id', $branchId)
                  ->orWhere('destination_branch_id', $branchId);
            });
        }

        return $query->get();
    }

    public function getInTransitTransfers(?int $branchId = null): Collection
    {
        $query = $this->model->inTransit()
            ->with(['items', 'sourceBranch', 'destinationBranch'])
            ->orderBy('expected_delivery_date', 'asc');

        if ($branchId) {
            $query->where('destination_branch_id', $branchId);
        }

        return $query->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('transfer_date', [$startDate, $endDate])
            ->with(['items', 'sourceBranch', 'destinationBranch'])
            ->orderBy('transfer_date', 'desc')
            ->get();
    }

    public function getWithItems(int $id): ?WarehouseTransfer
    {
        return $this->model->with([
            'items.product',
            'items.variant',
            'items.unit',
            'sourceBranch',
            'sourceLocation',
            'destinationBranch',
            'destinationLocation',
            'approvedBy',
        ])->find($id);
    }

    public function search(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['transfer_number'])) {
            $query->where('transfer_number', 'like', "%{$filters['transfer_number']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['source_branch_id'])) {
            $query->where('source_branch_id', $filters['source_branch_id']);
        }

        if (isset($filters['destination_branch_id'])) {
            $query->where('destination_branch_id', $filters['destination_branch_id']);
        }

        if (isset($filters['from_date'])) {
            $query->where('transfer_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('transfer_date', '<=', $filters['to_date']);
        }

        return $query->with(['items', 'sourceBranch', 'destinationBranch'])
            ->orderBy('transfer_date', 'desc')
            ->get();
    }
}
