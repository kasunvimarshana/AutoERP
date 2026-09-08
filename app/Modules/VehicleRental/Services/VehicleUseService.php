<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\Ownership\CompanyVehicleCoverageService;
use Modules\Vehicle\Services\VehicleAvailabilityService;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleUseService
{
    public function __construct(private readonly RentalAuthorization $authorization, private readonly AgreementValidation $validation,
        private readonly VehicleAvailabilityService $availability, private readonly VehicleUseAvailabilityBlocker $rentalBlocker,
        private readonly CompanyVehicleCoverageService $companyCoverage) {}

    public function list(AgreementContext $context, int $customerAgreement, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertUse($context, false);
        CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($customerAgreement);

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->where('customer_agreement_id', $customerAgreement)
            ->with(OperationalFields::USE_RELATIONS)->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }

    public function find(AgreementContext $context, int $id): VehicleUse
    {
        $this->authorization->assertUse($context, false);

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->with(OperationalFields::USE_RELATIONS)->findOrFail($id);
    }

    public function plan(AgreementContext $context, int $customerAgreement, int $expectedAgreementVersion, array $input): VehicleUse
    {
        $this->authorization->assertUse($context, true);
        $this->validation->assertContext($context);
        $data = Validator::make($input, ['vehicle_id' => ['required', 'integer', 'min:1'], 'owner_agreement_id' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['required', 'string'], 'ends_at' => ['required', 'string'], 'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
        $start = OperationalTime::parse($data['starts_at'], 'starts_at');
        $end = OperationalTime::parse($data['ends_at'], 'ends_at');
        if ($end <= $start) {
            throw ValidationException::withMessages(['ends_at' => ['End must be later than start.']]);
        }

        return DB::transaction(function () use ($context, $customerAgreement, $expectedAgreementVersion, $data, $start, $end): VehicleUse {
            $vehicle = $this->lockVehicle($context, (int) $data['vehicle_id']);
            $customer = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($customerAgreement);
            $this->version($customer->row_version, $expectedAgreementVersion);
            $this->coverage($customer, $start->toDateString(), $end->subSecond()->toDateString());
            $owner = null;
            if (($data['owner_agreement_id'] ?? null) !== null) {
                $owner = OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($data['owner_agreement_id']);
                if ((int) $owner->vehicle_id !== (int) $vehicle->id) {
                    throw ValidationException::withMessages(['owner_agreement_id' => ['Owner agreement must cover the selected vehicle.']]);
                }
                $this->coverage($owner, $start->toDateString(), $end->subSecond()->toDateString());
            } elseif (! $this->companyCoverage->covers($context->tenantId, (int) $vehicle->id, OperationalTime::database($start), OperationalTime::database($end))) {
                throw ValidationException::withMessages(['owner_agreement_id' => ['Select the owner agreement, or record valid company ownership in Vehicle for the full planned period.']]);
            }
            $this->assertAvailable($context, (int) $vehicle->id, OperationalTime::database($start), OperationalTime::database($end));
            $record = new VehicleUse;
            $record->forceFill(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId,
                'customer_agreement_id' => $customer->id, 'customer_agreement_version' => $customer->row_version,
                'owner_agreement_id' => $owner?->id, 'owner_agreement_version' => $owner?->row_version,
                'vehicle_id' => $vehicle->id, 'vehicle_label_snapshot' => $vehicle->registration_number ?: $vehicle->vehicle_number,
                'starts_at' => OperationalTime::database($start), 'ends_at' => OperationalTime::database($end),
                'starts_at_input' => $data['starts_at'], 'ends_at_input' => $data['ends_at'], 'notes' => $data['notes'] ?? null,
                'row_version' => AgreementFields::INITIAL_VERSION, 'status' => VehicleUseStatus::Planned])->save();
            $this->record($record, $context, VehicleUseAction::Plan);

            return $record->load(OperationalFields::USE_RELATIONS);
        });
    }

    public function transition(AgreementContext $context, int $id, int $expectedVersion, VehicleUseAction $action, array $input): VehicleUse
    {
        $this->authorization->assertUse($context, true);
        $this->validation->assertContext($context);
        $data = Validator::make($input, ['occurred_at' => ['nullable', 'string'], 'odometer' => ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN],
            'reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
        if (trim($data['reason']) === '') {
            throw ValidationException::withMessages(['reason' => ['Record the handover, return or cancellation reason.']]);
        }

        return DB::transaction(function () use ($context, $id, $expectedVersion, $action, $data): VehicleUse {
            $snapshot = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
            $vehicle = Vehicle::query()->where('tenant_id', $context->tenantId)->lockForUpdate()->findOrFail($snapshot->vehicle_id);
            $customer = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($snapshot->customer_agreement_id);
            $owner = $snapshot->owner_agreement_id === null ? null : OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($snapshot->owner_agreement_id);
            $record = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            $this->version($record->row_version, $expectedVersion);
            if ($action === VehicleUseAction::Cancel && $record->status === VehicleUseStatus::Planned) {
                $record->status = VehicleUseStatus::Cancelled;
            } elseif ($action === VehicleUseAction::Handover && $record->status === VehicleUseStatus::Planned) {
                $at = OperationalTime::parse($data['occurred_at'] ?? null, 'occurred_at');
                if ($at->isFuture() || $at < $record->starts_at || $at >= $record->ends_at) {
                    throw ValidationException::withMessages(['occurred_at' => ['Record an actual handover within the planned period, not a future event.']]);
                }
                $this->coverage($customer, $at->toDateString(), $at->toDateString());
                if ($owner !== null) {
                    $this->coverage($owner, $at->toDateString(), $at->toDateString());
                }
                $this->assertAvailable($context, (int) $record->vehicle_id, OperationalTime::database($at), $record->ends_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), (int) $record->id);
                if ($owner === null && ! $this->companyCoverage->covers($context->tenantId, (int) $record->vehicle_id, OperationalTime::database($at), $record->ends_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))) {
                    throw ValidationException::withMessages(['owner_agreement_id' => ['Company ownership no longer covers this use. Review the source before handover.']]);
                }
                app(VehicleStatusService::class)->changeTo($vehicle, VehicleStatus::Rented, $context->actorId, OperationalFields::HANDOVER_STATUS_REASON.$customer->reference);
                $record->status = VehicleUseStatus::InCustody;
                $record->handed_over_at = OperationalTime::database($at);
                $record->handover_odometer = $data['odometer'] ?? null;
            } elseif ($action === VehicleUseAction::ReturnVehicle && $record->status === VehicleUseStatus::InCustody) {
                $at = OperationalTime::parse($data['occurred_at'] ?? null, 'occurred_at');
                if ($at->isFuture() || $at <= $record->handed_over_at) {
                    throw ValidationException::withMessages(['occurred_at' => ['Return must follow handover and cannot be in the future.']]);
                }
                if (($data['odometer'] ?? null) !== null && $record->handover_odometer !== null && bccomp($data['odometer'], $record->handover_odometer, AgreementFields::DECIMAL_SCALE) < 0) {
                    throw ValidationException::withMessages(['odometer' => ['Return odometer cannot be lower than handover.']]);
                }
                if ($this->rentalBlocker->conflicts($context->tenantId, (int) $record->vehicle_id, $record->handed_over_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), OperationalTime::database($at), (int) $record->id)) {
                    throw new ConflictHttpException('Return conflicts with another vehicle-use period. Review or cancel conflicting plans first.');
                }
                $charts = RunningChart::query()->forTenant($context->tenantId)->where('vehicle_use_id', $record->id)->where('status', RunningChartStatus::Finalized->value);
                if ((clone $charts)->where('ends_at', '>', OperationalTime::database($at))->lockForUpdate()->first(['id']) !== null || (($data['odometer'] ?? null) !== null && (clone $charts)->where('end_odometer', '>', $data['odometer'])->lockForUpdate()->first(['id']) !== null)) {
                    throw new ConflictHttpException('Return contradicts finalized usage. Review the Running Charts first.');
                }
                if ($vehicle->status === VehicleStatus::Rented) {
                    app(VehicleStatusService::class)->changeTo($vehicle, VehicleStatus::Active, $context->actorId, OperationalFields::RETURN_STATUS_REASON.$customer->reference);
                }
                $record->status = VehicleUseStatus::Returned;
                $record->returned_at = OperationalTime::database($at);
                $record->return_odometer = $data['odometer'] ?? null;
            } else {
                throw ValidationException::withMessages(['status' => ['This action is invalid for the current vehicle-use state.']]);
            }
            $record->row_version++;
            $record->save();
            $this->record($record, $context, $action, $data['reason']);

            return $record->load(OperationalFields::USE_RELATIONS);
        });
    }

    private function assertAvailable(AgreementContext $context, int $vehicle, string $start, string $end, ?int $exclude = null): void
    {
        try {
            $this->availability->assertAvailable($context->tenantId, $context->organizationUnitId, $vehicle, $start, $end, $this->rentalBlocker);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['vehicle_id' => [$error->getMessage()]]);
        }
        if ($this->rentalBlocker->conflicts($context->tenantId, $vehicle, $start, $end, $exclude)) {
            throw new ConflictHttpException(VehicleUseAvailabilityBlocker::REASON);
        }
    }

    private function lockVehicle(AgreementContext $context, int $id): Vehicle
    {
        return Vehicle::query()->where('tenant_id', $context->tenantId)->where(fn ($q) => $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $context->organizationUnitId))->lockForUpdate()->findOrFail($id);
    }

    private function coverage(Agreement $agreement, string $start, string $end): void
    {
        if ($agreement->status !== AgreementStatus::Active || $agreement->starts_on->toDateString() > $start || ($agreement->ends_on !== null && $agreement->ends_on->toDateString() < $end)) {
            throw ValidationException::withMessages(['agreement' => ['An active agreement must cover the complete selected period.']]);
        }
    }

    private function version(int $actual, int $expected): void
    {
        if ($actual !== $expected) {
            throw new ConflictHttpException('This record changed. Reload before continuing.');
        }
    }

    private function record(VehicleUse $record, AgreementContext $context, VehicleUseAction $action, ?string $reason = null): void
    {
        $record->history()->make()->forceFill(['tenant_id' => $context->tenantId, 'actor_id' => $context->actorId, 'row_version' => $record->row_version,
            'action' => $action->value, 'reason' => $reason, 'snapshot' => $record->attributesToArray(), 'recorded_at' => now()])->save();
    }
}
