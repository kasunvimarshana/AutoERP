<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Procurement\Entities\PurchaseRequisitionLine;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionLineRepositoryInterface;

/**
 * Purchase Requisition Line Repository Implementation
 *
 * Handles all purchase requisition line data access operations.
 */
class PurchaseRequisitionLineRepository extends BaseRepository implements PurchaseRequisitionLineRepositoryInterface
{
    /**
     * PurchaseRequisitionLineRepository constructor.
     */
    public function __construct(PurchaseRequisitionLine $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinesByRequisition(int $requisitionId): Collection
    {
        return $this->model->where('purchase_requisition_id', $requisitionId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByRequisition(int $requisitionId): bool
    {
        return $this->model->where('purchase_requisition_id', $requisitionId)->delete();
    }
}
