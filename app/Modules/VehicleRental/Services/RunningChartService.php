<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RunningChartService
{
    public function __construct(private readonly RentalAuthorization $authorization, private readonly AgreementValidation $contextValidation, private readonly RunningChartValidation $validation) {}

    public function list(AgreementContext $context, int $useId, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, false);
        VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($useId);

        return RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->where('vehicle_use_id', $useId)->with(['vehicleUse', 'correctsChart'])->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }

    public function find(AgreementContext $context, int $id): RunningChart
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, false);

        return RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->with(['vehicleUse', 'correctsChart'])->findOrFail($id);
    }

    public function create(AgreementContext $context, int $useId, int $expectedUseVersion, array $input, ?int $corrects = null): RunningChart
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, true);
        $this->contextValidation->assertContext($context);

        return $this->atomic(function () use ($context, $useId, $expectedUseVersion, $input, $corrects): RunningChart {
            $use = $this->lockedUse($context, $useId);
            $this->version($use->row_version, $expectedUseVersion);
            if ($corrects !== null) {
                $original = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($corrects);
                if ((int) $original->vehicle_use_id !== $useId || $original->status !== RunningChartStatus::Reversed) {
                    throw ValidationException::withMessages(['corrects_chart_id' => ['Select a reversed chart from the same vehicle use.']]);
                }
            }
            $data = $this->validation->validate($input);
            $this->assertCoverage($use, $data['starts_at'], $data['ends_at']);
            $chart = new RunningChart;
            $chart->forceFill(array_merge($data, ['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'vehicle_use_id' => $useId, 'vehicle_use_version' => $use->row_version, 'corrects_chart_id' => $corrects, 'status' => RunningChartStatus::Draft, 'row_version' => AgreementFields::INITIAL_VERSION]))->save();
            $this->record($chart, $context, RunningChartAction::Create);

            return $chart->load(['vehicleUse', 'correctsChart']);
        });
    }

    public function change(AgreementContext $context, int $id, int $expectedVersion, RunningChartAction $action, array $input = [], ?string $reason = null): RunningChart
    {
        $this->authorization->assertChart($context, $action, true);
        $this->contextValidation->assertContext($context);

        return $this->atomic(function () use ($context, $id, $expectedVersion, $action, $input, $reason): RunningChart {
            $snapshot = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
            $use = $this->lockedUse($context, (int) $snapshot->vehicle_use_id);
            $chart = RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            $this->version($chart->row_version, $expectedVersion);
            if ($action === RunningChartAction::Update && $chart->status === RunningChartStatus::Draft) {
                $data = $this->validation->validate($input);
                $this->assertCoverage($use, $data['starts_at'], $data['ends_at']);
                $chart->forceFill($data);
            } elseif ($action === RunningChartAction::Finalize && $chart->status === RunningChartStatus::Draft) {
                $this->assertCoverage($use, $chart->starts_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), $chart->ends_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT));
                $timeline = RunningChart::query()->forTenant($context->tenantId)->whereKeyNot($chart->id)->where('status', RunningChartStatus::Finalized->value)->whereHas('vehicleUse', fn ($q) => $q->where('vehicle_id', $use->vehicle_id));
                if ((clone $timeline)->where('starts_at', '<', $chart->ends_at)->where('ends_at', '>', $chart->starts_at)->lockForUpdate()->first(['id']) !== null) {
                    throw new ConflictHttpException('Finalized usage already covers this vehicle period.');
                }
                $before = (clone $timeline)->where('ends_at', '<=', $chart->starts_at)->where(fn ($q) => $q->whereNotNull('end_odometer')->orWhereNotNull('start_odometer'))->orderByDesc('ends_at')->lockForUpdate()->first();
                $after = (clone $timeline)->where('starts_at', '>=', $chart->ends_at)->where(fn ($q) => $q->whereNotNull('start_odometer')->orWhereNotNull('end_odometer'))->orderBy('starts_at')->lockForUpdate()->first();
                // Use the earliest/latest known observations without filling missing measurements.
                $firstKnown = $chart->start_odometer ?? $chart->end_odometer;
                $lastKnown = $chart->end_odometer ?? $chart->start_odometer;
                $previousKnown = $before?->end_odometer ?? $before?->start_odometer;
                $followingKnown = $after?->start_odometer ?? $after?->end_odometer;
                foreach ([[$firstKnown, $use->handover_odometer], [$firstKnown, $previousKnown], [$use->return_odometer, $lastKnown], [$followingKnown, $lastKnown]] as [$later, $earlier]) {
                    if ($later !== null && $earlier !== null && bccomp($later, $earlier, AgreementFields::DECIMAL_SCALE) < 0) {
                        throw ValidationException::withMessages(['odometer' => ['Readings conflict with custody or adjacent finalized usage. Review the evidence.']]);
                    }
                }
                $chart->vehicle_use_version = $use->row_version;
                $chart->status = RunningChartStatus::Finalized;
                $chart->finalized_at = now();
            } elseif ($action === RunningChartAction::Reverse && $chart->status === RunningChartStatus::Finalized) {
                Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
                if (trim($reason ?? '') === '') {
                    throw ValidationException::withMessages(['reason' => ['Explain the reversal.']]);
                }
                $chart->status = RunningChartStatus::Reversed;
                $chart->reversed_at = now();
            } else {
                throw ValidationException::withMessages(['status' => ['Invalid action. Finalized evidence cannot be edited.']]);
            }
            $chart->row_version++;
            $chart->save();
            $this->record($chart, $context, $action, $reason);

            return $chart->load(['vehicleUse', 'correctsChart']);
        });
    }

    private function lockedUse(AgreementContext $context, int $id): VehicleUse
    {
        $snapshot = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
        Vehicle::query()->withTrashed()->where('tenant_id', $context->tenantId)->lockForUpdate()->findOrFail($snapshot->vehicle_id);

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
    }

    private function assertCoverage(VehicleUse $use, string $start, string $end): void
    {
        if (! in_array($use->status, [VehicleUseStatus::InCustody, VehicleUseStatus::Returned], true) || $use->handed_over_at === null || $start < $use->handed_over_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT) || ($use->returned_at !== null && $end > $use->returned_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))) {
            throw ValidationException::withMessages(['period' => ['Usage must fall within actual customer custody.']]);
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
