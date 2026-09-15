<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceSourceService;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\RentalCharge;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class BaseRentBilling
{
    public function __construct(private readonly AgreementService $agreements, private readonly RentalAuthorization $authorization,
        private readonly BaseRentPreview $preview, private readonly RentalChargeDocuments $documents, private readonly InvoiceSourceService $sources) {}

    public function create(AgreementKind $kind, AgreementContext $context, int $agreementId, array $input): Invoice
    {
        $this->authorization->assertBilling($context, $kind);
        $agreement = $this->agreements->find($kind, $context, $agreementId);
        $data = $this->documents->documentInput($input);

        return DB::transaction(function () use ($kind, $context, $agreement, $input, $data): Invoice {
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->documents->assertAgreement($agreement, (int) $data['expected_version']);
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

            return $this->documents->issue($kind, $context, $agreement, $charge, BaseChargeSource::forKind($kind)->value, 'Base rent '.$agreement->reference.' '.$charge->period_from.' – '.$charge->period_until, $data);
        });
    }

    public function reissue(AgreementKind $kind, AgreementContext $context, int $agreementId, int $chargeId, array $input): Invoice
    {
        $this->authorization->assertBilling($context, $kind);
        $agreement = $this->agreements->find($kind, $context, $agreementId);
        $data = $this->documents->documentInput($input);

        return DB::transaction(function () use ($kind, $context, $agreement, $chargeId, $data): Invoice {
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->documents->assertAgreement($agreement, (int) $data['expected_version']);
            $charge = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)->lockForUpdate()->findOrFail($chargeId);

            if ($charge->voided_at !== null) {
                throw new ConflictHttpException('This charge was voided and cannot be reissued.');
            }

            // Invoice's allocation guard rejects a second live invoice for this immutable quantity.
            return $this->documents->issue($kind, $context, $agreement, $charge, BaseChargeSource::forKind($kind)->value, 'Base rent '.$agreement->reference.' '.$charge->period_from.' – '.$charge->period_until, $data);
        });
    }

    public function list(AgreementKind $kind, AgreementContext $context, int $agreementId, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertBilling($context, $kind);
        $this->agreements->find($kind, $context, $agreementId);

        $page = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreementId)->orderByDesc('id')->paginate($perPage);
        $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, BaseChargeSource::forKind($kind)->value, $page->getCollection()->pluck('id')->all());

        return $page->through(static fn (RentalCharge $charge): array => ['id' => $charge->id, 'from' => $charge->period_from, 'until' => $charge->period_until,
            'amount' => $charge->amount, 'currency' => $charge->calculation['currency'], 'agreement' => $charge->calculation['agreement'],
            'calculation' => $charge->calculation, 'row_version' => $charge->row_version, 'voided_at' => $charge->voided_at, 'void_reason' => $charge->void_reason, 'invoices' => $documents[$charge->id] ?? []]);
    }

    public function void(AgreementKind $kind, AgreementContext $context, int $agreementId, int $chargeId, array $input): void
    {
        $this->authorization->assertBilling($context, $kind);
        $agreement = $this->agreements->find($kind, $context, $agreementId);
        $data = $this->documents->voidInput($input);
        DB::transaction(function () use ($kind, $context, $agreement, $chargeId, $data): void {
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->documents->assertAgreement($agreement, (int) $data['expected_version']);
            $charge = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->where('agreement_id', $agreement->id)->lockForUpdate()->findOrFail($chargeId);
            $this->documents->void($charge, BaseChargeSource::forKind($kind)->value, $context, $data);
        });
    }

    private function chargeClass(AgreementKind $kind): string
    {
        return $kind === AgreementKind::Customer ? CustomerBaseCharge::class : OwnerBaseCharge::class;
    }
}
