<?php

namespace App\Modules\Procurement\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\MasterData\Models\Currency;
use App\Modules\Organization\Models\Organization;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'vendor_code',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'tax_id',
        'registration_number',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'currency_id',
        'payment_terms_days',
        'credit_limit',
        'category',
        'status',
        'notes',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the vendor.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the organization that owns the vendor.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the vendor's currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Scope to get only active vendors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get vendor's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'company') {
            return $this->company_name;
        }
        
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get vendor's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        return $name ? "{$this->vendor_code} - {$name}" : $this->vendor_code;
    }
}
