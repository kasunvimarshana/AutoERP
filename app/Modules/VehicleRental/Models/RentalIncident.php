<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Enums\IncidentType;

final class RentalIncident extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_incidents';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_use_id' => 'integer',
            'vehicle_use_version' => 'integer',
            'running_chart_id' => 'integer',
            'created_by' => 'integer',
            'reviewed_by' => 'integer',
            'row_version' => 'integer',
            'incident_type' => IncidentType::class,
            'status' => IncidentStatus::class,
            'occurred_on' => 'immutable_date',
            'reviewed_at' => 'immutable_datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, int $organizationUnitId): Builder
    {
        return $query->forTenant($tenantId)->where('organization_unit_id', $organizationUnitId);
    }

    public function vehicleUse(): BelongsTo
    {
        return $this->belongsTo(VehicleUse::class, 'vehicle_use_id');
    }

    public function runningChart(): BelongsTo
    {
        return $this->belongsTo(RunningChart::class, 'running_chart_id');
    }

    protected static function booted(): void
    {
        self::deleting(static fn () => throw new LogicException('Rental incident evidence is immutable and cannot be deleted.'));
        self::updating(static function (self $incident): void {
            $permitted = ['status', 'row_version', 'reviewed_by', 'reviewed_at', 'updated_at'];
            if (array_diff(array_keys($incident->getDirty()), $permitted) !== []) {
                throw new LogicException('Recorded rental incident facts cannot be rewritten.');
            }
            if ($incident->getRawOriginal('status') !== IncidentStatus::Recorded->value) {
                throw new LogicException('Reviewed rental incident decisions are final; record new evidence separately.');
            }
        });
    }
}
