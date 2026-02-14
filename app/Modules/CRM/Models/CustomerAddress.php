<?php

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerAddress Model
 * 
 * Represents an address for a customer
 */
class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the customer that owns this address
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
