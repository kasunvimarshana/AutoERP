<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\BaseCharge;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class BaseRentBilling
{
    private const WHOLE_CHARGE = '1.000000';

    private const FIRST_LINE = 1;

    public function __construct(private readonly AgreementService $agreements, private readonly RentalAuthorization $authorization,
        private readonly BaseRentPreview $preview, private readonly TaxedSourceInvoiceFactory $factory, private readonly InvoiceCreationService $invoices, private readonly InvoiceSourceService $sources) {}

    public function create(AgreementKind $kind, AgreementContext $context, int $agreementId, array $input): Invoice
    {
        $this->authorization->assertBilling($context, $kind);
        $agreement = $this->agreements->find($kind, $context, $agreementId);
        $data = $this->documentInput($input);

        return DB::transaction(function () use ($kind, $context, $agreement, $input, $data): Invoice {
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->assertAgreement($agreement, (int) $data['expected_version']);
            $calculation = $this->preview->calculate($kind, $context, $agreement->id, $input);
            if (! preg_match(AgreementFields::DECIMAL_PATTERN, $calculation['base_rent'])) {
                throw ValidationException::withMessages(['amount' => ['Split this period into smaller charges within the supported monetary precision.']]);
            }
            if (bccomp($calculation['base_rent'], AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE) === 0) {
                throw ValidationException::withMessages(['amount' => ['This period has zero base rent; there is no base amount to invoice.']]);
            }
            $class = $this->chargeClass($kind);
            if ($class::query()->forContext($context->tenantId, $context->organizationUnitId)->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')->where('period_from', '<=', $calculation['until'])->where('period_until', '>=', $calculation['from'])->lockForUpdate()->exists()) {
                throw new ConflictHttpException('This base-rent period overlaps a recorded charge. Review its invoice; reissue the original charge after cancellation or reversal.');
            }
            $charge = new $class;
            $charge->forceFill(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'agreement_id' => $agreement->id,
                'period_from' => $calculation['from'], 'period_until' => $calculation['until'], 'amount' => $calculation['base_rent'],
                'calculation' => $calculation, 'actor_id' => $context->actorId])->save();

            return $this->issue($kind, $context, $agreement, $charge, $data);
        });
    }

    public function reissue(AgreementKind $kind, AgreementContext $context, int $agreementId, int $chargeId, array $input): Invoice
    {
        $this->authorization->assertBilling($context, $kind);
        $agreement = $this->agreements->find($kind, $context, $agreementId);
        $data = $this->documentInput($input);

        return DB::transaction(function () use ($kind, $context, $agreement, $chargeId, $data): Invoice {
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->assertAgreement($agreement, (int) $data['expected_version']);
            $charge = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)->lockForUpdate()->findOrFail($chargeId);

            if ($charge->voided_at !== null) {
                throw new ConflictHttpException('This charge was voided and cannot be reissued.');
            }

            // Invoice's allocation guard rejects a second live invoice for this immutable quantity.
            return $this->issue($kind, $context, $agreement, $charge, $data);
        });
    }

    public function list(AgreementKind $kind, AgreementContext $context, int $agreementId, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertBilling($context, $kind);
        $this->agreements->find($kind, $context, $agreementId);

        $page = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreementId)->orderByDesc('id')->paginate($perPage);
        $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, BaseChargeSource::forKind($kind)->value, $page->getCollection()->pluck('id')->all());

        return $page->through(static fn (BaseCharge $charge): array => ['id' => $charge->id, 'from' => $charge->period_from, 'until' => $charge->period_until,
            'amount' => $charge->amount, 'currency' => $charge->calculation['currency'], 'agreement' => $charge->calculation['agreement'],
            'calculation' => $charge->calculation, 'row_version' => $charge->row_version, 'voided_at' => $charge->voided_at, 'void_reason' => $charge->void_reason, 'invoices' => $documents[$charge->id] ?? []]);
    }

    public function void(AgreementKind $kind, AgreementContext $context, int $agreementId, int $chargeId, array $input): void
    {
        $this->authorization->assertBilling($context, $kind);
        $agreement = $this->agreements->find($kind, $context, $agreementId);
        $data = Validator::make($input, ['expected_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'expected_charge_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
        if (trim($data['reason']) === '') {
            throw ValidationException::withMessages(['reason' => ['Provide the reason for voiding this charge.']]);
        }
        DB::transaction(function () use ($kind, $context, $agreement, $chargeId, $data): void {
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->assertAgreement($agreement, (int) $data['expected_version']);
            $charge = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->where('agreement_id', $agreement->id)->lockForUpdate()->findOrFail($chargeId);
            if ($charge->row_version !== (int) $data['expected_charge_version'] || $charge->voided_at !== null) {
                throw new ConflictHttpException('This charge changed. Reload before continuing.');
            }
            $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, BaseChargeSource::forKind($kind)->value, [$chargeId]);
            foreach ($documents[$chargeId] ?? [] as $document) {
                if (! in_array($document['status'], [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value, InvoiceStatus::Reversed->value], true)) {
                    throw new ConflictHttpException('Cancel or reverse every live invoice before voiding its base charge.');
                }
            }
            $charge->forceFill(['voided_at' => now(), 'voided_by' => $context->actorId, 'void_reason' => trim($data['reason']), 'row_version' => $charge->row_version + 1])->save();
        });
    }

    private function issue(AgreementKind $kind, AgreementContext $context, Agreement $agreement, BaseCharge $charge, array $data): Invoice
    {
        $sourceType = BaseChargeSource::forKind($kind)->value;
        $customer = $kind === AgreementKind::Customer;
        $description = 'Base rent '.$agreement->reference.' '.$charge->period_from.' – '.$charge->period_until;
        $source = new CreateInvoiceData(tenantId: $context->tenantId, organizationUnitId: $context->organizationUnitId,
            invoiceType: $customer ? InvoiceType::Sales : InvoiceType::Purchase, direction: $customer ? InvoiceDirection::Outbound : InvoiceDirection::Inbound,
            invoiceDate: $data['invoice_date'], dueDate: $data['due_date'] ?? null, currencyId: $agreement->currency_id, exchangeRate: $data['exchange_rate'],
            partyType: $customer ? InvoicePartyType::Customer->value : InvoicePartyType::Supplier->value,
            partyId: $customer ? $agreement->customer_id : $agreement->supplier_id, createdBy: $context->actorId,
            supplyPeriodStart: $charge->period_from, supplyPeriodEnd: $charge->period_until,
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

    private function documentInput(array $input): array
    {
        return Validator::make($input, ['expected_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'invoice_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'due_date' => ['nullable', 'date_format:'.AgreementFields::DATE_FORMAT, 'after_or_equal:invoice_date'],
            'exchange_rate' => ['required', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'numeric', 'gt:0']])->validate();
    }

    private function assertAgreement(Agreement $agreement, int $version): void
    {
        if ($agreement->row_version !== $version) {
            throw new ConflictHttpException('This agreement changed. Reload before billing.');
        }
        if ($agreement->status === AgreementStatus::Draft) {
            throw ValidationException::withMessages(['agreement' => ['Activate the agreement before billing its base rent.']]);
        }
    }

    private function chargeClass(AgreementKind $kind): string
    {
        return $kind === AgreementKind::Customer ? CustomerBaseCharge::class : OwnerBaseCharge::class;
    }
}
