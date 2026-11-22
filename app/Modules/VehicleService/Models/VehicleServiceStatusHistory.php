<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;

final class VehicleServiceStatusHistory extends TenantOwnedModel
{
    protected $table = 'vehicle_service_status_histories';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('VehicleServiceStatusHistory is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('VehicleServiceStatusHistory is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'old_status' => VehicleServiceJobStatus::class,
            'new_status' => VehicleServiceJobStatus::class,
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }
}
