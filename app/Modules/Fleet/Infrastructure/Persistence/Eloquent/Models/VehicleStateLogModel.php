<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class VehicleStateLogModel extends Model
{
    use HasTenant;

    protected $table = 'fleet_vehicle_state_logs';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'vehicle_id', 'from_state', 'to_state',
        'reason', 'reference_type', 'reference_id', 'triggered_by', 'created_at',
    ];
}
