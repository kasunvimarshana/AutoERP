<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoicePostingLineData;
use Modules\Invoice\DTOs\InvoicePostingPlanData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Services\InvoiceCalculationService;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseInvoicePostingPlanFactory
{
    private const GOODS_RECEIPT_LINE = 'goods_receipt_note_line';

    private const PURCHASE_ADJUSTMENT = 'purchase_header_adjustment';

    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCalculationService $calculations,
        private readonly PurchaseAdjustmentAllocationService $adjustmentAllocations,
        private readonly PurchaseAdjustmentPolicyResolver $adjustmentPolicies,
    ) {}

    public function build(CreateInvoiceData $data): InvoicePostingPlanData
    {
        if ($data->direction !== InvoiceDirection::Inbound) {
            throw new InvalidArgumentException('Purchase invoice posting plans require an inbound invoice.');
        }

        $lines = [];
        foreach ($data->lines as $line) {
            $item = $line->itemId === null ? null : Item::query()->findOrFail($line->itemId);
            $role = $item instanceof Item && $this->affectsStock($item)
                ? $this->stockRole($data, $line->sourceLineType, $line->sourceLineId, $line->itemId)
                : FinanceAccountRoleCode::Expense;
            $amount = $this->math->add(
                $this->math->sub(
                    $this->math->mul($line->quantity, $line->unitPrice),
                    $line->discountAmount,
                ),
                $line->chargeAmount,
            );
            $this->append(
                $lines,
                $role,
                $amount,
                AdjustmentEffect::Increase,
                $line->description,
                $line->sourceLineType,
                $line->sourceLineId,
            );
            $this->append(
                $lines,
                FinanceAccountRoleCode::TaxReceivable,
                $line->taxAmount,
                AdjustmentEffect::Increase,
                'Input tax',
                $line->sourceLineType,
                $line->sourceLineId,
            );
        }

        $withholding = self::ZERO;
        foreach ($data->adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustmentData) {
                throw new InvalidArgumentException('Purchase invoice adjustments are invalid.');
            }
            $amount = $this->math->normalize($adjustment->amount);
            if ($this->math->isZero($amount)) {
                continue;
            }
            if ($adjustment->adjustmentType === AdjustmentType::Withholding) {
                if ($adjustment->effect !== AdjustmentEffect::Decrease) {
                    throw new InvalidArgumentException('Purchase invoice withholding must reduce the supplier payable.');
                }
                $withholding = $this->math->add($withholding, $amount);

                continue;
            }
            if ($adjustment->adjustmentType === AdjustmentType::Tax) {
                $this->append(
                    $lines,
                    FinanceAccountRoleCode::TaxReceivable,
                    $amount,
                    $adjustment->effect,
                    $adjustment->name,
                );

                continue;
            }

            $this->appendAdjustment($lines, $adjustment, $amount);
        }

        $calculation = $this->calculations->calculate($data);
        $this->append(
            $lines,
            FinanceAccountRoleCode::Payable,
            $calculation->grandTotal,
            AdjustmentEffect::Decrease,
            'Supplier payable',
        );
        $this->append(
            $lines,
            FinanceAccountRoleCode::WithholdingPayable,
            $withholding,
            AdjustmentEffect::Decrease,
            'Withholding payable',
        );

        return new InvoicePostingPlanData(
            profile: FinancePostingProfileCode::PurchaseInvoice,
            postingDate: $data->invoiceDate,
            lines: $lines,
            description: 'Supplier invoice posting',
        );
    }

    /** @param list<InvoicePostingLineData> $lines */
    private function appendAdjustment(array &$lines, InvoiceAdjustmentData $adjustment, string $amount): void
    {
        $source = $this->sourceAdjustment($adjustment);
        if (! $source instanceof PurchaseHeaderAdjustment) {
            $this->append(
                $lines,
                FinanceAccountRoleCode::Expense,
                $amount,
                $adjustment->effect,
                $adjustment->name,
            );

            return;
        }

        $roleValue = $this->adjustmentPolicies->invoiceProfileKeyFor($source);
        if ($roleValue === null) {
            throw new InvalidArgumentException('Purchase adjustment cannot be mapped to a Finance posting role.');
        }
        $role = FinanceAccountRoleCode::tryFrom($roleValue);
        if (! $role instanceof FinanceAccountRoleCode) {
            throw new InvalidArgumentException('Purchase adjustment Finance role is not part of the canonical catalogue.');
        }

        if (! $this->adjustmentAllocations->isCapitalizable($source)) {
            $this->append($lines, $role, $amount, $adjustment->effect, $adjustment->name);

            return;
        }

        $residual = $this->adjustmentAllocations->invoiceResidualAmount($source, $amount);
        $recognizedAtReceipt = $this->math->sub($amount, $residual);
        $this->append(
            $lines,
            FinanceAccountRoleCode::GoodsReceivedNotInvoiced,
            $recognizedAtReceipt,
            $adjustment->effect,
            $adjustment->name.' GRNI clearing',
        );
        $this->append($lines, $role, $residual, $adjustment->effect, $adjustment->name);
    }

    private function sourceAdjustment(InvoiceAdjustmentData $adjustment): ?PurchaseHeaderAdjustment
    {
        if ($adjustment->sourceAdjustmentType !== self::PURCHASE_ADJUSTMENT
            || $adjustment->sourceAdjustmentId === null) {
            return null;
        }

        return PurchaseHeaderAdjustment::query()->find($adjustment->sourceAdjustmentId);
    }

    private function stockRole(
        CreateInvoiceData $data,
        ?string $sourceLineType,
        ?int $sourceLineId,
        ?int $itemId,
    ): FinanceAccountRoleCode {
        if ($sourceLineType !== self::GOODS_RECEIPT_LINE || $sourceLineId === null) {
            throw new InvalidArgumentException(
                'Stockable purchase invoice lines must reference a posted goods receipt line.',
            );
        }

        $receiptLine = GoodsReceiptNoteLine::query()
            ->with('goodsReceiptNote')
            ->findOrFail($sourceLineId);
        $receipt = $receiptLine->goodsReceiptNote;
        $hasPostedInventoryReceipt = $receiptLine->inventory_movement_id !== null
            || $receiptLine->batchAllocations()->whereNotNull('inventory_movement_id')->exists();
        if ((int) $receiptLine->tenant_id !== $data->tenantId
            || $receiptLine->organization_unit_id !== $data->organizationUnitId
            || (int) $receiptLine->item_id !== (int) $itemId
            || $receiptLine->status !== GoodsReceiptNoteLineStatus::Posted
            || ! $receipt instanceof GoodsReceiptNote
            || $receipt->status !== GoodsReceiptNoteStatus::Posted
            || ! $hasPostedInventoryReceipt) {
            throw new InvalidArgumentException(
                'Stockable purchase invoice line is not backed by a posted inventory receipt.',
            );
        }

        return FinanceAccountRoleCode::GoodsReceivedNotInvoiced;
    }

    private function affectsStock(Item $item): bool
    {
        return (bool) $item->is_stockable
            && ! in_array($item->item_type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true);
    }

    /** @param list<InvoicePostingLineData> $lines */
    private function append(
        array &$lines,
        FinanceAccountRoleCode $role,
        string $amount,
        AdjustmentEffect $effect,
        string $description,
        ?string $sourceLineType = null,
        ?int $sourceLineId = null,
    ): void {
        $amount = $this->math->normalize($amount);
        if ($this->math->isZero($amount)) {
            return;
        }
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException('Purchase invoice posting amount cannot be negative.');
        }

        $lines[] = new InvoicePostingLineData(
            role: $role,
            debit: $effect === AdjustmentEffect::Increase ? $amount : self::ZERO,
            credit: $effect === AdjustmentEffect::Decrease ? $amount : self::ZERO,
            description: $description,
            sourceLineType: $sourceLineType,
            sourceLineId: $sourceLineId,
        );
    }
}
