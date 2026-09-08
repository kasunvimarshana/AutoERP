<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\VehicleUseStatus;

final class VehicleUse extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_uses';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['row_version' => 'integer', 'status' => VehicleUseStatus::class,
            'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'handed_over_at' => 'immutable_datetime', 'returned_at' => 'immutable_datetime',
            'handover_odometer' => 'decimal:6', 'return_odometer' => 'decimal:6']);
    }

    public function scopeForContext(Builder $query, int $tenant, int $organization): Builder
    {
        return $query->forTenant($tenant)->where('organization_unit_id', $organization);
    }

    public function customerAgreement(): BelongsTo
    {
        return $this->belongsTo(CustomerAgreement::class);
    }

    public function ownerAgreement(): BelongsTo
    {
        return $this->belongsTo(OwnerAgreement::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function history(): HasMany
    {
        return $this->hasMany(VehicleUseHistory::class)->orderBy('row_version');
    }

    protected static function booted(): void
    {
        self::deleting(static fn () => throw new LogicException('Preserve vehicle-use history. Cancel or return the vehicle.'));
        self::updating(static function (self $record): void {
            $allowed = ['status', 'row_version', 'handed_over_at', 'returned_at', 'handover_odometer', 'return_odometer', 'updated_at'];
            if (array_diff(array_keys($record->getDirty()), $allowed) !== []) {
                throw new LogicException('Vehicle-use identity and planned evidence are immutable.');
            }
        });
    }
}
