<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalIncidentModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_incidents';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_booking_id',
        'asset_id',
        'incident_type',
        'status',
        'occurred_at',
        'reported_by',
        'description',
        'estimated_cost',
        'recovered_amount',
        'recovery_status',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'rental_booking_id' => 'integer',
        'asset_id' => 'integer',
        'reported_by' => 'integer',
        'estimated_cost' => 'decimal:6',
        'recovered_amount' => 'decimal:6',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];
}
