<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class EloquentSalesOrderRepository extends EloquentRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrderModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SalesOrderModel $m): SalesOrder => $this->mapModelToDomainEntity($m));
    }

    public function save(SalesOrder $order): SalesOrder
    {
        $savedModel = null;

        DB::transaction(function () use ($order, &$savedModel) {
            $data = [
                'tenant_id'          => $order->getTenantId(),
                'reference_number'   => $order->getReferenceNumber(),
                'status'             => $order->getStatus(),
                'customer_id'        => $order->getCustomerId(),
                'customer_reference' => $order->getCustomerReference(),
                'order_date'         => $order->getOrderDate(),
                'required_date'      => $order->getRequiredDate(),
                'warehouse_id'       => $order->getWarehouseId(),
                'currency'           => $order->getCurrency(),
                'subtotal'           => $order->getSubtotal(),
                'tax_amount'         => $order->getTaxAmount(),
                'discount_amount'    => $order->getDiscountAmount(),
                'total_amount'       => $order->getTotalAmount(),
                'shipping_address'   => $order->getShippingAddress(),
                'notes'              => $order->getNotes(),
                'metadata'           => $order->getMetadata()->toArray(),
                'confirmed_by'       => $order->getConfirmedBy(),
                'confirmed_at'       => $order->getConfirmedAt()?->format('Y-m-d H:i:s'),
                'shipped_by'         => $order->getShippedBy(),
                'shipped_at'         => $order->getShippedAt()?->format('Y-m-d H:i:s'),
                'delivered_at'       => $order->getDeliveredAt()?->format('Y-m-d H:i:s'),
            ];

            if ($order->getId()) {
                $savedModel = $this->update($order->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof SalesOrderModel) {
            throw new \RuntimeException('Failed to save SalesOrder.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByCustomer(int $tenantId, int $customerId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
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

    public function findByReferenceNumber(int $tenantId, string $referenceNumber): ?SalesOrder
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('reference_number', $referenceNumber)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(SalesOrderModel $model): SalesOrder
    {
        return new SalesOrder(
            tenantId:          $model->tenant_id,
            referenceNumber:   $model->reference_number,
            customerId:        $model->customer_id,
            orderDate:         $model->order_date,
            customerReference: $model->customer_reference,
            requiredDate:      $model->required_date,
            warehouseId:       $model->warehouse_id,
            currency:          $model->currency,
            subtotal:          (float) $model->subtotal,
            taxAmount:         (float) $model->tax_amount,
            discountAmount:    (float) $model->discount_amount,
            totalAmount:       (float) $model->total_amount,
            shippingAddress:   isset($model->shipping_address) ? (array) $model->shipping_address : null,
            notes:             $model->notes,
            metadata:          isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:            $model->status,
            confirmedBy:       $model->confirmed_by,
            confirmedAt:       $model->confirmed_at,
            shippedBy:         $model->shipped_by,
            shippedAt:         $model->shipped_at,
            deliveredAt:       $model->delivered_at,
            id:                $model->id,
            createdAt:         $model->created_at,
            updatedAt:         $model->updated_at,
        );
    }
}
