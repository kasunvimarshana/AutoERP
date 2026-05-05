<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalReservationModel extends Model
{
    use SoftDeletes;

    protected $table = 'rental_reservations';

    protected $fillable = [
        'id',
        'tenant_id',
        'vehicle_id',
        'customer_id',
        'driver_id',
        'reservation_number',
        'start_at',
        'expected_return_at',
        'billing_unit',
        'base_rate',
        'estimated_distance',
        'estimated_amount',
        'status',
        'version',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'expected_return_at' => 'datetime',
        'base_rate' => 'decimal:6',
        'estimated_distance' => 'decimal:6',
        'estimated_amount' => 'decimal:6',
        'version' => 'integer',
    ];

    public function agreement(): HasOne
    {
        return $this->hasOne(RentalAgreementModel::class, 'reservation_id', 'id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
