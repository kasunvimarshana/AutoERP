<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Services\TaxCalculationService;

/** Prepares untaxed source lines for Invoice's existing draft and posting lifecycle. */
final class TaxedSourceInvoiceFactory
{
    public function __construct(private readonly TaxCalculationService $taxes, private readonly DecimalMath $math, private readonly InvoicePostingPlanFactory $plans) {}

    public function prepare(CreateInvoiceData $source, FinancePostingProfileCode $profile, FinanceAccountRoleCode $role): CreateInvoiceData
    {
        if ($source->adjustments !== [] || $source->taxCalculation !== null || $source->postingPlan !== null) {
            throw new InvalidArgumentException('Source invoice tax preparation requires unprepared lines and no header adjustments.');
        }
        $taxLines = [];
        foreach ($source->lines as $line) {
            if (! $this->math->isZero($line->taxAmount)) {
                throw new InvalidArgumentException('Source lines must not contain precomputed tax.');
            }
            $taxLines[] = new TaxCalculationLineData(lineNumber: $line->lineNumber, quantity: $line->quantity, unitPrice: $line->unitPrice,
                itemId: $line->itemId, taxGroupId: $line->metadata[InvoiceTaxMetadata::TAX_GROUP_ID] ?? null,
                discountBeforeTax: $line->discountAmount, chargeAfterTax: $line->chargeAmount);
        }
        $tax = $this->taxes->calculate(new TaxCalculationData(tenantId: $source->tenantId,
            documentType: 'invoice_'.$source->direction->value.'_'.$source->invoiceType->value,
            documentDate: $source->invoiceDate, organizationUnitId: $source->organizationUnitId,
            customerId: $source->direction === InvoiceDirection::Outbound ? $source->partyId : null,
            supplierId: $source->direction === InvoiceDirection::Inbound ? $source->partyId : null, lines: $taxLines));
        $results = [];
        foreach ($tax->lineResults as $result) {
            $results[$result->lineNumber] = $result;
        }
        $lines = [];
        foreach ($source->lines as $line) {
            $result = $results[$line->lineNumber];
            $lines[] = new InvoiceLineData(...array_replace(get_object_vars($line), [
                'taxAmount' => $result->taxAmount,
                'lineTotal' => $this->math->add($result->totalAmount, $result->withholdingAmount),
                'metadata' => array_replace($line->metadata ?? [], [InvoiceTaxMetadata::TAXES => array_map(static fn (TaxAmountData $amount): array => $amount->toArray(), $result->taxes),
                    InvoiceTaxMetadata::WITHHOLDING_AMOUNT => $result->withholdingAmount]),
            ]));
        }
        $adjustments = $this->math->isZero($tax->withholdingAmount) ? [] : [new InvoiceAdjustmentData(name: 'Tax withholding',
            adjustmentType: AdjustmentType::Withholding, effect: AdjustmentEffect::Decrease, amount: $tax->withholdingAmount, isSystemGenerated: true)];
        // Component summaries include withholding; withholding is not output/input tax.
        $ordinaryTax = $this->math->sub($tax->taxAmount, $tax->withholdingAmount);
        $base = $this->math->sub($this->math->add($tax->totalAmount, $tax->withholdingAmount), $ordinaryTax);
        $plan = $source->direction === InvoiceDirection::Outbound
            ? $this->plans->outbound($profile, $source->invoiceDate, $role, $base, $ordinaryTax, $tax->withholdingAmount)
            : $this->plans->inbound($profile, $source->invoiceDate, $role, $base, $ordinaryTax, $tax->withholdingAmount);

        return new CreateInvoiceData(...array_replace(get_object_vars($source), ['lines' => $lines, 'adjustments' => $adjustments, 'taxCalculation' => $tax, 'postingPlan' => $plan]));
    }
}
