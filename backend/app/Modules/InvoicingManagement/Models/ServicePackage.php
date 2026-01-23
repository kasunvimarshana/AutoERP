<?php

namespace App\Modules\InvoicingManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ServicePackage extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'package_code',
        'name',
        'description',
        'package_type',
        'regular_price',
        'package_price',
        'savings',
        'validity_days',
        'included_items',
        'is_active',
    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'package_price' => 'decimal:2',
        'savings' => 'decimal:2',
        'validity_days' => 'integer',
        'included_items' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->uuid)) {
                $package->uuid = Str::uuid();
            }
            if (empty($package->package_code)) {
                $package->package_code = static::generatePackageCode();
            }
            if (empty($package->savings) && $package->regular_price && $package->package_price) {
                $package->savings = $package->regular_price - $package->package_price;
            }
        });

        static::updating(function ($package) {
            if ($package->isDirty(['regular_price', 'package_price'])) {
                $package->savings = $package->regular_price - $package->package_price;
            }
        });
    }

    /**
     * Generate unique package code
     */
    protected static function generatePackageCode(): string
    {
        do {
            $code = 'PKG-' . strtoupper(Str::random(8));
        } while (static::where('package_code', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the service package
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        if (!$this->regular_price || $this->regular_price == 0) {
            return 0;
        }
        
        return round(($this->savings / $this->regular_price) * 100, 2);
    }

    /**
     * Check if package is valid
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active;
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
     * Scope: Active packages
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

    /**
     * Scope: By package type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('package_type', $type);
    }

    /**
     * Scope: Promotional packages
     */
    public function scopePromotional($query)
    {
        return $query->where('package_type', 'promotional')
            ->where('is_active', true);
    }

    /**
     * Scope: Routine service packages
     */
    public function scopeRoutineService($query)
    {
        return $query->where('package_type', 'routine_service')
            ->where('is_active', true);
    }
}
