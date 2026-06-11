<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Sales\DTOs\CreateSalesOrderData;
use Modules\Sales\DTOs\SalesLineData;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesOrderLineStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesQuotation;
use Modules\Sales\Validators\SalesValidationService;

final class SalesOrderService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly SalesCalculationService $calculator,
        private readonly SalesHeaderAdjustmentService $adjustments,
        private readonly SalesNumberService $numbers,
        private readonly SalesStatusService $statuses,
    ) {}

    public function create(CreateSalesOrderData $data): SalesOrder
    {
        $this->validate($data);
        $number = $data->salesOrderNumber ?? $this->numbers->next(
            $data->tenantId,
            $data->organizationUnitId,
            'order',
            $data->salesOrderDate,
            'SO',
        );
        $this->assertUniqueNumber($data->tenantId, $number);

        return DB::transaction(function () use ($data, $number): SalesOrder {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $order = SalesOrder::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'sales_order_number' => $number,
                'sales_order_date' => $data->salesOrderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'customer_id' => $data->customerId,
                'quotation_id' => $data->quotationId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => SalesOrderStatus::Draft,
                'subtotal' => $calculation->subtotal,
                'line_discount_total' => $calculation->lineDiscountTotal,
                'line_tax_total' => $calculation->lineTaxTotal,
                'line_charge_total' => $calculation->lineChargeTotal,
                'header_increase_total' => $calculation->headerIncreaseTotal,
                'header_decrease_total' => $calculation->headerDecreaseTotal,
                'grand_total' => $calculation->grandTotal,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);
            $this->replaceLinesAndAdjustments($order, $data, $calculation->lineTotals);

            return $this->load($order);
        });
    }

    public function update(SalesOrder $order, CreateSalesOrderData $data): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::Draft) {
            throw new InvalidArgumentException('Only draft sales orders can be edited.');
        }
        $this->validate($data);
        $number = $data->salesOrderNumber ?? (string) $order->sales_order_number;
        $this->assertUniqueNumber($data->tenantId, $number, (int) $order->getKey());

        return DB::transaction(function () use ($order, $data, $number): SalesOrder {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $order->fill([
                'sales_order_number' => $number,
                'sales_order_date' => $data->salesOrderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'customer_id' => $data->customerId,
                'quotation_id' => $data->quotationId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'subtotal' => $calculation->subtotal,
                'line_discount_total' => $calculation->lineDiscountTotal,
                'line_tax_total' => $calculation->lineTaxTotal,
                'line_charge_total' => $calculation->lineChargeTotal,
                'header_increase_total' => $calculation->headerIncreaseTotal,
                'header_decrease_total' => $calculation->headerDecreaseTotal,
                'grand_total' => $calculation->grandTotal,
                'notes' => $data->notes,
            ])->save();
            $order->lines()->delete();
            $order->adjustments()->delete();
            $this->replaceLinesAndAdjustments($order, $data, $calculation->lineTotals);

            return $this->load($order);
        });
    }

    public function delete(SalesOrder $order): void
    {
        if ($order->status !== SalesOrderStatus::Draft) {
            throw new InvalidArgumentException('Only draft sales orders can be deleted.');
        }
        $order->delete();
    }

    public function submit(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->statuses->transition($order, SalesOrderStatus::PendingApproval, $userId);

        return $this->load($order);
    }

    public function approve(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->statuses->transition($order, SalesOrderStatus::Approved, $userId);
        $order->approved_by = $userId;
        $order->approved_at = now();
        $order->save();

        return $this->load($order);
    }

    public function cancel(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if ($this->math->compare((string) $line->delivered_quantity, '0.000000') > 0
                || $this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
                throw new InvalidArgumentException('Sales orders with delivered or invoiced quantities cannot be cancelled.');
            }
        }
        $this->statuses->transition($order, SalesOrderStatus::Cancelled, $userId);
        $order->cancelled_by = $userId;
        $order->cancelled_at = now();
        $order->save();

        return $this->load($order);
    }

    public function close(SalesOrder $order, ?int $userId = null): SalesOrder
    {
        $this->statuses->transition($order, SalesOrderStatus::Closed, $userId);
        $order->closed_by = $userId;
        $order->closed_at = now();
        $order->save();

        return $this->load($order);
    }

    public function applyAllocated(SalesOrderLine $line, string $quantity, ?int $allocationId): void
    {
        $line->allocated_quantity = $this->math->add((string) $line->allocated_quantity, $quantity);
        $line->remaining_allocatable_quantity = $this->math->sub((string) $line->ordered_quantity, (string) $line->allocated_quantity);
        $line->inventory_allocation_id = $allocationId;
        $line->status = $this->math->isZero((string) $line->remaining_allocatable_quantity)
            ? SalesOrderLineStatus::Allocated
            : SalesOrderLineStatus::PartiallyAllocated;
        $line->save();
        $this->refreshStatus($line->order);
    }

    public function applyDelivered(SalesOrderLine $line, string $quantity): void
    {
        $line->delivered_quantity = $this->math->add((string) $line->delivered_quantity, $quantity);
        $line->remaining_deliverable_quantity = $this->math->sub(
            $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity),
            (string) $line->delivered_quantity,
        );
        $line->remaining_invoiceable_quantity = $this->math->sub((string) $line->delivered_quantity, (string) $line->invoiced_quantity);
        $line->remaining_returnable_quantity = $this->math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity);
        $line->status = $this->math->isZero((string) $line->remaining_deliverable_quantity)
            ? SalesOrderLineStatus::Delivered
            : SalesOrderLineStatus::PartiallyDelivered;
        $line->save();
        $this->refreshStatus($line->order);
    }

    public function reverseDelivered(SalesOrderLine $line, string $quantity): void
    {
        $line->delivered_quantity = $this->math->sub((string) $line->delivered_quantity, $quantity);
        $line->allocated_quantity = $this->math->sub((string) $line->allocated_quantity, $quantity);
        $line->remaining_deliverable_quantity = $this->math->sub((string) $line->ordered_quantity, (string) $line->delivered_quantity);
        $line->remaining_allocatable_quantity = $this->math->sub((string) $line->ordered_quantity, (string) $line->allocated_quantity);
        $line->remaining_invoiceable_quantity = $this->math->sub((string) $line->delivered_quantity, (string) $line->invoiced_quantity);
        $line->remaining_returnable_quantity = $this->math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity);
        $line->status = $this->math->isZero((string) $line->delivered_quantity) ? SalesOrderLineStatus::Open : SalesOrderLineStatus::PartiallyDelivered;
        $line->save();
        $this->refreshStatus($line->order);
    }

    public function applyInvoiced(SalesOrderLine $line, string $quantity): void
    {
        $line->invoiced_quantity = $this->math->add((string) $line->invoiced_quantity, $quantity);
        $basis = $this->math->compare((string) $line->delivered_quantity, '0.000000') > 0
            ? (string) $line->delivered_quantity
            : (string) $line->ordered_quantity;
        $line->remaining_invoiceable_quantity = $this->math->sub($basis, (string) $line->invoiced_quantity);
        $line->status = $this->math->isZero((string) $line->remaining_invoiceable_quantity)
            ? SalesOrderLineStatus::Invoiced
            : SalesOrderLineStatus::PartiallyInvoiced;
        $line->save();
        $this->refreshStatus($line->order);
    }

    public function applyReturned(SalesOrderLine $line, string $quantity): void
    {
        $line->returned_quantity = $this->math->add((string) $line->returned_quantity, $quantity);
        $line->remaining_returnable_quantity = $this->math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity);
        $line->status = $this->math->isZero((string) $line->remaining_returnable_quantity)
            ? SalesOrderLineStatus::Returned
            : SalesOrderLineStatus::PartiallyReturned;
        $line->save();
        $this->refreshStatus($line->order);
    }

    private function validate(CreateSalesOrderData $data): void
    {
        $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
        if ($data->warehouseId !== null) {
            $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        }
        if ($data->warehouseLocationId !== null) {
            if ($data->warehouseId === null) {
                throw new InvalidArgumentException('Sales warehouse is required when a location is selected.');
            }
            $this->validator->warehouseLocation($data->tenantId, $data->organizationUnitId, $data->warehouseId, $data->warehouseLocationId);
        }
        if ($data->currencyId !== null) {
            $this->validator->currency($data->tenantId, $data->organizationUnitId, $data->currencyId);
        }
        if ($data->quotationId !== null) {
            $quotation = SalesQuotation::query()->findOrFail($data->quotationId);
            $this->validator->assertTenantOrg((int) $quotation->tenant_id, $quotation->organization_unit_id, $data->tenantId, $data->organizationUnitId);
        }
        if ($data->lines === []) {
            throw new InvalidArgumentException('Sales order requires at least one line.');
        }
        foreach ($data->lines as $line) {
            $this->validateLine($data, $line);
        }
    }

    private function validateLine(CreateSalesOrderData $data, SalesLineData $line): void
    {
        $this->validator->assertPositive($line->quantity);
        $this->validator->assertNonNegative($line->unitPrice);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
        if ($line->uomId === null) {
            throw new InvalidArgumentException('Sales order line UOM is required.');
        }
        $this->validator->resolveUom($data->tenantId, $data->organizationUnitId, $item, $line->uomId, $line->quantity);
        if ($line->itemVariantId !== null) {
            $this->validator->itemVariant($data->tenantId, $data->organizationUnitId, $line->itemId, $line->itemVariantId);
        }
        foreach ([
            [$line->discountCalculationType, $line->discountRate],
            [$line->taxCalculationType, $line->taxRate],
            [$line->chargeCalculationType, $line->chargeRate],
        ] as [$type, $rate]) {
            if ($type === SalesAdjustmentCalculationType::Percentage && $this->math->compare($rate, '100.000000') > 0) {
                throw new InvalidArgumentException('Sales percentage rates cannot exceed 100.');
            }
        }
    }

    private function replaceLinesAndAdjustments(SalesOrder $order, CreateSalesOrderData $data, array $lineTotals): void
    {
        foreach ($data->lines as $index => $line) {
            $item = Item::query()->findOrFail($line->itemId);
            $uom = $this->validator->resolveUom($data->tenantId, $data->organizationUnitId, $item, (int) $line->uomId, $line->quantity);
            $amounts = $this->calculator->lineAmounts($line);
            $order->lines()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'quotation_line_id' => $line->sourceLineId,
                'line_number' => $index + 1,
                'item_id' => $line->itemId,
                'item_variant_id' => $line->itemVariantId,
                'description' => $line->description,
                'ordered_uom_id' => $uom['ordered_uom_id'],
                'base_uom_id' => $uom['base_uom_id'],
                'uom_conversion_factor' => $uom['factor'],
                'ordered_quantity' => $this->math->normalize($line->quantity),
                'base_quantity' => $line->baseQuantity ?? $uom['base_quantity'],
                'remaining_allocatable_quantity' => $this->math->normalize($line->quantity),
                'remaining_deliverable_quantity' => $this->math->normalize($line->quantity),
                'unit_price' => $this->math->normalize($line->unitPrice),
                'line_subtotal' => $amounts['subtotal'],
                'discount_calculation_type' => $line->discountCalculationType,
                'discount_rate' => $this->math->normalize($line->discountRate),
                'discount_amount' => $amounts['discount'],
                'tax_calculation_type' => $line->taxCalculationType,
                'tax_rate' => $this->math->normalize($line->taxRate),
                'tax_amount' => $amounts['tax'],
                'charge_calculation_type' => $line->chargeCalculationType,
                'charge_rate' => $this->math->normalize($line->chargeRate),
                'charge_amount' => $amounts['charge'],
                'line_total' => $lineTotals[$index],
                'status' => SalesOrderLineStatus::Open,
            ]);
        }
        $amounts = $this->calculator->headerAmounts($data->lines, $data->adjustments);
        foreach ($data->adjustments as $index => $adjustment) {
            $this->adjustments->create($data->tenantId, $data->organizationUnitId, 'sales_order', (int) $order->getKey(), $adjustment, $amounts[$index]);
        }
    }

    private function refreshStatus(?SalesOrder $order): void
    {
        if (! $order instanceof SalesOrder || in_array($order->status, [SalesOrderStatus::Closed, SalesOrderStatus::Cancelled], true)) {
            return;
        }
        $order->load('lines');
        $ordered = $this->math->sum($order->lines->pluck('ordered_quantity')->all());
        $allocated = $this->math->sum($order->lines->pluck('allocated_quantity')->all());
        $delivered = $this->math->sum($order->lines->pluck('delivered_quantity')->all());
        $invoiced = $this->math->sum($order->lines->pluck('invoiced_quantity')->all());
        $returned = $this->math->sum($order->lines->pluck('returned_quantity')->all());

        $status = match (true) {
            $this->math->compare($returned, $ordered) >= 0 => SalesOrderStatus::Returned,
            $this->math->compare($returned, '0.000000') > 0 => SalesOrderStatus::PartiallyReturned,
            $this->math->compare($invoiced, $ordered) >= 0 => SalesOrderStatus::Invoiced,
            $this->math->compare($invoiced, '0.000000') > 0 => SalesOrderStatus::PartiallyInvoiced,
            $this->math->compare($delivered, $ordered) >= 0 => SalesOrderStatus::Delivered,
            $this->math->compare($delivered, '0.000000') > 0 => SalesOrderStatus::PartiallyDelivered,
            $this->math->compare($allocated, $ordered) >= 0 => SalesOrderStatus::Allocated,
            $this->math->compare($allocated, '0.000000') > 0 => SalesOrderStatus::PartiallyAllocated,
            default => $order->status,
        };
        $order->status = $status;
        $order->allocated_total = $this->amountForQuantity($order, 'allocated_quantity');
        $order->delivered_total = $this->amountForQuantity($order, 'delivered_quantity');
        $order->invoiced_total = $this->amountForQuantity($order, 'invoiced_quantity');
        $order->returned_total = $this->amountForQuantity($order, 'returned_quantity');
        $order->save();
    }

    private function amountForQuantity(SalesOrder $order, string $column): string
    {
        $total = '0.000000';
        foreach ($order->lines as $line) {
            if ($this->math->isZero((string) $line->ordered_quantity)) {
                continue;
            }
            $ratio = $this->math->div((string) $line->{$column}, (string) $line->ordered_quantity, 12);
            $total = $this->math->add($total, $this->math->mul((string) $line->line_total, $ratio));
        }

        return $total;
    }

    private function assertUniqueNumber(int $tenantId, string $number, ?int $exceptId = null): void
    {
        $query = SalesOrder::query()->where('tenant_id', $tenantId)->where('sales_order_number', $number);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Sales order number already exists for this tenant.');
        }
    }

    private function load(SalesOrder $order): SalesOrder
    {
        return $order->refresh()->load([
            'customer.creditProfile',
            'quotation',
            'warehouse',
            'warehouseLocation',
            'currency',
            'lines.item',
            'lines.variant',
            'lines.orderedUom',
            'lines.baseUom',
            'adjustments',
        ]);
    }
}
