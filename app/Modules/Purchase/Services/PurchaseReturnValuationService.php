<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\PurchaseReturnLineValuationData;
use Modules\Purchase\Models\GoodsReceiptNoteLine;

final class PurchaseReturnValuationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function fromReceiptLine(GoodsReceiptNoteLine $sourceLine, string $returnedQuantity): PurchaseReturnLineValuationData
    {
        $sourceQuantity = $this->math->normalize((string) $sourceLine->accepted_quantity);
        $returnedQuantity = $this->math->normalize($returnedQuantity);
        $previouslyReturned = $this->math->normalize((string) $sourceLine->returned_quantity);
        $remainingQuantity = $this->math->sub($sourceQuantity, $this->math->add($previouslyReturned, $returnedQuantity));
        $ratio = $this->math->isZero($sourceQuantity)
            ? '0.000000000000'
            : $this->math->div($returnedQuantity, $sourceQuantity, 12);

        $baseAmount = $this->math->mul((string) $sourceLine->line_subtotal, $ratio);
        $discountAmount = $this->math->mul((string) $sourceLine->discount_amount, $ratio);
        $taxAmount = $this->math->mul((string) $sourceLine->tax_amount, $ratio);
        $chargeAmount = $this->math->mul((string) $sourceLine->charge_amount, $ratio);
        $lineTotal = $this->math->add(
            $this->math->sub($baseAmount, $discountAmount),
            $this->math->add($taxAmount, $chargeAmount),
        );

        return new PurchaseReturnLineValuationData(
            sourceQuantity: $sourceQuantity,
            previouslyReturnedQuantity: $previouslyReturned,
            remainingQuantity: $remainingQuantity,
            unitPrice: $this->math->normalize((string) $sourceLine->unit_price),
            costBasis: $this->math->normalize((string) $sourceLine->unit_price),
            baseAmount: $baseAmount,
            discountAmount: $discountAmount,
            taxAmount: $taxAmount,
            chargeAmount: $chargeAmount,
            lineTotal: $lineTotal,
        );
    }

    public function manual(string $quantity, string $costBasis): PurchaseReturnLineValuationData
    {
        $quantity = $this->math->normalize($quantity);
        $costBasis = $this->math->normalize($costBasis);
        $lineTotal = $this->math->mul($quantity, $costBasis);

        return new PurchaseReturnLineValuationData(
            sourceQuantity: $quantity,
            previouslyReturnedQuantity: '0.000000',
            remainingQuantity: '0.000000',
            unitPrice: $costBasis,
            costBasis: $costBasis,
            baseAmount: $lineTotal,
            discountAmount: '0.000000',
            taxAmount: '0.000000',
            chargeAmount: '0.000000',
            lineTotal: $lineTotal,
        );
    }
}
