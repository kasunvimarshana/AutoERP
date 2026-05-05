<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseModel extends Model
{
    protected $table = 'licenses';
    protected $fillable = [
        'id',
        'driver_id',
        'license_number',
        'license_class',
        'issue_date',
        'expiry_date',
        'country_code',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id', 'id');
    }

    public function scopeExpiring($query, int $daysThreshold = 30)
    {
        return $query->whereDate('expiry_date', '<=', now()->addDays($daysThreshold))
            ->whereDate('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereDate('expiry_date', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->whereDate('expiry_date', '>=', now());
    }
}
