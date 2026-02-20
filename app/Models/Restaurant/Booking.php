<?php

namespace App\Models\Restaurant;

use App\Models\BusinessLocation;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'bookings';

    protected $fillable = [
        'tenant_id', 'business_location_id', 'restaurant_table_id',
        'customer_id', 'correspondent_id', 'waiter_id',
        'booking_start', 'booking_end', 'no_of_persons',
        'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'booking_start' => 'datetime',
            'booking_end' => 'datetime',
            'no_of_persons' => 'integer',
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

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function correspondent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'correspondent_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }
}
