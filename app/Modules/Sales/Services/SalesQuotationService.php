<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\DTOs\CreateSalesOrderData;
use Modules\Sales\DTOs\CreateSalesQuotationData;
use Modules\Sales\DTOs\SalesHeaderAdjustmentData;
use Modules\Sales\DTOs\SalesLineData;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesQuotationStatus;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesQuotation;
use Modules\Sales\Validators\SalesValidationService;

final class SalesQuotationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly SalesCalculationService $calculator,
        private readonly SalesHeaderAdjustmentService $adjustments,
        private readonly SalesNumberService $numbers,
        private readonly SalesStatusService $statuses,
        private readonly SalesOrderService $orders,
    ) {}

    public function create(CreateSalesQuotationData $data): SalesQuotation
    {
        $this->validate($data);
        $number = $data->quotationNumber ?? $this->numbers->next(
            $data->tenantId,
            $data->organizationUnitId,
            'quotation',
            $data->quotationDate,
            'SQ',
        );
        $this->assertUniqueNumber($data->tenantId, $number);

        return DB::transaction(function () use ($data, $number): SalesQuotation {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $quotation = SalesQuotation::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'quotation_number' => $number,
                'quotation_date' => $data->quotationDate,
                'valid_until' => $data->validUntil,
                'customer_id' => $data->customerId,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => SalesQuotationStatus::Draft,
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

            $this->replaceLinesAndAdjustments($quotation, $data, $calculation->lineTotals);

            return $this->load($quotation);
        });
    }

    public function update(SalesQuotation $quotation, CreateSalesQuotationData $data): SalesQuotation
    {
        if ($quotation->status !== SalesQuotationStatus::Draft) {
            throw new InvalidArgumentException('Only draft sales quotations can be edited.');
        }
        $this->validate($data);
        $number = $data->quotationNumber ?? (string) $quotation->quotation_number;
        $this->assertUniqueNumber($data->tenantId, $number, (int) $quotation->getKey());

        return DB::transaction(function () use ($quotation, $data, $number): SalesQuotation {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $quotation->fill([
                'quotation_number' => $number,
                'quotation_date' => $data->quotationDate,
                'valid_until' => $data->validUntil,
                'customer_id' => $data->customerId,
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
            $quotation->lines()->delete();
            $quotation->adjustments()->delete();
            $this->replaceLinesAndAdjustments($quotation, $data, $calculation->lineTotals);

            return $this->load($quotation);
        });
    }

    public function delete(SalesQuotation $quotation): void
    {
        if ($quotation->status !== SalesQuotationStatus::Draft) {
            throw new InvalidArgumentException('Only draft sales quotations can be deleted.');
        }
        $quotation->delete();
    }

    public function send(SalesQuotation $quotation, ?int $userId = null): SalesQuotation
    {
        $this->statuses->transition($quotation, SalesQuotationStatus::Sent, $userId);

        return $this->load($quotation);
    }

    public function accept(SalesQuotation $quotation, ?int $userId = null): SalesQuotation
    {
        $this->statuses->transition($quotation, SalesQuotationStatus::Accepted, $userId);
        $quotation->approved_by = $userId;
        $quotation->approved_at = now();
        $quotation->save();

        return $this->load($quotation);
    }

    public function reject(SalesQuotation $quotation, ?int $userId = null, ?string $reason = null): SalesQuotation
    {
        $this->statuses->transition($quotation, SalesQuotationStatus::Rejected, $userId, $reason);

        return $this->load($quotation);
    }

    public function convertToOrder(
        SalesQuotation $quotation,
        ?string $orderDate = null,
        ?int $warehouseId = null,
        ?int $warehouseLocationId = null,
        ?int $userId = null,
    ): SalesOrder {
        if ($quotation->status !== SalesQuotationStatus::Accepted) {
            throw new InvalidArgumentException('Only accepted sales quotations can be converted.');
        }

        return DB::transaction(function () use ($quotation, $orderDate, $warehouseId, $warehouseLocationId, $userId) {
            $quotation->load(['lines', 'adjustments']);
            $order = $this->orders->create(new CreateSalesOrderData(
                tenantId: (int) $quotation->tenant_id,
                salesOrderDate: $orderDate ?? now()->toDateString(),
                customerId: (int) $quotation->customer_id,
                organizationUnitId: $quotation->organization_unit_id,
                quotationId: (int) $quotation->getKey(),
                warehouseId: $warehouseId,
                warehouseLocationId: $warehouseLocationId,
                currencyId: $quotation->currency_id,
                exchangeRate: (string) $quotation->exchange_rate,
                notes: $quotation->notes,
                createdBy: $userId,
                lines: $quotation->lines->map(fn ($line): SalesLineData => new SalesLineData(
                    itemId: (int) $line->item_id,
                    quantity: (string) $line->quantity,
                    unitPrice: (string) $line->unit_price,
                    itemVariantId: $line->item_variant_id,
                    description: $line->description,
                    uomId: $line->uom_id,
                    sourceLineId: (int) $line->getKey(),
                    discountCalculationType: SalesAdjustmentCalculationType::from($line->discount_calculation_type ?: 'fixed'),
                    discountRate: (string) $line->discount_rate,
                    discountAmount: (string) $line->discount_amount,
                    taxCalculationType: SalesAdjustmentCalculationType::from($line->tax_calculation_type ?: 'fixed'),
                    taxRate: (string) $line->tax_rate,
                    taxAmount: (string) $line->tax_amount,
                    chargeCalculationType: SalesAdjustmentCalculationType::from($line->charge_calculation_type ?: 'fixed'),
                    chargeRate: (string) $line->charge_rate,
                    chargeAmount: (string) $line->charge_amount,
                ))->all(),
                adjustments: $quotation->adjustments->map(fn ($adjustment): SalesHeaderAdjustmentData => new SalesHeaderAdjustmentData(
                    name: (string) $adjustment->name,
                    adjustmentType: $adjustment->adjustment_type,
                    effect: $adjustment->effect,
                    amount: (string) $adjustment->amount,
                    calculationType: $adjustment->calculation_type,
                    calculationBase: $adjustment->calculation_base,
                    rate: (string) $adjustment->rate,
                    allocationMethod: $adjustment->allocation_method,
                    isAllocatable: (bool) $adjustment->is_allocatable,
                    sortOrder: (int) $adjustment->sort_order,
                    description: $adjustment->description,
                ))->all(),
            ));
            $this->statuses->transition($quotation, SalesQuotationStatus::Converted, $userId);

            return $order;
        });
    }

    private function validate(CreateSalesQuotationData $data): void
    {
        $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
        if ($data->currencyId !== null) {
            $this->validator->currency($data->tenantId, $data->organizationUnitId, $data->currencyId);
        }
        if ($data->lines === []) {
            throw new InvalidArgumentException('Sales quotation requires at least one line.');
        }
        foreach ($data->lines as $line) {
            $this->validateLine($data->tenantId, $data->organizationUnitId, $line);
        }
    }

    private function validateLine(int $tenantId, ?int $organizationUnitId, SalesLineData $line): void
    {
        $this->validator->assertPositive($line->quantity);
        $this->validator->assertNonNegative($line->unitPrice);
        $item = $this->validator->item($tenantId, $organizationUnitId, $line->itemId);
        if ($line->uomId === null) {
            throw new InvalidArgumentException('Sales line UOM is required.');
        }
        $this->validator->resolveUom($tenantId, $organizationUnitId, $item, $line->uomId, $line->quantity);
        if ($line->itemVariantId !== null) {
            $this->validator->itemVariant($tenantId, $organizationUnitId, $line->itemId, $line->itemVariantId);
        }
    }

    private function replaceLinesAndAdjustments(SalesQuotation $quotation, CreateSalesQuotationData $data, array $lineTotals): void
    {
        foreach ($data->lines as $index => $line) {
            $amounts = $this->calculator->lineAmounts($line);
            $quotation->lines()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'line_number' => $index + 1,
                'item_id' => $line->itemId,
                'item_variant_id' => $line->itemVariantId,
                'description' => $line->description,
                'uom_id' => $line->uomId,
                'quantity' => $this->math->normalize($line->quantity),
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
            ]);
        }

        $amounts = $this->calculator->headerAmounts($data->lines, $data->adjustments);
        foreach ($data->adjustments as $index => $adjustment) {
            $this->adjustments->create($data->tenantId, $data->organizationUnitId, 'sales_quotation', (int) $quotation->getKey(), $adjustment, $amounts[$index]);
        }
    }

    private function assertUniqueNumber(int $tenantId, string $number, ?int $exceptId = null): void
    {
        $query = SalesQuotation::query()->where('tenant_id', $tenantId)->where('quotation_number', $number);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Sales quotation number already exists for this tenant.');
        }
    }

    private function load(SalesQuotation $quotation): SalesQuotation
    {
        return $quotation->refresh()->load(['customer.creditProfile', 'currency', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments']);
    }
}
