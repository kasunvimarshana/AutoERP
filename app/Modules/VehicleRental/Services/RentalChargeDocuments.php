<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceSourceService;
use Modules\Invoice\Services\TaxedSourceInvoiceFactory;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\RentalCharge;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RentalChargeDocuments
{
    private const WHOLE_CHARGE = '1.000000';

    private const FIRST_LINE = 1;

    public function __construct(
        private readonly TaxedSourceInvoiceFactory $factory,
        private readonly InvoiceCreationService $invoices,
        private readonly InvoiceSourceService $sources,
        private readonly RentalCalendar $calendar,
    ) {}

    public function issue(AgreementKind $kind, AgreementContext $context, Agreement $agreement, RentalCharge $charge, string $sourceType, string $description, array $data): Invoice
    {
        $this->assertCommercialCoverage($agreement, $charge, $context);

        $customer = $kind === AgreementKind::Customer;
        $source = new CreateInvoiceData(tenantId: $context->tenantId, organizationUnitId: $context->organizationUnitId,
            invoiceType: $customer ? InvoiceType::Sales : InvoiceType::Purchase, direction: $customer ? InvoiceDirection::Outbound : InvoiceDirection::Inbound,
            invoiceDate: $data['invoice_date'], dueDate: $data['due_date'] ?? null, currencyId: $agreement->currency_id, exchangeRate: $data['exchange_rate'],
            partyType: $customer ? InvoicePartyType::Customer->value : InvoicePartyType::Supplier->value,
            partyId: $customer ? $agreement->customer_id : $agreement->supplier_id, createdBy: $context->actorId,
            supplyPeriodStart: $charge->calculation['supply_from'] ?? $charge->period_from, supplyPeriodEnd: $charge->calculation['supply_until'] ?? $charge->period_until,
            lines: [new InvoiceLineData(lineNumber: self::FIRST_LINE, description: $description, quantity: self::WHOLE_CHARGE,
                unitPrice: $charge->amount, lineType: InvoiceLineType::Service, sourceLineType: $sourceType, sourceLineId: $charge->id)],
            sources: [new InvoiceSourceData(tenantId: $context->tenantId, organizationUnitId: $context->organizationUnitId, sourceType: $sourceType, sourceId: $charge->id,
                sourceDocumentNumber: $agreement->reference, sourceDocumentDate: $charge->period_from, sourceSubtotal: $charge->amount, sourceGrandTotal: $charge->amount)],
            sourceLines: [new InvoiceSourceLineData(tenantId: $context->tenantId, organizationUnitId: $context->organizationUnitId, sourceType: $sourceType, sourceId: $charge->id,
                sourceLineType: $sourceType, sourceLineId: $charge->id, sourceQuantity: self::WHOLE_CHARGE, invoicedQuantity: self::WHOLE_CHARGE,
                sourceUnitPrice: $charge->amount, sourceLineTotal: $charge->amount, invoicedLineTotal: $charge->amount)]);

        return $this->invoices->create($this->factory->prepare($source,
            $customer ? FinancePostingProfileCode::CustomerRentalInvoice : FinancePostingProfileCode::SupplierRentalInvoice,
            $customer ? FinanceAccountRoleCode::RentalRevenue : FinanceAccountRoleCode::RentalExpense));
    }

    public function documentInput(array $input): array
    {
        return Validator::make($input, ['expected_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'invoice_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'due_date' => ['nullable', 'date_format:'.AgreementFields::DATE_FORMAT, 'after_or_equal:invoice_date'],
            'exchange_rate' => ['required', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'numeric', 'gt:0']])->validate();
    }

    public function assertAgreement(Agreement $agreement, int $version): void
    {
        if ($agreement->row_version !== $version) {
            throw new ConflictHttpException('This agreement changed. Reload before billing.');
        }
        if ($agreement->status === AgreementStatus::Draft) {
            throw ValidationException::withMessages(['agreement' => ['Activate the agreement before billing.']]);
        }
    }

    public function voidInput(array $input): array
    {
        $data = Validator::make($input, ['expected_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'expected_charge_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
        if (trim($data['reason']) === '') {
            throw ValidationException::withMessages(['reason' => ['Provide the reason for voiding this charge.']]);
        }

        return $data;
    }

    // Caller holds the source mutex and charge row lock for the entire transaction.
    public function void(RentalCharge $charge, string $sourceType, AgreementContext $context, array $data): void
    {
        if ($charge->row_version !== (int) $data['expected_charge_version'] || $charge->voided_at !== null) {
            throw new ConflictHttpException('This charge changed. Reload before continuing.');
        }
        $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, $sourceType, [$charge->id]);
        foreach ($documents[$charge->id] ?? [] as $document) {
            if (! in_array($document['status'], [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value, InvoiceStatus::Reversed->value], true)) {
                throw new ConflictHttpException('Cancel or reverse every live invoice before voiding its Rental charge.');
            }
        }
        $charge->forceFill(['voided_at' => now(), 'voided_by' => $context->actorId, 'void_reason' => trim($data['reason']), 'row_version' => $charge->row_version + 1])->save();
    }

    private function assertCommercialCoverage(Agreement $agreement, RentalCharge $charge, AgreementContext $context): void
    {
        [$from, $until] = $this->commercialPeriod($charge, $context);
        $agreementStart = $agreement->starts_on->toDateString();
        $agreementEnd = $this->calendar->coverageEnd($agreement, $context);

        if ($from < $agreementStart || ($agreementEnd !== null && $until > $agreementEnd)) {
            throw ValidationException::withMessages([
                'agreement' => ['Automatic Rental billing must stay within the agreement commercial coverage. Record the physical overrun, but use a valid agreement revision or an explicitly authorized financial adjustment for any uncovered amount.'],
            ]);
        }
    }

    /** @return array{string, string} */
    private function commercialPeriod(RentalCharge $charge, AgreementContext $context): array
    {
        $calculation = $charge->calculation;
        if (isset($calculation['supply_from'], $calculation['supply_until'])) {
            return [(string) $calculation['supply_from'], (string) $calculation['supply_until']];
        }
        if (isset($calculation['from'], $calculation['until'])) {
            return [(string) $calculation['from'], (string) $calculation['until']];
        }
        if (isset($calculation['chart']['starts_at'], $calculation['chart']['ends_at'])) {
            $timezone = $this->calendar->timezone($context);
            $start = OperationalTime::parse($calculation['chart']['starts_at'], 'starts_at')->setTimezone($timezone);
            $end = OperationalTime::parse($calculation['chart']['ends_at'], 'ends_at')->setTimezone($timezone);

            return [$start->toDateString(), $end->subMicrosecond()->toDateString()];
        }

        return [(string) $charge->period_from, (string) $charge->period_until];
    }
}
