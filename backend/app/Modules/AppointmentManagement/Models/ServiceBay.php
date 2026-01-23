<?php

namespace App\Modules\AppointmentManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ServiceBay extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'bay_number',
        'name',
        'description',
        'bay_type',
        'status',
        'capacity',
        'equipment',
        'specializations',
        'is_active',
    ];

    protected $casts = [
        'equipment' => 'array',
        'specializations' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($serviceBay) {
            if (empty($serviceBay->uuid)) {
                $serviceBay->uuid = Str::uuid();
            }
            if (empty($serviceBay->bay_number)) {
                $serviceBay->bay_number = static::generateBayNumber();
            }
        });
    }

    /**
     * Generate unique bay number
     */
    protected static function generateBayNumber(): string
    {
        do {
            $code = 'BAY-' . strtoupper(Str::random(6));
        } while (static::where('bay_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the service bay
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the appointments for the service bay
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Check if bay is available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }

    /**
     * Get display name attribute
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->bay_number} - {$this->name}";
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
     * Scope: Active service bays
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
     * Scope: Available bays
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('is_active', true);
    }

    /**
     * Scope: By bay type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('bay_type', $type);
    }
}
