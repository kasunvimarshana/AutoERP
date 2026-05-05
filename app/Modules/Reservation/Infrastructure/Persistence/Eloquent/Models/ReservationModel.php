<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ReservationModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_reservations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'org_unit_id',
        'row_version',
        'reservation_number',
        'vehicle_id',
        'customer_id',
        'reserved_from',
        'reserved_to',
        'status',
        'estimated_amount',
        'currency',
        'notes',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'reserved_from' => 'datetime',
        'reserved_to' => 'datetime',
        'estimated_amount' => 'string',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];
}
