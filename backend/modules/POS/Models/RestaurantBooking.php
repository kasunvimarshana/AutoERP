<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;
use Modules\POS\Enums\BookingStatus;

/**
 * Restaurant Booking Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $table_id
 * @property string|null $contact_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property \Carbon\Carbon $booking_start
 * @property \Carbon\Carbon $booking_end
 * @property int $number_of_guests
 * @property BookingStatus $status
 * @property string|null $notes
 * @property string $created_by
 */
class RestaurantBooking extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_restaurant_bookings';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'table_id',
        'contact_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'booking_start',
        'booking_end',
        'number_of_guests',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'booking_start' => 'datetime',
        'booking_end' => 'datetime',
        'number_of_guests' => 'integer',
        'status' => BookingStatus::class,
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_start', '>', now())
                     ->where('status', BookingStatus::CONFIRMED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            BookingStatus::PENDING,
            BookingStatus::CONFIRMED,
            BookingStatus::SEATED,
        ]);
    }
}
