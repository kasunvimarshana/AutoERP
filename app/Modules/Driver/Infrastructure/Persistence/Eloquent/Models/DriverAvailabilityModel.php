<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAvailabilityModel extends Model
{
    protected $table = 'driver_availability';
    protected $fillable = [
        'id',
        'driver_id',
        'start_date',
        'end_date',
        'is_available',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_available' => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id', 'id');
    }

    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('is_available', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('start_date', '>', now())->orderBy('start_date');
    }
}
