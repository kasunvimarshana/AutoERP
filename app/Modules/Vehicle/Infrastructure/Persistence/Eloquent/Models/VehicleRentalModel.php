<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

class VehicleRentalModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'vehicle_rentals';

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'customer_id',
        'assigned_driver_id',
        'rental_no',
        'rental_status',
        'pricing_model',
        'base_rate',
        'distance_km',
        'included_km',
        'extra_km_rate',
        'subtotal',
        'tax_amount',
        'grand_total',
        'reserved_from',
        'reserved_until',
        'rented_out_at',
        'returned_at',
        'odometer_out',
        'odometer_in',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'vehicle_id' => 'integer',
        'customer_id' => 'integer',
        'assigned_driver_id' => 'integer',
        'base_rate' => 'decimal:6',
        'distance_km' => 'decimal:6',
        'included_km' => 'decimal:6',
        'extra_km_rate' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'reserved_from' => 'datetime',
        'reserved_until' => 'datetime',
        'rented_out_at' => 'datetime',
        'returned_at' => 'datetime',
        'odometer_out' => 'decimal:6',
        'odometer_in' => 'decimal:6',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }
}
