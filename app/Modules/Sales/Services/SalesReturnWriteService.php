<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\DTOs\CreateSalesReturnData;
use Modules\Sales\DTOs\ResolvedSalesReturnSource;
use Modules\Sales\DTOs\SalesReturnLineData;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Enums\SalesReturnType;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Validators\SalesValidationService;

final class SalesReturnWriteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly SalesNumberService $numbers,
        private readonly SalesReturnSourceService $sources,
        private readonly SalesReturnAdjustmentService $adjustments,
    ) {}

    public function create(CreateSalesReturnData $data): SalesReturn
    {
        $resolvedSources = $this->validate($data);

        return DB::transaction(function () use ($data, $resolvedSources): SalesReturn {
            $return = SalesReturn::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'return_number' => $data->returnNumber ?? $this->numbers->next(
                    $data->tenantId,
                    $data->organizationUnitId,
                    'return',
                    $data->returnDate,
                    'SRET',
                ),
                'return_date' => $data->returnDate,
                'customer_id' => $data->customerId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'return_type' => $data->returnType,
                'status' => SalesReturnStatus::Draft,
                'reason' => $data->reason,
                'replacement_sales_order_id' => $data->replacementSalesOrderId,
                'affects_inventory' => $data->returnType->affectsInventory(),
                'affects_customer_balance' => $data->returnType->affectsCustomerBalance(),
                'approval_required' => $data->approvalRequired
                    || $this->requiresApproval($data->returnType),
                'cost_basis' => $data->costBasis,
                'audit_metadata' => $data->auditMetadata,
                'created_by' => $data->createdBy,
            ]);

            $subtotal = '0.000000';
            foreach ($data->lines as $index => $lineData) {
                $lineTotal = $this->createLine(
                    $return,
                    $data,
                    $lineData,
                    $resolvedSources[$index],
                );
                $subtotal = $this->math->add($subtotal, $lineTotal);
            }

            $return->subtotal = $subtotal;
            $return->adjustment_return_total = $this->adjustments->allocate($return);
            $return->grand_total = (bool) $return->affects_customer_balance
                ? $this->math->add($subtotal, (string) $return->adjustment_return_total)
                : '0.000000';
            $return->save();

            return $return;
        });
    }

    private function createLine(
        SalesReturn $return,
        CreateSalesReturnData $data,
        SalesReturnLineData $line,
        ResolvedSalesReturnSource $source,
    ): string {
        $unitPrice = $line->unitPrice
            ?? $source->unitPrice
            ?? $line->costBasis
            ?? $data->costBasis
            ?? '0.000000';
        $discount = $this->proportional(
            $source->discountAmount,
            $line->returnedQuantity,
            $source->sourceQuantity,
        );
        $tax = $this->proportional(
            $source->taxAmount,
            $line->returnedQuantity,
            $source->sourceQuantity,
        );
        $charge = $this->proportional(
            $source->chargeAmount,
            $line->returnedQuantity,
            $source->sourceQuantity,
        );
        $lineTotal = $this->math->add(
            $this->math->add(
                $this->math->sub(
                    $this->math->mul($line->returnedQuantity, $unitPrice),
                    $discount,
                ),
                $tax,
            ),
            $charge,
        );

        $return->lines()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'item_id' => $line->itemId ?? $source->itemId,
            'item_variant_id' => $line->itemVariantId ?? $source->itemVariantId,
            'uom_id' => $line->uomId ?? $source->uomId,
            'source_line_type' => $line->sourceLineType,
            'source_line_id' => $line->sourceLineId,
            'returned_quantity' => $this->math->normalize($line->returnedQuantity),
            'source_quantity' => $source->sourceQuantity,
            'previously_returned_quantity' => $source->previouslyReturnedQuantity,
            'remaining_quantity' => $this->math->sub(
                $source->sourceQuantity,
                $this->math->add(
                    $source->previouslyReturnedQuantity,
                    $line->returnedQuantity,
                ),
            ),
            'unit_price' => $this->math->normalize($unitPrice),
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'charge_amount' => $charge,
            'line_total' => $lineTotal,
            'condition_status' => $line->conditionStatus,
            'reason' => $line->reason,
        ]);

        return $lineTotal;
    }

    /**
     * @return list<ResolvedSalesReturnSource>
     */
    private function validate(CreateSalesReturnData $data): array
    {
        $this->validator->customer(
            $data->tenantId,
            $data->organizationUnitId,
            $data->customerId,
        );
        $this->validateWarehouse($data);
        $this->validateReplacement($data);
        $this->validateUnreferencedReturn($data);

        if ($data->lines === []) {
            throw new InvalidArgumentException('Sales return requires at least one line.');
        }

        $sources = [];
        foreach ($data->lines as $line) {
            $sources[] = $this->validateLine($data, $line);
        }

        return $sources;
    }

    private function validateWarehouse(CreateSalesReturnData $data): void
    {
        if ($data->returnType->affectsInventory()) {
            if ($data->warehouseId === null) {
                throw new InvalidArgumentException(
                    'Inventory-affecting sales returns require a warehouse.',
                );
            }
            $this->validator->warehouse(
                $data->tenantId,
                $data->organizationUnitId,
                $data->warehouseId,
            );
        }
        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation(
                $data->tenantId,
                $data->organizationUnitId,
                (int) $data->warehouseId,
                $data->warehouseLocationId,
            );
        }
    }

    private function validateReplacement(CreateSalesReturnData $data): void
    {
        if (! in_array($data->returnType, [
            SalesReturnType::WarrantyReplacement,
            SalesReturnType::ExchangeReturn,
        ], true)) {
            return;
        }
        if ($data->replacementSalesOrderId === null) {
            throw new InvalidArgumentException(
                'Warranty and exchange returns require a replacement sales order.',
            );
        }

        $replacement = SalesOrder::query()->findOrFail($data->replacementSalesOrderId);
        $this->validator->assertTenantOrg(
            (int) $replacement->tenant_id,
            $replacement->organization_unit_id,
            $data->tenantId,
            $data->organizationUnitId,
        );
        if ((int) $replacement->customer_id !== $data->customerId) {
            throw new InvalidArgumentException(
                'Replacement sales order must belong to the return customer.',
            );
        }
    }

    private function validateUnreferencedReturn(CreateSalesReturnData $data): void
    {
        if (! in_array($data->returnType, [
            SalesReturnType::ManualCustomerReturn,
            SalesReturnType::OpeningImportedReturn,
        ], true) || $this->hasReferencedLine($data)) {
            return;
        }

        if (trim((string) $data->reason) === ''
            || ! ($data->approvalRequired || $this->requiresApproval($data->returnType))) {
            throw new InvalidArgumentException(
                'Unreferenced customer return requires approval and reason.',
            );
        }
        if ($data->costBasis === null) {
            throw new InvalidArgumentException(
                'Unreferenced customer return requires explicit cost basis.',
            );
        }
    }

    private function validateLine(
        CreateSalesReturnData $data,
        SalesReturnLineData $line,
    ): ResolvedSalesReturnSource {
        $this->validator->assertPositive($line->returnedQuantity);
        if (in_array($line->conditionStatus, ['damaged', 'quarantine', 'scrap'], true)
            && $data->warehouseLocationId === null) {
            throw new InvalidArgumentException(
                'Damaged, quarantine, or scrap returns require an explicit warehouse location.',
            );
        }

        $source = $this->sources->resolve($line);
        if ($line->sourceLineType !== null && $line->sourceLineId !== null) {
            $this->validator->assertTenantOrg(
                (int) $source->tenantId,
                $source->organizationUnitId,
                $data->tenantId,
                $data->organizationUnitId,
            );
            if ($source->customerId !== null && $source->customerId !== $data->customerId) {
                throw new InvalidArgumentException(
                    'Sales return source belongs to a different customer.',
                );
            }
            $remaining = $this->math->sub(
                $source->sourceQuantity,
                $source->previouslyReturnedQuantity,
            );
            if ($this->math->compare($line->returnedQuantity, $remaining) > 0) {
                throw new InvalidArgumentException(
                    'Returned quantity cannot exceed source remaining quantity.',
                );
            }
        } else {
            if ($line->itemId === null || $line->uomId === null) {
                throw new InvalidArgumentException(
                    'Unreferenced return lines require item and UOM.',
                );
            }
            $this->validator->item(
                $data->tenantId,
                $data->organizationUnitId,
                $line->itemId,
            );
        }

        return $source;
    }

    private function proportional(
        string $amount,
        string $quantity,
        string $sourceQuantity,
    ): string {
        return $this->math->isZero($amount) || $this->math->isZero($sourceQuantity)
            ? '0.000000'
            : $this->math->mul(
                $amount,
                $this->math->div($quantity, $sourceQuantity, 12),
            );
    }

    private function requiresApproval(SalesReturnType $type): bool
    {
        return in_array($type, [
            SalesReturnType::ManualCustomerReturn,
            SalesReturnType::InventoryAdjustmentOnly,
            SalesReturnType::WarrantyReplacement,
            SalesReturnType::ExchangeReturn,
            SalesReturnType::OpeningImportedReturn,
        ], true);
    }

    private function hasReferencedLine(CreateSalesReturnData $data): bool
    {
        foreach ($data->lines as $line) {
            if ($line->sourceLineType !== null && $line->sourceLineId !== null) {
                return true;
            }
        }

        return false;
    }
}
