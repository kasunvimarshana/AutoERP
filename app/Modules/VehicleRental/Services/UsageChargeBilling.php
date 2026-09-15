<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceSourceService;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Enums\UsageChargePolicy;
use Modules\VehicleRental\Enums\UsageChargeSource;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RunningChart;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class UsageChargeBilling
{
    public function __construct(private readonly RunningChartService $charts, private readonly AgreementService $agreements,
        private readonly RentalAuthorization $authorization, private readonly RentalChargeDocuments $documents, private readonly InvoiceSourceService $sources) {}

    public function create(AgreementKind $kind, AgreementContext $context, int $chartId, array $input): Invoice
    {
        $data = $this->documents->documentInput($input) + Validator::make($input, [
            'policy' => ['required', Rule::enum(UsageChargePolicy::class)],
            'component' => ['required', Rule::enum(UsageChargeComponent::class)],
        ])->validate();

        return $this->locked($kind, $context, $chartId, $input, function (Agreement $agreement, RunningChart $chart) use ($kind, $context, $data): Invoice {
            $component = UsageChargeComponent::from($data['component']);
            $class = $this->chargeClass($kind);
            if ($class::query()->forContext($context->tenantId, $context->organizationUnitId)->where('running_chart_id', $chart->id)
                ->where('component', $component->value)->whereNull('voided_at')->lockForUpdate()->exists()) {
                throw new ConflictHttpException('This chart component already has a charge on this side. Review or reissue its recorded charge.');
            }
            // Resolve the exact contract revision selected at vehicle assignment, not another side's rate.
            $versionField = $kind->value.'_agreement_version';
            $revision = $agreement->history()->where('row_version', $chart->vehicleUse->$versionField)->sole();
            $quote = $this->quote($component, $chart, $revision->snapshot['terms']);
            if ($quote['error'] !== null) {
                throw ValidationException::withMessages(['component' => [$quote['error']]]);
            }
            $rate = $quote['rate'];
            $quantity = $quote['quantity'];
            $amount = $quote['amount'];
            $start = CarbonImmutable::parse($chart->starts_at_input);
            $end = $chart->ends_at->setTimezone($start->getTimezone());
            $calculation = ['policy' => $data['policy'], 'component' => $component->value, 'label' => $component->label(),
                'quantity' => $quantity, 'denominator' => $component->denominator(), 'rate' => $rate, 'amount' => $amount,
                'currency' => $agreement->currency_code_snapshot,
                'agreement' => ['id' => $agreement->id, 'reference' => $agreement->reference, 'version' => $revision->row_version, 'kind' => $kind->value],
                'chart' => ['id' => $chart->id, 'reference' => $chart->reference, 'version' => $chart->row_version,
                    'starts_at' => $chart->starts_at_input, 'ends_at' => $chart->ends_at_input],
                'description' => $component->label().' · '.$chart->reference.' · '.$quantity.($component === UsageChargeComponent::NightOut ? ' nights' : ' minutes').' at '.$rate.($component === UsageChargeComponent::NightOut ? '/night' : '/hour')];
            $charge = new $class;
            $charge->forceFill(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId,
                'agreement_id' => $agreement->id, 'running_chart_id' => $chart->id, 'component' => $component->value,
                'period_from' => $start->toDateString(), 'period_until' => $end->toDateString(),
                'amount' => $amount, 'calculation' => $calculation, 'actor_id' => $context->actorId])->save();

            return $this->issue($kind, $context, $agreement, $charge, $data);
        });
    }

    public function reissue(AgreementKind $kind, AgreementContext $context, int $chartId, int $chargeId, array $input): Invoice
    {
        $data = $this->documents->documentInput($input);

        return $this->locked($kind, $context, $chartId, $input, function (Agreement $agreement, RunningChart $chart) use ($kind, $context, $chargeId, $data): Invoice {
            $charge = $this->findCharge($kind, $context, $chart, $chargeId);
            if ($charge->voided_at !== null) {
                throw new ConflictHttpException('A voided charge cannot be reissued.');
            }

            return $this->issue($kind, $context, $agreement, $charge, $data);
        });
    }

    public function void(AgreementKind $kind, AgreementContext $context, int $chartId, int $chargeId, array $input): void
    {
        $data = $this->documents->voidInput($input);
        $this->locked($kind, $context, $chartId, $input, function (Agreement $agreement, RunningChart $chart) use ($kind, $context, $chargeId, $data): void {
            $this->documents->void($this->findCharge($kind, $context, $chart, $chargeId), UsageChargeSource::forKind($kind)->value, $context, $data);
        });
    }

    public function list(AgreementKind $kind, AgreementContext $context, int $chartId, int $perPage): array
    {
        [$chart, $agreement] = $this->scope($kind, $context, $chartId);
        $page = $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('running_chart_id', $chart->id)->orderByDesc('id')->paginate($perPage);
        $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, UsageChargeSource::forKind($kind)->value, $page->getCollection()->pluck('id')->all());

        $versionField = $kind->value.'_agreement_version';
        $revision = $agreement->history()->where('row_version', $chart->vehicleUse->$versionField)->sole();

        return ['agreement' => ['reference' => $agreement->reference, 'version' => $agreement->row_version],
            'currency' => $agreement->currency_code_snapshot,
            'components' => array_map(fn (UsageChargeComponent $component): array => $this->quote($component, $chart, $revision->snapshot['terms']), UsageChargeComponent::cases()),
            'charges' => $page->through(static fn (RentalCharge $charge): array => ['id' => $charge->id, 'row_version' => $charge->row_version,
                'component' => $charge->component, 'amount' => $charge->amount, 'calculation' => $charge->calculation,
                'voided_at' => $charge->voided_at, 'void_reason' => $charge->void_reason, 'invoices' => $documents[$charge->id] ?? []])];
    }

    private function quote(UsageChargeComponent $component, RunningChart $chart, array $terms): array
    {
        $quantity = $chart->{$component->observation()};
        $rate = $terms[$component->rate()] ?? null;
        $error = null;
        $amount = null;
        if ($quantity === null || $rate === null) {
            $error = 'The recorded quantity and agreed rate must both be known. Blank is not zero.';
        } else {
            // Multiply integer minutes before division: never truncate hours before pricing.
            $amount = bcdiv(bcmul((string) $quantity, $rate, AgreementFields::DECIMAL_SCALE), (string) $component->denominator(), AgreementFields::DECIMAL_SCALE);
            if (! preg_match(AgreementFields::DECIMAL_PATTERN, $amount)) {
                $error = 'The calculated amount exceeds supported monetary precision.';
            } elseif (bccomp($amount, AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE) <= 0) {
                $error = 'Zero observations or zero rates do not create an invoice.';
            }
        }

        return ['component' => $component->value, 'label' => $component->label(), 'quantity' => $quantity,
            'rate' => $rate, 'denominator' => $component->denominator(), 'amount' => $amount, 'error' => $error];
    }

    private function scope(AgreementKind $kind, AgreementContext $context, int $chartId): array
    {
        $this->authorization->assertBilling($context, $kind);
        $chart = $this->charts->find($context, $chartId);
        $field = $kind->value.'_agreement_id';
        if ($chart->vehicleUse->$field === null) {
            throw ValidationException::withMessages(['agreement' => ['This vehicle use has no owner agreement.']]);
        }

        return [$chart, $this->agreements->find($kind, $context, (int) $chart->vehicleUse->$field)];
    }

    private function locked(AgreementKind $kind, AgreementContext $context, int $chartId, array $input, callable $work): mixed
    {
        [$snapshot, $agreement] = $this->scope($kind, $context, $chartId);
        $data = Validator::make($input, ['expected_chart_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'expected_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION]])->validate();

        return DB::transaction(function () use ($snapshot, $agreement, $context, $data, $work): mixed {
            // Match physical operations: vehicle first, then agreement and chart. Reversal uses the same vehicle mutex.
            Vehicle::query()->withTrashed()->where('tenant_id', $context->tenantId)->lockForUpdate()->findOrFail($snapshot->vehicleUse->vehicle_id);
            $agreement = $agreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreement->id);
            $this->documents->assertAgreement($agreement, (int) $data['expected_version']);
            $chart = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($snapshot->id);
            if ($chart->row_version !== (int) $data['expected_chart_version']) {
                throw new ConflictHttpException('This chart changed. Reload before billing.');
            }
            if ($chart->status !== RunningChartStatus::Finalized) {
                throw new ConflictHttpException('Only finalized chart evidence can support a charge.');
            }

            return $work($agreement, $chart);
        });
    }

    private function findCharge(AgreementKind $kind, AgreementContext $context, RunningChart $chart, int $id): RentalCharge
    {
        return $this->chargeClass($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('running_chart_id', $chart->id)->lockForUpdate()->findOrFail($id);
    }

    private function issue(AgreementKind $kind, AgreementContext $context, Agreement $agreement, RentalCharge $charge, array $data): Invoice
    {
        return $this->documents->issue($kind, $context, $agreement, $charge, UsageChargeSource::forKind($kind)->value, $charge->calculation['description'], $data);
    }

    private function chargeClass(AgreementKind $kind): string
    {
        return $kind === AgreementKind::Customer ? CustomerUsageCharge::class : OwnerUsageCharge::class;
    }
}
