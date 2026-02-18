<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Restaurant Table Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $name
 * @property string|null $description
 * @property int $capacity
 * @property bool $is_available
 */
class RestaurantTable extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_restaurant_tables';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'name',
        'description',
        'capacity',
        'is_available',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_available' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(RestaurantBooking::class, 'table_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
