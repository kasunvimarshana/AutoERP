<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseAdjustmentAllocation;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseAdjustmentAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseAdjustmentCatalogueService $catalogue,
    ) {}

    public function receiptShare(PurchaseHeaderAdjustment $adjustment, PurchaseOrder $order, GoodsReceiptNote $grn): string
    {
        $amount = $this->math->normalize((string) $adjustment->amount);
        if ($this->math->isZero($amount)) {
            return '0.000000';
        }

        $method = $adjustment->allocation_method instanceof PurchaseAdjustmentAllocationMethod
            ? $adjustment->allocation_method
            : PurchaseAdjustmentAllocationMethod::from((string) $adjustment->allocation_method);

        if ($method === PurchaseAdjustmentAllocationMethod::Manual) {
            if ($this->receiptCompletesOrder($order, $grn)) {
                return $amount;
            }

            throw ValidationException::withMessages([
                'adjustments' => ['Manual purchase adjustment allocations require explicit line allocations for partial receipts.'],
            ]);
        }

        if ($method === PurchaseAdjustmentAllocationMethod::FirstInvoice) {
            return $this->math->isZero($this->previouslyAllocated($adjustment))
                ? $amount
                : '0.000000';
        }

        if ($method === PurchaseAdjustmentAllocationMethod::LastInvoice) {
            return $this->receiptCompletesOrder($order, $grn)
                ? $this->math->sub($amount, $this->previouslyAllocated($adjustment))
                : '0.000000';
        }

        $totalBasis = $this->orderBasis($order, $adjustment);
        if ($this->math->isZero($totalBasis)) {
            throw ValidationException::withMessages([
                'adjustments' => ['Source subtotal must be greater than zero for proportional purchase adjustment allocation.'],
            ]);
        }

        return $this->math->mul(
            $amount,
            $this->math->div($this->receiptBasis($grn, $adjustment), $totalBasis, 12),
        );
    }

    public function recordReceiptAllocation(
        PurchaseHeaderAdjustment $origin,
        PurchaseHeaderAdjustment $target,
        GoodsReceiptNote $grn,
        string $allocatedAmount,
    ): PurchaseAdjustmentAllocation {
        $allocatedAmount = $this->math->normalize($allocatedAmount);
        $previouslyAllocated = $this->previouslyAllocated($origin);
        $remaining = $this->math->sub(
            (string) $origin->amount,
            $this->math->add($previouslyAllocated, $allocatedAmount),
        );
        $recognized = $this->recognizesAtGoodsReceipt($target) ? $allocatedAmount : '0.000000';

        return PurchaseAdjustmentAllocation::query()->create([
            'tenant_id' => (int) $origin->tenant_id,
            'organization_unit_id' => $origin->organization_unit_id,
            'purchase_header_adjustment_id' => (int) $origin->getKey(),
            'target_purchase_header_adjustment_id' => (int) $target->getKey(),
            'stage' => 'grn_recognition',
            'source_type' => $origin->source_type,
            'source_id' => $origin->source_id,
            'target_type' => 'goods_receipt_note',
            'target_id' => (int) $grn->getKey(),
            'allocation_method' => $this->enumValue($target->allocation_method),
            'calculation_base' => $this->enumValue($target->calculation_base),
            'basis_amount' => $this->receiptBasis($grn, $origin),
            'source_amount' => (string) $origin->amount,
            'signed_amount' => $this->signedAmount($target, $allocatedAmount),
            'allocated_amount' => $allocatedAmount,
            'recognized_at_grn_amount' => $recognized,
            'recognized_at_invoice_amount' => '0.000000',
            'remaining_amount' => $remaining,
            'cost_treatment' => $target->cost_treatment,
            'tax_treatment' => $target->tax_treatment,
            'finance_posting_profile_id' => $target->finance_posting_profile_id,
            'finance_account_id' => $target->finance_account_id,
            'provenance' => [
                'origin_adjustment_id' => (int) $origin->getKey(),
                'target_adjustment_id' => (int) $target->getKey(),
                'target_document' => 'goods_receipt_note',
                'target_document_id' => (int) $grn->getKey(),
            ],
        ]);
    }

    public function recordInvoiceAllocation(
        PurchaseHeaderAdjustment $source,
        InvoiceAdjustment $invoiceAdjustment,
        string $allocatedAmount,
        string $recognizedAmount,
    ): PurchaseAdjustmentAllocation {
        $origin = $this->origin($source);
        $allocatedAmount = $this->math->normalize($allocatedAmount);
        $recognizedAmount = $this->math->normalize($recognizedAmount);
        $remaining = $this->math->sub(
            (string) $origin->amount,
            $this->math->add($this->previouslyAllocated($origin), $allocatedAmount),
        );

        return PurchaseAdjustmentAllocation::query()->create([
            'tenant_id' => (int) $origin->tenant_id,
            'organization_unit_id' => $origin->organization_unit_id,
            'purchase_header_adjustment_id' => (int) $origin->getKey(),
            'target_purchase_header_adjustment_id' => (int) $source->getKey(),
            'stage' => 'invoice_recognition',
            'source_type' => $source->source_type,
            'source_id' => $source->source_id,
            'target_type' => 'purchase_invoice',
            'target_id' => (int) $invoiceAdjustment->invoice_id,
            'allocation_method' => $this->enumValue($source->allocation_method),
            'calculation_base' => $this->enumValue($source->calculation_base),
            'basis_amount' => '0.000000',
            'source_amount' => (string) $origin->amount,
            'signed_amount' => $this->signedAmount($source, $allocatedAmount),
            'allocated_amount' => $allocatedAmount,
            'recognized_at_grn_amount' => '0.000000',
            'recognized_at_invoice_amount' => $recognizedAmount,
            'remaining_amount' => $remaining,
            'cost_treatment' => $source->cost_treatment,
            'tax_treatment' => $source->tax_treatment,
            'finance_posting_profile_id' => $source->finance_posting_profile_id,
            'finance_account_id' => $source->finance_account_id,
            'provenance' => [
                'origin_adjustment_id' => (int) $origin->getKey(),
                'source_adjustment_id' => (int) $source->getKey(),
                'invoice_adjustment_id' => (int) $invoiceAdjustment->getKey(),
                'target_document' => 'purchase_invoice',
                'target_document_id' => (int) $invoiceAdjustment->invoice_id,
            ],
        ]);
    }

    public function origin(PurchaseHeaderAdjustment $adjustment): PurchaseHeaderAdjustment
    {
        if ($adjustment->origin_purchase_header_adjustment_id !== null) {
            $origin = $adjustment->relationLoaded('originAdjustment')
                ? $adjustment->originAdjustment
                : $adjustment->originAdjustment()->first();

            if ($origin instanceof PurchaseHeaderAdjustment) {
                return $origin;
            }
        }

        return $adjustment;
    }

    public function recognizesAtGoodsReceipt(PurchaseHeaderAdjustment $adjustment): bool
    {
        if ($this->math->isZero((string) $adjustment->amount)) {
            return false;
        }

        $type = $adjustment->adjustment_type instanceof PurchaseAdjustmentType
            ? $adjustment->adjustment_type
            : PurchaseAdjustmentType::from((string) $adjustment->adjustment_type);

        if (in_array($type, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true)) {
            return false;
        }

        return $this->isCapitalizableCostTreatment((string) $adjustment->cost_treatment);
    }

    public function isCapitalizable(PurchaseHeaderAdjustment $adjustment): bool
    {
        return $this->recognizesAtGoodsReceipt($adjustment);
    }

    public function receiveOnlySupported(PurchaseHeaderAdjustmentData $data): bool
    {
        $defaults = $this->catalogue->defaultsFor($data->adjustmentType);
        $costTreatment = (string) ($data->costTreatment ?? $defaults['cost_treatment']);
        $taxTreatment = (string) ($data->taxTreatment ?? $defaults['tax_treatment']);

        if ($data->allocationMethod !== PurchaseAdjustmentAllocationMethod::Proportional) {
            return false;
        }

        if (in_array($data->adjustmentType, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true)) {
            return false;
        }

        if (! in_array($taxTreatment, ['', 'none'], true)) {
            return false;
        }

        return $this->isCapitalizableCostTreatment($costTreatment);
    }

    public function recognizedAtGoodsReceiptFor(PurchaseHeaderAdjustment $adjustment): string
    {
        $origin = $this->origin($adjustment);

        return $this->math->normalize((string) PurchaseAdjustmentAllocation::query()
            ->where('purchase_header_adjustment_id', (int) $origin->getKey())
            ->where('stage', 'grn_recognition')
            ->where(function ($query) use ($adjustment): void {
                $query->where('target_purchase_header_adjustment_id', (int) $adjustment->getKey())
                    ->orWhere(function ($scope) use ($adjustment): void {
                        $scope->whereNull('target_purchase_header_adjustment_id')
                            ->where('target_type', $adjustment->source_type)
                            ->where('target_id', $adjustment->source_id);
                    });
            })
            ->sum('recognized_at_grn_amount'));
    }

    public function financeProfileKey(PurchaseHeaderAdjustment $adjustment): string
    {
        $treatment = mb_strtolower(trim((string) $adjustment->cost_treatment));

        return match ($treatment) {
            'input_tax', 'recoverable_tax' => 'tax_receivable',
            'withholding' => 'payable',
            default => 'expense',
        };
    }

    private function isCapitalizableCostTreatment(string $costTreatment): bool
    {
        $treatment = mb_strtolower(trim($costTreatment));

        return in_array($treatment, [
            'landed_cost',
            'inventory_cost_reduction',
        ], true)
            || str_contains($treatment, 'inventory')
            || str_contains($treatment, 'landed');
    }

    private function previouslyAllocated(PurchaseHeaderAdjustment $adjustment): string
    {
        $origin = $this->origin($adjustment);

        return $this->math->normalize((string) PurchaseAdjustmentAllocation::query()
            ->where('purchase_header_adjustment_id', (int) $origin->getKey())
            ->sum('allocated_amount'));
    }

    private function receiptCompletesOrder(PurchaseOrder $order, GoodsReceiptNote $grn): bool
    {
        $receiptByLine = [];
        $grn->loadMissing('lines');
        foreach ($grn->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine || $line->purchase_order_line_id === null) {
                continue;
            }
            $receiptByLine[(int) $line->purchase_order_line_id] = $this->math->add(
                $receiptByLine[(int) $line->purchase_order_line_id] ?? '0.000000',
                (string) $line->accepted_quantity,
            );
        }

        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            if ($this->math->compare($receiptByLine[(int) $line->getKey()] ?? '0.000000', (string) $line->ordered_quantity) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function orderBasis(PurchaseOrder $order, PurchaseHeaderAdjustment $adjustment): string
    {
        $basis = '0.000000';
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if ($line instanceof PurchaseOrderLine) {
                $basis = $this->math->add($basis, $this->lineBasis($line, $adjustment));
            }
        }

        return $basis;
    }

    private function receiptBasis(GoodsReceiptNote $grn, PurchaseHeaderAdjustment $adjustment): string
    {
        $basis = '0.000000';
        $grn->loadMissing('lines.purchaseOrderLine');
        foreach ($grn->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine || ! $line->purchaseOrderLine instanceof PurchaseOrderLine) {
                continue;
            }
            $sourceLine = $line->purchaseOrderLine;
            $lineBasis = $this->lineBasis($sourceLine, $adjustment);
            $ratio = $this->math->isZero((string) $sourceLine->ordered_quantity)
                ? '0.000000'
                : $this->math->div((string) $line->accepted_quantity, (string) $sourceLine->ordered_quantity, 12);
            $basis = $this->math->add($basis, $this->math->mul($lineBasis, $ratio));
        }

        return $basis;
    }

    private function lineBasis(PurchaseOrderLine $line, PurchaseHeaderAdjustment $adjustment): string
    {
        $base = $adjustment->calculation_base instanceof PurchaseAdjustmentCalculationBase
            ? $adjustment->calculation_base
            : PurchaseAdjustmentCalculationBase::from((string) $adjustment->calculation_base);

        $subtotal = (string) $line->line_subtotal;

        return match ($base) {
            PurchaseAdjustmentCalculationBase::Subtotal => $subtotal,
            PurchaseAdjustmentCalculationBase::SubtotalAfterLineDiscount => $this->math->sub($subtotal, (string) $line->discount_amount),
            PurchaseAdjustmentCalculationBase::SubtotalAfterLineAdjustments => $this->math->add(
                $this->math->sub($subtotal, (string) $line->discount_amount),
                (string) $line->charge_amount,
            ),
        };
    }

    private function signedAmount(PurchaseHeaderAdjustment $adjustment, string $amount): string
    {
        $effect = $adjustment->effect instanceof PurchaseAdjustmentEffect
            ? $adjustment->effect
            : PurchaseAdjustmentEffect::from((string) $adjustment->effect);

        return $effect === PurchaseAdjustmentEffect::Decrease
            ? '-'.$this->math->normalize($amount)
            : $this->math->normalize($amount);
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }
}
