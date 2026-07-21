<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\DTOs\TaxCalculationResult;
use Modules\Tax\DTOs\TaxLineCalculationResult;
use Modules\Tax\Services\TaxCalculationService;
use Modules\VehicleRental\Constants\VehicleRentalFinancialDocument;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use Modules\VehicleRental\DTOs\RentalFinancialDocumentData;
use Modules\VehicleRental\Enums\RentalCalculationSide;
use Modules\VehicleRental\Models\RentalCalculation;
use Modules\VehicleRental\Models\RentalCalculationLine;

final class RentalFinancialDocumentDataFactory
{
    private const IMMEDIATE_PAYMENT_TERMS_DAYS = 0;
    private const CREDIT_PAYMENT_MODE = 'Credit';
    private const IMMEDIATE_PAYMENT_MODE = 'Due on receipt';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxCalculationService $taxes,
        private readonly InvoicePostingPlanFactory $postingPlans,
    ) {}

    public function make(RentalCalculation $calculation, RentalFinancialDocumentData $data): CreateInvoiceData
    {
        $agreement = $calculation->agreement;
        if ($agreement === null) {
            throw new LogicException('Rental calculation has no agreement.');
        }

        $side = $calculation->side instanceof RentalCalculationSide
            ? $calculation->side
            : RentalCalculationSide::from((string) $calculation->side);
        $direction = $side === RentalCalculationSide::Customer
            ? InvoiceDirection::Outbound
            : InvoiceDirection::Inbound;
        $partyType = InvoicePartyType::forDirection($direction);
        $partyId = $side === RentalCalculationSide::Customer
            ? $agreement->customer_id
            : $agreement->supplier_id;
        if ($partyId === null || (int) $partyId < 1) {
            throw new LogicException('Rental calculation agreement has no financial-document party.');
        }

        $taxCalculation = $this->taxCalculation($calculation, $data, $side);
        $taxResults = [];
        foreach ($taxCalculation->lineResults as $taxResult) {
            $taxResults[$taxResult->lineNumber] = $taxResult;
        }

        $invoiceLines = [];
        $sourceLines = [];
        foreach ($calculation->lines->sortBy('line_number')->values() as $line) {
            if (! $line instanceof RentalCalculationLine) {
                continue;
            }

            $lineNumber = (int) $line->line_number;
            $taxResult = $taxResults[$lineNumber] ?? null;
            $taxAmount = $taxResult instanceof TaxLineCalculationResult
                ? $taxResult->taxAmount
                : VehicleRentalFinancialDocument::ZERO;
            $withholdingAmount = $taxResult instanceof TaxLineCalculationResult
                ? $taxResult->withholdingAmount
                : VehicleRentalFinancialDocument::ZERO;
            $invoiceLineTotal = $this->math->add((string) $line->line_total, $taxAmount);

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber,
                description: $this->lineDescription($line),
                quantity: (string) $line->quantity,
                unitPrice: (string) $line->unit_rate,
                lineType: InvoiceLineType::Service,
                taxAmount: $taxAmount,
                lineTotal: $invoiceLineTotal,
                sourceLineType: VehicleRentalSource::CALCULATION_LINE_DOCUMENT,
                sourceLineId: (int) $line->getKey(),
                metadata: [
                    InvoiceTaxMetadata::TAX_GROUP_ID => $line->is_taxable
                        ? $agreement->tax_group_id
                        : null,
                    InvoiceTaxMetadata::TAXES => $taxResult instanceof TaxLineCalculationResult
                        ? array_map(
                            fn (TaxAmountData $tax): array => $this->taxSnapshot($tax),
                            $taxResult->taxes,
                        )
                        : [],
                    InvoiceTaxMetadata::WITHHOLDING_AMOUNT => $withholdingAmount,
                ],
            );
            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $data->tenantId,
                sourceType: VehicleRentalSource::CALCULATION_DOCUMENT,
                sourceId: (int) $calculation->getKey(),
                sourceLineType: VehicleRentalSource::CALCULATION_LINE_DOCUMENT,
                sourceLineId: (int) $line->getKey(),
                sourceQuantity: (string) $line->quantity,
                invoicedQuantity: (string) $line->quantity,
                sourceUnitPrice: (string) $line->unit_rate,
                sourceLineTotal: (string) $line->line_total,
                organizationUnitId: $data->organizationUnitId,
                invoicedLineTotal: $invoiceLineTotal,
            );
        }

        if ($invoiceLines === []) {
            throw new LogicException('Rental calculation has no financial-document lines.');
        }

        $adjustments = [];
        if (! $this->math->isZero($taxCalculation->withholdingAmount)) {
            $adjustments[] = new InvoiceAdjustmentData(
                name: VehicleRentalFinancialDocument::WITHHOLDING_ADJUSTMENT_NAME,
                adjustmentType: AdjustmentType::Withholding,
                effect: AdjustmentEffect::Decrease,
                amount: $taxCalculation->withholdingAmount,
                calculationType: VehicleRentalFinancialDocument::FIXED_CALCULATION_TYPE,
                allocationMethod: AllocationMethod::Manual,
                isSystemGenerated: true,
                description: VehicleRentalFinancialDocument::WITHHOLDING_ADJUSTMENT_DESCRIPTION,
            );
        }

        $description = $side === RentalCalculationSide::Customer
            ? VehicleRentalFinancialDocument::CUSTOMER_INVOICE_DESCRIPTION
            : VehicleRentalFinancialDocument::OWNER_PAYABLE_DESCRIPTION;
        $postingPlan = $side === RentalCalculationSide::Customer
            ? $this->postingPlans->outbound(
                FinancePostingProfileCode::CustomerRentalInvoice,
                $data->invoiceDate,
                FinanceAccountRoleCode::RentalRevenue,
                (string) $calculation->subtotal_amount,
                $taxCalculation->taxAmount,
                $taxCalculation->withholdingAmount,
                $description,
            )
            : $this->postingPlans->inbound(
                FinancePostingProfileCode::SupplierRentalInvoice,
                $data->invoiceDate,
                FinanceAccountRoleCode::RentalExpense,
                (string) $calculation->subtotal_amount,
                $taxCalculation->taxAmount,
                $taxCalculation->withholdingAmount,
                $description,
            );
        $grandTotal = $this->math->sub(
            $this->math->add((string) $calculation->subtotal_amount, $taxCalculation->taxAmount),
            $taxCalculation->withholdingAmount,
        );
        $paymentTermsDays = (int) $agreement->payment_terms_days;
        $dueDate = CarbonImmutable::parse($data->invoiceDate)
            ->addDays($paymentTermsDays)
            ->toDateString();

        return new CreateInvoiceData(
            tenantId: $data->tenantId,
            invoiceType: InvoiceType::Rental,
            direction: $direction,
            invoiceDate: $data->invoiceDate,
            organizationUnitId: $data->organizationUnitId,
            partyType: $partyType->value,
            partyId: (int) $partyId,
            dueDate: $dueDate,
            currencyId: (int) $calculation->currency_id,
            exchangeRate: $this->math->normalize($data->exchangeRate),
            notes: $this->notes($calculation, $data->notes),
            createdBy: $data->actorId,
            lines: $invoiceLines,
            sources: [new InvoiceSourceData(
                tenantId: $data->tenantId,
                sourceType: VehicleRentalSource::CALCULATION_DOCUMENT,
                sourceId: (int) $calculation->getKey(),
                organizationUnitId: $data->organizationUnitId,
                sourceDocumentNumber: (string) $calculation->calculation_number,
                sourceDocumentDate: $calculation->period_end?->toDateString(),
                sourceSubtotal: (string) $calculation->subtotal_amount,
                sourceGrandTotal: $grandTotal,
                invoicedAmount: $grandTotal,
            )],
            sourceLines: $sourceLines,
            adjustments: $adjustments,
            taxCalculation: $taxCalculation,
            postingPlan: $postingPlan,
            supplyDate: $calculation->period_end?->toDateString(),
            supplyPeriodStart: $calculation->period_start?->toDateString(),
            supplyPeriodEnd: $calculation->period_end?->toDateString(),
            paymentMode: $this->paymentMode($paymentTermsDays),
            paymentTerms: $this->paymentTerms($paymentTermsDays),
        );
    }

    private function taxCalculation(
        RentalCalculation $calculation,
        RentalFinancialDocumentData $data,
        RentalCalculationSide $side,
    ): TaxCalculationResult {
        $agreement = $calculation->agreement;
        $taxLines = [];
        foreach ($calculation->lines->sortBy('line_number') as $line) {
            if (! $line instanceof RentalCalculationLine || ! $line->is_taxable) {
                continue;
            }
            $taxLines[] = new TaxCalculationLineData(
                lineNumber: (int) $line->line_number,
                quantity: (string) $line->quantity,
                unitPrice: (string) $line->unit_rate,
                taxGroupId: $agreement?->tax_group_id,
            );
        }

        if ($taxLines === []) {
            return new TaxCalculationResult(
                taxableAmount: VehicleRentalFinancialDocument::ZERO,
                taxAmount: VehicleRentalFinancialDocument::ZERO,
                withholdingAmount: VehicleRentalFinancialDocument::ZERO,
                totalAmount: VehicleRentalFinancialDocument::ZERO,
                lineTaxAmount: VehicleRentalFinancialDocument::ZERO,
                headerTaxAmount: VehicleRentalFinancialDocument::ZERO,
                lineResults: [],
            );
        }

        return $this->taxes->calculate(new TaxCalculationData(
            tenantId: $data->tenantId,
            documentType: $side === RentalCalculationSide::Customer
                ? VehicleRentalFinancialDocument::CUSTOMER_TAX_DOCUMENT
                : VehicleRentalFinancialDocument::OWNER_TAX_DOCUMENT,
            documentDate: $data->invoiceDate,
            organizationUnitId: $data->organizationUnitId,
            customerId: $side === RentalCalculationSide::Customer ? $agreement?->customer_id : null,
            supplierId: $side === RentalCalculationSide::Owner ? $agreement?->supplier_id : null,
            documentTaxGroupId: $agreement?->tax_group_id,
            lines: $taxLines,
        ));
    }

    private function lineDescription(RentalCalculationLine $line): string
    {
        $description = trim((string) $line->description);

        return $description !== '' ? $description : Str::headline($line->rate_code->value);
    }

    /** @return array<string, int|string|bool> */
    private function taxSnapshot(TaxAmountData $tax): array
    {
        return [
            'tax_id' => $tax->taxId,
            'tax_code' => $tax->taxCode,
            'tax_name' => $tax->taxName,
            'tax_type' => $tax->taxType,
            'calculation_method' => $tax->calculationMethod,
            'rate' => $tax->rate,
            'sequence' => $tax->sequence,
            'taxable_amount' => $tax->taxableAmount,
            'tax_amount' => $tax->taxAmount,
            'total_after_tax' => $tax->totalAfterTax,
            'is_withholding' => $tax->isWithholding,
            'recoverable' => $tax->recoverable,
            'payable' => $tax->payable,
            'receivable' => $tax->receivable,
        ];
    }

    private function notes(RentalCalculation $calculation, ?string $notes): string
    {
        $parts = ['Rental calculation '.$calculation->calculation_number.'.'];
        $notes = trim((string) $notes);
        if ($notes !== '') {
            $parts[] = $notes;
        }

        return implode(' ', $parts);
    }

    private function paymentMode(int $paymentTermsDays): string
    {
        return $paymentTermsDays > self::IMMEDIATE_PAYMENT_TERMS_DAYS
            ? self::CREDIT_PAYMENT_MODE
            : self::IMMEDIATE_PAYMENT_MODE;
    }

    private function paymentTerms(int $paymentTermsDays): string
    {
        return $paymentTermsDays > self::IMMEDIATE_PAYMENT_TERMS_DAYS
            ? 'Credit - '.$paymentTermsDays.' calendar days'
            : self::IMMEDIATE_PAYMENT_MODE;
    }
}
