<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Customer Model
 * 
 * Represents a customer (individual or business)
 */
class Customer extends Model
{
    use HasFactory, TenantScoped, HasUuid, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'tax_number',
        'credit_limit',
        'payment_terms',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [];

    protected static function newFactory()
    {
        return \Database\Factories\CustomerFactory::new();
    }

    /**
     * Get contacts for this customer
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    /**
     * Get addresses for this customer
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get tags for this customer
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag', 'customer_id', 'tag_id');
    }

    /**
     * Get vehicles for this customer
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
