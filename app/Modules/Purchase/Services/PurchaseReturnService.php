<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchaseDebitNoteData;
use Modules\Purchase\DTOs\PurchasePostingResult;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Validators\PurchaseValidationService;

final class PurchaseReturnService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseInventoryIntegrationService $inventory,
        private readonly PurchaseReturnAdjustmentService $adjustments,
        private readonly PurchaseDebitNoteService $debitNotes,
        private readonly PurchaseNumberService $numbers,
    ) {}

    public function create(CreatePurchaseReturnData $data): PurchaseReturn
    {
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);

        foreach ($data->lines as $line) {
            if ($line->sourceLineType !== 'goods_receipt_note_line') {
                throw new \InvalidArgumentException('Normal purchase returns require a goods receipt note line source.');
            }
            $sourceLine = GoodsReceiptNoteLine::query()->findOrFail($line->sourceLineId);
            $this->validator->assertTenantOrg((int) $sourceLine->tenant_id, $sourceLine->organization_unit_id, $data->tenantId, $data->organizationUnitId);
            $this->validator->assertReturnWithinReceipt($sourceLine, $line->returnedQuantity);
        }

        return DB::transaction(function () use ($data): PurchaseReturn {
            $return = PurchaseReturn::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'supplier_type' => $data->supplierType,
                'supplier_id' => $data->supplierId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'return_number' => $data->returnNumber ?? $this->numbers->next($data->tenantId, 'PRET', 'purchase_returns', 'return_number'),
                'return_date' => $data->returnDate,
                'status' => PurchaseReturnStatus::Draft,
                'reason' => $data->reason,
                'created_by' => $data->createdBy,
            ]);

            $subtotal = '0.000000';
            $adjustmentReturnTotal = '0.000000';
            foreach ($data->lines as $lineData) {
                $sourceLine = GoodsReceiptNoteLine::query()->with('goodsReceiptNote')->findOrFail($lineData->sourceLineId);
                $lineTotal = $this->math->mul($lineData->returnedQuantity, (string) $sourceLine->unit_price);
                $subtotal = $this->math->add($subtotal, $lineTotal);
                $adjustmentReturnTotal = $this->math->add($adjustmentReturnTotal, $this->adjustments->allocateFromReceiptLine($return, $sourceLine, $lineData->returnedQuantity));

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
                    'discount_amount' => '0.000000',
                    'tax_amount' => '0.000000',
                    'charge_amount' => '0.000000',
                    'line_total' => $lineTotal,
                    'reason' => $lineData->reason,
                ]);
            }

            $return->subtotal = $subtotal;
            $return->adjustment_return_total = $adjustmentReturnTotal;
            $return->grand_total = $this->math->add($subtotal, $adjustmentReturnTotal);
            $return->save();

            return $return->refresh()->load(['lines', 'adjustmentAllocations']);
        });
    }

    public function post(PurchaseReturn $return, ?int $postedBy = null): PurchasePostingResult
    {
        return DB::transaction(function () use ($return, $postedBy): PurchasePostingResult {
            $return->load('lines');
            $movementIds = [];
            foreach ($return->lines as $line) {
                $movement = $this->inventory->returnOut($return, $line, $postedBy);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                    $movementIds[] = (int) $movement->getKey();
                }
                $line->save();

                $sourceLine = GoodsReceiptNoteLine::query()->find((int) $line->source_line_id);
                if ($sourceLine instanceof GoodsReceiptNoteLine) {
                    $sourceLine->returned_quantity = $this->math->add((string) $sourceLine->returned_quantity, (string) $line->returned_quantity);
                    $sourceLine->remaining_quantity = $this->math->sub((string) $sourceLine->accepted_quantity, (string) $sourceLine->returned_quantity);
                    $sourceLine->save();
                }
            }

            $debitNote = $this->debitNotes->create(new PurchaseDebitNoteData(
                tenantId: (int) $return->tenant_id,
                debitNoteDate: $return->return_date->toDateString(),
                amount: (string) $return->grand_total,
                organizationUnitId: $return->organization_unit_id,
                supplierType: $return->supplier_type,
                supplierId: $return->supplier_id,
                purchaseReturnId: (int) $return->getKey(),
                reason: $return->reason,
            ));

            $return->status = PurchaseReturnStatus::Posted;
            $return->posted_by = $postedBy;
            $return->posted_at = now();
            $return->debit_note_id = $debitNote->getKey();
            $return->save();

            return new PurchasePostingResult((int) $return->getKey(), (string) $return->return_number, $return->status->value, $movementIds, debitNoteId: (int) $debitNote->getKey());
        });
    }
}
