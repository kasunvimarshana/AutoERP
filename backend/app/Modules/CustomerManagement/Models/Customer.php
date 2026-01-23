<?php

namespace App\Modules\CustomerManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CustomerFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'customer_type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'date_of_birth',
        'id_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'credit_limit',
        'payment_terms_days',
        'status',
        'preferred_language',
        'preferences',
        'metadata',
        'last_service_date',
        'lifetime_value',
        'total_services',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'credit_limit' => 'decimal:2',
        'lifetime_value' => 'decimal:2',
        'last_service_date' => 'datetime',
        'preferences' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = Str::uuid();
            }
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateCustomerCode();
            }
        });
    }

    /**
     * Generate unique customer code
     */
    protected static function generateCustomerCode(): string
    {
        do {
            $code = 'CUST-' . strtoupper(Str::random(8));
        } while (static::where('customer_code', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the customer
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the vehicles owned by the customer
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'current_customer_id');
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get display name (company or full name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->customer_type === 'business' && $this->company_name
            ? $this->company_name
            : $this->full_name;
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Active customers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
