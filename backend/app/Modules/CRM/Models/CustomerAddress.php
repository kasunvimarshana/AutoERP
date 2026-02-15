<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\MasterData\Models\Country;

class CustomerAddress extends Model
{
    use HasFactory, SoftDeletes, HasUuid, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'type',
        'is_primary',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_id',
        'landmark',
        'contact_person',
        'contact_phone',
        'contact_email',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the customer that owns the address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the country for the address.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the user who created the address.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the address.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get formatted address.
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if this is a billing address.
     */
    public function isBillingAddress(): bool
    {
        return in_array($this->type, ['billing', 'both']);
    }

    /**
     * Check if this is a shipping address.
     */
    public function isShippingAddress(): bool
    {
        return in_array($this->type, ['shipping', 'both']);
    }

    /**
     * Check if this is the primary address.
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }
}
