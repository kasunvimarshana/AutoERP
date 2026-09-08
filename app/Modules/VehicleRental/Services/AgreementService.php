<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
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
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class AgreementService
{
    public function __construct(private readonly RentalAuthorization $authorization, private readonly AgreementValidation $validation) {}

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

    public function change(AgreementKind $kind, AgreementContext $context, int $id, int $expectedVersion, AgreementAction $action, array $input = [], ?string $reason = null): Agreement
    {
        $this->authorization->assert($context, $kind, true);

        return $this->atomic(function () use ($kind, $context, $id, $expectedVersion, $action, $input, $reason): Agreement {
            $this->validation->assertContext($context);
            if ($kind === AgreementKind::Owner && $action === AgreementAction::Update) {
                $snapshot = OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($id);
                $vehicleIds = array_unique([(int) $snapshot->vehicle_id, (int) ($input['vehicle_id'] ?? $snapshot->vehicle_id)]);
                Vehicle::query()->forTenant($context->tenantId, $context->organizationUnitId)->whereIn('id', $vehicleIds)->orderBy('id')->lockForUpdate()->get();
            }
            $record = $this->model($kind)::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($id);
            if ($record->row_version !== $expectedVersion) {
                throw new ConflictHttpException('This agreement changed. Reload it before continuing.');
            }
            if ($action === AgreementAction::Update && $record->status === AgreementStatus::Draft) {
                $record->forceFill($this->validation->validate($input, $kind, $context));
            } elseif ($action === AgreementAction::Activate && $record->status === AgreementStatus::Draft) {
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
        return $kind === AgreementKind::Owner ? ['party', 'currency', 'vehicle'] : ['party', 'currency'];
    }
}
