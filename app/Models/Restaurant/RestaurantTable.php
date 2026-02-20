<?php

namespace App\Models\Restaurant;

use App\Models\BusinessLocation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantTable extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'tenant_id', 'business_location_id', 'name',
        'capacity', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'restaurant_table_id');
    }
}
