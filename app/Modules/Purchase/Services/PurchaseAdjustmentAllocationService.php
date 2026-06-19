<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Purchase\DTOs\PreparedPurchaseInvoiceData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
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
        private readonly PurchaseAdjustmentPolicyResolver $policies,
        private readonly PurchaseAdjustmentAllocator $allocator,
        private readonly PurchaseAdjustmentAllocationLedger $ledger,
        private readonly PurchaseProgressService $progress,
    ) {}

    public function receiptShare(PurchaseHeaderAdjustment $adjustment, PurchaseOrder $order, GoodsReceiptNote $grn): string
    {
        if (! $this->policies->recognizesAtGoodsReceipt($adjustment)) {
            return '0.000000';
        }

        $amount = $this->math->normalize((string) $adjustment->amount);
        if ($this->math->isZero($amount)) {
            return '0.000000';
        }

        $method = $this->method($adjustment);
        if (in_array($method, [PurchaseAdjustmentAllocationMethod::FirstInvoice, PurchaseAdjustmentAllocationMethod::LastInvoice], true)) {
            return '0.000000';
        }

        $remaining = $this->ledger->remaining($adjustment);
        $isFinalReceipt = $this->progress->receiptCompletesOrder($order, $grn);

        if ($method === PurchaseAdjustmentAllocationMethod::Manual) {
            $plan = $this->ledger->manualPlanAmounts($adjustment);
            if ($plan === []) {
                throw ValidationException::withMessages([
                    'adjustments' => ['Manual purchase adjustment allocations require explicit line allocations.'],
                ]);
            }

            return $this->allocator->manual($plan, $this->receiptLineQuantities($grn), $remaining, $isFinalReceipt);
        }

        return $this->allocator->proportional(
            $amount,
            $this->receiptBasis($grn, $adjustment),
            $this->orderBasis($order, $adjustment),
            $remaining,
            $isFinalReceipt,
        );
    }

    public function recordReceiptAllocation(
        PurchaseHeaderAdjustment $origin,
        PurchaseHeaderAdjustment $target,
        GoodsReceiptNote $grn,
        string $allocatedAmount,
    ): ?PurchaseAdjustmentAllocation {
        $recognized = $this->recognizesAtGoodsReceipt($target) ? $allocatedAmount : '0.000000';

        return $this->ledger->recordReceiptAllocation(
            $this->origin($origin),
            $target,
            $grn,
            $allocatedAmount,
            $recognized,
            $this->receiptBasis($grn, $origin),
        );
    }

    public function recordInvoiceAllocation(
        PurchaseHeaderAdjustment $source,
        InvoiceAdjustment $invoiceAdjustment,
        string $allocatedAmount,
        string $recognizedAmount,
    ): ?PurchaseAdjustmentAllocation {
        return $this->ledger->recordInvoiceAllocation($source, $invoiceAdjustment, $allocatedAmount, $recognizedAmount);
    }

    public function recordInvoiceAllocationsForInvoice(Invoice $invoice, PreparedPurchaseInvoiceData $prepared): void
    {
        $invoice->loadMissing('adjustments');
        foreach ($invoice->adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustment
                || $adjustment->source_adjustment_type !== 'purchase_header_adjustment'
                || $adjustment->source_adjustment_id === null
            ) {
                continue;
            }

            $source = PurchaseHeaderAdjustment::query()
                ->lockForUpdate()
                ->find((int) $adjustment->source_adjustment_id);
            if (! $source instanceof PurchaseHeaderAdjustment) {
                continue;
            }

            $amount = $this->invoiceLedgerAmount($source, (string) $adjustment->amount, $prepared);
            $this->recordInvoiceAllocation($source, $adjustment, $amount, $amount);
        }
    }

    public function recognizedAtInvoiceForAdjustment(PurchaseHeaderAdjustment $source, InvoiceAdjustment $invoiceAdjustment): string
    {
        return $this->ledger->effectiveRecognizedForInvoiceAdjustment($source, $invoiceAdjustment);
    }

    public function releaseForTarget(string $targetType, int $targetId, string $eventType): void
    {
        $this->ledger->reverseForTarget($targetType, $targetId, $eventType);
    }

    public function recordManualPlanForOrder(PurchaseHeaderAdjustment $origin, PurchaseOrder $order, string $fieldPrefix): void
    {
        $data = $origin->getAttribute('manual_allocation_payload');
        if (! is_array($data)) {
            return;
        }
        if ($this->method($origin) !== PurchaseAdjustmentAllocationMethod::Manual) {
            throw ValidationException::withMessages(["{$fieldPrefix}.allocations" => ['Manual allocation rows are only valid when allocation_method is manual.']]);
        }

        $this->ledger->recordManualPlan($origin, $order, $data, $fieldPrefix);
    }

    public function origin(PurchaseHeaderAdjustment $adjustment): PurchaseHeaderAdjustment
    {
        return $this->ledger->origin($adjustment);
    }

    public function recognizesAtGoodsReceipt(PurchaseHeaderAdjustment $adjustment): bool
    {
        if ($this->math->isZero((string) $adjustment->amount)) {
            return false;
        }

        return $this->policies->recognizesAtGoodsReceipt($adjustment);
    }

    public function isCapitalizable(PurchaseHeaderAdjustment $adjustment): bool
    {
        return $this->recognizesAtGoodsReceipt($adjustment);
    }

    public function receiveOnlySupported(PurchaseHeaderAdjustmentData $data): bool
    {
        if (! in_array($data->allocationMethod, [PurchaseAdjustmentAllocationMethod::Proportional, PurchaseAdjustmentAllocationMethod::Manual], true)) {
            return false;
        }

        return $this->policies->receiveOnlySupported($data);
    }

    /**
     * @param  array<string, string>  $currentInvoiceQuantities  keyed as source_line_type:id
     */
    public function invoiceShare(PurchaseHeaderAdjustment $adjustment, PurchaseOrder $order, array $currentInvoiceQuantities): string
    {
        $amount = $this->math->normalize((string) $adjustment->amount);
        if ($this->math->isZero($amount)) {
            return '0.000000';
        }

        $origin = $this->origin($adjustment);
        $method = $this->method($adjustment);
        $remaining = $this->ledger->remaining($origin);
        $isFinalInvoice = $this->progress->invoiceCompletesOrder($order, $currentInvoiceQuantities);

        if ($method === PurchaseAdjustmentAllocationMethod::FirstInvoice) {
            return $this->math->compare($this->ledger->effectiveRecognizedAtInvoice($origin), '0.000000') > 0
                ? '0.000000'
                : $remaining;
        }

        if ($method === PurchaseAdjustmentAllocationMethod::LastInvoice) {
            return $isFinalInvoice ? $remaining : '0.000000';
        }

        if ($method === PurchaseAdjustmentAllocationMethod::Manual) {
            $plan = $this->ledger->manualPlanAmounts($origin);
            if ($plan === []) {
                throw ValidationException::withMessages([
                    'adjustments' => ['Manual purchase adjustment allocations require explicit line allocations.'],
                ]);
            }

            return $this->allocator->manual($plan, $this->invoiceLineQuantities($order, $currentInvoiceQuantities), $remaining, $isFinalInvoice);
        }

        return $this->allocator->proportional(
            (string) $origin->amount,
            $this->invoiceBasis($order, $adjustment, $currentInvoiceQuantities),
            $this->orderBasis($order, $adjustment),
            $remaining,
            $isFinalInvoice,
        );
    }

    public function recognizedAtGoodsReceiptFor(PurchaseHeaderAdjustment $adjustment): string
    {
        $origin = $this->origin($adjustment);
        if ((int) $origin->getKey() === (int) $adjustment->getKey()) {
            return $this->ledger->effectiveRecognizedAtGoodsReceipt($origin);
        }

        return $this->ledger->effectiveRecognizedAtGoodsReceipt($origin, $adjustment);
    }

    private function invoiceLedgerAmount(PurchaseHeaderAdjustment $source, string $invoiceAdjustmentAmount, PreparedPurchaseInvoiceData $prepared): string
    {
        $method = $this->method($source);
        $origin = $this->origin($source);

        if ($method === PurchaseAdjustmentAllocationMethod::FirstInvoice
            && $this->math->compare($this->ledger->effectiveRecognizedAtInvoice($origin), '0.000000') > 0) {
            return '0.000000';
        }

        if ($method === PurchaseAdjustmentAllocationMethod::LastInvoice
            && ! $this->invoiceCompletesAdjustmentSource($origin, $prepared)) {
            return '0.000000';
        }

        $amount = $this->invoiceResidualAmount($source, $invoiceAdjustmentAmount);
        $remaining = $this->ledger->remaining($origin);

        return $this->math->compare($amount, $remaining) > 0 ? $remaining : $amount;
    }

    private function invoiceCompletesAdjustmentSource(PurchaseHeaderAdjustment $origin, PreparedPurchaseInvoiceData $prepared): bool
    {
        if ((string) $origin->source_type !== 'purchase_order' || $origin->source_id === null) {
            return false;
        }

        $order = PurchaseOrder::query()->with('lines')->find((int) $origin->source_id);

        return $order instanceof PurchaseOrder && $this->progress->invoiceCompletesOrder($order, $prepared->lineQuantities);
    }

    public function invoiceResidualAmount(PurchaseHeaderAdjustment $source, string $invoiceAdjustmentAmount): string
    {
        $amount = $this->math->normalize($invoiceAdjustmentAmount);
        if (! $this->isCapitalizable($source)) {
            return $amount;
        }

        if ($source->origin_purchase_header_adjustment_id === null && $source->source_type === 'purchase_order') {
            return $amount;
        }

        $recognizedAtGrn = $this->recognizedAtGoodsReceiptFor($source);
        if ($this->math->compare($recognizedAtGrn, $amount) >= 0) {
            return '0.000000';
        }

        return $this->math->sub($amount, $recognizedAtGrn);
    }

    public function allocationMethodValue(PurchaseHeaderAdjustment $adjustment): string
    {
        return $this->method($adjustment)->value;
    }

    /**
     * @return array<int, string> keyed by goods receipt line id
     */
    public function manualReceiptLineShares(PurchaseHeaderAdjustment $adjustment, GoodsReceiptNote $grn): array
    {
        if ($this->method($adjustment) !== PurchaseAdjustmentAllocationMethod::Manual) {
            return [];
        }

        $origin = $this->origin($adjustment);
        $plan = $this->ledger->manualPlanAmounts($origin);
        if ($plan === []) {
            return [];
        }

        $grn->loadMissing('lines.purchaseOrderLine');
        $lines = $grn->lines
            ->filter(fn (GoodsReceiptNoteLine $line): bool => $line->purchaseOrderLine instanceof PurchaseOrderLine)
            ->sortBy(fn (GoodsReceiptNoteLine $line): int => (int) $line->getKey())
            ->values();
        $shares = [];
        $allocated = '0.000000';
        $lastIndex = $lines->count() - 1;
        foreach ($lines as $index => $line) {
            $sourceLine = $line->purchaseOrderLine;
            if (! $sourceLine instanceof PurchaseOrderLine) {
                continue;
            }
            if ($index === $lastIndex) {
                $share = $this->math->sub((string) $adjustment->amount, $allocated);
            } else {
                $planAmount = $plan[(int) $sourceLine->getKey()] ?? '0.000000';
                $ratio = $this->math->isZero((string) $sourceLine->ordered_quantity)
                    ? '0.000000'
                    : $this->math->div((string) $line->accepted_quantity, (string) $sourceLine->ordered_quantity, 12);
                $share = $this->math->mul($planAmount, $ratio);
                $allocated = $this->math->add($allocated, $share);
            }
            if (! $this->math->isZero($share)) {
                $shares[(int) $line->getKey()] = $share;
            }
        }

        return $shares;
    }

    public function financeProfileKey(PurchaseHeaderAdjustment $adjustment): string
    {
        return $this->policies->resolveForModel($adjustment)['profile_key'] ?? 'expense';
    }

    private function method(PurchaseHeaderAdjustment $adjustment): PurchaseAdjustmentAllocationMethod
    {
        return $adjustment->allocation_method instanceof PurchaseAdjustmentAllocationMethod
            ? $adjustment->allocation_method
            : PurchaseAdjustmentAllocationMethod::from((string) $adjustment->allocation_method);
    }

    /**
     * @return array<int, array{event_quantity: string, source_quantity: string}>
     */
    private function receiptLineQuantities(GoodsReceiptNote $grn): array
    {
        $quantities = [];
        $grn->loadMissing('lines.purchaseOrderLine');
        foreach ($grn->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine || ! $line->purchaseOrderLine instanceof PurchaseOrderLine) {
                continue;
            }
            $quantities[(int) $line->purchaseOrderLine->getKey()] = [
                'event_quantity' => (string) $line->accepted_quantity,
                'source_quantity' => (string) $line->purchaseOrderLine->ordered_quantity,
            ];
        }

        return $quantities;
    }

    /**
     * @param  array<string, string>  $currentInvoiceQuantities  keyed as source_line_type:id
     * @return array<int, array{event_quantity: string, source_quantity: string}>
     */
    private function invoiceLineQuantities(PurchaseOrder $order, array $currentInvoiceQuantities): array
    {
        $quantities = [];
        $order->loadMissing('lines');
        foreach ($currentInvoiceQuantities as $lineKey => $quantity) {
            [$type, $id] = explode(':', (string) $lineKey, 2);
            $line = null;
            if ($type === 'purchase_order_line') {
                $line = $order->lines->firstWhere('id', (int) $id);
            } elseif ($type === 'goods_receipt_note_line') {
                $receiptLine = GoodsReceiptNoteLine::query()->with('purchaseOrderLine')->find((int) $id);
                $line = $receiptLine instanceof GoodsReceiptNoteLine ? $receiptLine->purchaseOrderLine : null;
            }
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            $lineId = (int) $line->getKey();
            $quantities[$lineId] = [
                'event_quantity' => $this->math->add($quantities[$lineId]['event_quantity'] ?? '0.000000', $quantity),
                'source_quantity' => (string) $line->ordered_quantity,
            ];
        }

        return $quantities;
    }

    /**
     * @param  array<string, string>  $currentInvoiceQuantities  keyed as source_line_type:id
     */
    private function invoiceBasis(PurchaseOrder $order, PurchaseHeaderAdjustment $adjustment, array $currentInvoiceQuantities): string
    {
        $basis = '0.000000';
        $order->loadMissing('lines');
        foreach ($this->invoiceLineQuantities($order, $currentInvoiceQuantities) as $lineId => $quantities) {
            $line = $order->lines->firstWhere('id', $lineId);
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            $lineBasis = $this->lineBasis($line, $adjustment);
            $ratio = $this->math->isZero($quantities['source_quantity'])
                ? '0.000000'
                : $this->math->div($quantities['event_quantity'], $quantities['source_quantity'], 12);
            $basis = $this->math->add($basis, $this->math->mul($lineBasis, $ratio));
        }

        return $basis;
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
}
