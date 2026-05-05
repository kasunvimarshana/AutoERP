<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverCommissionModel extends Model
{
    protected $table = 'driver_commissions';
    protected $fillable = [
        'id',
        'driver_id',
        'rental_transaction_id',
        'commission_amount',
        'commission_percentage',
        'earned_date',
        'paid_date',
        'status',
        'tenant_id',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:4',
        'earned_date' => 'datetime',
        'paid_date' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id', 'id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByDriver($query, string $driverId)
    {
        return $query->where('driver_id', $driverId);
    }
}
