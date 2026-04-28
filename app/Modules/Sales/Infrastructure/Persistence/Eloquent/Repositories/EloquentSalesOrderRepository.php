<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class EloquentSalesOrderRepository extends EloquentRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrderModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SalesOrderModel $m): SalesOrder => $this->mapModelToDomainEntity($m));
    }

    public function save(SalesOrder $order): SalesOrder
    {
        return DB::transaction(function () use ($order): SalesOrder {
            $data = [
                'tenant_id' => $order->getTenantId(),
                'customer_id' => $order->getCustomerId(),
                'org_unit_id' => $order->getOrgUnitId(),
                'warehouse_id' => $order->getWarehouseId(),
                'so_number' => $order->getSoNumber(),
                'status' => $order->getStatus(),
                'currency_id' => $order->getCurrencyId(),
                'exchange_rate' => $order->getExchangeRate(),
                'order_date' => $order->getOrderDate()->format('Y-m-d'),
                'requested_delivery_date' => $order->getRequestedDeliveryDate()?->format('Y-m-d'),
                'price_list_id' => $order->getPriceListId(),
                'subtotal' => $order->getSubtotal(),
                'tax_total' => $order->getTaxTotal(),
                'discount_total' => $order->getDiscountTotal(),
                'grand_total' => $order->getGrandTotal(),
                'notes' => $order->getNotes(),
                'metadata' => $order->getMetadata(),
                'created_by' => $order->getCreatedBy(),
                'approved_by' => $order->getApprovedBy(),
            ];

            if ($order->getId()) {
                $model = $this->update($order->getId(), $data);
            } else {
                $model = $this->create($data);
            }

            /** @var SalesOrderModel $model */
            $keptLineIds = [];
            foreach ($order->getLines() as $line) {
                $lineData = [
                    'tenant_id' => (int) $model->tenant_id,
                    'product_id' => $line->getProductId(),
                    'variant_id' => $line->getVariantId(),
                    'description' => $line->getDescription(),
                    'uom_id' => $line->getUomId(),
                    'ordered_qty' => $line->getOrderedQty(),
                    'shipped_qty' => $line->getShippedQty(),
                    'reserved_qty' => $line->getReservedQty(),
                    'unit_price' => $line->getUnitPrice(),
                    'discount_pct' => $line->getDiscountPct(),
                    'tax_group_id' => $line->getTaxGroupId(),
                    'line_total' => $line->getLineTotal(),
                    'income_account_id' => $line->getIncomeAccountId(),
                    'batch_id' => $line->getBatchId(),
                    'serial_id' => $line->getSerialId(),
                ];

                $lineId = $line->getId();
                if ($lineId !== null) {
                    $updated = $model->lines()
                        ->where('tenant_id', (int) $model->tenant_id)
                        ->whereKey($lineId)
                        ->update($lineData);

                    if ($updated > 0) {
                        $keptLineIds[] = $lineId;
                        continue;
                    }
                }

                $createdLine = $model->lines()->create($lineData);
                $keptLineIds[] = (int) $createdLine->id;
            }

            $lineCleanupQuery = $model->lines()->where('tenant_id', (int) $model->tenant_id);
            if ($keptLineIds === []) {
                $lineCleanupQuery->delete();
            } else {
                $lineCleanupQuery->whereNotIn('id', $keptLineIds)->delete();
            }

            $model->load('lines');

            return $this->toDomainEntity($model);
        });
    }

    public function find(int|string $id, array $columns = ['*']): ?SalesOrder
    {
        /** @var SalesOrderModel|null $model */
        $model = $this->model->newQuery()->with('lines')->find($id, $columns);

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndSoNumber(int $tenantId, string $soNumber): ?SalesOrder
    {
        /** @var SalesOrderModel|null $model */
        $model = $this->model->newQuery()->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('so_number', $soNumber)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(SalesOrderModel $model): SalesOrder
    {
        $order = new SalesOrder(
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            warehouseId: (int) $model->warehouse_id,
            currencyId: (int) $model->currency_id,
            orderDate: new \DateTimeImmutable((string) $model->order_date),
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            soNumber: $model->so_number,
            status: (string) $model->status,
            exchangeRate: (string) $model->exchange_rate,
            requestedDeliveryDate: $model->requested_delivery_date !== null
                ? new \DateTimeImmutable((string) $model->requested_delivery_date)
                : null,
            priceListId: $model->price_list_id !== null ? (int) $model->price_list_id : null,
            subtotal: (string) $model->subtotal,
            taxTotal: (string) $model->tax_total,
            discountTotal: (string) $model->discount_total,
            grandTotal: (string) $model->grand_total,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            createdBy: $model->created_by !== null ? (int) $model->created_by : null,
            approvedBy: $model->approved_by !== null ? (int) $model->approved_by : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        $lines = [];
        foreach ($model->lines ?? [] as $lineModel) {
            $lines[] = new SalesOrderLine(
                tenantId: (int) $lineModel->tenant_id,
                productId: (int) $lineModel->product_id,
                uomId: (int) $lineModel->uom_id,
                salesOrderId: (int) $lineModel->sales_order_id,
                variantId: $lineModel->variant_id !== null ? (int) $lineModel->variant_id : null,
                description: $lineModel->description,
                orderedQty: (string) $lineModel->ordered_qty,
                shippedQty: (string) $lineModel->shipped_qty,
                reservedQty: (string) $lineModel->reserved_qty,
                unitPrice: (string) $lineModel->unit_price,
                discountPct: (string) $lineModel->discount_pct,
                taxGroupId: $lineModel->tax_group_id !== null ? (int) $lineModel->tax_group_id : null,
                lineTotal: (string) $lineModel->line_total,
                incomeAccountId: $lineModel->income_account_id !== null ? (int) $lineModel->income_account_id : null,
                batchId: $lineModel->batch_id !== null ? (int) $lineModel->batch_id : null,
                serialId: $lineModel->serial_id !== null ? (int) $lineModel->serial_id : null,
                id: (int) $lineModel->id,
            );
        }
        $order->setLines($lines);

        return $order;
    }
}
