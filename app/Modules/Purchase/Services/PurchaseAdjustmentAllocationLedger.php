<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseAdjustmentAllocation;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseAdjustmentAllocationLedger
{
    public function __construct(private readonly DecimalMath $math) {}

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

    /**
     * @param  list<array{client_line_key?: string|null, purchase_order_line_id?: int|null, amount: string}>  $allocations
     */
    public function recordManualPlan(PurchaseHeaderAdjustment $origin, PurchaseOrder $order, array $allocations, string $fieldPrefix): void
    {
        if (! (bool) $origin->is_allocatable) {
            throw ValidationException::withMessages(["{$fieldPrefix}.is_allocatable" => ['Manual allocation requires an allocatable adjustment.']]);
        }
        if ($allocations === []) {
            throw ValidationException::withMessages(["{$fieldPrefix}.allocations" => ['Manual allocation requires explicit line allocations.']]);
        }

        $order->loadMissing('lines');
        $linesByClientKey = [];
        $linesById = [];
        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            $linesById[(int) $line->getKey()] = $line;
        }
        foreach ($allocations as $row) {
            if (isset($row['purchase_order_line_id'])) {
                continue;
            }
            if (($row['client_line_key'] ?? null) === null) {
                throw ValidationException::withMessages(["{$fieldPrefix}.allocations" => ['Manual allocation rows must reference a purchase line.']]);
            }
        }

        $seen = [];
        $total = '0.000000';
        foreach ($allocations as $index => $row) {
            $line = null;
            if (isset($row['purchase_order_line_id'])) {
                $line = $linesById[(int) $row['purchase_order_line_id']] ?? null;
            } else {
                $clientKey = (string) $row['client_line_key'];
                if (! isset($linesByClientKey[$clientKey])) {
                    $lineNumber = $this->lineNumberFromClientKey($clientKey);
                    $line = $lineNumber === null ? null : $order->lines->firstWhere('line_number', $lineNumber);
                }
            }

            if (! $line instanceof PurchaseOrderLine) {
                throw ValidationException::withMessages(["{$fieldPrefix}.allocations.{$index}.client_line_key" => ['Manual allocation references an unknown purchase line.']]);
            }
            $lineId = (int) $line->getKey();
            if (isset($seen[$lineId])) {
                throw ValidationException::withMessages(["{$fieldPrefix}.allocations.{$index}.client_line_key" => ['Manual allocation cannot reference the same purchase line more than once.']]);
            }
            $seen[$lineId] = true;

            $amount = $this->math->normalize((string) $row['amount']);
            if ($this->math->isNegative($amount)) {
                throw ValidationException::withMessages(["{$fieldPrefix}.allocations.{$index}.amount" => ['Manual allocation amount cannot be negative.']]);
            }
            $total = $this->math->add($total, $amount);

            $this->createEntry($origin, null, [
                'stage' => 'manual_plan',
                'event_type' => 'manual_plan',
                'target_type' => 'purchase_order',
                'target_id' => (int) $order->getKey(),
                'target_line_type' => 'purchase_order_line',
                'target_line_id' => $lineId,
                'allocated_amount' => $amount,
                'recognized_at_grn_amount' => '0.000000',
                'recognized_at_invoice_amount' => '0.000000',
                'basis_amount' => (string) $line->line_subtotal,
                'correlation_key' => $this->correlationKey($origin, 'manual_plan', 'purchase_order_line', $lineId, null),
                'provenance' => ['manual_plan' => true, 'purchase_order_line_id' => $lineId],
            ], updateSummary: false);
        }

        if ($this->math->compare($total, (string) $origin->amount) !== 0) {
            throw ValidationException::withMessages(["{$fieldPrefix}.allocations" => ['Manual allocation total must equal the adjustment amount.']]);
        }
    }

    public function manualPlanAmounts(PurchaseHeaderAdjustment $adjustment): array
    {
        $origin = $this->origin($adjustment);

        return PurchaseAdjustmentAllocation::query()
            ->where('purchase_header_adjustment_id', (int) $origin->getKey())
            ->where('stage', 'manual_plan')
            ->where('entry_type', 'allocation')
            ->orderBy('target_line_id')
            ->get(['target_line_id', 'allocated_amount'])
            ->mapWithKeys(fn (PurchaseAdjustmentAllocation $row): array => [(int) $row->target_line_id => (string) $row->allocated_amount])
            ->all();
    }

    public function recordReceiptAllocation(PurchaseHeaderAdjustment $origin, PurchaseHeaderAdjustment $target, GoodsReceiptNote $grn, string $allocatedAmount, string $recognizedAmount, string $basisAmount): ?PurchaseAdjustmentAllocation
    {
        $allocatedAmount = $this->math->normalize($allocatedAmount);
        $recognizedAmount = $this->math->normalize($recognizedAmount);
        if ($this->math->isZero($allocatedAmount) && $this->math->isZero($recognizedAmount)) {
            return null;
        }

        return $this->createEntry($origin, $target, [
            'stage' => 'grn_recognition',
            'event_type' => 'goods_receipt_post',
            'source_type' => $origin->source_type,
            'source_id' => $origin->source_id,
            'target_type' => 'goods_receipt_note',
            'target_id' => (int) $grn->getKey(),
            'allocated_amount' => $allocatedAmount,
            'recognized_at_grn_amount' => $recognizedAmount,
            'recognized_at_invoice_amount' => '0.000000',
            'basis_amount' => $basisAmount,
            'correlation_key' => $this->correlationKey($origin, 'grn_recognition', 'goods_receipt_note', (int) $grn->getKey(), (int) $target->getKey()),
            'provenance' => [
                'origin_adjustment_id' => (int) $origin->getKey(),
                'target_adjustment_id' => (int) $target->getKey(),
                'target_document' => 'goods_receipt_note',
                'target_document_id' => (int) $grn->getKey(),
            ],
        ]);
    }

    public function recordInvoiceAllocation(PurchaseHeaderAdjustment $source, InvoiceAdjustment $invoiceAdjustment, string $allocatedAmount, string $recognizedAmount): ?PurchaseAdjustmentAllocation
    {
        $origin = $this->origin($source);
        $allocatedAmount = $this->math->normalize($allocatedAmount);
        $recognizedAmount = $this->math->normalize($recognizedAmount);
        if ($this->math->isZero($allocatedAmount) && $this->math->isZero($recognizedAmount)) {
            return null;
        }

        return $this->createEntry($origin, $source, [
            'stage' => 'invoice_recognition',
            'event_type' => 'supplier_invoice_post',
            'source_type' => $source->source_type,
            'source_id' => $source->source_id,
            'target_type' => 'purchase_invoice',
            'target_id' => (int) $invoiceAdjustment->invoice_id,
            'target_line_type' => 'invoice_adjustment',
            'target_line_id' => (int) $invoiceAdjustment->getKey(),
            'allocated_amount' => $allocatedAmount,
            'recognized_at_grn_amount' => '0.000000',
            'recognized_at_invoice_amount' => $recognizedAmount,
            'basis_amount' => '0.000000',
            'correlation_key' => $this->correlationKey($origin, 'invoice_recognition', 'invoice_adjustment', (int) $invoiceAdjustment->getKey(), (int) $source->getKey()),
            'provenance' => [
                'origin_adjustment_id' => (int) $origin->getKey(),
                'source_adjustment_id' => (int) $source->getKey(),
                'invoice_adjustment_id' => (int) $invoiceAdjustment->getKey(),
                'target_document' => 'purchase_invoice',
                'target_document_id' => (int) $invoiceAdjustment->invoice_id,
            ],
        ]);
    }

    public function effectiveRecognizedForInvoiceAdjustment(PurchaseHeaderAdjustment $source, InvoiceAdjustment $invoiceAdjustment): string
    {
        $origin = $this->origin($source);
        $base = PurchaseAdjustmentAllocation::query()
            ->where('purchase_header_adjustment_id', (int) $origin->getKey())
            ->where('target_purchase_header_adjustment_id', (int) $source->getKey())
            ->where('stage', 'invoice_recognition')
            ->where('target_type', 'purchase_invoice')
            ->where('target_id', (int) $invoiceAdjustment->invoice_id)
            ->where('target_line_type', 'invoice_adjustment')
            ->where('target_line_id', (int) $invoiceAdjustment->getKey());

        $allocated = (clone $base)->where('entry_type', 'allocation')->sum('recognized_at_invoice_amount');
        $reversed = (clone $base)->where('entry_type', 'reversal')->sum('recognized_at_invoice_amount');

        return $this->math->sub($this->math->normalize((string) $allocated), $this->math->normalize((string) $reversed));
    }

    public function reverseForTarget(string $targetType, int $targetId, string $eventType): void
    {
        $rows = PurchaseAdjustmentAllocation::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('entry_type', 'allocation')
            ->where('stage', '!=', 'manual_plan')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($rows as $row) {
            if (! $row instanceof PurchaseAdjustmentAllocation) {
                continue;
            }
            $alreadyReversed = PurchaseAdjustmentAllocation::query()
                ->where('reversal_of_id', (int) $row->getKey())
                ->where('entry_type', 'reversal')
                ->lockForUpdate()
                ->exists();
            if ($alreadyReversed) {
                continue;
            }

            $origin = $row->sourceAdjustment()->lockForUpdate()->first();
            if (! $origin instanceof PurchaseHeaderAdjustment) {
                continue;
            }

            PurchaseAdjustmentAllocation::query()->create([
                'tenant_id' => (int) $row->tenant_id,
                'organization_unit_id' => $row->organization_unit_id,
                'purchase_header_adjustment_id' => (int) $row->purchase_header_adjustment_id,
                'target_purchase_header_adjustment_id' => $row->target_purchase_header_adjustment_id,
                'stage' => (string) $row->stage,
                'entry_type' => 'reversal',
                'reversal_of_id' => (int) $row->getKey(),
                'event_type' => $eventType,
                'source_type' => $row->source_type,
                'source_id' => $row->source_id,
                'target_type' => $row->target_type,
                'target_id' => $row->target_id,
                'target_line_type' => $row->target_line_type,
                'target_line_id' => $row->target_line_id,
                'allocation_method' => $row->allocation_method,
                'calculation_base' => $row->calculation_base,
                'basis_amount' => (string) $row->basis_amount,
                'source_amount' => (string) $row->source_amount,
                'signed_amount' => ltrim((string) $row->signed_amount, '-'),
                'allocated_amount' => (string) $row->allocated_amount,
                'recognized_at_grn_amount' => (string) $row->recognized_at_grn_amount,
                'recognized_at_invoice_amount' => (string) $row->recognized_at_invoice_amount,
                'remaining_amount' => '0.000000',
                'cost_treatment' => $row->cost_treatment,
                'tax_treatment' => $row->tax_treatment,
                'finance_posting_profile_id' => $row->finance_posting_profile_id,
                'finance_account_id' => $row->finance_account_id,
                'correlation_key' => $this->correlationKey($origin, 'reversal', $targetType, $targetId, (int) $row->getKey()),
                'provenance' => ['reversal_of_id' => (int) $row->getKey(), 'event_type' => $eventType],
            ]);

            $this->syncSummary($origin);
        }
    }

    public function effectiveAllocated(PurchaseHeaderAdjustment $adjustment, ?string $stage = null): string
    {
        return $this->effectiveSum($adjustment, 'allocated_amount', $stage);
    }

    public function effectiveRecognizedAtGoodsReceipt(PurchaseHeaderAdjustment $adjustment, ?PurchaseHeaderAdjustment $target = null): string
    {
        return $this->effectiveSum($adjustment, 'recognized_at_grn_amount', 'grn_recognition', $target);
    }

    public function effectiveRecognizedAtInvoice(PurchaseHeaderAdjustment $adjustment, ?PurchaseHeaderAdjustment $target = null): string
    {
        return $this->effectiveSum($adjustment, 'recognized_at_invoice_amount', 'invoice_recognition', $target);
    }

    public function remaining(PurchaseHeaderAdjustment $adjustment): string
    {
        $origin = $this->origin($adjustment);
        $remaining = $this->math->sub((string) $origin->amount, $this->effectiveAllocated($origin));

        return $this->math->isNegative($remaining) ? '0.000000' : $remaining;
    }

    private function createEntry(PurchaseHeaderAdjustment $origin, ?PurchaseHeaderAdjustment $target, array $attributes, bool $updateSummary = true): PurchaseAdjustmentAllocation
    {
        $origin = PurchaseHeaderAdjustment::query()->lockForUpdate()->findOrFail($origin->getKey());
        $existing = PurchaseAdjustmentAllocation::query()
            ->where('correlation_key', $attributes['correlation_key'])
            ->lockForUpdate()
            ->first();
        if ($existing instanceof PurchaseAdjustmentAllocation) {
            return $existing;
        }

        $allocatedAmount = $this->math->normalize((string) $attributes['allocated_amount']);
        $previous = $attributes['stage'] === 'manual_plan' ? '0.000000' : $this->effectiveAllocated($origin);
        $remaining = $attributes['stage'] === 'manual_plan'
            ? $this->math->sub((string) $origin->amount, $this->math->sum($this->manualPlanAmounts($origin)))
            : $this->math->sub((string) $origin->amount, $this->math->add($previous, $allocatedAmount));
        if ($attributes['stage'] !== 'manual_plan' && $this->math->isNegative($remaining)) {
            throw new InvalidArgumentException('Purchase adjustment allocation cannot exceed source adjustment amount.');
        }

        $row = PurchaseAdjustmentAllocation::query()->create(array_merge([
            'tenant_id' => (int) $origin->tenant_id,
            'organization_unit_id' => $origin->organization_unit_id,
            'purchase_header_adjustment_id' => (int) $origin->getKey(),
            'target_purchase_header_adjustment_id' => $target?->getKey(),
            'source_type' => $origin->source_type,
            'source_id' => $origin->source_id,
            'entry_type' => 'allocation',
            'allocation_method' => $this->enumValue($target?->allocation_method ?? $origin->allocation_method),
            'calculation_base' => $this->enumValue($target?->calculation_base ?? $origin->calculation_base),
            'source_amount' => (string) $origin->amount,
            'signed_amount' => $this->signedAmount($target ?? $origin, $allocatedAmount),
            'remaining_amount' => $this->math->isNegative($remaining) ? '0.000000' : $remaining,
            'cost_treatment' => $target?->cost_treatment ?? $origin->cost_treatment,
            'tax_treatment' => $target?->tax_treatment ?? $origin->tax_treatment,
            'finance_posting_profile_id' => $target?->finance_posting_profile_id ?? $origin->finance_posting_profile_id,
            'finance_account_id' => $target?->finance_account_id ?? $origin->finance_account_id,
        ], $attributes));

        if ($updateSummary) {
            $this->syncSummary($origin);
            if ($target instanceof PurchaseHeaderAdjustment && (int) $target->getKey() !== (int) $origin->getKey()) {
                $target->allocated_amount = $this->math->compare($allocatedAmount, (string) $target->amount) > 0 ? (string) $target->amount : $allocatedAmount;
                $target->remaining_amount = $this->math->sub((string) $target->amount, (string) $target->allocated_amount);
                $target->save();
            }
        }

        return $row;
    }

    private function syncSummary(PurchaseHeaderAdjustment $origin): void
    {
        $origin = PurchaseHeaderAdjustment::query()->lockForUpdate()->findOrFail($origin->getKey());
        $allocated = $this->effectiveAllocated($origin);
        $remaining = $this->math->sub((string) $origin->amount, $allocated);
        if ($this->math->isNegative($remaining)) {
            throw new InvalidArgumentException('Purchase adjustment allocation cannot exceed source adjustment amount.');
        }
        $origin->allocated_amount = $allocated;
        $origin->remaining_amount = $remaining;
        $origin->save();
    }

    private function effectiveSum(PurchaseHeaderAdjustment $adjustment, string $column, ?string $stage = null, ?PurchaseHeaderAdjustment $target = null): string
    {
        $origin = $this->origin($adjustment);
        $base = PurchaseAdjustmentAllocation::query()
            ->where('purchase_header_adjustment_id', (int) $origin->getKey())
            ->where('stage', '!=', 'manual_plan');
        if ($stage !== null) {
            $base->where('stage', $stage);
        }
        if ($target instanceof PurchaseHeaderAdjustment) {
            $base->where('target_purchase_header_adjustment_id', (int) $target->getKey());
        }

        $allocated = (clone $base)->where('entry_type', 'allocation')->sum($column);
        $reversed = (clone $base)->where('entry_type', 'reversal')->sum($column);

        return $this->math->sub($this->math->normalize((string) $allocated), $this->math->normalize((string) $reversed));
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

    private function correlationKey(PurchaseHeaderAdjustment $origin, string $stage, string $targetType, int $targetId, ?int $targetAdjustmentId): string
    {
        return hash('sha256', implode('|', [
            (string) $origin->tenant_id,
            (string) ($origin->organization_unit_id ?? 'none'),
            (string) $origin->getKey(),
            $stage,
            $targetType,
            (string) $targetId,
            (string) ($targetAdjustmentId ?? 'none'),
        ]));
    }

    private function lineNumberFromClientKey(string $clientKey): ?int
    {
        if (preg_match('/^(?:fast-purchase-line-|purchase-order-line-)?(\d+)$/', $clientKey, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
