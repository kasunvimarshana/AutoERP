<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
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
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalDocumentStatus;
use Modules\VehicleRental\Models\RentalCalculationLine;
use Modules\VehicleRental\Models\RentalCalculationRun;

final class RentalInvoiceIntegrationService
{
    private const DEFAULT_PAYMENT_TERM_DAYS = 0;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCreationService $invoices,
    ) {}

    /** @param list<int>|null $lineIds */
    public function create(
        RentalCalculationRun $run,
        int $expectedVersion,
        string $invoiceDate,
        ?string $dueDate,
        InvoiceStatus $status,
        ?array $lineIds,
        ?int $userId,
        ?string $notes = null,
    ): Invoice {
        return DB::transaction(function () use ($run, $expectedVersion, $invoiceDate, $dueDate, $status, $lineIds, $userId, $notes): Invoice {
            $run = RentalCalculationRun::query()
                ->with(['billingPeriod.agreement', 'lines'])
                ->lockForUpdate()
                ->findOrFail($run->getKey());
            $this->assertExpectedVersion($run, $expectedVersion);
            if ($run->calculation_status !== RentalCalculationStatus::Approved) {
                throw new InvalidArgumentException('Only an approved rental calculation can create an invoice or owner payable.');
            }

            $agreement = $run->billingPeriod->agreement;
            $resolvedDueDate = $dueDate ?? $this->dueDateFromPaymentTerms(
                $invoiceDate,
                (int) ($agreement->payment_term_days ?? self::DEFAULT_PAYMENT_TERM_DAYS),
            );
            $selected = $run->lines
                ->when($lineIds !== null && $lineIds !== [], fn ($lines) => $lines->whereIn('id', $lineIds))
                ->values();
            if ($selected->isEmpty()) {
                throw new InvalidArgumentException('Select at least one remaining calculation line.');
            }

            $invoiceLines = [];
            $sourceLines = [];
            $adjustments = [];
            $lineNumber = 1;
            foreach ($selected as $line) {
                $previouslyInvoiced = (string) InvoiceSourceLine::query()
                    ->where('tenant_id', $run->tenant_id)
                    ->where('source_type', 'rental_calculation_run')
                    ->where('source_id', $run->getKey())
                    ->where('source_line_type', 'rental_calculation_line')
                    ->where('source_line_id', $line->getKey())
                    ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value]))
                    ->sum('invoiced_quantity');
                $net = (string) $line->net_amount;
                $isPositive = $this->math->compare($net, '0') > 0;
                $isNegative = $this->math->compare($net, '0') < 0;
                $negativeAdjustmentType = AdjustmentType::CreditNote;
                $negativeConsumed = $isNegative && $this->hasActiveAdjustment($line, $negativeAdjustmentType);
                if (($isPositive && $this->math->compare($previouslyInvoiced, '1.000000') >= 0) || $negativeConsumed) {
                    continue;
                }

                if ($isPositive) {
                    $lineTotal = $this->math->add($net, (string) $line->tax_amount);
                    $invoiceLines[] = new InvoiceLineData(
                        lineNumber: $lineNumber++,
                        description: (string) $line->description,
                        quantity: '1.000000',
                        unitPrice: $net,
                        lineType: InvoiceLineType::Charge,
                        taxAmount: (string) $line->tax_amount,
                        lineTotal: $lineTotal,
                        sourceLineType: 'rental_calculation_line',
                        sourceLineId: (int) $line->getKey(),
                        metadata: [
                            'component_code' => $line->component_code->value,
                            'measured_quantity' => (string) $line->measured_quantity,
                            'chargeable_quantity' => (string) $line->chargeable_quantity,
                            'unit' => $line->unit,
                        ],
                    );
                    $sourceLines[] = new InvoiceSourceLineData(
                        tenantId: (int) $run->tenant_id,
                        sourceType: 'rental_calculation_run',
                        sourceId: (int) $run->getKey(),
                        sourceLineType: 'rental_calculation_line',
                        sourceLineId: (int) $line->getKey(),
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: $net,
                        sourceLineTotal: $lineTotal,
                        organizationUnitId: $run->organization_unit_id,
                        previouslyInvoicedQuantity: $previouslyInvoiced,
                        invoicedLineTotal: $lineTotal,
                    );
                } elseif ($isNegative) {
                    $adjustments[] = new InvoiceAdjustmentData(
                        name: (string) $line->description,
                        adjustmentType: $negativeAdjustmentType,
                        effect: AdjustmentEffect::Decrease,
                        amount: $this->math->sub('0', (string) $line->total_amount),
                        sourceType: 'rental_calculation_line',
                        sourceId: (int) $line->getKey(),
                        calculationType: 'fixed',
                        allocationMethod: AllocationMethod::Manual,
                        isSystemGenerated: true,
                        description: 'Rental source adjustment',
                    );
                }

                if ($isPositive
                    && $this->math->compare((string) $line->withholding_amount, '0') > 0
                    && ! $this->hasActiveAdjustment($line, AdjustmentType::Withholding)) {
                    $adjustments[] = new InvoiceAdjustmentData(
                        name: 'Withholding tax - '.$line->description,
                        adjustmentType: AdjustmentType::Withholding,
                        effect: AdjustmentEffect::Decrease,
                        amount: (string) $line->withholding_amount,
                        sourceType: 'rental_calculation_line',
                        sourceId: (int) $line->getKey(),
                        calculationType: 'fixed',
                        allocationMethod: AllocationMethod::Manual,
                        isSystemGenerated: true,
                    );
                }
            }

            if ($invoiceLines === []) {
                throw new InvalidArgumentException('Selected rental calculation has no positive uninvoiced lines.');
            }

            $sourceSubtotal = $this->math->sum(array_map(fn (InvoiceLineData $line) => $this->math->mul($line->quantity, $line->unitPrice), $invoiceLines));
            $sourceTax = $this->math->sum(array_map(fn (InvoiceLineData $line) => $line->taxAmount, $invoiceLines));
            $adjustmentTotal = $this->math->sum(array_map(fn (InvoiceAdjustmentData $adjustment) => $adjustment->amount, $adjustments));
            $sourceGrand = $this->math->sub($this->math->add($sourceSubtotal, $sourceTax), $adjustmentTotal);
            if ($this->math->compare($sourceGrand, '0') < 0) {
                throw new InvalidArgumentException('Rental deductions cannot exceed the positive payable or invoice amount.');
            }

            $direction = $agreement->agreement_kind === RentalAgreementKind::CustomerRental
                ? InvoiceDirection::Outbound
                : InvoiceDirection::Inbound;
            $partyType = $agreement->agreement_kind === RentalAgreementKind::CustomerRental ? 'customer' : 'supplier';
            $partyId = $agreement->agreement_kind === RentalAgreementKind::CustomerRental ? $agreement->customer_id : $agreement->supplier_id;
            $invoice = $this->invoices->create(new CreateInvoiceData(
                tenantId: (int) $run->tenant_id,
                invoiceType: InvoiceType::Rental,
                direction: $direction,
                invoiceDate: $invoiceDate,
                organizationUnitId: $run->organization_unit_id,
                partyType: $partyType,
                partyId: (int) $partyId,
                dueDate: $resolvedDueDate,
                currencyId: $run->currency_id,
                status: $status,
                notes: $notes ?? 'Rental calculation '.$run->billingPeriod->billing_cycle_key,
                createdBy: $userId,
                lines: $invoiceLines,
                sources: [new InvoiceSourceData(
                    tenantId: (int) $run->tenant_id,
                    sourceType: 'rental_calculation_run',
                    sourceId: (int) $run->getKey(),
                    organizationUnitId: $run->organization_unit_id,
                    sourceDocumentNumber: 'RC-'.$run->getKey().'-'.$run->run_version,
                    sourceDocumentDate: $run->billingPeriod->period_end->toDateString(),
                    sourceSubtotal: $sourceSubtotal,
                    sourceAdjustmentTotal: $adjustmentTotal,
                    sourceGrandTotal: $sourceGrand,
                )],
                sourceLines: $sourceLines,
                adjustments: $adjustments,
            ));

            $uninvoiced = $run->lines->filter(fn (RentalCalculationLine $line): bool => ! $this->isConsumed($run, $line, $agreement->agreement_kind))->count();
            $run->document_status = $uninvoiced === 0
                ? RentalDocumentStatus::Generated
                : RentalDocumentStatus::PartiallyGenerated;
            $run->row_version = $expectedVersion + 1;
            $run->updated_by = $userId;
            $run->save();

            return $invoice;
        });
    }

    private function dueDateFromPaymentTerms(string $invoiceDate, int $paymentTermDays): string
    {
        return CarbonImmutable::parse($invoiceDate)->addDays($paymentTermDays)->toDateString();
    }

    private function hasActiveAdjustment(RentalCalculationLine $line, AdjustmentType $type): bool
    {
        return InvoiceAdjustment::query()
            ->where('tenant_id', $line->tenant_id)
            ->where('source_type', 'rental_calculation_line')
            ->where('source_id', $line->getKey())
            ->where('adjustment_type', $type->value)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ]))
            ->exists();
    }

    private function assertExpectedVersion(RentalCalculationRun $run, int $expectedVersion): void
    {
        if ((int) $run->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental calculation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function isConsumed(
        RentalCalculationRun $run,
        RentalCalculationLine $line,
        RentalAgreementKind $agreementKind,
    ): bool {
        $net = (string) $line->net_amount;
        if ($this->math->compare($net, '0') > 0) {
            return $this->math->compare((string) InvoiceSourceLine::query()
                ->where('tenant_id', $run->tenant_id)
                ->where('source_type', 'rental_calculation_run')
                ->where('source_id', $run->getKey())
                ->where('source_line_type', 'rental_calculation_line')
                ->where('source_line_id', $line->getKey())
                ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [
                    InvoiceStatus::Cancelled->value,
                    InvoiceStatus::Void->value,
                ]))
                ->sum('invoiced_quantity'), '1.000000') >= 0;
        }
        if ($this->math->compare($net, '0') < 0) {
            return $this->hasActiveAdjustment(
                $line,
                AdjustmentType::CreditNote,
            );
        }

        return true;
    }
}
