<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;

final class InvoiceCalculationService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  list<InvoiceAdjustmentData>|null  $adjustments
     */
    public function calculate(CreateInvoiceData $data, ?array $adjustments = null): InvoiceCalculationResult
    {
        $subtotal = '0.000000';
        $discountTotal = '0.000000';
        $taxTotal = '0.000000';
        $chargeTotal = '0.000000';
        $adjustmentTotal = '0.000000';
        $lineGrandTotal = '0.000000';
        $headerImpact = '0.000000';
        $lineTotals = [];

        foreach ($data->lines as $line) {
            $lineBase = $this->lineBase($line);
            $lineTotal = $this->lineTotal($line);

            $subtotal = $this->math->add($subtotal, $lineBase);
            $discountTotal = $this->math->add($discountTotal, $line->discountAmount);
            $taxTotal = $this->math->add($taxTotal, $line->taxAmount);
            $chargeTotal = $this->math->add($chargeTotal, $line->chargeAmount);
            $lineGrandTotal = $this->math->add($lineGrandTotal, $lineTotal);
            $lineTotals[] = $lineTotal;
        }

        foreach ($adjustments ?? $data->adjustments as $adjustment) {
            $amount = $this->math->normalize($adjustment->amount);
            $headerImpact = $adjustment->effect === AdjustmentEffect::Increase
                ? $this->math->add($headerImpact, $amount)
                : $this->math->sub($headerImpact, $amount);

            if ($adjustment->adjustmentType === AdjustmentType::Discount
                || $adjustment->adjustmentType === AdjustmentType::CreditNote
                || $adjustment->adjustmentType === AdjustmentType::Withholding
            ) {
                $discountTotal = $this->math->add($discountTotal, $amount);

                continue;
            }

            if ($adjustment->adjustmentType === AdjustmentType::Tax) {
                $taxTotal = $this->math->add($taxTotal, $amount);

                continue;
            }

            if ($adjustment->adjustmentType === AdjustmentType::Freight
                || $adjustment->adjustmentType === AdjustmentType::Charge
                || $adjustment->adjustmentType === AdjustmentType::DebitNote
            ) {
                $chargeTotal = $this->math->add($chargeTotal, $amount);

                continue;
            }

            $adjustmentTotal = $adjustment->effect === AdjustmentEffect::Increase
                ? $this->math->add($adjustmentTotal, $amount)
                : $this->math->sub($adjustmentTotal, $amount);
        }

        $grandTotal = $this->math->add($lineGrandTotal, $headerImpact);
        if ($this->math->isNegative($grandTotal)) {
            throw new InvalidArgumentException('Invoice grand total cannot be negative.');
        }

        return new InvoiceCalculationResult(
            subtotal: $subtotal,
            discountTotal: $discountTotal,
            taxTotal: $taxTotal,
            chargeTotal: $chargeTotal,
            adjustmentTotal: $adjustmentTotal,
            grandTotal: $grandTotal,
            lineTotals: $lineTotals,
        );
    }

    public function lineBase(InvoiceLineData $line): string
    {
        return $this->math->mul($line->quantity, $line->unitPrice);
    }

    public function lineTotal(InvoiceLineData $line): string
    {
        if ($line->lineTotal !== null) {
            return $this->math->normalize($line->lineTotal);
        }

        $total = $this->lineBase($line);
        $total = $this->math->sub($total, $line->discountAmount);
        $total = $this->math->add($total, $line->taxAmount);

        return $this->math->add($total, $line->chargeAmount);
    }

    public function totalAdjustmentAmount(array $adjustments): string
    {
        $total = '0.000000';
        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustmentData) {
                continue;
            }

            $total = $adjustment->effect === AdjustmentEffect::Increase
                ? $this->math->add($total, $adjustment->amount)
                : $this->math->sub($total, $adjustment->amount);
        }

        return $total;
    }
}
