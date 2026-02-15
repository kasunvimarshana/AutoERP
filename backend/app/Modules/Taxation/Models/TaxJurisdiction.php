<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\MasterData\Models\TaxRate;

class TaxJurisdiction extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'code',
        'jurisdiction_type',
        'country_code',
        'state_code',
        'city_name',
        'postal_code',
        'tax_rate_id',
        'tax_group_id',
        'priority',
        'is_reverse_charge',
        'is_active',
        'rules',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_reverse_charge' => 'boolean',
        'is_active' => 'boolean',
        'rules' => 'array',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class);
    }

    public function taxCalculations(): HasMany
    {
        return $this->hasMany(TaxCalculation::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isReverseCharge(): bool
    {
        return $this->is_reverse_charge;
    }

    public function matchesLocation(string $countryCode = null, string $stateCode = null, string $cityName = null, string $postalCode = null): bool
    {
        if ($this->country_code && $this->country_code !== $countryCode) {
            return false;
        }

        if ($this->state_code && $this->state_code !== $stateCode) {
            return false;
        }

        if ($this->city_name && strcasecmp($this->city_name, $cityName) !== 0) {
            return false;
        }

        if ($this->postal_code && $this->postal_code !== $postalCode) {
            return false;
        }

        return true;
    }

    public function scopeForLocation($query, string $countryCode = null, string $stateCode = null, string $cityName = null, string $postalCode = null)
    {
        return $query->where(function ($q) use ($countryCode, $stateCode, $cityName, $postalCode) {
            if ($countryCode) {
                $q->where('country_code', $countryCode)->orWhereNull('country_code');
            }
            
            if ($stateCode) {
                $q->where('state_code', $stateCode)->orWhereNull('state_code');
            }
            
            if ($cityName) {
                $q->where('city_name', $cityName)->orWhereNull('city_name');
            }
            
            if ($postalCode) {
                $q->where('postal_code', $postalCode)->orWhereNull('postal_code');
            }
        })->orderByDesc('priority');
    }
}
