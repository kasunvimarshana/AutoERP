<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;

final class RentalAssignment extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_assignments';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'vehicle_id' => 'integer',
            'source_assignment_id' => 'integer',
            'replaces_assignment_id' => 'integer',
            'side' => RentalAssignmentSide::class,
            'status' => RentalAssignmentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'handover_odometer' => 'decimal:6',
            'return_odometer' => 'decimal:6',
            'driver_employee_id' => 'integer',
            'self_drive' => 'boolean',
            'created_by' => 'integer',
            'closed_by' => 'integer',
            'closed_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    public function sourceAssignment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_assignment_id');
    }

    public function replacesAssignment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_assignment_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'driver_employee_id');
    }

    public function custodyEvents(): HasMany
    {
        return $this->hasMany(RentalCustodyEvent::class, 'assignment_id')->orderBy('event_at');
    }

}
