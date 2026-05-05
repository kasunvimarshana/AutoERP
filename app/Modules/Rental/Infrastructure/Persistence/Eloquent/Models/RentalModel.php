<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalModel extends Model
{
    use HasTenant, SoftDeletes;

    protected $table = 'fleet_rentals';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version',
        'customer_id', 'vehicle_id', 'driver_id',
        'rental_number', 'rental_type', 'status',
        'pickup_location', 'return_location',
        'scheduled_start_at', 'scheduled_end_at',
        'actual_start_at', 'actual_end_at',
        'start_odometer', 'end_odometer',
        'rate_per_day', 'estimated_days', 'actual_days',
        'subtotal', 'discount_amount', 'tax_amount',
        'total_amount', 'deposit_amount',
        'notes', 'cancelled_at', 'cancellation_reason',
        'metadata', 'is_active',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'rate_per_day'        => 'string',
        'estimated_days'      => 'string',
        'actual_days'         => 'string',
        'subtotal'            => 'string',
        'discount_amount'     => 'string',
        'tax_amount'          => 'string',
        'total_amount'        => 'string',
        'deposit_amount'      => 'string',
        'start_odometer'      => 'string',
        'end_odometer'        => 'string',
        'metadata'            => 'array',
        'scheduled_start_at'  => 'datetime',
        'scheduled_end_at'    => 'datetime',
        'actual_start_at'     => 'datetime',
        'actual_end_at'       => 'datetime',
        'cancelled_at'        => 'datetime',
    ];

    public function charges(): HasMany
    {
        return $this->hasMany(RentalChargeModel::class, 'rental_id');
    }
}
