<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseProcurementBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function remainingInvoiceableForPurchaseOrderLine(PurchaseOrderLine $line): string
    {
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->invoiced_quantity);

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
}
