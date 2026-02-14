<?php

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerContact Model
 * 
 * Represents a contact person for a customer
 */
class CustomerContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'title',
        'email',
        'phone',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the customer that owns this contact
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
