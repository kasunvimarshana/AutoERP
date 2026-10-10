<?php
declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\IncidentFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\IncidentPermission;
use Modules\VehicleRental\Enums\IncidentReviewAction;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Enums\IncidentType;
use Modules\VehicleRental\Models\RentalIncident;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RentalIncidentService
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly AgreementValidation $contextValidation,
    ) {}

    public function vehicleUseOptions(AgreementContext $context, ?string $search, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertIncident($context, IncidentPermission::Record);
        $search = trim($search ?? '');
        $pattern = '%'.$search.'%';

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with('customerAgreement')
            ->when($search !== '', fn ($query) => $query->where(function ($match) use ($pattern): void {
                $match->where('vehicle_label_snapshot', 'like', $pattern)
                    ->orWhereHas('customerAgreement', fn ($agreement) => $agreement
                        ->where('reference', 'like', $pattern)
                        ->orWhere('party_name_snapshot', 'like', $pattern));
            }))
            ->orderByDesc('id')->paginate($perPage);
    }

    public function list(AgreementContext $context, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertIncident($context, IncidentPermission::View);
        return RentalIncident::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['vehicleUse.customerAgreement', 'vehicleUse.ownerAgreement', 'vehicleUse.vehicle', 'runningChart'])
            ->orderByDesc('occurred_on')->orderByDesc('id')->paginate($perPage);
    }

    public function find(AgreementContext $context, int $id): RentalIncident
    {
        $this->authorization->assertIncident($context, IncidentPermission::View);
        return RentalIncident::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['vehicleUse.customerAgreement', 'vehicleUse.ownerAgreement', 'vehicleUse.vehicle', 'runningChart'])
            ->findOrFail($id);
    }

    public function history(AgreementContext $context, int $id, int $perPage): LengthAwarePaginator
    {
        $this->find($context, $id);
        return DB::table('vehicle_rental_incident_events')
            ->where('tenant_id', $context->tenantId)->where('incident_id', $id)->orderBy('id')->paginate($perPage);
    }

    public function create(AgreementContext $context, array $input): RentalIncident
    {
        $this->authorization->assertIncident($context, IncidentPermission::Record);
        $this->contextValidation->assertContext($context);
        foreach (['evidence_reference', 'description'] as $field) {
            if (is_string($input[$field] ?? null)) {
                $input[$field] = trim($input[$field]);
            }
        }
        $data = Validator::make($input, [
            'vehicle_use_id' => ['required', 'integer', 'min:1'],
            'expected_use_version' => ['required', 'integer', 'min:1'],
            'running_chart_id' => ['nullable', 'integer', 'min:1'],
            'incident_type' => ['required', Rule::enum(IncidentType::class)],
            'occurred_on' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'evidence_reference' => ['required', 'string', 'max:'.IncidentFields::EVIDENCE_REFERENCE_LENGTH],
            'description' => ['required', 'string', 'max:'.IncidentFields::DESCRIPTION_LENGTH],
        ])->validate();

        return DB::transaction(function () use ($context, $data): RentalIncident {
            $use = VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()->findOrFail((int) $data['vehicle_use_id']);
            if ((int) $use->row_version !== (int) $data['expected_use_version']) {
                throw new ConflictHttpException('The vehicle assignment changed. Reload and select its current revision.');
            }
            $chartId = isset($data['running_chart_id']) ? (int) $data['running_chart_id'] : null;
            if ($chartId !== null && RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)
                ->where('vehicle_use_id', $use->getKey())->whereKey($chartId)->first() === null) {
                throw ValidationException::withMessages([
                    'running_chart_id' => ['Select a Running Chart belonging to this vehicle use.'],
                ]);
            }

            $incident = new RentalIncident();
            $incident->forceFill([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_use_id' => $use->getKey(),
                'vehicle_use_version' => $use->row_version,
                'running_chart_id' => $chartId,
                'reference' => IncidentFields::REFERENCE_PREFIX.Str::ulid(),
                'incident_type' => IncidentType::from($data['incident_type']),
                'occurred_on' => $data['occurred_on'],
                'evidence_reference' => $data['evidence_reference'],
                'description' => $data['description'],
                'status' => IncidentStatus::Recorded,
                'row_version' => AgreementFields::INITIAL_VERSION,
                'created_by' => $context->actorId,
            ])->save();
            $this->recordEvent($incident, $context, IncidentStatus::Recorded->value, null);
            return $this->load($incident);
        });
    }

    public function review(AgreementContext $context, int $id, int $expectedVersion, IncidentReviewAction $action, string $reason): RentalIncident
    {
        $this->authorization->assertIncident($context, IncidentPermission::Review);
        $this->contextValidation->assertContext($context);
        $reason = trim($reason);
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'max:'.IncidentFields::REVIEW_REASON_LENGTH],
        ])->validate();

        return DB::transaction(function () use ($context, $id, $expectedVersion, $action, $reason): RentalIncident {
            $incident = RentalIncident::query()->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()->findOrFail($id);
            if ($incident->row_version !== $expectedVersion) {
                throw new ConflictHttpException('This incident changed. Reload before reviewing.');
            }
            if ($incident->status !== IncidentStatus::Recorded) {
                throw new ConflictHttpException('This incident has already been reviewed. Its evidence is immutable.');
            }
            $incident->forceFill([
                'status' => $action->status(),
                'row_version' => $incident->row_version + 1,
                'reviewed_by' => $context->actorId,
                'reviewed_at' => now(),
            ])->save();
            $this->recordEvent($incident, $context, $action->value, $reason);
            return $this->load($incident);
        });
    }

    private function load(RentalIncident $incident): RentalIncident
    {
        return $incident->refresh()->load([
            'vehicleUse.customerAgreement', 'vehicleUse.ownerAgreement',
            'vehicleUse.vehicle', 'runningChart',
        ]);
    }

    private function recordEvent(RentalIncident $incident, AgreementContext $context, string $action, ?string $reason): void
    {
        DB::table('vehicle_rental_incident_events')->insert([
            'tenant_id' => $context->tenantId,
            'incident_id' => $incident->getKey(),
            'actor_id' => $context->actorId,
            'action' => $action,
            'reason' => $reason,
            'snapshot' => json_encode($incident->attributesToArray(), JSON_THROW_ON_ERROR),
            'recorded_at' => now(),
        ]);
    }
}
