<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\CreatePurchaseDebitNoteData;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchasePostingResult;
use Modules\Purchase\DTOs\PurchaseReturnLineData;
use Modules\Purchase\DTOs\PurchaseReturnLineValuationData;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnAdjustmentAllocation;
use Modules\Purchase\Models\PurchaseReturnLine;
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
        private readonly PurchaseReturnValuationService $valuations,
        private readonly PurchaseStatusService $statuses,
        private readonly TaxReturnAllocationService $taxReturns,
        private readonly PurchaseDocumentLockService $locks,
    ) {}

    public function create(CreatePurchaseReturnData $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data): PurchaseReturn {
            $policy = $this->serverPolicy($data);
            $referenced = null;

            if ($data->returnType === PurchaseReturnType::ManualSupplierReturn) {
                $this->validateManualSupplierReturn($data);
                if ($data->warehouseId === null) {
                    throw new InvalidArgumentException('Unreferenced supplier return requires warehouse.');
                }
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
            } else {
                $referenced = $this->resolveReferencedSource($data, lockSources: true);
            }

            $sourceHeader = $referenced['header'] ?? null;
            $headerCostBasis = $data->returnType === PurchaseReturnType::ManualSupplierReturn
                ? ($data->costBasis ?? $this->firstManualLineCostBasis($data))
                : null;
            $return = PurchaseReturn::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'supplier_type' => $data->returnType === PurchaseReturnType::ManualSupplierReturn
                    ? 'supplier'
                    : $sourceHeader?->supplier_type,
                'supplier_id' => $data->returnType === PurchaseReturnType::ManualSupplierReturn
                    ? $data->supplierId
                    : $sourceHeader?->supplier_id,
                'warehouse_id' => $data->returnType === PurchaseReturnType::ManualSupplierReturn
                    ? $data->warehouseId
                    : $sourceHeader?->warehouse_id,
                'warehouse_location_id' => $data->returnType === PurchaseReturnType::ManualSupplierReturn
                    ? $data->warehouseLocationId
                    : $sourceHeader?->warehouse_location_id,
                'return_number' => $data->returnNumber ?? $this->numbers->next($data->tenantId, 'PRET', 'purchase_returns', 'return_number'),
                'return_type' => $data->returnType,
                'source_type' => $policy['source_type'],
                'source_id' => $sourceHeader?->getKey(),
                'return_date' => $data->returnDate,
                'status' => PurchaseReturnStatus::Draft,
                'reason' => $data->reason,
                'approval_required' => $policy['approval_required'],
                'affects_supplier_balance' => $policy['affects_supplier_balance'],
                'cost_basis' => $headerCostBasis,
                'audit_metadata' => $data->auditMetadata,
                'created_by' => $data->createdBy,
            ]);

            $subtotal = '0.000000';
            $adjustmentReturnTotal = '0.000000';

            foreach ($data->lines as $lineIndex => $lineData) {
                if ($data->returnType === PurchaseReturnType::ManualSupplierReturn) {
                    $valuation = $this->valuations->manual($lineData->returnedQuantity, (string) $lineData->costBasis);
                    $subtotal = $this->math->add($subtotal, $valuation->lineTotal);
                    $this->createManualLine($return, $lineData, $valuation, $data->reason, $lineIndex + 1);

                    continue;
                }

                /** @var array<int, GoodsReceiptNoteLine> $sourceLines */
                $sourceLines = $referenced['lines'];
                $sourceLine = $sourceLines[$lineData->sourceLineId] ?? null;
                if (! $sourceLine instanceof GoodsReceiptNoteLine) {
                    throw new InvalidArgumentException('Selected goods receipt line was not found for this return.');
                }

                $valuation = $this->valuations->fromReceiptLine($sourceLine, $lineData->returnedQuantity);
                $subtotal = $this->math->add($subtotal, $valuation->lineTotal);
                $adjustmentReturnTotal = $this->math->add(
                    $adjustmentReturnTotal,
                    $this->adjustments->previewFromReceiptLine($return, $sourceLine, $lineData->returnedQuantity),
                );
                $this->createReferencedLine($return, $lineData, $sourceLine, $valuation);
            }

            $return->subtotal = $subtotal;
            $return->adjustment_return_total = $adjustmentReturnTotal;
            $return->grand_total = $policy['affects_supplier_balance']
                ? $this->math->add($subtotal, $adjustmentReturnTotal)
                : '0.000000';
            $return->save();

            return $return->refresh()->load([
                'supplier',
                'warehouse',
                'warehouseLocation',
                'sourceGoodsReceipt',
                'lines.item',
                'lines.variant',
                'lines.uom',
                'adjustmentAllocations',
                'debitNote',
            ]);
        });
    }

    public function approve(PurchaseReturn $return, ?int $approvedBy = null): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $approvedBy): PurchaseReturn {
            $return = PurchaseReturn::query()->lockForUpdate()->findOrFail($return->getKey());

            if (! (bool) $return->approval_required) {
                throw new InvalidArgumentException('Purchase return does not require approval.');
            }
            if ($return->status !== PurchaseReturnStatus::Draft) {
                throw new InvalidArgumentException('Only draft purchase returns can be approved.');
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
        return DB::transaction(function () use ($return, $postedBy): PurchasePostingResult {
            $snapshot = PurchaseReturn::query()->with('lines')->findOrFail($return->getKey());
            $sourceLines = $this->lockReturnSourcesForPost($snapshot);
            $returnContext = $this->lockReturnContextForPost($snapshot, $sourceLines);
            $return = $returnContext['return'];
            $postedLineSums = $returnContext['posted_line_sums'];
            if ($return->status === PurchaseReturnStatus::Posted) {
                return new PurchasePostingResult(
                    (int) $return->getKey(),
                    (string) $return->return_number,
                    $return->status->value,
                    $return->lines
                        ->pluck('inventory_movement_id')
                        ->filter()
                        ->map(static fn ($id): int => (int) $id)
                        ->values()
                        ->all(),
                    debitNoteId: $return->debit_note_id === null ? null : (int) $return->debit_note_id,
                );
            }
            $this->assertPostable($return);
            $this->assertLockedReturnSources($return, $sourceLines);

            $movementIds = [];
            $subtotal = '0.000000';
            $adjustmentReturnTotal = '0.000000';
            $touchedGoodsReceipts = [];

            foreach ($return->lines as $line) {
                if ($line->source_line_type === 'goods_receipt_note_line') {
                    $sourceLine = $sourceLines[(int) $line->source_line_id] ?? null;
                    if (! $sourceLine instanceof GoodsReceiptNoteLine) {
                        throw new InvalidArgumentException('Selected goods receipt line was not found for this return.');
                    }

                    $this->validator->assertReturnWithinReceipt($sourceLine, (string) $line->returned_quantity);
                    $valuation = $this->valuations->fromReceiptLine(
                        $sourceLine,
                        (string) $line->returned_quantity,
                        (int) $return->getKey(),
                        $postedLineSums[(int) $sourceLine->getKey()] ?? null,
                    );
                    $this->applyValuationToLine($line, $valuation);
                    $subtotal = $this->math->add($subtotal, $valuation->lineTotal);
                } else {
                    $valuation = $this->valuations->manual((string) $line->returned_quantity, (string) $line->cost_basis);
                    $this->applyValuationToLine($line, $valuation);
                    $subtotal = $this->math->add($subtotal, $valuation->lineTotal);
                }
            }

            foreach ($return->lines as $line) {
                $movement = $this->inventory->returnOut($return, $line, $postedBy);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                    $movementIds[] = (int) $movement->getKey();
                }
                $line->save();

                $sourceLine = $line->source_line_type === 'goods_receipt_note_line'
                    ? ($sourceLines[(int) $line->source_line_id] ?? null)
                    : null;

                if ($sourceLine instanceof GoodsReceiptNoteLine) {
                    $receiptId = (int) $sourceLine->goods_receipt_note_id;
                    $adjustmentReturnTotal = $this->math->add(
                        $adjustmentReturnTotal,
                        $this->adjustments->allocateFromLockedReceiptLine(
                            $return,
                            $sourceLine,
                            (string) $line->returned_quantity,
                            $returnContext['receipt_lines_by_grn'][$receiptId] ?? collect(),
                            $returnContext['adjustments_by_grn'][$receiptId] ?? collect(),
                            $returnContext['adjustment_allocations'],
                        ),
                    );
                    $sourceLine->returned_quantity = $this->math->add((string) $sourceLine->returned_quantity, (string) $line->returned_quantity);
                    $returnable = $this->math->sub((string) $sourceLine->accepted_quantity, (string) $sourceLine->returned_quantity);
                    $sourceLine->status = GoodsReceiptNoteLineStatus::Posted;
                    $sourceLine->save();
                    $touchedGoodsReceipts[(int) $sourceLine->goods_receipt_note_id] = true;

                    if ($sourceLine->purchaseOrderLine instanceof PurchaseOrderLine) {
                        $this->orderQuantities->applyReturned($sourceLine->purchaseOrderLine, (string) $line->returned_quantity);
                    }
                }
            }

            $return->subtotal = $subtotal;
            $return->adjustment_return_total = $adjustmentReturnTotal;
            $return->grand_total = (bool) $return->affects_supplier_balance
                ? $this->math->add($subtotal, $adjustmentReturnTotal)
                : '0.000000';
            $return->save();

            foreach (array_keys($touchedGoodsReceipts) as $goodsReceiptId) {
                $this->statuses->refreshGoodsReceipt(GoodsReceiptNote::query()->with('lines')->findOrFail((int) $goodsReceiptId));
            }

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
            }

            $return->status = PurchaseReturnStatus::Posted;
            $return->posted_by = $postedBy;
            $return->posted_at = now();
            $return->debit_note_id = $debitNote?->getKey();
            $return->save();

            $this->taxReturns->reversePurchaseReturn($return->refresh()->load('lines'), $debitNote === null ? null : (int) $debitNote->getKey());

            return new PurchasePostingResult(
                (int) $return->getKey(),
                (string) $return->return_number,
                $return->status->value,
                $movementIds,
                debitNoteId: $debitNote === null ? null : (int) $debitNote->getKey(),
            );
        });
    }

    public function cancel(PurchaseReturn $return): PurchaseReturn
    {
        return DB::transaction(function () use ($return): PurchaseReturn {
            $locked = $this->locks->purchaseReturns([(int) $return->getKey()])->first();
            if (! $locked instanceof PurchaseReturn) {
                throw new InvalidArgumentException('Purchase return was not found.');
            }
            $this->locks->purchaseReturnLinesForReturns([(int) $locked->getKey()]);

            if ($locked->status === PurchaseReturnStatus::Posted) {
                throw new InvalidArgumentException('Posted purchase returns cannot be cancelled.');
            }

            $locked->status = PurchaseReturnStatus::Cancelled;
            $locked->save();

            return $locked->refresh();
        });
    }

    /**
     * @return array{approval_required: bool, affects_supplier_balance: bool, source_type: string}
     */
    private function serverPolicy(CreatePurchaseReturnData $data): array
    {
        if ($data->returnType === PurchaseReturnType::ManualSupplierReturn) {
            return [
                'approval_required' => true,
                'affects_supplier_balance' => true,
                'source_type' => 'manual_supplier_return',
            ];
        }

        return [
            'approval_required' => false,
            'affects_supplier_balance' => true,
            'source_type' => 'goods_receipt_note',
        ];
    }

    /**
     * @return array{header: GoodsReceiptNote, lines: array<int, GoodsReceiptNoteLine>}
     */
    private function resolveReferencedSource(CreatePurchaseReturnData $data, bool $lockSources): array
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Purchase return requires at least one source line.');
        }
        if ($data->sourceType !== null && $data->sourceType !== 'goods_receipt_note') {
            throw new InvalidArgumentException('Referenced purchase returns can only use a goods receipt note source.');
        }

        $lineIds = [];
        foreach ($data->lines as $index => $line) {
                if (! $line instanceof PurchaseReturnLineData || $line->sourceLineType !== 'goods_receipt_note_line' || $line->sourceLineId === null) {
                    throw new InvalidArgumentException('Normal purchase returns require a goods receipt note line source.');
                }
            $this->validator->assertPositiveQuantity($line->returnedQuantity);
            if (isset($lineIds[$line->sourceLineId])) {
                throw new InvalidArgumentException('Duplicate goods receipt note line selected for return.');
            }
            $lineIds[$line->sourceLineId] = "lines.{$index}.source_line_id";
        }

        /** @var Collection<int, GoodsReceiptNoteLine> $lines */
        $lines = $lockSources
            ? collect($this->lockReceiptSourcesByLineIds(array_keys($lineIds)))
            : GoodsReceiptNoteLine::query()
                ->with(['goodsReceiptNote', 'purchaseOrderLine'])
                ->whereIn('id', array_keys($lineIds))
                ->get()
                ->keyBy(fn (GoodsReceiptNoteLine $line): int => (int) $line->getKey());
        if ($lines->count() !== count($lineIds)) {
            throw new InvalidArgumentException('One or more selected goods receipt note lines were not found.');
        }

        $sourceHeader = null;
        $resolvedLines = [];
        foreach ($data->lines as $index => $lineData) {
            $sourceLine = $lines->get($lineData->sourceLineId);
            if (! $sourceLine instanceof GoodsReceiptNoteLine || ! $sourceLine->goodsReceiptNote instanceof GoodsReceiptNote) {
                throw new InvalidArgumentException('Selected goods receipt note line was not found.');
            }

            $this->validator->assertTenantOrg(
                $sourceLine->tenant_id !== null ? (int) $sourceLine->tenant_id : null,
                $sourceLine->organization_unit_id !== null ? (int) $sourceLine->organization_unit_id : null,
                $data->tenantId,
                $data->organizationUnitId,
                "lines.{$index}.source_line_id",
                'goods receipt line',
            );

            $header = $sourceLine->goodsReceiptNote;
            $this->validator->assertTenantOrg(
                $header->tenant_id !== null ? (int) $header->tenant_id : null,
                $header->organization_unit_id !== null ? (int) $header->organization_unit_id : null,
                $data->tenantId,
                $data->organizationUnitId,
                'source_id',
                'goods receipt note',
            );

            if ($sourceHeader === null) {
                $sourceHeader = $header;
            } elseif ((int) $sourceHeader->getKey() !== (int) $header->getKey()) {
                throw new InvalidArgumentException('Purchase return lines must belong to the same goods receipt note.');
            }

            if ($data->sourceId !== null && (int) $data->sourceId !== (int) $header->getKey()) {
                throw new InvalidArgumentException('Purchase return source does not match the selected goods receipt note lines.');
            }
            if ($header->supplier_id === null) {
                throw new InvalidArgumentException('Referenced purchase returns require a supplier-backed goods receipt note.');
            }
            if (! $this->isReturnableReceiptStatus($header)) {
                throw new InvalidArgumentException('Purchase returns can only reference posted goods receipt notes.');
            }
            if ($sourceLine->status === GoodsReceiptNoteLineStatus::Reversed) {
                throw new InvalidArgumentException('Purchase return source goods receipt line is no longer returnable.');
            }
            if ($sourceLine->purchaseOrderLine instanceof PurchaseOrderLine
                && $sourceLine->purchaseOrderLine->relationLoaded('order')
                && in_array($sourceLine->purchaseOrderLine->order?->status, [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled], true)) {
                throw new InvalidArgumentException('Purchase returns cannot be created after the source purchase order is closed or cancelled.');
            }

            $this->validator->assertReturnWithinReceipt($sourceLine, $lineData->returnedQuantity);
            $resolvedLines[(int) $sourceLine->getKey()] = $sourceLine;
        }

        if (! $sourceHeader instanceof GoodsReceiptNote) {
            throw new InvalidArgumentException('Purchase return source goods receipt note was not found.');
        }

        return ['header' => $sourceHeader, 'lines' => $resolvedLines];
    }

    private function isReturnableReceiptStatus(GoodsReceiptNote $header): bool
    {
        $status = $header->status instanceof GoodsReceiptNoteStatus
            ? $header->status
            : GoodsReceiptNoteStatus::from((string) $header->status);

        return in_array($status, [
            GoodsReceiptNoteStatus::Posted,
        ], true);
    }

    private function createManualLine(
        PurchaseReturn $return,
        PurchaseReturnLineData $lineData,
        PurchaseReturnLineValuationData $valuation,
        ?string $headerReason,
        int $lineNumber,
    ): void {
        $return->lines()->create([
            'tenant_id' => $return->tenant_id,
            'organization_unit_id' => $return->organization_unit_id,
            'line_number' => $lineNumber,
            'client_line_key' => $lineData->clientLineKey,
            'item_id' => $lineData->itemId,
            'item_variant_id' => $lineData->itemVariantId,
            'uom_id' => $lineData->uomId,
            'source_line_type' => null,
            'source_line_id' => null,
            'returned_quantity' => $this->math->normalize($lineData->returnedQuantity),
            'source_quantity' => $valuation->sourceQuantity,
            'previously_returned_quantity' => $valuation->previouslyReturnedQuantity,
            'remaining_quantity' => $valuation->remainingQuantity,
            'unit_price' => $valuation->unitPrice,
            'cost_basis' => $valuation->costBasis,
            'base_amount' => $valuation->baseAmount,
            'discount_amount' => $valuation->discountAmount,
            'tax_amount' => $valuation->taxAmount,
            'charge_amount' => $valuation->chargeAmount,
            'line_total' => $valuation->lineTotal,
            'reason' => $lineData->reason ?? $headerReason,
        ]);
    }

    private function createReferencedLine(
        PurchaseReturn $return,
        PurchaseReturnLineData $lineData,
        GoodsReceiptNoteLine $sourceLine,
        PurchaseReturnLineValuationData $valuation,
    ): void {
        $lineNumber = ((int) $return->lines()->max('line_number')) + 1;
        $return->lines()->create([
            'tenant_id' => $return->tenant_id,
            'organization_unit_id' => $return->organization_unit_id,
            'line_number' => $lineNumber,
            'client_line_key' => $lineData->clientLineKey,
            'item_id' => $sourceLine->item_id,
            'item_variant_id' => $sourceLine->item_variant_id,
            'uom_id' => $sourceLine->uom_id,
            'source_line_type' => 'goods_receipt_note_line',
            'source_line_id' => $sourceLine->getKey(),
            'returned_quantity' => $this->math->normalize($lineData->returnedQuantity),
            'source_quantity' => $valuation->sourceQuantity,
            'previously_returned_quantity' => $valuation->previouslyReturnedQuantity,
            'remaining_quantity' => $valuation->remainingQuantity,
            'unit_price' => $valuation->unitPrice,
            'cost_basis' => $valuation->costBasis,
            'base_amount' => $valuation->baseAmount,
            'discount_amount' => $valuation->discountAmount,
            'tax_amount' => $valuation->taxAmount,
            'charge_amount' => $valuation->chargeAmount,
            'line_total' => $valuation->lineTotal,
            'reason' => $lineData->reason,
        ]);
    }

    private function applyValuationToLine(PurchaseReturnLine $line, PurchaseReturnLineValuationData $valuation): void
    {
        $line->source_quantity = $valuation->sourceQuantity;
        $line->previously_returned_quantity = $valuation->previouslyReturnedQuantity;
        $line->remaining_quantity = $valuation->remainingQuantity;
        $line->unit_price = $valuation->unitPrice;
        $line->cost_basis = $valuation->costBasis;
        $line->base_amount = $valuation->baseAmount;
        $line->discount_amount = $valuation->discountAmount;
        $line->tax_amount = $valuation->taxAmount;
        $line->charge_amount = $valuation->chargeAmount;
        $line->line_total = $valuation->lineTotal;
    }

    private function assertPostable(PurchaseReturn $return): void
    {
        if ((bool) $return->approval_required && $return->status !== PurchaseReturnStatus::Approved) {
            throw new InvalidArgumentException('Purchase return must be approved before posting.');
        }
        if (! (bool) $return->approval_required && $return->status !== PurchaseReturnStatus::Draft) {
            throw new InvalidArgumentException('Only draft purchase returns can be posted without approval.');
        }
    }

    /**
     * @return array<int, GoodsReceiptNoteLine>
     */
    private function lockReturnSourcesForPost(PurchaseReturn $return): array
    {
        $sourceLineIds = $return->lines
            ->filter(fn (PurchaseReturnLine $line): bool => $line->source_line_type === 'goods_receipt_note_line')
            ->map(fn (PurchaseReturnLine $line): int => (int) $line->source_line_id)
            ->values()
            ->all();

        if ($sourceLineIds === []) {
            return [];
        }

        return $this->lockReceiptSourcesByLineIds($sourceLineIds);
    }

    /**
     * @param  array<int, GoodsReceiptNoteLine>  $sourceLines
     * @return array{
     *     return: PurchaseReturn,
     *     posted_line_sums: array<int, array{base_amount: string, discount_amount: string, tax_amount: string, charge_amount: string, line_total: string}>,
     *     receipt_lines_by_grn: array<int, Collection<int, GoodsReceiptNoteLine>>,
     *     adjustments_by_grn: array<int, Collection<int, PurchaseHeaderAdjustment>>,
     *     adjustment_allocations: Collection<int, PurchaseReturnAdjustmentAllocation>
     * }
     */
    private function lockReturnContextForPost(PurchaseReturn $return, array $sourceLines): array
    {
        $sourceLineIds = array_keys($sourceLines);
        $postedReturnIds = [];
        if ($sourceLineIds !== []) {
            $postedReturnIds = PurchaseReturnLine::query()
                ->where('source_line_type', 'goods_receipt_note_line')
                ->whereIn('source_line_id', $sourceLineIds)
                ->where('purchase_return_id', '!=', $return->getKey())
                ->whereHas('purchaseReturn', fn ($query) => $query->where('status', PurchaseReturnStatus::Posted->value))
                ->pluck('purchase_return_id')
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $returnIds = array_values(array_unique(array_merge([(int) $return->getKey()], $postedReturnIds)));
        sort($returnIds);

        $lockedReturns = $this->locks->purchaseReturns($returnIds);
        $locked = $lockedReturns->first(fn (PurchaseReturn $candidate): bool => (int) $candidate->getKey() === (int) $return->getKey());
        if (! $locked instanceof PurchaseReturn) {
            throw new InvalidArgumentException('Purchase return was not found.');
        }

        $lockedLines = $this->locks
            ->purchaseReturnLinesForReturns($returnIds)
            ->values();
        $locked->setRelation('lines', $lockedLines
            ->where('purchase_return_id', (int) $locked->getKey())
            ->values());

        $postedReturnIdMap = array_fill_keys($postedReturnIds, true);
        $postedLineSums = $this->sumLockedPostedReturnLines($lockedLines, $postedReturnIdMap, $sourceLineIds);
        $goodsReceiptIds = array_values(array_unique(array_map(
            static fn (GoodsReceiptNoteLine $line): int => (int) $line->goods_receipt_note_id,
            $sourceLines,
        )));
        sort($goodsReceiptIds);

        $receiptLinesByGrn = [];
        foreach ($sourceLines as $sourceLine) {
            $receipt = $sourceLine->goodsReceiptNote;
            if ($receipt instanceof GoodsReceiptNote && $receipt->relationLoaded('lines')) {
                $receiptLinesByGrn[(int) $receipt->getKey()] = $receipt->lines->values();
            }
        }

        $adjustments = $goodsReceiptIds === []
            ? collect()
            : PurchaseHeaderAdjustment::query()
                ->where('source_type', 'goods_receipt_note')
                ->whereIn('source_id', $goodsReceiptIds)
                ->orderBy('source_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        $adjustmentsByGrn = [];
        foreach ($adjustments->groupBy('source_id') as $receiptId => $rows) {
            $adjustmentsByGrn[(int) $receiptId] = $rows->values();
        }

        $adjustmentIds = $adjustments
            ->map(fn (PurchaseHeaderAdjustment $adjustment): int => (int) $adjustment->getKey())
            ->values()
            ->all();
        $adjustmentAllocations = $adjustmentIds === []
            ? collect()
            : PurchaseReturnAdjustmentAllocation::query()
                ->whereIn('purchase_header_adjustment_id', $adjustmentIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

        return [
            'return' => $locked,
            'posted_line_sums' => $postedLineSums,
            'receipt_lines_by_grn' => $receiptLinesByGrn,
            'adjustments_by_grn' => $adjustmentsByGrn,
            'adjustment_allocations' => $adjustmentAllocations,
        ];
    }

    /**
     * @param  Collection<int, PurchaseReturnLine>  $lockedLines
     * @param  array<int, bool>  $postedReturnIds
     * @param  list<int>  $sourceLineIds
     * @return array<int, array{base_amount: string, discount_amount: string, tax_amount: string, charge_amount: string, line_total: string}>
     */
    private function sumLockedPostedReturnLines(Collection $lockedLines, array $postedReturnIds, array $sourceLineIds): array
    {
        $sourceLineIdMap = array_fill_keys($sourceLineIds, true);
        $sums = [];
        foreach ($lockedLines as $line) {
            if (! $line instanceof PurchaseReturnLine
                || $line->source_line_type !== 'goods_receipt_note_line'
                || $line->source_line_id === null
                || ! isset($postedReturnIds[(int) $line->purchase_return_id])
                || ! isset($sourceLineIdMap[(int) $line->source_line_id])
            ) {
                continue;
            }

            $sourceLineId = (int) $line->source_line_id;
            $sums[$sourceLineId] ??= [
                'base_amount' => '0.000000',
                'discount_amount' => '0.000000',
                'tax_amount' => '0.000000',
                'charge_amount' => '0.000000',
                'line_total' => '0.000000',
            ];
            foreach (array_keys($sums[$sourceLineId]) as $field) {
                $sums[$sourceLineId][$field] = $this->math->add($sums[$sourceLineId][$field], (string) $line->{$field});
            }
        }

        return $sums;
    }

    /**
     * @param  array<int, GoodsReceiptNoteLine>  $sourceLines
     */
    private function assertLockedReturnSources(PurchaseReturn $return, array $sourceLines): void
    {
        if ($sourceLines === []) {
            return;
        }

        $resolved = [];
        $seen = [];
        foreach ($return->lines as $line) {
            if ($line->source_line_type !== 'goods_receipt_note_line') {
                continue;
            }
            if (isset($seen[(int) $line->source_line_id])) {
                throw new InvalidArgumentException('Duplicate goods receipt note line selected for return.');
            }
            $seen[(int) $line->source_line_id] = true;

            $sourceLine = $sourceLines[(int) $line->source_line_id] ?? null;
            if (! $sourceLine instanceof GoodsReceiptNoteLine || ! $sourceLine->goodsReceiptNote instanceof GoodsReceiptNote) {
                throw new InvalidArgumentException('Selected goods receipt note line was not found for this return.');
            }
            if (! $this->isReturnableReceiptStatus($sourceLine->goodsReceiptNote)
                || $sourceLine->status === GoodsReceiptNoteLineStatus::Reversed) {
                throw new InvalidArgumentException('Purchase return source goods receipt is no longer returnable.');
            }
            if ((int) $sourceLine->tenant_id !== (int) $return->tenant_id
                || $sourceLine->organization_unit_id !== $return->organization_unit_id
                || (int) $sourceLine->goods_receipt_note_id !== (int) $return->source_id
                || (int) $sourceLine->goodsReceiptNote->supplier_id !== (int) $return->supplier_id
                || (int) $sourceLine->goodsReceiptNote->warehouse_id !== (int) $return->warehouse_id
            ) {
                throw new InvalidArgumentException('Purchase return source line is outside the return scope.');
            }
            if ($sourceLine->purchaseOrderLine instanceof PurchaseOrderLine
                && $sourceLine->purchaseOrderLine->relationLoaded('order')
                && in_array($sourceLine->purchaseOrderLine->order?->status, [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled], true)) {
                throw new InvalidArgumentException('Purchase returns cannot be posted after the source purchase order is closed or cancelled.');
            }

            $resolved[(int) $sourceLine->getKey()] = $sourceLine;
        }
    }

    /**
     * @param  list<int>  $sourceLineIds
     * @return array<int, GoodsReceiptNoteLine>
     */
    private function lockReceiptSourcesByLineIds(array $sourceLineIds): array
    {
        /** @var Collection<int, GoodsReceiptNoteLine> $snapshots */
        $snapshots = GoodsReceiptNoteLine::query()
            ->with(['goodsReceiptNote', 'purchaseOrderLine'])
            ->whereIn('id', $sourceLineIds)
            ->get();

        if ($snapshots->count() !== count(array_unique($sourceLineIds))) {
            throw new InvalidArgumentException('One or more selected goods receipt note lines were not found.');
        }

        $purchaseOrderLineIds = $snapshots
            ->map(fn (GoodsReceiptNoteLine $line): ?int => $line->purchase_order_line_id === null ? null : (int) $line->purchase_order_line_id)
            ->filter()
            ->values()
            ->all();
        $purchaseOrderIds = $snapshots
            ->map(fn (GoodsReceiptNoteLine $line): ?int => $line->purchaseOrderLine instanceof PurchaseOrderLine ? (int) $line->purchaseOrderLine->purchase_order_id : null)
            ->filter()
            ->values()
            ->all();
        $goodsReceiptIds = $snapshots
            ->map(fn (GoodsReceiptNoteLine $line): int => (int) $line->goods_receipt_note_id)
            ->values()
            ->all();

        $lockedOrders = $this->locks->purchaseOrders($purchaseOrderIds)
            ->keyBy(fn ($order): int => (int) $order->getKey());
        $lockedOrderLines = $this->locks->purchaseOrderLines($purchaseOrderLineIds)
            ->keyBy(fn (PurchaseOrderLine $line): int => (int) $line->getKey());
        $lockedReceipts = $this->locks->goodsReceipts($goodsReceiptIds)
            ->keyBy(fn (GoodsReceiptNote $receipt): int => (int) $receipt->getKey());
        $allReceiptLineIds = GoodsReceiptNoteLine::query()
            ->whereIn('goods_receipt_note_id', array_values(array_unique($goodsReceiptIds)))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $lockedLines = $this->locks->goodsReceiptLines($allReceiptLineIds)
            ->keyBy(fn (GoodsReceiptNoteLine $line): int => (int) $line->getKey());

        foreach ($lockedReceipts as $receipt) {
            $receipt->setRelation('lines', $lockedLines
                ->where('goods_receipt_note_id', (int) $receipt->getKey())
                ->values());
        }

        $resolved = [];
        foreach ($lockedLines as $line) {
            $receipt = $lockedReceipts->get((int) $line->goods_receipt_note_id);
            if ($receipt instanceof GoodsReceiptNote) {
                $line->setRelation('goodsReceiptNote', $receipt);
            }

            if ($line->purchase_order_line_id !== null) {
                $orderLine = $lockedOrderLines->get((int) $line->purchase_order_line_id);
                if ($orderLine instanceof PurchaseOrderLine) {
                    $order = $lockedOrders->get((int) $orderLine->purchase_order_id);
                    if ($order !== null) {
                        $orderLine->setRelation('order', $order);
                    }
                    $line->setRelation('purchaseOrderLine', $orderLine);
                }
            }

            if (in_array((int) $line->getKey(), $sourceLineIds, true)) {
                $resolved[(int) $line->getKey()] = $line;
            }
        }

        return $resolved;
    }

    private function validateManualSupplierReturn(CreatePurchaseReturnData $data): void
    {
        if ($data->supplierId === null) {
            throw new InvalidArgumentException('Unreferenced supplier return requires supplier.');
        }
        if ($data->supplierType !== null && $data->supplierType !== 'supplier') {
            throw new InvalidArgumentException('Unreferenced supplier return supplier type is not supported.');
        }
        if (trim((string) $data->reason) === '') {
            throw new InvalidArgumentException('Unreferenced supplier return requires reason.');
        }

        $this->validator->supplier($data->tenantId, $data->organizationUnitId, $data->supplierId, 'supplier_id');
        if ($data->costBasis !== null) {
            $this->validator->assertNonNegative($data->costBasis, 'Unreferenced supplier return cost basis cannot be negative.');
        }

        $seenClientLineKeys = [];
        foreach ($data->lines as $index => $line) {
            if ($line->itemId === null || $line->uomId === null || $line->costBasis === null) {
                throw new InvalidArgumentException('Unreferenced supplier return lines require item, UOM, and cost basis.');
            }
            $clientLineKey = trim((string) $line->clientLineKey);
            if ($clientLineKey === '') {
                throw new InvalidArgumentException('Unreferenced supplier return lines require a client line key.');
            }
            if (isset($seenClientLineKeys[$clientLineKey])) {
                throw new InvalidArgumentException('Duplicate manual return line key.');
            }
            $seenClientLineKeys[$clientLineKey] = true;
            $this->validator->assertPositiveQuantity($line->returnedQuantity);
            $this->validator->assertNonNegative($line->costBasis, 'Unreferenced supplier return line cost basis cannot be negative.');
            $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId, "lines.{$index}.item_id");
            $this->validator->uom($data->tenantId, $data->organizationUnitId, $line->uomId, "lines.{$index}.uom_id");
        }
    }

    private function firstManualLineCostBasis(CreatePurchaseReturnData $data): ?string
    {
        foreach ($data->lines as $line) {
            if ($line instanceof PurchaseReturnLineData && $line->costBasis !== null) {
                return $line->costBasis;
            }
        }

        return null;
    }
}
