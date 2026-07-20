<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSource;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
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
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Models\RentalCalculation;
use Modules\VehicleRental\Models\RentalCalculationLine;

final class RentalFinancialDocumentService
{
    /** @var list<string> */
    private const INVOICE_RELATIONS = [
        'customer',
        'supplier',
        'currency',
        'lines',
        'sources',
        'sourceLines',
        'adjustments',
        'balance',
        'postingPlan',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxCalculationService $taxes,
        private readonly InvoiceCreationService $invoices,
        private readonly InvoiceStatusService $invoiceStatuses,
        private readonly InvoicePostingPlanFactory $postingPlans,
    ) {}

    public function create(RentalCalculation $calculation, RentalFinancialDocumentData $data): Invoice
    {
        return DB::transaction(function () use ($calculation, $data): Invoice {
            $calculation = RentalCalculation::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->with(['agreement.customer', 'agreement.supplier', 'currency', 'lines'])
                ->lockForUpdate()
                ->findOrFail($calculation->getKey());

            if ((int) $calculation->row_version !== $data->expectedVersion) {
                throw new InvalidArgumentException(
                    'Rental calculation was changed by another request. Reload it before creating the financial document.',
                );
            }
            if ($calculation->status !== RentalCalculationStatus::Calculated
                || $calculation->active_marker !== true) {
                throw new InvalidArgumentException('Only an active calculated Rental snapshot can create a financial document.');
            }

            $existing = $this->financialDocument($calculation, lockRows: true);
            if ($existing instanceof Invoice) {
                return $existing;
            }

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
                            ? array_map($this->taxSnapshot(...), $taxResult->taxes)
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
                throw new InvalidArgumentException('Rental calculation has no financial-document lines.');
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
            $dueDate = CarbonImmutable::parse($data->invoiceDate)
                ->addDays((int) $agreement->payment_terms_days)
                ->toDateString();

            $invoice = $this->invoices->create(new CreateInvoiceData(
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
            ));

            $invoice = $this->invoiceStatuses->transition($invoice, InvoiceStatus::Approved, $data->actorId);
            $invoice = $this->invoiceStatuses->transition($invoice, InvoiceStatus::Posted, $data->actorId);

            return $invoice->refresh()->load(self::INVOICE_RELATIONS);
        });
    }

    public function assertNoActiveFinancialDocument(RentalCalculation $calculation): void
    {
        if ($this->financialDocument($calculation, lockRows: true) instanceof Invoice) {
            throw new InvalidArgumentException(
                'Reverse or cancel the Rental financial document before cancelling its calculation.',
            );
        }
    }

    public function attachFinancialDocuments(Collection $calculations): void
    {
        if ($calculations->isEmpty()) {
            return;
        }

        $calculationIds = $calculations->map(static fn (RentalCalculation $calculation): int => (int) $calculation->getKey());
        $first = $calculations->first();
        if (! $first instanceof RentalCalculation) {
            return;
        }

        $sources = InvoiceSource::query()
            ->where('tenant_id', (int) $first->tenant_id)
            ->when(
                $first->organization_unit_id === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $first->organization_unit_id),
            )
            ->where('source_type', VehicleRentalSource::CALCULATION_DOCUMENT)
            ->whereIn('source_id', $calculationIds)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', $this->terminalStatuses()))
            ->with(['invoice.customer', 'invoice.supplier', 'invoice.currency'])
            ->orderByDesc('id')
            ->get()
            ->unique('source_id')
            ->keyBy('source_id');

        foreach ($calculations as $calculation) {
            $source = $sources->get((int) $calculation->getKey());
            $calculation->setRelation('financialDocument', $source?->invoice);
        }
    }

    public function financialDocument(RentalCalculation $calculation, bool $lockRows = false): ?Invoice
    {
        $query = InvoiceSource::query()
            ->where('tenant_id', (int) $calculation->tenant_id)
            ->when(
                $calculation->organization_unit_id === null,
                fn ($scope) => $scope->whereNull('organization_unit_id'),
                fn ($scope) => $scope->where('organization_unit_id', $calculation->organization_unit_id),
            )
            ->where('source_type', VehicleRentalSource::CALCULATION_DOCUMENT)
            ->where('source_id', $calculation->getKey())
            ->whereHas('invoice', fn ($scope) => $scope->whereNotIn('status', $this->terminalStatuses()))
            ->with(['invoice.customer', 'invoice.supplier', 'invoice.currency'])
            ->orderByDesc('id');
        if ($lockRows) {
            $query->lockForUpdate();
        }

        return $query->first()?->invoice;
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

    /** @return list<string> */
    private function terminalStatuses(): array
    {
        return [
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Void->value,
            InvoiceStatus::Reversed->value,
        ];
    }
}
