<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\DTOs\CreateSalesDeliveryData;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesHeaderAdjustment;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Services\Tax\SalesDeliveryTaxDocumentMapper;
use Modules\Sales\Validators\SalesValidationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;

final class SalesDeliveryService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly SalesHeaderAdjustmentService $adjustments,
        private readonly SalesInventoryIntegrationService $inventory,
        private readonly SalesOrderService $orders,
        private readonly SalesNumberService $numbers,
        private readonly SalesStatusService $statuses,
        private readonly TaxDocumentIntegrationService $taxDocuments,
        private readonly SalesDeliveryTaxDocumentMapper $taxDocumentMapper,
        private readonly SalesDocumentLockService $locks,
        private readonly SalesDocumentBlockerService $blockers,
    ) {}

    public function create(CreateSalesDeliveryData $data): SalesDelivery
    {
        $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation($data->tenantId, $data->organizationUnitId, $data->warehouseId, $data->warehouseLocationId);
        }
        if ($data->lines === []) {
            throw new InvalidArgumentException('Sales delivery requires at least one line.');
        }

        $order = $data->salesOrderId === null
            ? null
            : SalesOrder::query()->with(['lines', 'adjustments'])->findOrFail($data->salesOrderId);
        if ($order instanceof SalesOrder) {
            $this->validator->assertTenantOrg((int) $order->tenant_id, $order->organization_unit_id, $data->tenantId, $data->organizationUnitId);
            if ((int) $order->customer_id !== $data->customerId) {
                throw new InvalidArgumentException('Sales delivery customer must match the sales order customer.');
            }
            $status = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;
            if ($status !== 'approved') {
                throw new InvalidArgumentException('Sales delivery requires an approved open sales order.');
            }
        }

        foreach ($data->lines as $line) {
            $this->validator->assertPositive($line->deliveredQuantity);
            $this->validator->assertNonNegative($line->unitPrice);
            $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
            if ($line->salesOrderLineId !== null) {
                $orderLine = SalesOrderLine::query()->with('order')->findOrFail($line->salesOrderLineId);
                $this->validator->assertTenantOrg((int) $orderLine->tenant_id, $orderLine->organization_unit_id, $data->tenantId, $data->organizationUnitId);
                if ($order instanceof SalesOrder && (int) $orderLine->sales_order_id !== (int) $order->getKey()) {
                    throw new InvalidArgumentException('Sales delivery line must belong to the selected sales order.');
                }
                $this->validator->assertDeliveryWithinOrder($orderLine, $line->deliveredQuantity);
            }
        }

        return DB::transaction(function () use ($data, $order): SalesDelivery {
            $delivery = SalesDelivery::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'delivery_number' => $data->deliveryNumber ?? $this->numbers->next(
                    $data->tenantId,
                    $data->organizationUnitId,
                    'delivery',
                    $data->deliveryDate,
                    'SD',
                ),
                'delivery_date' => $data->deliveryDate,
                'sales_order_id' => $data->salesOrderId,
                'customer_id' => $data->customerId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'status' => SalesDeliveryStatus::Draft,
                'notes' => $data->notes,
                'delivered_by' => $data->deliveredBy,
            ]);

            $deliverySubtotal = '0.000000';
            foreach ($data->lines as $line) {
                $source = $line->salesOrderLineId === null ? null : SalesOrderLine::query()->find($line->salesOrderLineId);
                $ratio = $source instanceof SalesOrderLine && ! $this->math->isZero((string) $source->ordered_quantity)
                    ? $this->math->div($line->deliveredQuantity, (string) $source->ordered_quantity, 12)
                    : '1.000000';
                $lineTotal = $source instanceof SalesOrderLine
                    ? $this->math->mul((string) $source->line_total, $ratio)
                    : $this->math->mul($line->deliveredQuantity, $line->unitPrice);
                $deliverySubtotal = $this->math->add($deliverySubtotal, $this->math->mul($line->deliveredQuantity, $line->unitPrice));

                $delivery->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'sales_order_line_id' => $line->salesOrderLineId,
                    'item_id' => $line->itemId,
                    'item_variant_id' => $line->itemVariantId,
                    'description' => $line->description ?? $source?->description,
                    'uom_id' => $line->uomId ?? $source?->ordered_uom_id,
                    'ordered_quantity' => $line->orderedQuantity === '0.000000' ? (string) ($source?->ordered_quantity ?? '0.000000') : $line->orderedQuantity,
                    'delivered_quantity' => $this->math->normalize($line->deliveredQuantity),
                    'remaining_quantity' => $this->math->normalize($line->deliveredQuantity),
                    'unit_price' => $this->math->normalize($line->unitPrice),
                    'line_total' => $lineTotal,
                ]);
            }

            if ($order instanceof SalesOrder && ! $this->math->isZero((string) $order->subtotal)) {
                $ratio = $this->math->div($deliverySubtotal, (string) $order->subtotal, 12);
                foreach ($order->adjustments as $adjustment) {
                    if ($adjustment instanceof SalesHeaderAdjustment) {
                        $this->adjustments->cloneProportionally($adjustment, 'sales_delivery', (int) $delivery->getKey(), $ratio);
                    }
                }
            }

            return $this->load($delivery);
        });
    }

    public function post(SalesDelivery $delivery, ?int $userId = null): SalesDelivery
    {
        return DB::transaction(function () use ($delivery, $userId): SalesDelivery {
            $delivery = SalesDelivery::query()->lockForUpdate()->findOrFail($delivery->getKey());
            if ($delivery->status !== SalesDeliveryStatus::Draft) {
                throw new InvalidArgumentException('Only draft sales deliveries can be posted.');
            }
            $delivery->load('lines.salesOrderLine');
            $lineIds = $delivery->lines
                ->pluck('sales_order_line_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();
            $this->locks->salesOrderLines($lineIds);
            foreach ($delivery->lines as $line) {
                $issue = $this->inventory->issueForDelivery($delivery, $line, $userId);
                $allocation = $issue['allocation'];
                $line->inventory_movement_id = $issue['movement']?->getKey();
                $line->status = 'posted';
                $line->save();

                if ($line->salesOrderLine instanceof SalesOrderLine) {
                    if ($issue['allocated_now']) {
                        $this->orders->applyAllocated($line->salesOrderLine, (string) $line->delivered_quantity, $allocation?->getKey());
                    }
                    $this->orders->applyDelivered($line->salesOrderLine, (string) $line->delivered_quantity);
                }
            }
            $this->statuses->transition($delivery, SalesDeliveryStatus::Posted, $userId);
            $delivery->posted_by = $userId;
            $delivery->posted_at = now();
            $delivery->save();
            $this->taxDocuments->post($this->taxDocumentMapper->map($delivery->refresh()->load(['lines.salesOrderLine'])));

            return $this->load($delivery);
        });
    }

    public function reverse(SalesDelivery $delivery, ?int $userId = null): SalesDelivery
    {
        return DB::transaction(function () use ($delivery, $userId): SalesDelivery {
            $delivery = SalesDelivery::query()->lockForUpdate()->findOrFail($delivery->getKey());
            if ($delivery->status !== SalesDeliveryStatus::Posted) {
                throw new InvalidArgumentException('Only posted sales deliveries can be reversed.');
            }
            $delivery->load(['lines.inventoryMovement', 'lines.salesOrderLine']);
            $lineIds = $delivery->lines
                ->pluck('sales_order_line_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();
            $this->locks->salesOrderLines($lineIds);
            $blocker = $this->blockers->salesDeliveryReverseBlocker($delivery);
            if ($blocker !== null) {
                throw new InvalidArgumentException($blocker['reason']);
            }
            foreach ($delivery->lines as $line) {
                if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0
                    || $this->math->compare((string) $line->returned_quantity, '0.000000') > 0) {
                    throw new InvalidArgumentException('Invoiced or returned sales deliveries cannot be reversed.');
                }
                $this->inventory->reverseDelivery($line, $userId);
                if ($line->salesOrderLine instanceof SalesOrderLine) {
                    $this->orders->reverseDelivered($line->salesOrderLine, (string) $line->delivered_quantity);
                }
                $line->status = 'reversed';
                $line->save();
            }
            $this->statuses->transition($delivery, SalesDeliveryStatus::Reversed, $userId);
            $delivery->reversed_by = $userId;
            $delivery->reversed_at = now();
            $delivery->save();
            $this->taxDocuments->reverse(
                $this->taxDocumentMapper->map($delivery->refresh()->load(['lines.salesOrderLine'])),
                'sales_delivery_reversal',
                'sales_delivery_reversal',
            );

            return $this->load($delivery);
        });
    }

    private function load(SalesDelivery $delivery): SalesDelivery
    {
        return $delivery->refresh()->load([
            'salesOrder',
            'customer',
            'warehouse',
            'warehouseLocation',
            'lines.item',
            'lines.variant',
            'lines.uom',
            'adjustments',
        ]);
    }
}
