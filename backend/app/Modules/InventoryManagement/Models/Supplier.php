<?php

namespace App\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\SupplierFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'supplier_code',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'payment_terms',
        'credit_limit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->uuid)) {
                $supplier->uuid = Str::uuid();
            }
            if (empty($supplier->supplier_code)) {
                $supplier->supplier_code = static::generateSupplierCode();
            }
        });
    }

    /**
     * Generate unique supplier code
     */
    protected static function generateSupplierCode(): string
    {
        do {
            $code = 'SUP-' . strtoupper(Str::random(8));
        } while (static::where('supplier_code', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the supplier
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the purchase orders for the supplier
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get display name attribute
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->contact_person
            ? "{$this->company_name} ({$this->contact_person})"
            : $this->company_name;
    }

    /**
     * Get full address attribute
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
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
     * Scope: Active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
