<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\DTOs\SalesCalculationResult;
use Modules\Sales\DTOs\SalesHeaderAdjustmentData;
use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentEffect;

final class SalesCalculationService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array{subtotal: string, discount: string, tax: string, charge: string, total: string}
     */
    public function lineAmounts(object $line, string $quantityProperty = 'quantity'): array
    {
        $quantity = (string) ($line->{$quantityProperty} ?? $line->quantity ?? $line->deliveredQuantity ?? $line->returnedQuantity);
        $subtotal = $this->math->mul($quantity, (string) $line->unitPrice);
        $discount = $this->amount(
            $subtotal,
            $line->discountCalculationType ?? SalesAdjustmentCalculationType::Fixed,
            (string) ($line->discountRate ?? '0.000000'),
            (string) ($line->discountAmount ?? '0.000000'),
        );
        $taxBase = $this->math->sub($subtotal, $discount);
        $tax = $this->amount(
            $taxBase,
            $line->taxCalculationType ?? SalesAdjustmentCalculationType::Fixed,
            (string) ($line->taxRate ?? '0.000000'),
            (string) ($line->taxAmount ?? '0.000000'),
        );
        $charge = $this->amount(
            $subtotal,
            $line->chargeCalculationType ?? SalesAdjustmentCalculationType::Fixed,
            (string) ($line->chargeRate ?? '0.000000'),
            (string) ($line->chargeAmount ?? '0.000000'),
        );
        $total = $this->math->add($this->math->add($this->math->sub($subtotal, $discount), $tax), $charge);

        if ($this->math->isNegative($total)) {
            throw new InvalidArgumentException('Sales line total cannot be negative.');
        }

        return compact('subtotal', 'discount', 'tax', 'charge', 'total');
    }

    /**
     * @param  list<object>  $lines
     * @param  list<SalesHeaderAdjustmentData>  $adjustments
     */
    public function calculate(array $lines, array $adjustments = []): SalesCalculationResult
    {
        $subtotal = '0.000000';
        $discount = '0.000000';
        $tax = '0.000000';
        $charge = '0.000000';
        $afterDiscount = '0.000000';
        $afterLineAdjustments = '0.000000';
        $lineTotals = [];

        foreach ($lines as $line) {
            $amounts = $this->lineAmounts($line);
            $subtotal = $this->math->add($subtotal, $amounts['subtotal']);
            $discount = $this->math->add($discount, $amounts['discount']);
            $tax = $this->math->add($tax, $amounts['tax']);
            $charge = $this->math->add($charge, $amounts['charge']);
            $afterDiscount = $this->math->add($afterDiscount, $this->math->sub($amounts['subtotal'], $amounts['discount']));
            $afterLineAdjustments = $this->math->add($afterLineAdjustments, $amounts['total']);
            $lineTotals[] = $amounts['total'];
        }

        $increases = '0.000000';
        $decreases = '0.000000';
        foreach ($adjustments as $adjustment) {
            $amount = $this->headerAmount($adjustment, $subtotal, $afterDiscount, $afterLineAdjustments);
            if ($adjustment->effect === SalesAdjustmentEffect::Increase) {
                $increases = $this->math->add($increases, $amount);
            } else {
                $decreases = $this->math->add($decreases, $amount);
            }
        }

        $grandTotal = $this->math->sub($this->math->add($afterLineAdjustments, $increases), $decreases);
        if ($this->math->isNegative($grandTotal)) {
            throw new InvalidArgumentException('Sales document total cannot be negative.');
        }

        return new SalesCalculationResult(
            $subtotal,
            $discount,
            $tax,
            $charge,
            $increases,
            $decreases,
            $grandTotal,
            $lineTotals,
        );
    }

    public function headerAmount(
        SalesHeaderAdjustmentData $adjustment,
        string $subtotal,
        string $subtotalAfterLineDiscount,
        string $subtotalAfterLineAdjustments,
    ): string {
        $base = match ($adjustment->calculationBase) {
            SalesAdjustmentCalculationBase::Subtotal => $subtotal,
            SalesAdjustmentCalculationBase::SubtotalAfterLineDiscount => $subtotalAfterLineDiscount,
            SalesAdjustmentCalculationBase::SubtotalAfterLineAdjustments => $subtotalAfterLineAdjustments,
        };

        return $this->amount($base, $adjustment->calculationType, $adjustment->rate, $adjustment->amount);
    }

    /**
     * @param  list<object>  $lines
     * @param  list<SalesHeaderAdjustmentData>  $adjustments
     * @return list<string>
     */
    public function headerAmounts(array $lines, array $adjustments): array
    {
        $subtotal = '0.000000';
        $afterDiscount = '0.000000';
        $afterLineAdjustments = '0.000000';

        foreach ($lines as $line) {
            $amounts = $this->lineAmounts($line);
            $subtotal = $this->math->add($subtotal, $amounts['subtotal']);
            $afterDiscount = $this->math->add($afterDiscount, $this->math->sub($amounts['subtotal'], $amounts['discount']));
            $afterLineAdjustments = $this->math->add($afterLineAdjustments, $amounts['total']);
        }

        return array_map(
            fn (SalesHeaderAdjustmentData $adjustment): string => $this->headerAmount($adjustment, $subtotal, $afterDiscount, $afterLineAdjustments),
            $adjustments,
        );
    }

    private function amount(
        string $base,
        SalesAdjustmentCalculationType|string $type,
        string $rate,
        string $fixed,
    ): string {
        $calculationType = $type instanceof SalesAdjustmentCalculationType
            ? $type
            : SalesAdjustmentCalculationType::from($type);

        return $calculationType === SalesAdjustmentCalculationType::Percentage
            ? $this->math->div($this->math->mul($base, $rate, 12), '100')
            : $this->math->normalize($fixed);
    }
}
