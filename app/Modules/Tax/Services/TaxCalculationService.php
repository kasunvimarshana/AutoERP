<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Tax\DTOs\ApplicableTaxData;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\DTOs\TaxCalculationResult;
use Modules\Tax\DTOs\TaxDeterminationContext;
use Modules\Tax\DTOs\TaxLineCalculationResult;

final class TaxCalculationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxDeterminationService $determination,
    ) {}

    public function calculate(TaxCalculationData $data): TaxCalculationResult
    {
        $lineResults = [];
        $lineTaxableTotal = '0.000000';
        $lineTaxTotal = '0.000000';
        $lineWithholdingTotal = '0.000000';
        $lineTotal = '0.000000';

        foreach ($data->lines as $line) {
            if (! $line instanceof TaxCalculationLineData) {
                continue;
            }

            $result = $this->calculateLine($data, $line);
            $lineResults[] = $result;
            $lineTaxableTotal = $this->math->add($lineTaxableTotal, $result->taxableAmount);
            $lineTaxTotal = $this->math->add($lineTaxTotal, $result->taxAmount);
            $lineWithholdingTotal = $this->math->add($lineWithholdingTotal, $result->withholdingAmount);
            $lineTotal = $this->math->add($lineTotal, $result->totalAmount);
        }

        $headerTaxes = [];
        $headerTaxTotal = '0.000000';
        $headerWithholdingTotal = '0.000000';
        $headerBase = $this->math->add(
            $this->math->sub($lineTaxableTotal, $data->headerDiscountBeforeTax),
            $data->headerChargeBeforeTax,
        );
        if ($this->math->isNegative($headerBase)) {
            throw new InvalidArgumentException('Header taxable amount cannot be negative.');
        }

        if ($data->headerTaxGroupId !== null) {
            $headerApplicableTaxes = $this->determination->determine(new TaxDeterminationContext(
                tenantId: $data->tenantId,
                documentType: $data->documentType,
                documentDate: $data->documentDate,
                organizationUnitId: $data->organizationUnitId,
                customerId: $data->customerId,
                supplierId: $data->supplierId,
                documentTaxGroupId: $data->headerTaxGroupId,
            ))->taxes;

            $header = $this->applyTaxes($headerBase, $headerApplicableTaxes);
            $headerTaxes = $header['taxes'];
            $headerTaxTotal = $header['tax_amount'];
            $headerWithholdingTotal = $header['withholding_amount'];
        }

        $total = $lineTotal;
        $total = $this->math->sub($total, $data->headerDiscountBeforeTax);
        $total = $this->math->add($total, $data->headerChargeBeforeTax);
        $total = $this->math->add($total, $headerTaxTotal);
        $total = $this->math->sub($total, $headerWithholdingTotal);
        $total = $this->math->sub($total, $data->headerDiscountAfterTax);
        $total = $this->math->add($total, $data->headerChargeAfterTax);

        if ($this->math->isNegative($total)) {
            throw new InvalidArgumentException('Tax calculation total cannot be negative.');
        }

        $taxableAmount = $data->headerTaxGroupId !== null ? $headerBase : $lineTaxableTotal;
        $taxAmount = $this->math->add($lineTaxTotal, $headerTaxTotal);
        $withholdingAmount = $this->math->add($lineWithholdingTotal, $headerWithholdingTotal);

        return new TaxCalculationResult(
            taxableAmount: $taxableAmount,
            taxAmount: $taxAmount,
            withholdingAmount: $withholdingAmount,
            totalAmount: $total,
            lineTaxAmount: $lineTaxTotal,
            headerTaxAmount: $headerTaxTotal,
            lineResults: $lineResults,
            headerTaxes: $headerTaxes,
        );
    }

    private function calculateLine(TaxCalculationData $data, TaxCalculationLineData $line): TaxLineCalculationResult
    {
        $base = $line->taxableAmount !== null
            ? $this->math->normalize($line->taxableAmount)
            : $this->math->mul($line->quantity, $line->unitPrice);
        $base = $this->math->sub($base, $line->discountBeforeTax);
        $base = $this->math->add($base, $line->chargeBeforeTax);
        if ($this->math->isNegative($base)) {
            throw new InvalidArgumentException('Line taxable amount cannot be negative.');
        }

        $applicableTaxes = $line->applicableTaxes;
        if ($applicableTaxes === []) {
            $applicableTaxes = $this->determination->determine(new TaxDeterminationContext(
                tenantId: $data->tenantId,
                documentType: $data->documentType,
                documentDate: $data->documentDate,
                organizationUnitId: $data->organizationUnitId,
                customerId: $data->customerId,
                supplierId: $data->supplierId,
                itemId: $line->itemId,
                documentTaxGroupId: $line->taxGroupId ?? $data->documentTaxGroupId,
            ))->taxes;
        }

        $taxed = $this->applyTaxes($base, $applicableTaxes);
        $total = $taxed['total_amount'];
        $total = $this->math->sub($total, $line->discountAfterTax);
        $total = $this->math->add($total, $line->chargeAfterTax);
        if ($this->math->isNegative($total)) {
            throw new InvalidArgumentException('Line total cannot be negative.');
        }

        return new TaxLineCalculationResult(
            lineNumber: $line->lineNumber,
            taxableAmount: $base,
            taxAmount: $taxed['tax_amount'],
            withholdingAmount: $taxed['withholding_amount'],
            totalAmount: $total,
            taxes: $taxed['taxes'],
        );
    }

    /**
     * @param  list<ApplicableTaxData>  $applicableTaxes
     * @return array{taxes: list<TaxAmountData>, tax_amount: string, withholding_amount: string, total_amount: string}
     */
    private function applyTaxes(string $base, array $applicableTaxes): array
    {
        usort($applicableTaxes, static fn (ApplicableTaxData $left, ApplicableTaxData $right): int => $left->sequence <=> $right->sequence);

        $currentTotal = $this->math->normalize($base);
        $taxAmount = '0.000000';
        $withholdingAmount = '0.000000';
        $results = [];

        foreach ($applicableTaxes as $tax) {
            $calculated = $this->taxAmount($base, $currentTotal, $tax);
            if ($this->math->isNegative($calculated['amount'])) {
                throw new InvalidArgumentException('Tax amount cannot be negative.');
            }

            $currentTotal = $tax->isWithholding
                ? $this->math->sub($currentTotal, $calculated['amount'])
                : ($tax->calculationMethod === 'inclusive'
                    ? $currentTotal
                    : $this->math->add($currentTotal, $calculated['amount']));

            $taxAmount = $this->math->add($taxAmount, $calculated['amount']);
            if ($tax->isWithholding) {
                $withholdingAmount = $this->math->add($withholdingAmount, $calculated['amount']);
            }

            $results[] = new TaxAmountData(
                taxId: $tax->taxId,
                taxCode: $tax->taxCode,
                taxName: $tax->taxName,
                taxType: $tax->taxType,
                calculationMethod: $tax->calculationMethod,
                rate: $this->math->normalize($tax->rate),
                sequence: $tax->sequence,
                taxableAmount: $calculated['taxable'],
                taxAmount: $calculated['amount'],
                totalAfterTax: $currentTotal,
                isWithholding: $tax->isWithholding,
                recoverable: $tax->recoverable,
                payable: $tax->payable,
                receivable: $tax->receivable,
            );
        }

        return [
            'taxes' => $results,
            'tax_amount' => $taxAmount,
            'withholding_amount' => $withholdingAmount,
            'total_amount' => $currentTotal,
        ];
    }

    /**
     * @return array{taxable: string, amount: string}
     */
    private function taxAmount(string $base, string $currentTotal, ApplicableTaxData $tax): array
    {
        $method = $tax->calculationMethod;
        $rate = $this->math->normalize($tax->rate);
        if ($this->math->isNegative($rate)) {
            throw new InvalidArgumentException('Tax rate cannot be negative.');
        }

        return match ($method) {
            'fixed' => [
                'taxable' => $this->math->normalize($base),
                'amount' => $rate,
            ],
            'inclusive' => $this->inclusiveAmount($currentTotal, $rate),
            'compound' => [
                'taxable' => $this->math->normalize($currentTotal),
                'amount' => $this->percentageAmount($currentTotal, $rate),
            ],
            'percentage', 'exclusive' => [
                'taxable' => $this->math->normalize($base),
                'amount' => $this->percentageAmount($base, $rate),
            ],
            default => throw new InvalidArgumentException("Unsupported tax calculation method [{$method}]."),
        };
    }

    private function percentageAmount(string $base, string $rate): string
    {
        return $this->math->div($this->math->mul($base, $rate, 12), '100.000000');
    }

    /**
     * @return array{taxable: string, amount: string}
     */
    private function inclusiveAmount(string $gross, string $rate): array
    {
        if ($this->math->isZero($rate)) {
            return [
                'taxable' => $this->math->normalize($gross),
                'amount' => '0.000000',
            ];
        }

        $divisor = $this->math->add('100.000000', $rate, 12);
        $taxable = $this->math->div($this->math->mul($gross, '100.000000', 12), $divisor);

        return [
            'taxable' => $taxable,
            'amount' => $this->math->sub($gross, $taxable),
        ];
    }
}
