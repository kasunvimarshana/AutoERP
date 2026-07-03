<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\Concerns\AssertsPurchaseExpectedVersion;
use Modules\Purchase\Validators\PurchaseValidationService;
use Modules\Tenant\Models\TenantModel;

final class PurchaseOrderWriteService
{
    use AssertsPurchaseExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseOrderCalculationService $calculator,
        private readonly PurchaseHeaderAdjustmentService $adjustments,
        private readonly PurchaseUomService $uoms,
        private readonly PurchaseNumberService $numbers,
        private readonly PurchaseAdjustmentCatalogueService $adjustmentCatalogue,
        private readonly PurchaseAdjustmentAllocationService $adjustmentAllocations,
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

    public function update(PurchaseOrder $order, CreatePurchaseOrderData $data, ?int $expectedVersion = null): PurchaseOrder
    {
        $this->validateOrderData($data);

        $number = $data->purchaseOrderNumber ?? (string) $order->purchase_order_number;
        $this->assertUniqueNumber($data->tenantId, $number, (int) $order->getKey());

        return DB::transaction(function () use ($order, $data, $number, $expectedVersion): PurchaseOrder {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            $this->assertExpectedVersion($order, $expectedVersion);
            $this->assertEditable($order);

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

    public function delete(PurchaseOrder $order, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($order, $expectedVersion): void {
            $order = PurchaseOrder::query()->with('lines')->lockForUpdate()->findOrFail($order->getKey());
            $this->assertExpectedVersion($order, $expectedVersion);
            $this->assertEditable($order);

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

        $this->validator->supplier($data->tenantId, $data->organizationUnitId, $data->supplierId, 'supplier_id');
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId, 'warehouse_id');

        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation(
                $data->tenantId,
                $data->organizationUnitId,
                $data->warehouseId,
                $data->warehouseLocationId,
                'warehouse_location_id',
            );
        }

        if ($data->currencyId !== null) {
            $this->validator->currency($data->tenantId, $data->organizationUnitId, $data->currencyId, 'currency_id');
            $this->assertExchangeRateAllowed($data);
        }

        $seenLines = [];
        foreach ($data->lines as $index => $line) {
            $this->validateLine($data, $line, $index);

            $uomId = $line->orderedUomId ?? $line->uomId;
            $key = implode(':', [$line->itemId, $line->itemVariantId ?? 0, $uomId]);
            if (isset($seenLines[$key])) {
                throw new InvalidArgumentException('Duplicate purchase order line for item, variant, and UOM.');
            }
            $seenLines[$key] = true;
        }

        foreach ($data->adjustments as $index => $adjustment) {
            $this->validator->assertNonNegative(
                $adjustment->amount,
                'Purchase header adjustment amount cannot be negative.',
            );
            $this->validator->assertNonNegative(
                $adjustment->rate,
                'Purchase header adjustment rate cannot be negative.',
            );
            $this->assertPercentageRate($adjustment->calculationType, $adjustment->rate);
            $this->adjustmentCatalogue->validate($adjustment, "adjustments.{$index}");
        }
    }

    private function assertExchangeRateAllowed(CreatePurchaseOrderData $data): void
    {
        $tenantCurrencyId = TenantModel::query()
            ->whereKey($data->tenantId)
            ->value('base_currency_id');

        if ($tenantCurrencyId !== null
            && (int) $tenantCurrencyId === $data->currencyId
            && $this->math->compare($data->exchangeRate, '1.000000') !== 0
        ) {
            throw ValidationException::withMessages([
                'exchange_rate' => ['Tenant base currency must use an exchange rate of 1.000000.'],
            ]);
        }
    }

    private function validateLine(CreatePurchaseOrderData $data, object $line, int $index): void
    {
        $this->validator->assertPositiveQuantity($line->orderedQuantity);
        $this->validator->assertNonNegative($line->unitPrice, 'Purchase unit price cannot be negative.');
        $this->validator->assertNonNegative($line->discountAmount, 'Purchase line discount cannot be negative.');
        $this->validator->assertNonNegative($line->taxAmount, 'Purchase line tax cannot be negative.');
        $this->validator->assertNonNegative($line->chargeAmount, 'Purchase line charge cannot be negative.');

        $item = $this->validator->item(
            $data->tenantId,
            $data->organizationUnitId,
            $line->itemId,
            "lines.{$index}.item_id",
        );
        $uomId = $line->orderedUomId ?? $line->uomId;
        if ($uomId === null) {
            throw new InvalidArgumentException('Purchase line UOM is required.');
        }

        $uomField = $line->orderedUomId !== null
            ? "lines.{$index}.ordered_uom_id"
            : "lines.{$index}.uom_id";
        $this->validator->uom($data->tenantId, $data->organizationUnitId, $uomId, $uomField);
        $this->uoms->resolveLineUom($data->tenantId, $item, $uomId, $line->orderedQuantity);

        if ($line->itemVariantId !== null) {
            $this->validator->itemVariant(
                $data->tenantId,
                $data->organizationUnitId,
                $line->itemId,
                $line->itemVariantId,
                "lines.{$index}.item_variant_id",
            );
        }

        $this->assertPercentageRate($line->discountCalculationType, $line->discountRate);
        $this->assertPercentageRate($line->taxCalculationType, $line->taxRate);
        $this->assertPercentageRate($line->chargeCalculationType, $line->chargeRate);
        if ($line->taxGroupId !== null) {
            $this->validator->taxGroup($data->tenantId, $data->organizationUnitId, $line->taxGroupId, "lines.{$index}.tax_group_id");
        }
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
        $createdLineIdsByClientKey = [];

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

            $createdLine = $order->lines()->create([
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
                'remaining_invoiceable_quantity' => $this->math->normalize($line->orderedQuantity),
                'remaining_returnable_quantity' => '0.000000',
                'unit_price' => $this->math->normalize($line->unitPrice),
                'line_subtotal' => $amounts['subtotal'],
                'discount_calculation_type' => $line->discountCalculationType,
                'discount_rate' => $this->math->normalize($line->discountRate),
                'discount_amount' => $amounts['discount'],
                'tax_calculation_type' => $line->taxCalculationType,
                'tax_rate' => $this->math->normalize($line->taxRate),
                'tax_amount' => $amounts['tax'],
                'tax_group_id' => $line->taxGroupId,
                'charge_calculation_type' => $line->chargeCalculationType,
                'charge_rate' => $this->math->normalize($line->chargeRate),
                'charge_amount' => $amounts['charge'],
                'line_total' => $lineTotals[$index],
                'status' => PurchaseOrderLineStatus::Open,
            ]);

            if ($line->clientLineKey !== null && trim($line->clientLineKey) !== '') {
                $createdLineIdsByClientKey[$line->clientLineKey] = (int) $createdLine->getKey();
            }
        }

        foreach ($data->adjustments as $index => $adjustment) {
            $adjustment = $this->withPersistedManualAllocationLines($adjustment, $createdLineIdsByClientKey, "adjustments.{$index}");
            $createdAdjustment = $this->adjustments->create(
                $data->tenantId,
                $data->organizationUnitId,
                'purchase_order',
                (int) $order->getKey(),
                $adjustment,
                $adjustmentAmounts[$index] ?? null,
                $data->createdBy,
                "adjustments.{$index}",
            );
            $this->adjustmentAllocations->recordManualPlanForOrder($createdAdjustment, $order, "adjustments.{$index}");
        }
    }

    /**
     * @param  array<string, int>  $lineIdsByClientKey
     */
    private function withPersistedManualAllocationLines(
        PurchaseHeaderAdjustmentData $adjustment,
        array $lineIdsByClientKey,
        string $fieldPrefix,
    ): PurchaseHeaderAdjustmentData {
        if ($adjustment->manualAllocations === []) {
            return $adjustment;
        }

        $allocations = [];
        foreach ($adjustment->manualAllocations as $index => $row) {
            $lineId = $row['purchase_order_line_id'] ?? null;
            if ($lineId === null) {
                $clientKey = trim((string) ($row['client_line_key'] ?? ''));
                $lineId = $lineIdsByClientKey[$clientKey] ?? null;
                if ($lineId === null) {
                    throw ValidationException::withMessages([
                        "{$fieldPrefix}.allocations.{$index}.client_line_key" => ['Manual allocation references an unknown purchase line.'],
                    ]);
                }
            }
            $allocations[] = [
                'purchase_order_line_id' => (int) $lineId,
                'amount' => (string) $row['amount'],
            ];
        }

        return new PurchaseHeaderAdjustmentData(
            name: $adjustment->name,
            adjustmentType: $adjustment->adjustmentType,
            effect: $adjustment->effect,
            amount: $adjustment->amount,
            calculationType: $adjustment->calculationType,
            calculationBase: $adjustment->calculationBase,
            rate: $adjustment->rate,
            allocationMethod: $adjustment->allocationMethod,
            isAllocatable: $adjustment->isAllocatable,
            sortOrder: $adjustment->sortOrder,
            description: $adjustment->description,
            manualAllocations: $allocations,
        );
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
