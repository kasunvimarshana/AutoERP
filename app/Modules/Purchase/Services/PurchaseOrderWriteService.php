<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Validators\PurchaseValidationService;

final class PurchaseOrderWriteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseOrderCalculationService $calculator,
        private readonly PurchaseHeaderAdjustmentService $adjustments,
        private readonly PurchaseUomService $uoms,
        private readonly PurchaseNumberService $numbers,
    ) {}

    public function create(CreatePurchaseOrderData $data): PurchaseOrder
    {
        $this->validateOrderData($data);

        $number = $data->purchaseOrderNumber
            ?? $this->numbers->next($data->tenantId, 'PO', 'purchase_orders', 'purchase_order_number');
        $this->assertUniqueNumber($data->tenantId, $number);

        return DB::transaction(function () use ($data, $number): PurchaseOrder {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $order = PurchaseOrder::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'supplier_type' => $data->supplierType ?? 'supplier',
                'supplier_id' => $data->supplierId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'purchase_order_number' => $number,
                'purchase_order_date' => $data->purchaseOrderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => PurchaseOrderStatus::Draft,
                'subtotal' => $calculation->subtotal,
                'discount_total' => $calculation->discountTotal,
                'tax_total' => $calculation->taxTotal,
                'charge_total' => $calculation->chargeTotal,
                'adjustment_total' => $calculation->adjustmentTotal,
                'header_increase_total' => $calculation->headerIncreaseTotal,
                'header_decrease_total' => $calculation->headerDecreaseTotal,
                'grand_total' => $calculation->grandTotal,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $this->replaceLinesAndAdjustments($order, $data, $calculation->lineTotals);

            return $order;
        });
    }

    public function update(PurchaseOrder $order, CreatePurchaseOrderData $data): PurchaseOrder
    {
        $this->assertEditable($order);
        $this->validateOrderData($data);

        $number = $data->purchaseOrderNumber ?? (string) $order->purchase_order_number;
        $this->assertUniqueNumber($data->tenantId, $number, (int) $order->getKey());

        return DB::transaction(function () use ($order, $data, $number): PurchaseOrder {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $order->fill([
                'supplier_type' => $data->supplierType ?? 'supplier',
                'supplier_id' => $data->supplierId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'purchase_order_number' => $number,
                'purchase_order_date' => $data->purchaseOrderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'subtotal' => $calculation->subtotal,
                'discount_total' => $calculation->discountTotal,
                'tax_total' => $calculation->taxTotal,
                'charge_total' => $calculation->chargeTotal,
                'adjustment_total' => $calculation->adjustmentTotal,
                'header_increase_total' => $calculation->headerIncreaseTotal,
                'header_decrease_total' => $calculation->headerDecreaseTotal,
                'grand_total' => $calculation->grandTotal,
                'notes' => $data->notes,
            ]);
            $order->save();

            $order->lines()->delete();
            $order->adjustments()->delete();
            $this->replaceLinesAndAdjustments($order, $data, $calculation->lineTotals);

            return $order;
        });
    }

    public function delete(PurchaseOrder $order): void
    {
        $this->assertEditable($order);

        DB::transaction(function () use ($order): void {
            $order->lines()->delete();
            $order->adjustments()->delete();
            $order->delete();
        });
    }

    private function validateOrderData(CreatePurchaseOrderData $data): void
    {
        if ($data->supplierId === null) {
            throw new InvalidArgumentException('Purchase supplier is required.');
        }

        if ($data->warehouseId === null) {
            throw new InvalidArgumentException('Purchase warehouse is required.');
        }

        if ($data->lines === []) {
            throw new InvalidArgumentException('Purchase order requires at least one line.');
        }

        $this->validator->supplier($data->tenantId, $data->organizationUnitId, $data->supplierId);
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);

        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation(
                $data->tenantId,
                $data->organizationUnitId,
                $data->warehouseId,
                $data->warehouseLocationId,
            );
        }

        if ($data->currencyId !== null) {
            $this->validator->currency($data->tenantId, $data->organizationUnitId, $data->currencyId);
        }

        $seenLines = [];
        foreach ($data->lines as $line) {
            $this->validateLine($data, $line);

            $uomId = $line->orderedUomId ?? $line->uomId;
            $key = implode(':', [$line->itemId, $line->itemVariantId ?? 0, $uomId]);
            if (isset($seenLines[$key])) {
                throw new InvalidArgumentException('Duplicate purchase order line for item, variant, and UOM.');
            }
            $seenLines[$key] = true;
        }

        foreach ($data->adjustments as $adjustment) {
            $this->validator->assertNonNegative(
                $adjustment->amount,
                'Purchase header adjustment amount cannot be negative.',
            );
            $this->validator->assertNonNegative(
                $adjustment->rate,
                'Purchase header adjustment rate cannot be negative.',
            );
            $this->assertPercentageRate($adjustment->calculationType, $adjustment->rate);
        }
    }

    private function validateLine(CreatePurchaseOrderData $data, object $line): void
    {
        $this->validator->assertPositiveQuantity($line->orderedQuantity);
        $this->validator->assertNonNegative($line->unitPrice, 'Purchase unit price cannot be negative.');
        $this->validator->assertNonNegative($line->discountAmount, 'Purchase line discount cannot be negative.');
        $this->validator->assertNonNegative($line->taxAmount, 'Purchase line tax cannot be negative.');
        $this->validator->assertNonNegative($line->chargeAmount, 'Purchase line charge cannot be negative.');

        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
        $uomId = $line->orderedUomId ?? $line->uomId;
        if ($uomId === null) {
            throw new InvalidArgumentException('Purchase line UOM is required.');
        }

        $this->validator->uom($data->tenantId, $data->organizationUnitId, $uomId);
        $this->uoms->resolveLineUom($data->tenantId, $item, $uomId, $line->orderedQuantity);

        if ($line->itemVariantId !== null) {
            $this->validator->itemVariant(
                $data->tenantId,
                $data->organizationUnitId,
                $line->itemId,
                $line->itemVariantId,
            );
        }

        $this->assertPercentageRate($line->discountCalculationType, $line->discountRate);
        $this->assertPercentageRate($line->taxCalculationType, $line->taxRate);
        $this->assertPercentageRate($line->chargeCalculationType, $line->chargeRate);
    }

    private function assertPercentageRate(
        PurchaseAdjustmentCalculationType $calculationType,
        string $rate,
    ): void {
        if ($calculationType === PurchaseAdjustmentCalculationType::Percentage
            && $this->math->compare($rate, '100.000000') > 0) {
            throw new InvalidArgumentException('Purchase percentage rates cannot exceed 100.');
        }
    }

    /**
     * @param  list<string>  $lineTotals
     */
    private function replaceLinesAndAdjustments(
        PurchaseOrder $order,
        CreatePurchaseOrderData $data,
        array $lineTotals,
    ): void {
        $adjustmentAmounts = $this->calculator->headerAdjustmentAmounts($data->lines, $data->adjustments);

        foreach ($data->lines as $index => $line) {
            $item = Item::query()->findOrFail($line->itemId);
            $uomId = $line->orderedUomId ?? $line->uomId;
            $uom = $this->uoms->resolveLineUom(
                $data->tenantId,
                $item,
                (int) $uomId,
                $line->orderedQuantity,
            );
            $amounts = $this->calculator->lineAmounts($line);

            $order->lines()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'line_number' => $index + 1,
                'item_id' => $line->itemId,
                'item_variant_id' => $line->itemVariantId,
                'description' => $line->description,
                'uom_id' => $uom['ordered_uom_id'],
                'ordered_uom_id' => $uom['ordered_uom_id'],
                'base_uom_id' => $uom['base_uom_id'],
                'uom_conversion_factor' => $uom['conversion_factor'],
                'ordered_quantity' => $this->math->normalize($line->orderedQuantity),
                'base_quantity' => $line->baseQuantity ?? $uom['base_quantity'],
                'remaining_quantity' => $this->math->normalize($line->orderedQuantity),
                'remaining_receivable_quantity' => $this->math->normalize($line->orderedQuantity),
                'remaining_invoiceable_quantity' => '0.000000',
                'remaining_returnable_quantity' => '0.000000',
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
                'status' => PurchaseOrderLineStatus::Open,
            ]);
        }

        foreach ($data->adjustments as $index => $adjustment) {
            $this->adjustments->create(
                $data->tenantId,
                $data->organizationUnitId,
                'purchase_order',
                (int) $order->getKey(),
                $adjustment,
                $adjustmentAmounts[$index] ?? null,
            );
        }
    }

    private function assertEditable(PurchaseOrder $order): void
    {
        if ($order->status !== PurchaseOrderStatus::Draft) {
            throw new InvalidArgumentException('Only draft purchase orders can be edited.');
        }
    }

    private function assertUniqueNumber(int $tenantId, string $number, ?int $exceptId = null): void
    {
        $query = PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('purchase_order_number', $number);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Purchase order number already exists for this tenant.');
        }
    }
}
