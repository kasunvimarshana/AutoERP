<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\InvoiceLine;
use Modules\Sales\DTOs\CreateSalesDeliveryData;
use Modules\Sales\DTOs\CreateSalesReturnData;
use Modules\Sales\DTOs\SalesCreditNoteData;
use Modules\Sales\DTOs\SalesDeliveryLineData;
use Modules\Sales\DTOs\SalesPostingResult;
use Modules\Sales\DTOs\SalesReturnLineData;
use Modules\Sales\Enums\SalesCreditNoteStatus;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Enums\SalesReturnType;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesHeaderAdjustment;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnAdjustmentAllocation;
use Modules\Sales\Models\SalesReturnLine;
use Modules\Sales\Validators\SalesValidationService;
use Modules\Tax\Services\TaxReturnAllocationService;

final class SalesReturnService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly SalesInventoryIntegrationService $inventory,
        private readonly SalesCreditNoteService $creditNotes,
        private readonly SalesNumberService $numbers,
        private readonly SalesStatusService $statuses,
        private readonly SalesOrderService $orders,
        private readonly SalesDeliveryService $deliveries,
        private readonly TaxReturnAllocationService $taxReturns,
    ) {}

    public function create(CreateSalesReturnData $data): SalesReturn
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): SalesReturn {
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
                'approval_required' => $data->approvalRequired || $this->requiresApproval($data->returnType),
                'cost_basis' => $data->costBasis,
                'audit_metadata' => $data->auditMetadata,
                'created_by' => $data->createdBy,
            ]);

            $subtotal = '0.000000';
            foreach ($data->lines as $lineData) {
                $source = $this->sourceLine($lineData);
                $itemId = $lineData->itemId ?? $source['item_id'];
                $variantId = $lineData->itemVariantId ?? $source['item_variant_id'];
                $uomId = $lineData->uomId ?? $source['uom_id'];
                $unitPrice = $lineData->unitPrice ?? $source['unit_price'] ?? $lineData->costBasis ?? $data->costBasis ?? '0.000000';
                $discount = $this->proportional($source['discount_amount'], $lineData->returnedQuantity, $source['source_quantity']);
                $tax = $this->proportional($source['tax_amount'], $lineData->returnedQuantity, $source['source_quantity']);
                $charge = $this->proportional($source['charge_amount'], $lineData->returnedQuantity, $source['source_quantity']);
                $lineTotal = $this->math->add(
                    $this->math->add(
                        $this->math->sub($this->math->mul($lineData->returnedQuantity, $unitPrice), $discount),
                        $tax,
                    ),
                    $charge,
                );
                $subtotal = $this->math->add($subtotal, $lineTotal);

                $return->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'item_id' => $itemId,
                    'item_variant_id' => $variantId,
                    'uom_id' => $uomId,
                    'source_line_type' => $lineData->sourceLineType,
                    'source_line_id' => $lineData->sourceLineId,
                    'returned_quantity' => $this->math->normalize($lineData->returnedQuantity),
                    'source_quantity' => $source['source_quantity'],
                    'previously_returned_quantity' => $source['previously_returned_quantity'],
                    'remaining_quantity' => $this->math->sub(
                        $source['source_quantity'],
                        $this->math->add($source['previously_returned_quantity'], $lineData->returnedQuantity),
                    ),
                    'unit_price' => $this->math->normalize($unitPrice),
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'charge_amount' => $charge,
                    'line_total' => $lineTotal,
                    'condition_status' => $lineData->conditionStatus,
                    'reason' => $lineData->reason,
                ]);
            }

            $return->subtotal = $subtotal;
            $return->adjustment_return_total = $this->allocateAdjustments($return);
            $return->grand_total = (bool) $return->affects_customer_balance
                ? $this->math->add($subtotal, (string) $return->adjustment_return_total)
                : '0.000000';
            $return->save();

            return $this->load($return);
        });
    }

    public function approve(SalesReturn $return, ?int $userId = null): SalesReturn
    {
        $this->statuses->transition($return, SalesReturnStatus::Approved, $userId);
        $return->approved_by = $userId;
        $return->approved_at = now();
        $return->save();

        return $this->load($return);
    }

    public function post(SalesReturn $return, ?int $userId = null): SalesPostingResult
    {
        if ($return->status === SalesReturnStatus::Posted) {
            throw new InvalidArgumentException('Posted sales returns are immutable.');
        }
        if ((bool) $return->approval_required && $return->status !== SalesReturnStatus::Approved) {
            throw new InvalidArgumentException('Sales return must be approved before posting.');
        }

        return DB::transaction(function () use ($return, $userId): SalesPostingResult {
            $return->load('lines');
            $movementIds = [];
            foreach ($return->lines as $line) {
                $movement = $this->inventory->returnIn($return, $line, $userId);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                    $movementIds[] = (int) $movement->getKey();
                    $line->save();
                }
                $this->applyToSource($line);
            }

            $replacementDelivery = $this->dispatchReplacement($return, $userId);
            if ($replacementDelivery !== null) {
                foreach ($replacementDelivery->lines as $line) {
                    if ($line->inventory_movement_id !== null) {
                        $movementIds[] = (int) $line->inventory_movement_id;
                    }
                }
            }

            $creditNote = null;
            if ((bool) $return->affects_customer_balance && $this->math->compare((string) $return->grand_total, '0.000000') > 0) {
                $creditNote = $this->creditNotes->create(new SalesCreditNoteData(
                    tenantId: (int) $return->tenant_id,
                    creditNoteDate: $return->return_date->toDateString(),
                    customerId: (int) $return->customer_id,
                    amount: (string) $return->grand_total,
                    organizationUnitId: $return->organization_unit_id,
                    salesReturnId: (int) $return->getKey(),
                    reason: $return->reason ?: 'Sales return '.$return->return_number,
                ));
                $creditNote->status = SalesCreditNoteStatus::Posted;
                $creditNote->save();
            }

            $this->statuses->transition($return, SalesReturnStatus::Posted, $userId);
            $return->credit_note_id = $creditNote?->getKey();
            $return->posted_by = $userId;
            $return->posted_at = now();
            $return->save();
            $this->taxReturns->reverseSalesReturn($return->refresh()->load('lines'), $creditNote === null ? null : (int) $creditNote->getKey());

            return new SalesPostingResult(
                (int) $return->getKey(),
                (string) $return->return_number,
                SalesReturnStatus::Posted->value,
                $movementIds,
                creditNoteId: $creditNote?->getKey(),
            );
        });
    }

    public function cancel(SalesReturn $return, ?int $userId = null): SalesReturn
    {
        if ($return->status === SalesReturnStatus::Posted) {
            throw new InvalidArgumentException('Posted sales returns cannot be cancelled.');
        }
        $this->statuses->transition($return, SalesReturnStatus::Cancelled, $userId);

        return $this->load($return);
    }

    private function validate(CreateSalesReturnData $data): void
    {
        $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);
        if ($data->returnType->affectsInventory()) {
            if ($data->warehouseId === null) {
                throw new InvalidArgumentException('Inventory-affecting sales returns require a warehouse.');
            }
            $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        }
        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation($data->tenantId, $data->organizationUnitId, (int) $data->warehouseId, $data->warehouseLocationId);
        }
        if ($data->lines === []) {
            throw new InvalidArgumentException('Sales return requires at least one line.');
        }
        if (in_array($data->returnType, [SalesReturnType::WarrantyReplacement, SalesReturnType::ExchangeReturn], true)) {
            if ($data->replacementSalesOrderId === null) {
                throw new InvalidArgumentException('Warranty and exchange returns require a replacement sales order.');
            }
            $replacement = SalesOrder::query()->findOrFail($data->replacementSalesOrderId);
            $this->validator->assertTenantOrg((int) $replacement->tenant_id, $replacement->organization_unit_id, $data->tenantId, $data->organizationUnitId);
            if ((int) $replacement->customer_id !== $data->customerId) {
                throw new InvalidArgumentException('Replacement sales order must belong to the return customer.');
            }
        }
        if (in_array($data->returnType, [SalesReturnType::ManualCustomerReturn, SalesReturnType::OpeningImportedReturn], true)
            && ! $this->hasReferencedLine($data)) {
            if (trim((string) $data->reason) === '' || ! ($data->approvalRequired || $this->requiresApproval($data->returnType))) {
                throw new InvalidArgumentException('Unreferenced customer return requires approval and reason.');
            }
            if ($data->costBasis === null) {
                throw new InvalidArgumentException('Unreferenced customer return requires explicit cost basis.');
            }
        }

        foreach ($data->lines as $line) {
            $this->validator->assertPositive($line->returnedQuantity);
            if (in_array($line->conditionStatus, ['damaged', 'quarantine', 'scrap'], true) && $data->warehouseLocationId === null) {
                throw new InvalidArgumentException('Damaged, quarantine, or scrap returns require an explicit warehouse location.');
            }
            $source = $this->sourceLine($line);
            if ($line->sourceLineType !== null && $line->sourceLineId !== null) {
                $this->validator->assertTenantOrg($source['tenant_id'], $source['organization_unit_id'], $data->tenantId, $data->organizationUnitId);
                if ($source['customer_id'] !== null && $source['customer_id'] !== $data->customerId) {
                    throw new InvalidArgumentException('Sales return source belongs to a different customer.');
                }
                $remaining = $this->math->sub($source['source_quantity'], $source['previously_returned_quantity']);
                if ($this->math->compare($line->returnedQuantity, $remaining) > 0) {
                    throw new InvalidArgumentException('Returned quantity cannot exceed source remaining quantity.');
                }
            } else {
                if ($line->itemId === null || $line->uomId === null) {
                    throw new InvalidArgumentException('Unreferenced return lines require item and UOM.');
                }
                $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
            }
        }
    }

    /**
     * @return array{
     *   tenant_id: ?int, organization_unit_id: ?int, customer_id: ?int, item_id: ?int,
     *   item_variant_id: ?int, uom_id: ?int, source_quantity: string,
     *   previously_returned_quantity: string, unit_price: ?string,
     *   discount_amount: string, tax_amount: string, charge_amount: string
     * }
     */
    private function sourceLine(SalesReturnLineData $line): array
    {
        $empty = [
            'tenant_id' => null,
            'organization_unit_id' => null,
            'customer_id' => null,
            'item_id' => $line->itemId,
            'item_variant_id' => $line->itemVariantId,
            'uom_id' => $line->uomId,
            'source_quantity' => $line->returnedQuantity,
            'previously_returned_quantity' => '0.000000',
            'unit_price' => $line->unitPrice ?? $line->costBasis,
            'discount_amount' => '0.000000',
            'tax_amount' => '0.000000',
            'charge_amount' => '0.000000',
        ];
        if ($line->sourceLineType === null || $line->sourceLineId === null) {
            return $empty;
        }

        if ($line->sourceLineType === 'sales_delivery_line') {
            $source = SalesDeliveryLine::query()->with(['delivery', 'salesOrderLine'])->findOrFail($line->sourceLineId);

            return [
                'tenant_id' => (int) $source->tenant_id,
                'organization_unit_id' => $source->organization_unit_id,
                'customer_id' => (int) $source->delivery->customer_id,
                'item_id' => (int) $source->item_id,
                'item_variant_id' => $source->item_variant_id,
                'uom_id' => $source->uom_id,
                'source_quantity' => (string) $source->delivered_quantity,
                'previously_returned_quantity' => (string) $source->returned_quantity,
                'unit_price' => (string) $source->unit_price,
                'discount_amount' => (string) ($source->salesOrderLine?->discount_amount ?? '0.000000'),
                'tax_amount' => (string) ($source->salesOrderLine?->tax_amount ?? '0.000000'),
                'charge_amount' => (string) ($source->salesOrderLine?->charge_amount ?? '0.000000'),
            ];
        }
        if ($line->sourceLineType === 'sales_order_line') {
            $source = SalesOrderLine::query()->with('order')->findOrFail($line->sourceLineId);
            $basis = $this->math->compare((string) $source->delivered_quantity, '0.000000') > 0
                ? (string) $source->delivered_quantity
                : (string) $source->ordered_quantity;

            return [
                'tenant_id' => (int) $source->tenant_id,
                'organization_unit_id' => $source->organization_unit_id,
                'customer_id' => (int) $source->order->customer_id,
                'item_id' => (int) $source->item_id,
                'item_variant_id' => $source->item_variant_id,
                'uom_id' => $source->ordered_uom_id,
                'source_quantity' => $basis,
                'previously_returned_quantity' => (string) $source->returned_quantity,
                'unit_price' => (string) $source->unit_price,
                'discount_amount' => (string) $source->discount_amount,
                'tax_amount' => (string) $source->tax_amount,
                'charge_amount' => (string) $source->charge_amount,
            ];
        }
        if ($line->sourceLineType === 'invoice_line') {
            $source = InvoiceLine::query()->with('invoice')->findOrFail($line->sourceLineId);
            $previous = (string) SalesReturnLine::query()
                ->where('source_line_type', 'invoice_line')
                ->where('source_line_id', $source->getKey())
                ->whereHas('salesReturn', fn ($query) => $query->where('status', SalesReturnStatus::Posted))
                ->sum('returned_quantity');

            return [
                'tenant_id' => (int) $source->tenant_id,
                'organization_unit_id' => $source->organization_unit_id,
                'customer_id' => $source->invoice->party_type === 'customer' ? (int) $source->invoice->party_id : null,
                'item_id' => $source->item_id,
                'item_variant_id' => null,
                'uom_id' => $source->uom_id,
                'source_quantity' => (string) $source->quantity,
                'previously_returned_quantity' => $previous,
                'unit_price' => (string) $source->unit_price,
                'discount_amount' => (string) $source->discount_amount,
                'tax_amount' => (string) $source->tax_amount,
                'charge_amount' => (string) $source->charge_amount,
            ];
        }

        throw new InvalidArgumentException('Unsupported sales return source line type.');
    }

    private function applyToSource(SalesReturnLine $line): void
    {
        if ($line->source_line_type === 'sales_delivery_line') {
            $deliveryLine = SalesDeliveryLine::query()->with(['delivery', 'salesOrderLine'])->findOrFail($line->source_line_id);
            $deliveryLine->returned_quantity = $this->math->add((string) $deliveryLine->returned_quantity, (string) $line->returned_quantity);
            $deliveryLine->remaining_quantity = $this->math->sub((string) $deliveryLine->delivered_quantity, (string) $deliveryLine->returned_quantity);
            $deliveryLine->status = $this->math->isZero((string) $deliveryLine->remaining_quantity) ? 'returned' : 'partially_returned';
            $deliveryLine->save();
            if ($deliveryLine->salesOrderLine instanceof SalesOrderLine) {
                $this->orders->applyReturned($deliveryLine->salesOrderLine, (string) $line->returned_quantity);
            }
            $this->refreshDeliveryReturnStatus($deliveryLine->delivery);
        } elseif ($line->source_line_type === 'sales_order_line') {
            $this->orders->applyReturned(SalesOrderLine::query()->findOrFail($line->source_line_id), (string) $line->returned_quantity);
        } elseif ($line->source_line_type === 'invoice_line') {
            $invoiceLine = InvoiceLine::query()->findOrFail($line->source_line_id);
            if ($invoiceLine->source_line_type === 'sales_delivery_line') {
                $proxy = $line->replicate();
                $proxy->source_line_type = 'sales_delivery_line';
                $proxy->source_line_id = $invoiceLine->source_line_id;
                $this->applyToSource($proxy);
            } elseif ($invoiceLine->source_line_type === 'sales_order_line') {
                $this->orders->applyReturned(SalesOrderLine::query()->findOrFail($invoiceLine->source_line_id), (string) $line->returned_quantity);
            }
        }
    }

    private function allocateAdjustments(SalesReturn $return): string
    {
        $return->load('lines');
        $groups = [];
        foreach ($return->lines as $line) {
            $source = null;
            if ($line->source_line_type === 'sales_delivery_line') {
                $deliveryLine = SalesDeliveryLine::query()->find($line->source_line_id);
                $source = $deliveryLine === null ? null : ['sales_delivery', (int) $deliveryLine->sales_delivery_id];
            } elseif ($line->source_line_type === 'sales_order_line') {
                $orderLine = SalesOrderLine::query()->find($line->source_line_id);
                $source = $orderLine === null ? null : ['sales_order', (int) $orderLine->sales_order_id];
            }
            if ($source === null) {
                continue;
            }
            $key = $source[0].':'.$source[1];
            $groups[$key] = $this->math->add($groups[$key] ?? '0.000000', (string) $line->line_total);
        }

        $net = '0.000000';
        foreach ($groups as $key => $returnedLineTotal) {
            [$sourceType, $sourceId] = explode(':', $key, 2);
            $sourceSubtotal = $sourceType === 'sales_delivery'
                ? $this->deliverySubtotal((int) $sourceId)
                : (string) SalesOrder::query()->findOrFail((int) $sourceId)->subtotal;
            if ($this->math->isZero($sourceSubtotal)) {
                continue;
            }
            $ratio = $this->math->div($returnedLineTotal, $sourceSubtotal, 12);
            $adjustments = SalesHeaderAdjustment::query()
                ->where('source_type', $sourceType)
                ->where('source_id', (int) $sourceId)
                ->get();
            foreach ($adjustments as $adjustment) {
                $returnedAmount = $this->math->mul((string) $adjustment->amount, $ratio);
                $previous = (string) $adjustment->returned_amount;
                $remaining = $this->math->sub((string) $adjustment->amount, $this->math->add($previous, $returnedAmount));
                SalesReturnAdjustmentAllocation::query()->create([
                    'tenant_id' => $return->tenant_id,
                    'organization_unit_id' => $return->organization_unit_id,
                    'sales_return_id' => $return->getKey(),
                    'sales_header_adjustment_id' => $adjustment->getKey(),
                    'adjustment_type' => $adjustment->adjustment_type,
                    'effect' => $adjustment->effect,
                    'source_amount' => $adjustment->amount,
                    'previously_returned_amount' => $previous,
                    'returned_amount' => $returnedAmount,
                    'remaining_amount' => $remaining,
                ]);
                $adjustment->returned_amount = $this->math->add($previous, $returnedAmount);
                $adjustment->remaining_amount = $remaining;
                $adjustment->save();
                $net = $adjustment->effect->value === 'increase'
                    ? $this->math->add($net, $returnedAmount)
                    : $this->math->sub($net, $returnedAmount);
            }
        }

        return $net;
    }

    private function deliverySubtotal(int $deliveryId): string
    {
        $delivery = SalesDelivery::query()->with('lines')->findOrFail($deliveryId);
        $total = '0.000000';
        foreach ($delivery->lines as $line) {
            $total = $this->math->add($total, $this->math->mul((string) $line->delivered_quantity, (string) $line->unit_price));
        }

        return $total;
    }

    private function refreshDeliveryReturnStatus(SalesDelivery $delivery): void
    {
        $delivery->load('lines');
        $delivered = $this->math->sum($delivery->lines->pluck('delivered_quantity')->all());
        $returned = $this->math->sum($delivery->lines->pluck('returned_quantity')->all());
        $delivery->status = $this->math->compare($returned, $delivered) >= 0
            ? SalesDeliveryStatus::Returned
            : SalesDeliveryStatus::PartiallyReturned;
        $delivery->save();
    }

    private function dispatchReplacement(SalesReturn $return, ?int $userId): ?SalesDelivery
    {
        if (! in_array($return->return_type, [
            SalesReturnType::WarrantyReplacement,
            SalesReturnType::ExchangeReturn,
        ], true)) {
            return null;
        }

        $order = SalesOrder::query()->with('lines')->findOrFail($return->replacement_sales_order_id);
        if ($order->warehouse_id === null) {
            throw new InvalidArgumentException('Replacement sales order requires a warehouse before the return can be posted.');
        }

        $lines = [];
        foreach ($order->lines as $line) {
            if ($this->math->compare((string) $line->remaining_deliverable_quantity, '0.000000') <= 0) {
                continue;
            }

            $lines[] = new SalesDeliveryLineData(
                itemId: (int) $line->item_id,
                deliveredQuantity: (string) $line->remaining_deliverable_quantity,
                unitPrice: (string) $line->unit_price,
                salesOrderLineId: (int) $line->getKey(),
                itemVariantId: $line->item_variant_id,
                description: $line->description,
                uomId: $line->ordered_uom_id,
                orderedQuantity: (string) $line->ordered_quantity,
            );
        }
        if ($lines === []) {
            throw new InvalidArgumentException('Replacement sales order has no deliverable quantities.');
        }

        $delivery = $this->deliveries->create(new CreateSalesDeliveryData(
            tenantId: (int) $return->tenant_id,
            deliveryDate: $return->return_date->toDateString(),
            customerId: (int) $return->customer_id,
            warehouseId: (int) $order->warehouse_id,
            organizationUnitId: $return->organization_unit_id,
            salesOrderId: (int) $order->getKey(),
            warehouseLocationId: $order->warehouse_location_id,
            notes: ucfirst(str_replace('_', ' ', $return->return_type->value)).' for '.$return->return_number,
            deliveredBy: $userId,
            lines: $lines,
        ));

        return $this->deliveries->post($delivery, $userId);
    }

    private function proportional(string $amount, string $quantity, string $sourceQuantity): string
    {
        return $this->math->isZero($amount) || $this->math->isZero($sourceQuantity)
            ? '0.000000'
            : $this->math->mul($amount, $this->math->div($quantity, $sourceQuantity, 12));
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

    private function load(SalesReturn $return): SalesReturn
    {
        return $return->refresh()->load([
            'customer',
            'warehouse',
            'warehouseLocation',
            'replacementSalesOrder',
            'creditNote',
            'lines.item',
            'lines.variant',
            'lines.uom',
            'adjustmentAllocations',
        ]);
    }
}
