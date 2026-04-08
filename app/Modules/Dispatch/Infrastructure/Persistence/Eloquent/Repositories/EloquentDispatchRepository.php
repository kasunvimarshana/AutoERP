<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;

class EloquentDispatchRepository extends EloquentRepository implements DispatchRepositoryInterface
{
    public function __construct(DispatchModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (DispatchModel $m): Dispatch => $this->mapModelToDomainEntity($m));
    }

    public function save(Dispatch $dispatch): Dispatch
    {
        $savedModel = null;

        DB::transaction(function () use ($dispatch, &$savedModel) {
            $data = [
                'tenant_id'              => $dispatch->getTenantId(),
                'reference_number'       => $dispatch->getReferenceNumber(),
                'status'                 => $dispatch->getStatus(),
                'warehouse_id'           => $dispatch->getWarehouseId(),
                'sales_order_id'         => $dispatch->getSalesOrderId(),
                'customer_id'            => $dispatch->getCustomerId(),
                'customer_reference'     => $dispatch->getCustomerReference(),
                'dispatch_date'          => $dispatch->getDispatchDate(),
                'estimated_delivery_date'=> $dispatch->getEstimatedDeliveryDate(),
                'actual_delivery_date'   => $dispatch->getActualDeliveryDate(),
                'carrier'                => $dispatch->getCarrier(),
                'tracking_number'        => $dispatch->getTrackingNumber(),
                'currency'               => $dispatch->getCurrency(),
                'total_weight'           => $dispatch->getTotalWeight(),
                'notes'                  => $dispatch->getNotes(),
                'metadata'               => $dispatch->getMetadata()->toArray(),
                'confirmed_by'           => $dispatch->getConfirmedBy(),
                'confirmed_at'           => $dispatch->getConfirmedAt()?->format('Y-m-d H:i:s'),
                'shipped_by'             => $dispatch->getShippedBy(),
                'shipped_at'             => $dispatch->getShippedAt()?->format('Y-m-d H:i:s'),
            ];

            if ($dispatch->getId()) {
                $savedModel = $this->update($dispatch->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof DispatchModel) {
            throw new \RuntimeException('Failed to save Dispatch.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?Dispatch
    {
        $model = $this->findModel($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
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

    public function findBySalesOrder(int $tenantId, int $salesOrderId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('sales_order_id', $salesOrderId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByReferenceNumber(int $tenantId, string $referenceNumber): ?Dispatch
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('reference_number', $referenceNumber)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed
    {
        $query = $this->model->newQuery();

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get()->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(DispatchModel $model): Dispatch
    {
        return new Dispatch(
            tenantId:              $model->tenant_id,
            referenceNumber:       $model->reference_number,
            warehouseId:           $model->warehouse_id,
            customerId:            $model->customer_id,
            dispatchDate:          $model->dispatch_date instanceof \DateTimeInterface
                                       ? $model->dispatch_date->format('Y-m-d')
                                       : (string) $model->dispatch_date,
            salesOrderId:          $model->sales_order_id,
            customerReference:     $model->customer_reference,
            estimatedDeliveryDate: $model->estimated_delivery_date instanceof \DateTimeInterface
                                       ? $model->estimated_delivery_date->format('Y-m-d')
                                       : $model->estimated_delivery_date,
            actualDeliveryDate:    $model->actual_delivery_date instanceof \DateTimeInterface
                                       ? $model->actual_delivery_date->format('Y-m-d')
                                       : $model->actual_delivery_date,
            carrier:               $model->carrier,
            trackingNumber:        $model->tracking_number,
            currency:              $model->currency,
            totalWeight:           $model->total_weight !== null ? (float) $model->total_weight : null,
            notes:                 $model->notes,
            metadata:              isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:                $model->status,
            confirmedBy:           $model->confirmed_by,
            confirmedAt:           $model->confirmed_at,
            shippedBy:             $model->shipped_by,
            shippedAt:             $model->shipped_at,
            id:                    $model->id,
            createdAt:             $model->created_at,
            updatedAt:             $model->updated_at,
        );
    }
}
