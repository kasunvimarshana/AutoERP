<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\DriverIdentitySource;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RunningChartService
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly AgreementValidation $contextValidation,
        private readonly RunningChartValidation $validation,
        private readonly OdometerContinuity $odometerContinuity,
        private readonly RunningChartNumberService $numbers,
    ) {}

    public function list(AgreementContext $context, int $useId, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, false);
        VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($useId);

        return RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->where('vehicle_use_id', $useId)->with(['vehicleUse', 'correctsChart', 'correctedByChart'])->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }

    public function find(AgreementContext $context, int $id): RunningChart
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, false);

        return RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->with(['vehicleUse', 'correctsChart', 'correctedByChart'])->findOrFail($id);
    }

    public function delete(AgreementContext $context, int $id): never
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, true);
        throw new \LogicException('Preserve Running Chart evidence. Use reversal.');
    }

    public function create(AgreementContext $context, int $useId, int $expectedUseVersion, array $input, ?int $corrects = null): RunningChart
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, true);
        $this->contextValidation->assertContext($context);

        if (empty(trim((string) ($input['reference'] ?? '')))) {
            $input['reference'] = $this->numbers->next($context, $input['starts_at'] ?? null);
        }

        return $this->atomic(function () use ($context, $useId, $expectedUseVersion, $input, $corrects): RunningChart {
            $use = $this->lockedUse($context, $useId);
            $this->version($use->row_version, $expectedUseVersion);
            if ($corrects !== null) {
                $original = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($corrects);
                if ((int) $original->vehicle_use_id !== $useId || $original->status !== RunningChartStatus::Reversed) {
                    throw ValidationException::withMessages(['corrects_chart_id' => ['Select a reversed chart from the same vehicle use.']]);
                }
            }
            $data = $this->validation->validate($input, $context);
            $this->assertCoverage($use, $data['starts_at'], $data['ends_at']);
            $chart = new RunningChart;
            $chart->forceFill(array_merge($data, ['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'vehicle_use_id' => $useId, 'vehicle_use_version' => $use->row_version, 'corrects_chart_id' => $corrects, 'status' => RunningChartStatus::Draft, 'row_version' => AgreementFields::INITIAL_VERSION]))->save();
            $this->record($chart, $context, RunningChartAction::Create);

            return $chart->load(['vehicleUse', 'correctsChart', 'correctedByChart']);
        });
    }

    public function change(AgreementContext $context, int $id, int $expectedVersion, RunningChartAction $action, array $input = [], ?string $reason = null): RunningChart
    {
        $this->authorization->assertChart($context, $action, true);
        $this->contextValidation->assertContext($context);

        return $this->atomic(function () use ($context, $id, $expectedVersion, $action, $input, $reason): RunningChart {
            $snapshot = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
            $use = $this->lockedUse($context, (int) $snapshot->vehicle_use_id);
            if ($action === RunningChartAction::Finalize) {
                $this->lockDriverTimeline($context, $snapshot);
            }
            $chart = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            $this->version($chart->row_version, $expectedVersion);
            if ($action === RunningChartAction::Update && $chart->status === RunningChartStatus::Draft) {
                $existing = [
                    'reference' => $chart->reference,
                    'starts_at' => $chart->starts_at->toIso8601String(),
                    'ends_at' => $chart->ends_at->toIso8601String(),
                    'start_odometer' => $chart->start_odometer,
                    'end_odometer' => $chart->end_odometer,
                    'garage_km' => $chart->garage_km,
                    'commercial_km' => $chart->commercial_km,
                    'ac_mode' => $chart->ac_mode?->value,
                    'normal_ot_minutes' => $chart->normal_ot_minutes,
                    'double_ot_minutes' => $chart->double_ot_minutes,
                    'triple_ot_minutes' => $chart->triple_ot_minutes,
                    'night_outs' => $chart->night_outs,
                    'driver_identity_source' => $chart->driver_identity_source?->value,
                    'driver_employee_id' => $chart->driver_employee_id,
                    'driver_name_snapshot' => $chart->driver_name_snapshot,
                    'driver_reference_snapshot' => $chart->driver_reference_snapshot,
                    'driver_observation' => $chart->driver_observation,
                    'notes' => $chart->notes,
                ];
                $mergedInput = array_merge($existing, $input);
                if (empty(trim((string) ($mergedInput['reference'] ?? '')))) {
                    $mergedInput['reference'] = $chart->reference;
                }
                $data = $this->validation->validate($mergedInput, $context);
                $this->assertCoverage($use, $data['starts_at'], $data['ends_at']);
                $chart->forceFill($data);
            } elseif ($action === RunningChartAction::Finalize && $chart->status === RunningChartStatus::Draft) {
                $startsAt = $chart->starts_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT);
                $endsAt = $chart->ends_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT);
                $this->assertCoverage($use, $startsAt, $endsAt);
                $chart->starts_at = $startsAt;
                $chart->ends_at = $endsAt;
                $timeline = RunningChart::query()->forTenant($context->tenantId)->whereKeyNot($chart->id)->where('status', RunningChartStatus::Finalized->value)->whereHas('vehicleUse', fn ($q) => $q->where('vehicle_id', $use->vehicle_id));
                if ((clone $timeline)->where('starts_at', '<', $chart->ends_at)->where('ends_at', '>', $chart->starts_at)->lockForUpdate()->first(['id']) !== null) {
                    throw new ConflictHttpException('Finalized usage already covers this vehicle period.');
                }
                $this->assertDriverAvailable($context, $chart);
                $this->odometerContinuity->assertReading($context->tenantId, (int) $use->vehicle_id, $chart->starts_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), $chart->start_odometer);
                $this->odometerContinuity->assertReading($context->tenantId, (int) $use->vehicle_id, $chart->ends_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), $chart->end_odometer);
                $chart->vehicle_use_version = $use->row_version;
                $chart->status = RunningChartStatus::Finalized;
                $chart->finalized_at = now();
            } elseif ($action === RunningChartAction::Reverse && $chart->status === RunningChartStatus::Finalized) {
                Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
                if (trim($reason ?? '') === '') {
                    throw ValidationException::withMessages(['reason' => ['Explain the reversal.']]);
                }
                foreach ([CustomerUsageCharge::class, OwnerUsageCharge::class] as $charges) {
                    if ($charges::query()->forTenant($context->tenantId)->where('running_chart_id', $chart->id)->whereNull('voided_at')->lockForUpdate()->exists()) {
                        throw new ConflictHttpException('Release the invoices and void all customer and owner usage charges before reversing this chart.');
                    }
                }
                $chart->status = RunningChartStatus::Reversed;
                $chart->reversed_at = now();
            } else {
                throw ValidationException::withMessages(['status' => ['Invalid action. Finalized evidence cannot be edited.']]);
            }
            $chart->row_version++;
            $chart->save();
            $this->record($chart, $context, $action, $reason);

            return $chart->load(['vehicleUse', 'correctsChart', 'correctedByChart']);
        });
    }

    private function lockDriverTimeline(AgreementContext $context, RunningChart $chart): void
    {
        $timeline = $this->driverIdentityQuery($context, $chart);
        if ($timeline === null) {
            return;
        }

        // Lock every row for this identity in stable order before the current chart row. Two
        // concurrent finalizations for the same driver therefore serialize before overlap checks.
        $timeline->orderBy('id')->lockForUpdate()->get(['id']);
    }

    private function assertDriverAvailable(AgreementContext $context, RunningChart $chart): void
    {
        $conflicts = $this->driverIdentityQuery($context, $chart);
        if ($conflicts === null) {
            return;
        }

        if ($conflicts->whereKeyNot($chart->id)
            ->where('status', RunningChartStatus::Finalized->value)
            ->where('starts_at', '<', $chart->ends_at)
            ->where('ends_at', '>', $chart->starts_at)
            ->first(['id']) !== null) {
            throw new ConflictHttpException('This driver already has finalized Rental usage during the selected period.');
        }
    }

    private function driverIdentityQuery(AgreementContext $context, RunningChart $chart): ?Builder
    {
        if ($chart->driver_identity_source === null) {
            return null;
        }

        $query = RunningChart::query()->forTenant($context->tenantId);
        if ($chart->driver_identity_source === DriverIdentitySource::Employee) {
            return $query->where('driver_identity_source', DriverIdentitySource::Employee->value)
                ->where('driver_employee_id', $chart->driver_employee_id);
        }

        return $query->where('driver_identity_source', DriverIdentitySource::External->value)
            ->where('driver_reference_snapshot', $chart->driver_reference_snapshot);
    }

    private function lockedUse(AgreementContext $context, int $id): VehicleUse
    {
        $snapshot = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
        Vehicle::query()->withTrashed()->where('tenant_id', $context->tenantId)->lockForUpdate()->findOrFail($snapshot->vehicle_id);

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
    }

    private function assertCoverage(VehicleUse $use, string &$start, string &$end): void
    {
        if (! in_array($use->status, [VehicleUseStatus::InCustody, VehicleUseStatus::Returned], true) || $use->handed_over_at === null) {
            throw ValidationException::withMessages(['period' => ['Usage must fall within actual customer custody.']]);
        }

        $handover = CarbonImmutable::createFromFormat(OperationalFields::DATABASE_TIMESTAMP_FORMAT, $use->handed_over_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), OperationalFields::UTC);
        $usageStart = CarbonImmutable::createFromFormat(OperationalFields::DATABASE_TIMESTAMP_FORMAT, $start, OperationalFields::UTC);
        $usageEnd = CarbonImmutable::createFromFormat(OperationalFields::DATABASE_TIMESTAMP_FORMAT, $end, OperationalFields::UTC);

        if ($usageStart < $handover) {
            if ($usageStart >= $handover->subMinute()) {
                $start = $use->handed_over_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT);
                $usageStart = $handover;
            } else {
                throw ValidationException::withMessages([
                    'period' => ['Usage must fall within actual customer custody.'],
                    'starts_at' => ['Usage must fall within actual customer custody.'],
                ]);
            }
        }

        if ($use->returned_at !== null) {
            $return = CarbonImmutable::createFromFormat(OperationalFields::DATABASE_TIMESTAMP_FORMAT, $use->returned_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), OperationalFields::UTC);
            if ($usageEnd > $return) {
                if ($usageEnd <= $return->addMinute()) {
                    $end = $use->returned_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT);
                    $usageEnd = $return;
                } else {
                    throw ValidationException::withMessages([
                        'period' => ['Usage must fall within actual customer custody.'],
                        'ends_at' => ['Usage must fall within actual customer custody.'],
                    ]);
                }
            }
        }

        if ($usageEnd <= $usageStart) {
            throw ValidationException::withMessages(['ends_at' => ['Usage end must follow usage start.']]);
        }
    }

    private function version(int $actual, int $expected): void
    {
        if ($actual !== $expected) {
            throw new ConflictHttpException('This record changed. Reload before continuing.');
        }
    }

    private function record(RunningChart $chart, AgreementContext $context, RunningChartAction $action, ?string $reason = null): void
    {
        $chart->history()->make()->forceFill(['tenant_id' => $context->tenantId, 'actor_id' => $context->actorId, 'row_version' => $chart->row_version, 'action' => $action->value, 'reason' => $reason, 'snapshot' => $chart->attributesToArray(), 'recorded_at' => now()])->save();
    }

    private function atomic(callable $operation): RunningChart
    {
        try {
            return DB::transaction($operation);
        } catch (UniqueConstraintViolationException $error) {
            throw new ConflictHttpException('Chart reference or correction already exists. Reload and review.', $error);
        }
    }
}
