<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class AgreementService
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly AgreementValidation $validation,
        private readonly RentalCalendar $calendar,
    ) {}

    public function list(AgreementKind $kind, AgreementContext $context, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $this->authorization->assert($context, $kind, false);

        return $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->with($this->relations($kind))
            ->when($search !== null && $search !== '', fn ($q) => $q->where('reference', 'like', '%'.$search.'%'))->orderByDesc('id')->paginate($perPage);
    }

    public function find(AgreementKind $kind, AgreementContext $context, int $id): Agreement
    {
        $this->authorization->assert($context, $kind, false);

        return $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->with($this->relations($kind))->findOrFail($id);
    }

    public function create(AgreementKind $kind, AgreementContext $context, array $input): Agreement
    {
        $this->authorization->assert($context, $kind, true);

        return $this->atomic(function () use ($kind, $context, $input): Agreement {
            $data = $this->validation->validate($input, $kind, $context);
            $class = $this->model($kind);
            $record = new $class;
            $record->forceFill(array_merge($data, ['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId,
                'row_version' => AgreementFields::INITIAL_VERSION, 'status' => AgreementStatus::Draft]))->save();
            $this->record($record, $context, AgreementAction::Create);

            return $record->load($this->relations($kind));
        });
    }

    public function successor(AgreementKind $kind, AgreementContext $context, int $id, int $expectedVersion, array $input): Agreement
    {
        $this->authorization->assert($context, $kind, true);
        $this->validation->assertContext($context);
        $data = Validator::make($input, [
            'reference' => ['required', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
            'agreed_on' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'executing_on' => ['nullable', 'date_format:'.AgreementFields::DATE_FORMAT],
            'starts_on' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'ends_on' => ['nullable', 'date_format:'.AgreementFields::DATE_FORMAT, 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
        ])->validate();
        if (trim($data['reason']) === '') {
            throw ValidationException::withMessages(['reason' => ['Explain the commercial change that requires a successor agreement.']]);
        }

        return $this->atomic(function () use ($kind, $context, $id, $expectedVersion, $data): Agreement {
            $predecessor = $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            if ($predecessor->row_version !== $expectedVersion) {
                throw new ConflictHttpException('This agreement changed. Reload it before creating a successor.');
            }
            if ($predecessor->status !== AgreementStatus::Active) {
                throw ValidationException::withMessages(['status' => ['Only an active agreement can have a successor draft.']]);
            }
            $this->assertSuccessorStartsAfter($predecessor, $data['starts_on']);

            $successorTerms = $predecessor->terms;
            // Payment receipts remain attached to the predecessor agreement. A successor must not
            // manufacture a second security-deposit obligation; operators can explicitly set a new
            // requirement while reviewing the successor draft if the amended contract requires one.
            $successorTerms[AgreementFields::DEPOSIT_REQUIREMENT] = null;
            $clone = [
                'reference' => trim($data['reference']),
                'party_id' => $kind === AgreementKind::Customer ? $predecessor->customer_id : $predecessor->supplier_id,
                'currency_id' => $predecessor->currency_id,
                'agreed_on' => $data['agreed_on'],
                'executing_on' => $data['executing_on'] ?? null,
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'] ?? null,
                'basis' => $predecessor->basis->value,
                'driver_mode' => $predecessor->driver_mode->value,
                'terms' => $successorTerms,
                'notes' => $predecessor->notes,
            ];
            if ($kind === AgreementKind::Owner) {
                $clone['vehicle_id'] = $predecessor->vehicle_id;
            }
            $validated = $this->validation->validate($clone, $kind, $context);

            $class = $this->model($kind);
            $successor = new $class;
            $successor->forceFill(array_merge($validated, [
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'supersedes_agreement_id' => $predecessor->id,
                'row_version' => AgreementFields::INITIAL_VERSION,
                'status' => AgreementStatus::Draft,
            ]))->save();
            $this->record($successor, $context, AgreementAction::Create, trim($data['reason']));

            return $successor->load($this->relations($kind));
        });
    }

    public function change(AgreementKind $kind, AgreementContext $context, int $id, int $expectedVersion, AgreementAction $action, array $input = [], ?string $reason = null): Agreement
    {
        $this->authorization->assert($context, $kind, true);

        return $this->atomic(function () use ($kind, $context, $id, $expectedVersion, $action, $input, $reason): Agreement {
            $this->validation->assertContext($context);
            $snapshot = $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);

            if ($kind === AgreementKind::Owner && $action === AgreementAction::Update) {
                $vehicleIds = array_unique([(int) $snapshot->vehicle_id, (int) ($input['vehicle_id'] ?? $snapshot->vehicle_id)]);
                Vehicle::query()->forTenant($context->tenantId, $context->organizationUnitId)->whereIn('id', $vehicleIds)->orderBy('id')->lockForUpdate()->get();
            }

            $predecessor = null;
            if ($action === AgreementAction::Activate && $snapshot->status === AgreementStatus::Draft && $snapshot->supersedes_agreement_id !== null) {
                $predecessor = $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
                    ->lockForUpdate()->findOrFail((int) $snapshot->supersedes_agreement_id);
            }

            $record = $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            if ($record->row_version !== $expectedVersion) {
                throw new ConflictHttpException('This agreement changed. Reload it before continuing.');
            }

            if ($action === AgreementAction::Update && $record->status === AgreementStatus::Draft) {
                $validated = $this->validation->validate($input, $kind, $context);
                if ($record->supersedes_agreement_id !== null) {
                    $this->assertSuccessorIdentity($kind, $context, $record, $validated);
                }
                $record->forceFill($validated);
            } elseif ($action === AgreementAction::Activate && $record->status === AgreementStatus::Draft) {
                if ($record->supersedes_agreement_id !== null) {
                    if ($predecessor === null || $predecessor->status !== AgreementStatus::Active) {
                        throw new ConflictHttpException('The predecessor agreement is no longer active. Review the successor before activation.');
                    }
                    $this->activateSuccessor($kind, $context, $predecessor, $record);
                }
                $record->status = AgreementStatus::Active;
                $record->activated_at = now();
            } elseif ($action === AgreementAction::Close && $record->status === AgreementStatus::Active) {
                if (trim($reason ?? '') === '' || mb_strlen($reason) > AgreementFields::NOTES_LENGTH) {
                    throw ValidationException::withMessages(['reason' => ['Provide a closure reason within the notes length limit.']]);
                }
                $foreignKey = $kind === AgreementKind::Customer ? 'customer_agreement_id' : 'owner_agreement_id';
                if (VehicleUse::query()->forTenant($context->tenantId)->where($foreignKey, $record->id)
                    ->whereIn('status', [VehicleUseStatus::Planned->value, VehicleUseStatus::InCustody->value])->lockForUpdate()->first(['id']) !== null) {
                    throw ValidationException::withMessages(['status' => ['Return vehicles and cancel remaining plans before closing this agreement.']]);
                }
                $record->status = AgreementStatus::Closed;
                $record->closed_at = now();
            } else {
                throw ValidationException::withMessages(['status' => ['This action is invalid for the current state. Activated terms cannot be edited.']]);
            }
            $record->row_version++;
            $record->save();
            $this->record($record, $context, $action, $reason);

            return $record->load($this->relations($kind));
        });
    }

    private function activateSuccessor(AgreementKind $kind, AgreementContext $context, Agreement $predecessor, Agreement $successor): void
    {
        $this->assertSuccessorStartsAfter($predecessor, $successor->starts_on->toDateString());
        $timezone = $this->calendar->timezone($context);
        $effectiveDate = CarbonImmutable::createFromFormat('!'.AgreementFields::DATE_FORMAT, $successor->starts_on->toDateString(), $timezone);
        if ($effectiveDate->isAfter($this->calendar->today($context))) {
            throw ValidationException::withMessages(['starts_on' => ['Keep this successor as Draft until its effective start date. Early activation would interrupt the still-current predecessor agreement.']]);
        }
        $this->assertCutoverAvailable($kind, $context, $predecessor, $successor->starts_on->toDateString());

        $cutoff = CarbonImmutable::createFromFormat('!'.AgreementFields::DATE_FORMAT, $successor->starts_on->toDateString(), 'UTC')->subDay();
        if ($predecessor->ends_on === null || $predecessor->ends_on->toDateString() >= $successor->starts_on->toDateString()) {
            $predecessor->ends_on = $cutoff->toDateString();
        }
        $predecessor->status = AgreementStatus::Closed;
        $predecessor->closed_at = now();
        $predecessor->row_version++;
        $predecessor->save();
        $this->record($predecessor, $context, AgreementAction::Supersede, 'Activated successor '.$successor->reference);
    }

    private function assertCutoverAvailable(AgreementKind $kind, AgreementContext $context, Agreement $predecessor, string $successorStart): void
    {
        $foreignKey = $kind === AgreementKind::Customer ? 'customer_agreement_id' : 'owner_agreement_id';
        $uses = VehicleUse::query()->forTenant($context->tenantId)->where($foreignKey, $predecessor->id)
            ->where('status', '!=', VehicleUseStatus::Cancelled->value)
            ->orderBy('id')->lockForUpdate()->get(['id', 'ends_at_input']);
        $crossingUse = $uses->first(function (VehicleUse $use) use ($successorStart): bool {
            if ($use->ends_at_input === null) {
                return true;
            }
            $end = OperationalTime::parse($use->ends_at_input, 'ends_at');
            $boundary = CarbonImmutable::createFromFormat('!'.AgreementFields::DATE_FORMAT, $successorStart, $end->getTimezone());

            return $end > $boundary;
        });
        if ($crossingUse !== null) {
            throw ValidationException::withMessages(['starts_on' => ['Return or reschedule vehicle use that crosses the successor effective date before activation.']]);
        }

        $baseChargeClass = $kind === AgreementKind::Customer ? CustomerBaseCharge::class : OwnerBaseCharge::class;
        if ($baseChargeClass::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $predecessor->id)->whereNull('voided_at')
            ->where('period_until', '>=', $successorStart)->lockForUpdate()->first(['id']) !== null) {
            throw ValidationException::withMessages(['starts_on' => ['The predecessor has a base-rent charge on or after this date. Release and void that charge before activation.']]);
        }

        $usageChargeClass = $kind === AgreementKind::Customer ? CustomerUsageCharge::class : OwnerUsageCharge::class;
        if ($usageChargeClass::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $predecessor->id)->whereNull('voided_at')
            ->where('period_until', '>=', $successorStart)->lockForUpdate()->first(['id']) !== null) {
            throw ValidationException::withMessages(['starts_on' => ['The predecessor has a usage assessment whose commercial period reaches this date. Release and void that assessment before activation.']]);
        }
    }

    private function assertSuccessorIdentity(AgreementKind $kind, AgreementContext $context, Agreement $successor, array $validated): void
    {
        $predecessor = $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail((int) $successor->supersedes_agreement_id);
        $partyField = $kind === AgreementKind::Customer ? 'customer_id' : 'supplier_id';
        if ((int) $validated[$partyField] !== (int) $predecessor->{$partyField}) {
            throw ValidationException::withMessages(['party_id' => ['A successor must keep the predecessor counterparty. Create a new agreement for a different party.']]);
        }
        if ($kind === AgreementKind::Owner && (int) $validated['vehicle_id'] !== (int) $predecessor->vehicle_id) {
            throw ValidationException::withMessages(['vehicle_id' => ['An owner-agreement successor must keep the same supplied vehicle. Create a new owner agreement for another vehicle.']]);
        }
        $this->assertSuccessorStartsAfter($predecessor, $validated['starts_on']);
    }

    private function assertSuccessorStartsAfter(Agreement $predecessor, string $successorStart): void
    {
        $next = CarbonImmutable::createFromFormat('!'.AgreementFields::DATE_FORMAT, $successorStart, 'UTC');
        $current = CarbonImmutable::createFromFormat('!'.AgreementFields::DATE_FORMAT, $predecessor->starts_on->format(AgreementFields::DATE_FORMAT), 'UTC');
        if ($next <= $current) {
            throw ValidationException::withMessages(['starts_on' => ['A successor must start after the predecessor start date.']]);
        }
    }

    private function record(Agreement $record, AgreementContext $context, AgreementAction $action, ?string $reason = null): void
    {
        $history = $record->history()->make();
        $history->forceFill(['tenant_id' => $context->tenantId, 'row_version' => $record->row_version, 'actor_id' => $context->actorId,
            'action' => $action->value, 'reason' => $reason, 'snapshot' => $record->attributesToArray(), 'recorded_at' => now()])->save();
    }

    private function atomic(callable $operation): Agreement
    {
        try {
            return DB::transaction($operation);
        } catch (UniqueConstraintViolationException $error) {
            throw new ConflictHttpException('An agreement reference or revision already exists. Reload and review the request.', $error);
        }
    }

    private function model(AgreementKind $kind): string
    {
        return $kind === AgreementKind::Customer ? CustomerAgreement::class : OwnerAgreement::class;
    }

    private function relations(AgreementKind $kind): array
    {
        return $kind === AgreementKind::Owner ? ['party', 'currency', 'vehicle', 'supersedesAgreement'] : ['party', 'currency', 'supersedesAgreement'];
    }
}
