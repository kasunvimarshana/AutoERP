<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalTransactionModel extends Model
{
    protected $table = 'rental_transactions';

    protected $fillable = [
        'id',
        'tenant_id',
        'agreement_id',
        'checked_out_at',
        'checked_in_at',
        'odometer_out',
        'odometer_in',
        'fuel_level_out',
        'fuel_level_in',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
        'status',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'odometer_out' => 'integer',
        'odometer_in' => 'integer',
        'pickup_latitude' => 'decimal:7',
        'pickup_longitude' => 'decimal:7',
        'dropoff_latitude' => 'decimal:7',
        'dropoff_longitude' => 'decimal:7',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementModel::class, 'agreement_id', 'id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
