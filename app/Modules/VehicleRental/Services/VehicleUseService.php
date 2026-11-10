<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
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
        private readonly CompanyVehicleCoverageService $companyCoverage, private readonly OdometerContinuity $odometerContinuity,
        private readonly RentalCalendar $calendar) {}

    public function list(AgreementContext $context, int $customerAgreement, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertUse($context, false);
        CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($customerAgreement);

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->where('customer_agreement_id', $customerAgreement)
            ->with(OperationalFields::USE_RELATIONS)->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }

    public function availableVehicles(AgreementContext $context, ?string $startsAt, ?string $endsAt, ?int $categoryId = null): array
    {
        $this->authorization->assertUse($context, false);
        if (empty($startsAt)) {
            throw ValidationException::withMessages(['starts_at' => ['Provide the start date and time to check vehicle availability.']]);
        }
        [$start, $end] = OperationalTime::plannedPeriod($startsAt, $endsAt);
        $startDb = OperationalTime::database($start);
        $endDb = $end === null ? null : OperationalTime::database($end);

        $vehicles = Vehicle::query()
            ->where('tenant_id', $context->tenantId)
            ->when(
                $context->organizationUnitId === null,
                fn ($q) => $q->whereNull('organization_unit_id'),
                fn ($q) => $q->where(fn ($sub) => $sub->whereNull('organization_unit_id')->orWhere('organization_unit_id', $context->organizationUnitId)),
            )
            ->where('status', VehicleStatus::Active)
            ->when($categoryId !== null, fn ($q) => $q->where('vehicle_category_id', $categoryId))
            ->with(['make', 'model', 'category'])
            ->orderBy('registration_number')
            ->orderBy('vehicle_number')
            ->get();

        $available = [];
        foreach ($vehicles as $vehicle) {
            if ($this->rentalBlocker->conflicts($context->tenantId, (int) $vehicle->id, $startDb, $endDb)) {
                continue;
            }
            try {
                $this->availability->assertUnblocked($context->tenantId, $context->organizationUnitId, (int) $vehicle->id, $startDb, $endDb, $this->rentalBlocker);
            } catch (InvalidArgumentException) {
                continue;
            }
            $available[] = [
                'id' => (int) $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'registration_number' => $vehicle->registration_number,
                'display_label' => $vehicle->registration_number ?: $vehicle->vehicle_number,
                'make' => $vehicle->make?->name,
                'model' => $vehicle->model?->name,
                'category' => $vehicle->category?->name,
                'category_id' => $vehicle->vehicle_category_id,
                'odometer_reading' => (string) ($vehicle->odometer_reading ?? '0'),
            ];
        }

        return $available;
    }

    public function find(AgreementContext $context, int $id): VehicleUse
    {
        $this->authorization->assertUse($context, false);

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->with(OperationalFields::USE_RELATIONS)->findOrFail($id);
    }

    public function plan(AgreementContext $context, int $customerAgreement, int $expectedAgreementVersion, array $input, ?int $replaces = null): VehicleUse
    {
        $this->authorization->assertUse($context, true);
        $this->validation->assertContext($context);
        $data = $this->validatePlanInput($input);
        [$start, $end] = OperationalTime::plannedPeriod($data['starts_at'], $data['ends_at'] ?? null);

        return DB::transaction(function () use ($context, $customerAgreement, $expectedAgreementVersion, $data, $start, $end, $replaces): VehicleUse {
            $vehicle = $this->lockVehicle($context, (int) $data['vehicle_id']);
            $customer = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($customerAgreement);
            $this->version($customer->row_version, $expectedAgreementVersion);
            $this->coveragePeriod($customer, $context, $start, $end);
            $owner = null;
            if (($data['owner_agreement_id'] ?? null) !== null) {
                $owner = OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($data['owner_agreement_id']);
                if ((int) $owner->vehicle_id !== (int) $vehicle->id) {
                    throw ValidationException::withMessages(['owner_agreement_id' => ['Owner agreement must cover the selected vehicle.']]);
                }
                if ($end !== null) {
                    $this->coveragePeriod($owner, $context, $start, $end);
                } elseif ($customer->ends_on === null && $owner->ends_on !== null) {
                    throw ValidationException::withMessages(['owner_agreement_id' => ['An open-ended customer rental requires open-ended vehicle supply.']]);
                } else {
                    $this->coveragePeriod($owner, $context, $start, null);
                }
            } else {
                $companyEnd = $end !== null ? OperationalTime::database($end) : null;
                if (! $this->companyCoverage->covers($context->tenantId, (int) $vehicle->id, OperationalTime::database($start), $companyEnd)) {
                    throw ValidationException::withMessages(['owner_agreement_id' => ['Select the owner agreement, or record valid company ownership in Vehicle for the full planned period.']]);
                }
            }
            $this->assertAvailable($context, (int) $vehicle->id, OperationalTime::database($start), ($end === null ? null : OperationalTime::database($end)));
            if ($replaces !== null) {
                $previous = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($replaces);
                if ($previous->status !== VehicleUseStatus::Returned || (int) $previous->customer_agreement_id !== (int) $customer->id || (int) $previous->vehicle_id === (int) $vehicle->id || $previous->returned_at?->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT) !== OperationalTime::database($start)) {
                    throw ValidationException::withMessages(['replacement' => ['Replacement must continue the same customer agreement at the actual return boundary using a different vehicle.']]);
                }
            }
            $record = new VehicleUse;
            $record->forceFill(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId,
                'customer_agreement_id' => $customer->id, 'customer_agreement_version' => $customer->row_version,
                'owner_agreement_id' => $owner?->id, 'owner_agreement_version' => $owner?->row_version,
                'vehicle_id' => $vehicle->id, 'replaces_use_id' => $replaces, 'vehicle_label_snapshot' => $vehicle->registration_number ?: $vehicle->vehicle_number,
                'starts_at' => OperationalTime::database($start), 'ends_at' => ($end === null ? null : OperationalTime::database($end)),
                'starts_at_input' => $data['starts_at'], 'ends_at_input' => $data['ends_at'] ?? null, 'notes' => $data['notes'] ?? null,
                'row_version' => AgreementFields::INITIAL_VERSION, 'status' => VehicleUseStatus::Planned])->save();
            $this->record($record, $context, VehicleUseAction::Plan);

            return $record->load(OperationalFields::USE_RELATIONS);
        });
    }

    public function transition(AgreementContext $context, int $id, int $expectedVersion, VehicleUseAction $action, array $input): VehicleUse
    {
        $this->authorization->assertUse($context, true);
        $this->validation->assertContext($context);
        if (array_key_exists('odometer', $input) && ($input['odometer'] === '' || $input['odometer'] === null)) {
            $input['odometer'] = null;
        } elseif (isset($input['odometer']) && is_numeric($input['odometer'])) {
            $input['odometer'] = (string) $input['odometer'];
        }
        $data = Validator::make($input, ['occurred_at' => ['nullable', 'string'], 'odometer' => ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN],
            'reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH]])->validate();
        if (trim($data['reason']) === '') {
            throw ValidationException::withMessages(['reason' => ['Record the handover, return or cancellation reason.']]);
        }

        return DB::transaction(function () use ($context, $id, $expectedVersion, $action, $data): VehicleUse {
            $snapshot = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
            $vehicle = Vehicle::query()->withTrashed()->where('tenant_id', $context->tenantId)->lockForUpdate()->findOrFail($snapshot->vehicle_id);
            $customer = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($snapshot->customer_agreement_id);
            $owner = $snapshot->owner_agreement_id === null ? null : OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($snapshot->owner_agreement_id);
            $record = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            $this->version($record->row_version, $expectedVersion);
            if ($action === VehicleUseAction::Cancel && $record->status === VehicleUseStatus::Planned) {
                $record->status = VehicleUseStatus::Cancelled;
            } elseif ($action === VehicleUseAction::Handover && $record->status === VehicleUseStatus::Planned) {
                $tz = $this->calendar->timezone($context);
                $now = CarbonImmutable::now($tz)->addMinute();
                $at = OperationalTime::parse($data['occurred_at'] ?? null, 'occurred_at')->setTimezone($tz);
                if ($at->greaterThan($now)) {
                    throw ValidationException::withMessages(['occurred_at' => ['Handover time cannot be in the future.']]);
                }
                if ($record->ends_at !== null && $at >= $record->ends_at) {
                    throw ValidationException::withMessages(['occurred_at' => ['Record an actual handover before the planned return.']]);
                }
                $this->coverageInstant($customer, $context, $at);
                if ($owner !== null) {
                    $this->coverageInstant($owner, $context, $at);
                }
                $this->assertAvailable($context, (int) $record->vehicle_id, OperationalTime::database($at), $record->ends_at?->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), (int) $record->id);
                $companyHandoverEnd = $record->ends_at !== null
                    ? $record->ends_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT)
                    : ($customer->ends_on !== null ? OperationalTime::database(CarbonImmutable::parse($customer->ends_on->toDateString().' 23:59:59')) : null);
                if ($owner === null && ! $this->companyCoverage->covers($context->tenantId, (int) $record->vehicle_id, OperationalTime::database($at), $companyHandoverEnd)) {
                    throw ValidationException::withMessages(['owner_agreement_id' => ['Company ownership no longer covers this use. Review the source before handover.']]);
                }
                $this->odometerContinuity->assertReading($context->tenantId, (int) $record->vehicle_id, OperationalTime::database($at), $data['odometer'] ?? null);
                app(VehicleStatusService::class)->changeTo($vehicle, VehicleStatus::Rented, $context->actorId, OperationalFields::HANDOVER_STATUS_REASON.$customer->reference);
                $record->status = VehicleUseStatus::InCustody;
                $record->handed_over_at = OperationalTime::database($at);
                $record->handover_odometer = $data['odometer'] ?? null;
            } elseif ($action === VehicleUseAction::ReturnVehicle && $record->status === VehicleUseStatus::InCustody) {
                $tz = $this->calendar->timezone($context);
                $now = CarbonImmutable::now($tz)->addMinute();
                $at = OperationalTime::parse($data['occurred_at'] ?? null, 'occurred_at')->setTimezone($tz);
                if ($at->greaterThan($now)) {
                    throw ValidationException::withMessages(['occurred_at' => ['Return time cannot be in the future.']]);
                }
                if ($at <= $record->handed_over_at) {
                    throw ValidationException::withMessages(['occurred_at' => ['Return must follow handover.']]);
                }
                if (($data['odometer'] ?? null) !== null && $record->handover_odometer !== null && bccomp($data['odometer'], $record->handover_odometer, AgreementFields::DECIMAL_SCALE) < 0) {
                    throw ValidationException::withMessages(['odometer' => ['Return odometer cannot be lower than handover.']]);
                }
                if ($this->rentalBlocker->conflicts($context->tenantId, (int) $record->vehicle_id, $record->handed_over_at->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT), OperationalTime::database($at), (int) $record->id)) {
                    throw new ConflictHttpException('Return conflicts with another vehicle-use period. Review or cancel conflicting plans first.');
                }
                $charts = RunningChart::query()->forTenant($context->tenantId)->where('vehicle_use_id', $record->id)->where('status', RunningChartStatus::Finalized->value);
                if ((clone $charts)->where('ends_at', '>', OperationalTime::database($at))->lockForUpdate()->first(['id']) !== null || (($data['odometer'] ?? null) !== null && (clone $charts)->where(fn ($q) => $q->where('end_odometer', '>', $data['odometer'])->orWhere('start_odometer', '>', $data['odometer']))->lockForUpdate()->first(['id']) !== null)) {
                    throw new ConflictHttpException('Return contradicts finalized usage. Review the Running Charts first.');
                }
                $this->odometerContinuity->assertReading($context->tenantId, (int) $record->vehicle_id, OperationalTime::database($at), $data['odometer'] ?? null);
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

    public function replace(AgreementContext $context, int $id, int $expectedVersion, array $input): VehicleUse
    {
        $this->authorization->assertUse($context, true);
        $this->validation->assertContext($context);
        if (array_key_exists('return_odometer', $input) && ($input['return_odometer'] === '' || $input['return_odometer'] === null)) {
            $input['return_odometer'] = null;
        } elseif (isset($input['return_odometer']) && is_numeric($input['return_odometer'])) {
            $input['return_odometer'] = (string) $input['return_odometer'];
        }
        if (array_key_exists('handover_odometer', $input) && ($input['handover_odometer'] === '' || $input['handover_odometer'] === null)) {
            $input['handover_odometer'] = null;
        } elseif (isset($input['handover_odometer']) && is_numeric($input['handover_odometer'])) {
            $input['handover_odometer'] = (string) $input['handover_odometer'];
        }
        if (array_key_exists('owner_agreement_id', $input) && ($input['owner_agreement_id'] === '' || $input['owner_agreement_id'] === null)) {
            $input['owner_agreement_id'] = null;
        }
        if (array_key_exists('ends_at', $input) && ($input['ends_at'] === '' || (is_string($input['ends_at']) && trim($input['ends_at']) === ''))) {
            $input['ends_at'] = null;
        }
        if (array_key_exists('notes', $input) && ($input['notes'] === '' || (is_string($input['notes']) && trim($input['notes']) === ''))) {
            $input['notes'] = null;
        }
        $data = Validator::make($input, array_merge($this->planRules(), [
            'reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
            'return_odometer' => ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN],
            'handover_odometer' => ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN],
        ]))->validate();
        $data['owner_agreement_id'] = empty($data['owner_agreement_id']) ? null : (int) $data['owner_agreement_id'];
        $data['ends_at'] = empty($data['ends_at']) ? null : $data['ends_at'];
        $data['notes'] = empty($data['notes']) ? null : $data['notes'];
        $data['return_odometer'] = (isset($data['return_odometer']) && trim((string) $data['return_odometer']) !== '') ? (string) $data['return_odometer'] : null;
        $data['handover_odometer'] = (isset($data['handover_odometer']) && trim((string) $data['handover_odometer']) !== '') ? (string) $data['handover_odometer'] : null;
        if (trim($data['reason']) === '') {
            throw ValidationException::withMessages(['reason' => ['Explain the vehicle replacement.']]);
        }
        // Validate the complete planned replacement period before mutating the existing custody record.
        OperationalTime::plannedPeriod($data['starts_at'], $data['ends_at'] ?? null);

        $tz = $this->calendar->timezone($context);
        $now = CarbonImmutable::now($tz)->addMinute();
        $startsAt = OperationalTime::parse($data['starts_at'], 'starts_at')->setTimezone($tz);
        if ($startsAt->greaterThan($now)) {
            throw ValidationException::withMessages(['starts_at' => ['Replacement time cannot be in the future.']]);
        }

        return DB::transaction(function () use ($context, $id, $expectedVersion, $data, $startsAt): VehicleUse {
            $previous = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
            $this->version($previous->row_version, $expectedVersion);
            if ((int) $previous->vehicle_id === (int) $data['vehicle_id']) {
                throw ValidationException::withMessages(['vehicle_id' => ['Select a different physical vehicle.']]);
            }
            if ($previous->status !== VehicleUseStatus::InCustody) {
                throw ValidationException::withMessages(['status' => ['Only in-custody vehicles can be replaced.']]);
            }
            if ($previous->handed_over_at !== null && $startsAt <= $previous->handed_over_at) {
                throw ValidationException::withMessages(['starts_at' => ["Replacement time must follow the previous vehicle's handover time."]]);
            }
            Vehicle::query()->where('tenant_id', $context->tenantId)->whereIn('id', [(int) $previous->vehicle_id, (int) $data['vehicle_id']])->orderBy('id')->lockForUpdate()->get();

            try {
                $previous = $this->transition($context, $id, $expectedVersion, VehicleUseAction::ReturnVehicle, [
                    'occurred_at' => $data['starts_at'],
                    'odometer' => $data['return_odometer'] ?? null,
                    'reason' => $data['reason'],
                ]);
            } catch (ValidationException $e) {
                $messages = $e->errors();
                if (isset($messages['odometer'])) {
                    $messages['return_odometer'] = $messages['odometer'];
                    unset($messages['odometer']);
                }
                if (isset($messages['occurred_at'])) {
                    $messages['starts_at'] = array_map(static function (string $msg): string {
                        if (str_contains($msg, 'future')) {
                            return 'Replacement time cannot be in the future.';
                        }
                        if (str_contains($msg, 'handover') || str_contains($msg, 'follow')) {
                            return "Replacement time must follow the previous vehicle's handover time.";
                        }

                        return $msg;
                    }, $messages['occurred_at']);
                    unset($messages['occurred_at']);
                }
                throw ValidationException::withMessages($messages);
            }

            $customer = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($previous->customer_agreement_id);
            $next = $this->plan($context, (int) $customer->id, $customer->row_version, $data, (int) $previous->id);

            try {
                return $this->transition($context, (int) $next->id, $next->row_version, VehicleUseAction::Handover, [
                    'occurred_at' => $data['starts_at'],
                    'odometer' => $data['handover_odometer'] ?? null,
                    'reason' => $data['reason'],
                ]);
            } catch (ValidationException $e) {
                $messages = $e->errors();
                if (isset($messages['odometer'])) {
                    $messages['handover_odometer'] = $messages['odometer'];
                    unset($messages['odometer']);
                }
                if (isset($messages['occurred_at'])) {
                    $messages['starts_at'] = array_map(static function (string $msg): string {
                        if (str_contains($msg, 'future')) {
                            return 'Replacement time cannot be in the future.';
                        }

                        return $msg;
                    }, $messages['occurred_at']);
                    unset($messages['occurred_at']);
                }
                throw ValidationException::withMessages($messages);
            }
        });
    }

    private function assertAvailable(AgreementContext $context, int $vehicle, string $start, ?string $end, ?int $exclude = null): void
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

    public function delete(AgreementContext $context, int $id): never
    {
        $this->authorization->assertUse($context, true);
        throw new \LogicException('Preserve vehicle-use history. Cancel or return the vehicle.');
    }

    private function lockVehicle(AgreementContext $context, int $id): Vehicle
    {
        return Vehicle::query()->where('tenant_id', $context->tenantId)->where(fn ($q) => $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $context->organizationUnitId))->lockForUpdate()->findOrFail($id);
    }

    private function coveragePeriod(Agreement $agreement, AgreementContext $context, CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        $from = $this->calendar->civilDate($context, $start);
        $until = $end === null ? null : $this->calendar->civilDate($context, $end->subMicrosecond());
        $this->assertCoverage($agreement, $from, $until);
    }

    private function coverageInstant(Agreement $agreement, AgreementContext $context, CarbonImmutable $instant): void
    {
        $date = $this->calendar->civilDate($context, $instant);
        $this->assertCoverage($agreement, $date, $date);
    }

    private function assertCoverage(Agreement $agreement, string $start, ?string $end): void
    {
        $startsAfter = $agreement->starts_on->toDateString() > $start;
        $endsBefore = $agreement->ends_on !== null && (
            $end === null
                ? $agreement->ends_on->toDateString() < $start
                : $agreement->ends_on->toDateString() < $end
        );

        if ($agreement->status !== AgreementStatus::Active || $startsAfter || $endsBefore) {
            throw ValidationException::withMessages(['agreement' => ['An active agreement must cover the complete selected period.']]);
        }
    }

    private function validatePlanInput(array $input): array
    {
        if (array_key_exists('owner_agreement_id', $input) && ($input['owner_agreement_id'] === '' || $input['owner_agreement_id'] === null)) {
            $input['owner_agreement_id'] = null;
        }
        if (array_key_exists('ends_at', $input) && ($input['ends_at'] === '' || (is_string($input['ends_at']) && trim($input['ends_at']) === ''))) {
            $input['ends_at'] = null;
        }
        if (array_key_exists('notes', $input) && ($input['notes'] === '' || (is_string($input['notes']) && trim($input['notes']) === ''))) {
            $input['notes'] = null;
        }

        $validated = Validator::make($input, $this->planRules())->validate();
        $validated['owner_agreement_id'] = empty($validated['owner_agreement_id']) ? null : (int) $validated['owner_agreement_id'];
        $validated['ends_at'] = empty($validated['ends_at']) ? null : $validated['ends_at'];
        $validated['notes'] = empty($validated['notes']) ? null : $validated['notes'];

        return $validated;
    }

    private function planRules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'owner_agreement_id' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['required', 'string'],
            'ends_at' => ['sometimes', 'nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
        ];
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
