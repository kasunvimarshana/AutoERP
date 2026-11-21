<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Vehicle\Enums\VehicleStatus;

final class VehicleStatusHistory extends TenantOwnedModel
{
    protected $table = 'vehicle_status_histories';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('VehicleStatusHistory is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('VehicleStatusHistory is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'old_status' => VehicleStatus::class,
            'new_status' => VehicleStatus::class,
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
