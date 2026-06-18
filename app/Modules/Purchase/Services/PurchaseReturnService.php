<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\CreatePurchaseDebitNoteData;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchasePostingResult;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Validators\PurchaseValidationService;
use Modules\Tax\Services\TaxReturnAllocationService;

final class PurchaseReturnService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseInventoryIntegrationService $inventory,
        private readonly PurchaseReturnAdjustmentService $adjustments,
        private readonly PurchaseDebitNoteService $debitNotes,
        private readonly PurchaseNumberService $numbers,
        private readonly PurchaseOrderQuantityService $orderQuantities,
        private readonly TaxReturnAllocationService $taxReturns,
    ) {}

    public function create(CreatePurchaseReturnData $data): PurchaseReturn
    {
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

        if ($data->returnType === PurchaseReturnType::ManualSupplierReturn) {
            $this->validateManualSupplierReturn($data);
        } else {
            foreach ($data->lines as $index => $line) {
                if ($line->sourceLineType !== 'goods_receipt_note_line') {
                    throw new \InvalidArgumentException('Normal purchase returns require a goods receipt note line source.');
                }
                $sourceLine = GoodsReceiptNoteLine::query()->findOrFail($line->sourceLineId);
                $this->validator->assertTenantOrg(
                    $sourceLine->tenant_id !== null ? (int) $sourceLine->tenant_id : null,
                    $sourceLine->organization_unit_id !== null ? (int) $sourceLine->organization_unit_id : null,
                    $data->tenantId,
                    $data->organizationUnitId,
                    "lines.{$index}.source_line_id",
                    'goods receipt line',
                );
                $this->validator->assertReturnWithinReceipt($sourceLine, $line->returnedQuantity);
            }
        }

        return DB::transaction(function () use ($data): PurchaseReturn {
            $sourceHeader = null;
            if ($data->returnType === PurchaseReturnType::Referenced && isset($data->lines[0])) {
                $sourceHeader = GoodsReceiptNoteLine::query()
                    ->with('goodsReceiptNote')
                    ->find($data->lines[0]->sourceLineId)
                    ?->goodsReceiptNote;
            }

            $return = PurchaseReturn::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'supplier_type' => $data->supplierType ?? $sourceHeader?->supplier_type,
                'supplier_id' => $data->supplierId ?? $sourceHeader?->supplier_id,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'return_number' => $data->returnNumber ?? $this->numbers->next($data->tenantId, 'PRET', 'purchase_returns', 'return_number'),
                'return_type' => $data->returnType,
                'source_type' => $data->sourceType ?? ($data->returnType === PurchaseReturnType::ManualSupplierReturn ? 'manual_supplier_return' : null),
                'source_id' => $data->sourceId,
                'return_date' => $data->returnDate,
                'status' => PurchaseReturnStatus::Draft,
                'reason' => $data->reason,
                'approval_required' => $data->approvalRequired,
                'affects_supplier_balance' => $data->affectsSupplierBalance,
                'cost_basis' => $data->costBasis,
                'audit_metadata' => $data->auditMetadata,
                'created_by' => $data->createdBy,
            ]);

            $subtotal = '0.000000';
            $adjustmentReturnTotal = '0.000000';
            foreach ($data->lines as $lineData) {
                if ($data->returnType === PurchaseReturnType::ManualSupplierReturn) {
                    $lineTotal = $this->math->mul($lineData->returnedQuantity, (string) $lineData->costBasis);
                    $subtotal = $this->math->add($subtotal, $lineTotal);
                    $return->lines()->create([
                        'tenant_id' => $return->tenant_id,
                        'organization_unit_id' => $return->organization_unit_id,
                        'item_id' => $lineData->itemId,
                        'item_variant_id' => $lineData->itemVariantId,
                        'uom_id' => $lineData->uomId,
                        'source_line_type' => 'manual_supplier_return',
                        'source_line_id' => 0,
                        'returned_quantity' => $this->math->normalize($lineData->returnedQuantity),
                        'source_quantity' => $this->math->normalize($lineData->returnedQuantity),
                        'previously_returned_quantity' => '0.000000',
                        'remaining_quantity' => '0.000000',
                        'unit_price' => $this->math->normalize((string) $lineData->costBasis),
                        'cost_basis' => $this->math->normalize((string) $lineData->costBasis),
                        'line_total' => $lineTotal,
                        'reason' => $lineData->reason ?? $data->reason,
                    ]);

                    continue;
                }

                $sourceLine = GoodsReceiptNoteLine::query()->with('goodsReceiptNote')->findOrFail($lineData->sourceLineId);
                $lineTotal = $this->math->mul($lineData->returnedQuantity, (string) $sourceLine->unit_price);
                $subtotal = $this->math->add($subtotal, $lineTotal);
                $adjustmentReturnTotal = $this->math->add($adjustmentReturnTotal, $this->adjustments->previewFromReceiptLine($return, $sourceLine, $lineData->returnedQuantity));

                $return->lines()->create([
                    'tenant_id' => $return->tenant_id,
                    'organization_unit_id' => $return->organization_unit_id,
                    'item_id' => $sourceLine->item_id,
                    'item_variant_id' => $sourceLine->item_variant_id,
                    'uom_id' => $sourceLine->uom_id,
                    'source_line_type' => $lineData->sourceLineType,
                    'source_line_id' => $lineData->sourceLineId,
                    'returned_quantity' => $this->math->normalize($lineData->returnedQuantity),
                    'source_quantity' => $sourceLine->accepted_quantity,
                    'previously_returned_quantity' => $sourceLine->returned_quantity,
                    'remaining_quantity' => $this->math->sub((string) $sourceLine->accepted_quantity, $this->math->add((string) $sourceLine->returned_quantity, $lineData->returnedQuantity)),
                    'unit_price' => $sourceLine->unit_price,
                    'cost_basis' => $sourceLine->unit_price,
                    'discount_amount' => '0.000000',
                    'tax_amount' => '0.000000',
                    'charge_amount' => '0.000000',
                    'line_total' => $lineTotal,
                    'reason' => $lineData->reason,
                ]);
            }

            $return->subtotal = $subtotal;
            $return->adjustment_return_total = $adjustmentReturnTotal;
            $return->grand_total = $data->affectsSupplierBalance ? $this->math->add($subtotal, $adjustmentReturnTotal) : '0.000000';
            $return->save();

            return $return->refresh()->load(['supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustmentAllocations']);
        });
    }

    public function approve(PurchaseReturn $return, ?int $approvedBy = null): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $approvedBy): PurchaseReturn {
            $return = PurchaseReturn::query()->lockForUpdate()->findOrFail($return->getKey());

            if (! (bool) $return->approval_required) {
                throw new \InvalidArgumentException('Purchase return does not require approval.');
            }
            if ($return->status !== PurchaseReturnStatus::Draft) {
                throw new \InvalidArgumentException('Only draft purchase returns can be approved.');
            }

            $return->status = PurchaseReturnStatus::Approved;
            $return->approved_by = $approvedBy;
            $return->approved_at = now();
            $return->save();

            return $return->refresh()->load(['supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustmentAllocations']);
        });
    }

    public function post(PurchaseReturn $return, ?int $postedBy = null): PurchasePostingResult
    {
        if ((bool) $return->approval_required && $return->status !== PurchaseReturnStatus::Approved) {
            throw new \InvalidArgumentException('Purchase return must be approved before posting.');
        }
        if (! (bool) $return->approval_required && $return->status !== PurchaseReturnStatus::Draft) {
            throw new \InvalidArgumentException('Only draft purchase returns can be posted without approval.');
        }

        return DB::transaction(function () use ($return, $postedBy): PurchasePostingResult {
            $return = PurchaseReturn::query()->with('lines')->lockForUpdate()->findOrFail($return->getKey());
            if ((bool) $return->approval_required && $return->status !== PurchaseReturnStatus::Approved) {
                throw new \InvalidArgumentException('Purchase return must be approved before posting.');
            }
            if (! (bool) $return->approval_required && $return->status !== PurchaseReturnStatus::Draft) {
                throw new \InvalidArgumentException('Only draft purchase returns can be posted without approval.');
            }

            $movementIds = [];
            $adjustmentReturnTotal = '0.000000';
            foreach ($return->lines as $line) {
                $movement = $this->inventory->returnOut($return, $line, $postedBy);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                    $movementIds[] = (int) $movement->getKey();
                }
                $line->save();

                $sourceLine = $line->source_line_type === 'goods_receipt_note_line'
                    ? GoodsReceiptNoteLine::query()->with('purchaseOrderLine')->lockForUpdate()->find((int) $line->source_line_id)
                    : null;
                if ($sourceLine instanceof GoodsReceiptNoteLine) {
                    $this->validator->assertReturnWithinReceipt($sourceLine, (string) $line->returned_quantity);
                    $adjustmentReturnTotal = $this->math->add(
                        $adjustmentReturnTotal,
                        $this->adjustments->allocateFromReceiptLine($return, $sourceLine, (string) $line->returned_quantity),
                    );
                    $sourceLine->returned_quantity = $this->math->add((string) $sourceLine->returned_quantity, (string) $line->returned_quantity);
                    $returnable = $this->math->sub((string) $sourceLine->accepted_quantity, (string) $sourceLine->returned_quantity);
                    $sourceLine->status = $this->math->isZero($returnable)
                        ? GoodsReceiptNoteLineStatus::Returned
                        : GoodsReceiptNoteLineStatus::PartiallyReturned;
                    $sourceLine->save();
                    if ($sourceLine->purchaseOrderLine instanceof PurchaseOrderLine) {
                        $this->orderQuantities->applyReturned($sourceLine->purchaseOrderLine, (string) $line->returned_quantity);
                    }
                }
            }

            $return->adjustment_return_total = $adjustmentReturnTotal;
            $return->grand_total = (bool) $return->affects_supplier_balance
                ? $this->math->add((string) $return->subtotal, $adjustmentReturnTotal)
                : '0.000000';
            $return->save();

            $debitNote = null;
            if ((bool) $return->affects_supplier_balance && ! $this->math->isZero((string) $return->grand_total)) {
                $debitNote = $this->debitNotes->create(new CreatePurchaseDebitNoteData(
                    tenantId: (int) $return->tenant_id,
                    debitNoteDate: $return->return_date->toDateString(),
                    amount: (string) $return->grand_total,
                    organizationUnitId: $return->organization_unit_id,
                    supplierType: $return->supplier_type,
                    supplierId: $return->supplier_id,
                    purchaseReturnId: (int) $return->getKey(),
                    sourceType: $return->return_type?->value ?? 'purchase_return',
                    sourceId: (int) $return->getKey(),
                    reason: $return->reason ?: 'Purchase return '.$return->return_number,
                ));
                $debitNote = $this->debitNotes->approve($debitNote, $postedBy);
                $debitNote = $this->debitNotes->post($debitNote);
            }

            $return->status = PurchaseReturnStatus::Posted;
            $return->posted_by = $postedBy;
            $return->posted_at = now();
            $return->debit_note_id = $debitNote?->getKey();
            $return->save();
            $this->taxReturns->reversePurchaseReturn($return->refresh()->load('lines'), $debitNote === null ? null : (int) $debitNote->getKey());

            return new PurchasePostingResult((int) $return->getKey(), (string) $return->return_number, $return->status->value, $movementIds, debitNoteId: $debitNote === null ? null : (int) $debitNote->getKey());
        });
    }

    public function cancel(PurchaseReturn $return): PurchaseReturn
    {
        if ($return->status === PurchaseReturnStatus::Posted) {
            throw new \InvalidArgumentException('Posted purchase returns cannot be cancelled.');
        }

        $return->status = PurchaseReturnStatus::Cancelled;
        $return->save();

        return $return->refresh();
    }

    private function validateManualSupplierReturn(CreatePurchaseReturnData $data): void
    {
        if ($data->supplierId === null) {
            throw new \InvalidArgumentException('Unreferenced supplier return requires supplier.');
        }
        if (trim((string) $data->reason) === '') {
            throw new \InvalidArgumentException('Unreferenced supplier return requires reason.');
        }
        if (! $data->approvalRequired) {
            throw new \InvalidArgumentException('Unreferenced supplier return requires approval.');
        }
        if ($data->costBasis === null) {
            throw new \InvalidArgumentException('Unreferenced supplier return requires explicit cost basis.');
        }

        $this->validator->supplier($data->tenantId, $data->organizationUnitId, $data->supplierId, 'supplier_id');
        $this->validator->assertNonNegative($data->costBasis, 'Unreferenced supplier return cost basis cannot be negative.');

        foreach ($data->lines as $index => $line) {
            if ($line->itemId === null || $line->uomId === null || $line->costBasis === null) {
                throw new \InvalidArgumentException('Unreferenced supplier return lines require item, UOM, and cost basis.');
            }
            $this->validator->assertPositiveQuantity($line->returnedQuantity);
            $this->validator->assertNonNegative($line->costBasis, 'Unreferenced supplier return line cost basis cannot be negative.');
            $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId, "lines.{$index}.item_id");
            $this->validator->uom($data->tenantId, $data->organizationUnitId, $line->uomId, "lines.{$index}.uom_id");
        }
    }

}
