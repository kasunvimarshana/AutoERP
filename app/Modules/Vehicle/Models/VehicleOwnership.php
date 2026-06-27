<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final class VehicleOwnership extends TenantOwnedModel
{
    protected $table = 'vehicle_ownerships';

    protected $guarded = [
        'id', 'tenant_id', 'organization_unit_id', 'row_version',
        'owner_scope_key', 'owner_code_snapshot', 'owner_name_snapshot',
        'current_guard', 'active_guard', 'supersedes_ownership_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'owner_type' => VehicleOwnerType::class,
            'owner_id' => 'integer',
            'ownership_type' => VehicleOwnershipType::class,
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'is_current' => 'boolean',
            'current_guard' => 'integer',
            'active_guard' => 'integer',
            'supersedes_ownership_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }

    protected static function booted(): void
    {
        static::deleting(static function (): never {
            throw new \LogicException('Vehicle ownership history cannot be deleted. End or supersede the relationship instead.');
        });
    }

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function supersedes(): BelongsTo { return $this->belongsTo(self::class, 'supersedes_ownership_id'); }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeForOwnerType(Builder $query, VehicleOwnerType|string $ownerType): Builder
    {
        return $query->where('owner_type', $ownerType instanceof VehicleOwnerType ? $ownerType->value : $ownerType);
    }
}
