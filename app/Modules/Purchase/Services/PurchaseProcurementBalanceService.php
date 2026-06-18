<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseProcurementBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function purchaseOrderReceivableRemainingSql(string $table = 'purchase_order_lines'): string
    {
        return "({$table}.ordered_quantity - {$table}.cancelled_quantity - {$table}.received_quantity)";
    }

    public function purchaseOrderInvoiceableRemainingSql(string $table = 'purchase_order_lines'): string
    {
        return "({$table}.ordered_quantity - {$table}.cancelled_quantity - {$table}.invoiced_quantity)";
    }

    public function purchaseOrderReturnableRemainingSql(string $table = 'purchase_order_lines'): string
    {
        return "({$table}.received_quantity - {$table}.returned_quantity)";
    }

    public function goodsReceiptInvoiceableRemainingSql(string $table = 'goods_receipt_note_lines'): string
    {
        return "({$table}.accepted_quantity - {$table}.invoiced_quantity)";
    }

    public function goodsReceiptReturnableRemainingSql(string $table = 'goods_receipt_note_lines'): string
    {
        return "({$table}.accepted_quantity - {$table}.returned_quantity)";
    }

    public function wherePurchaseOrderLineReceivable(Builder $query): Builder
    {
        return $query->whereRaw($this->purchaseOrderReceivableRemainingSql().' > 0');
    }

    public function wherePurchaseOrderLineInvoiceable(Builder $query): Builder
    {
        return $query->whereRaw($this->purchaseOrderInvoiceableRemainingSql().' > 0');
    }

    public function wherePurchaseOrderLineReturnable(Builder $query): Builder
    {
        return $query->whereRaw($this->purchaseOrderReturnableRemainingSql().' > 0');
    }

    public function whereGoodsReceiptLineInvoiceable(Builder $query): Builder
    {
        return $query->whereRaw($this->goodsReceiptInvoiceableRemainingSql().' > 0');
    }

    public function whereGoodsReceiptLineReturnable(Builder $query): Builder
    {
        return $query->whereRaw($this->goodsReceiptReturnableRemainingSql().' > 0');
    }

    public function applyPurchaseOrderProgressFilter(Builder $query, string $progressField, string $progressStatus): void
    {
        if ($progressField === 'receipt_status') {
            match ($progressStatus) {
                'not_received' => $query->whereDoesntHave('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.received_quantity > 0')),
                'partially_received' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.received_quantity > 0'))
                    ->whereHas('lines', fn (Builder $line) => $this->wherePurchaseOrderLineReceivable($line)),
                'received' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.received_quantity > 0'))
                    ->whereDoesntHave('lines', fn (Builder $line) => $this->wherePurchaseOrderLineReceivable($line)),
                default => null,
            };
        }

        if ($progressField === 'invoice_status') {
            match ($progressStatus) {
                'not_invoiced' => $query->whereDoesntHave('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.invoiced_quantity > 0')),
                'partially_invoiced' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.invoiced_quantity > 0'))
                    ->whereHas('lines', fn (Builder $line) => $this->wherePurchaseOrderLineInvoiceable($line)),
                'invoiced' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.invoiced_quantity > 0'))
                    ->whereDoesntHave('lines', fn (Builder $line) => $this->wherePurchaseOrderLineInvoiceable($line)),
                default => null,
            };
        }

        if ($progressField === 'return_status') {
            match ($progressStatus) {
                'not_returned' => $query->whereDoesntHave('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.returned_quantity > 0')),
                'partially_returned' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.returned_quantity > 0'))
                    ->whereHas('lines', fn (Builder $line) => $this->wherePurchaseOrderLineReturnable($line)),
                'returned' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('purchase_order_lines.returned_quantity > 0'))
                    ->whereDoesntHave('lines', fn (Builder $line) => $this->wherePurchaseOrderLineReturnable($line)),
                default => null,
            };
        }
    }

    public function applyGoodsReceiptProgressFilter(Builder $query, string $progressField, string $progressStatus): void
    {
        if ($progressField === 'invoice_status') {
            match ($progressStatus) {
                'not_invoiced' => $query->whereDoesntHave('lines', fn (Builder $line) => $line->whereRaw('goods_receipt_note_lines.invoiced_quantity > 0')),
                'partially_invoiced' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('goods_receipt_note_lines.invoiced_quantity > 0'))
                    ->whereHas('lines', fn (Builder $line) => $this->whereGoodsReceiptLineInvoiceable($line)),
                'invoiced' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('goods_receipt_note_lines.invoiced_quantity > 0'))
                    ->whereDoesntHave('lines', fn (Builder $line) => $this->whereGoodsReceiptLineInvoiceable($line)),
                default => null,
            };
        }

        if ($progressField === 'return_status') {
            match ($progressStatus) {
                'not_returned' => $query->whereDoesntHave('lines', fn (Builder $line) => $line->whereRaw('goods_receipt_note_lines.returned_quantity > 0')),
                'partially_returned' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('goods_receipt_note_lines.returned_quantity > 0'))
                    ->whereHas('lines', fn (Builder $line) => $this->whereGoodsReceiptLineReturnable($line)),
                'returned' => $query
                    ->whereHas('lines', fn (Builder $line) => $line->whereRaw('goods_receipt_note_lines.returned_quantity > 0'))
                    ->whereDoesntHave('lines', fn (Builder $line) => $this->whereGoodsReceiptLineReturnable($line)),
                default => null,
            };
        }
    }

    public function remainingInvoiceableForPurchaseOrderLine(PurchaseOrderLine $line): string
    {
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->invoiced_quantity);

        return $this->math->isNegative($remaining) ? '0.000000' : $this->math->normalize($remaining);
    }

    public function remainingReceivableForPurchaseOrderLine(PurchaseOrderLine $line): string
    {
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->received_quantity);

        return $this->math->isNegative($remaining) ? '0.000000' : $this->math->normalize($remaining);
    }

    public function remainingReturnableForPurchaseOrderLine(PurchaseOrderLine $line): string
    {
        $remaining = $this->math->sub((string) $line->received_quantity, (string) $line->returned_quantity);

        return $this->math->isNegative($remaining) ? '0.000000' : $this->math->normalize($remaining);
    }

    public function remainingInvoiceableForGoodsReceiptLine(GoodsReceiptNoteLine $line): string
    {
        $grnRemaining = $this->math->sub((string) $line->accepted_quantity, (string) $line->invoiced_quantity);
        if ($this->math->isNegative($grnRemaining)) {
            return '0.000000';
        }

        if (! $line->relationLoaded('purchaseOrderLine')) {
            $line->load('purchaseOrderLine');
        }

        if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
            $poRemaining = $this->remainingInvoiceableForPurchaseOrderLine($line->purchaseOrderLine);

            return $this->math->compare($grnRemaining, $poRemaining) > 0
                ? $poRemaining
                : $this->math->normalize($grnRemaining);
        }

        return $this->math->normalize($grnRemaining);
    }

    public function remainingReturnableForGoodsReceiptLine(GoodsReceiptNoteLine $line): string
    {
        $remaining = $this->math->sub((string) $line->accepted_quantity, (string) $line->returned_quantity);

        return $this->math->isNegative($remaining) ? '0.000000' : $this->math->normalize($remaining);
    }

    public function receiptStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'ordered_quantity', 'cancelled_quantity', 'received_quantity', 'not_received', 'partially_received', 'received');
    }

    public function purchaseOrderInvoiceStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'ordered_quantity', 'cancelled_quantity', 'invoiced_quantity', 'not_invoiced', 'partially_invoiced', 'invoiced');
    }

    public function purchaseOrderReturnStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'received_quantity', null, 'returned_quantity', 'not_returned', 'partially_returned', 'returned');
    }

    public function goodsReceiptInvoiceStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'accepted_quantity', null, 'invoiced_quantity', 'not_invoiced', 'partially_invoiced', 'invoiced');
    }

    public function goodsReceiptReturnStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'accepted_quantity', null, 'returned_quantity', 'not_returned', 'partially_returned', 'returned');
    }

    private function quantityProgress(
        iterable $lines,
        string $basisColumn,
        ?string $cancelledColumn,
        string $progressColumn,
        string $none,
        string $partial,
        string $complete,
    ): string {
        $basis = '0.000000';
        $progress = '0.000000';

        foreach ($lines as $line) {
            $lineBasis = (string) ($line->{$basisColumn} ?? '0.000000');
            if ($cancelledColumn !== null) {
                $lineBasis = $this->math->sub($lineBasis, (string) ($line->{$cancelledColumn} ?? '0.000000'));
            }
            $basis = $this->math->add($basis, $lineBasis);
            $progress = $this->math->add($progress, (string) ($line->{$progressColumn} ?? '0.000000'));
        }

        if ($this->math->compare($progress, '0.000000') <= 0 || $this->math->compare($basis, '0.000000') <= 0) {
            return $none;
        }

        return $this->math->compare($progress, $basis) >= 0 ? $complete : $partial;
    }
}
