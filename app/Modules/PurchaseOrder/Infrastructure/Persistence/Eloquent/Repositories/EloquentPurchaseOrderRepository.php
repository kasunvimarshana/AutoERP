<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

class EloquentPurchaseOrderRepository extends EloquentRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrderModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseOrderModel $m): PurchaseOrder => $this->mapModelToDomainEntity($m));
    }

    public function save(PurchaseOrder $order): PurchaseOrder
    {
        $savedModel = null;

        DB::transaction(function () use ($order, &$savedModel) {
            $data = [
                'tenant_id'          => $order->getTenantId(),
                'reference_number'   => $order->getReferenceNumber(),
                'status'             => $order->getStatus(),
                'supplier_id'        => $order->getSupplierId(),
                'supplier_reference' => $order->getSupplierReference(),
                'order_date'         => $order->getOrderDate(),
                'expected_date'      => $order->getExpectedDate(),
                'warehouse_id'       => $order->getWarehouseId(),
                'currency'           => $order->getCurrency(),
                'subtotal'           => $order->getSubtotal(),
                'tax_amount'         => $order->getTaxAmount(),
                'discount_amount'    => $order->getDiscountAmount(),
                'total_amount'       => $order->getTotalAmount(),
                'notes'              => $order->getNotes(),
                'metadata'           => $order->getMetadata()->toArray(),
                'approved_by'        => $order->getApprovedBy(),
                'approved_at'        => $order->getApprovedAt()?->format('Y-m-d H:i:s'),
                'submitted_by'       => $order->getSubmittedBy(),
                'submitted_at'       => $order->getSubmittedAt()?->format('Y-m-d H:i:s'),
            ];

            if ($order->getId()) {
                $savedModel = $this->update($order->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PurchaseOrderModel) {
            throw new \RuntimeException('Failed to save PurchaseOrder.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findBySupplier(int $tenantId, int $supplierId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByReferenceNumber(int $tenantId, string $referenceNumber): ?PurchaseOrder
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('reference_number', $referenceNumber)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(PurchaseOrderModel $model): PurchaseOrder
    {
        return new PurchaseOrder(
            tenantId:          $model->tenant_id,
            referenceNumber:   $model->reference_number,
            supplierId:        $model->supplier_id,
            orderDate:         $model->order_date,
            supplierReference: $model->supplier_reference,
            expectedDate:      $model->expected_date,
            warehouseId:       $model->warehouse_id,
            currency:          $model->currency,
            subtotal:          (float) $model->subtotal,
            taxAmount:         (float) $model->tax_amount,
            discountAmount:    (float) $model->discount_amount,
            totalAmount:       (float) $model->total_amount,
            notes:             $model->notes,
            metadata:          isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:            $model->status,
            approvedBy:        $model->approved_by,
            approvedAt:        $model->approved_at,
            submittedBy:       $model->submitted_by,
            submittedAt:       $model->submitted_at,
            id:                $model->id,
            createdAt:         $model->created_at,
            updatedAt:         $model->updated_at,
        );
    }
}
