<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Procurement\Entities\PurchaseRequisition;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;

/**
 * Purchase Requisition Repository Implementation
 *
 * Handles all purchase requisition data access operations.
 */
class PurchaseRequisitionRepository extends BaseRepository implements PurchaseRequisitionRepositoryInterface
{
    /**
     * PurchaseRequisitionRepository constructor.
     */
    public function __construct(PurchaseRequisition $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByRequisitionNumber(string $requisitionNumber): ?PurchaseRequisition
    {
        return $this->model->where('requisition_number', $requisitionNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequisitionsByRequester(int $requesterId): Collection
    {
        return $this->model->where('requester_id', $requesterId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequisitionsByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequisitionsByApprovalStatus(string $approvalStatus): Collection
    {
        return $this->model->where('approval_status', $approvalStatus)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findWithLines(int $id): ?PurchaseRequisition
    {
        return $this->model->with('lines')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('requisition_number', 'LIKE', "%{$query}%")
            ->orWhere('department', 'LIKE', "%{$query}%")
            ->get();
    }
}
