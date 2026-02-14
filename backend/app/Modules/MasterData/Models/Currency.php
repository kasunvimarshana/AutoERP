<?php

namespace App\Modules\MasterData\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'symbol',
        'decimal_places',
        'exchange_rate',
        'is_base',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'exchange_rate' => 'decimal:8',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the currency.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get base currency.
     */
    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }

    /**
     * Convert amount from this currency to base currency.
     */
    public function toBaseCurrency(float $amount): float
    {
        return $amount * $this->exchange_rate;
    }

    /**
     * Convert amount from base currency to this currency.
     */
    public function fromBaseCurrency(float $amount): float
    {
        return $this->exchange_rate > 0 ? $amount / $this->exchange_rate : 0;
    }

    /**
     * Format amount with currency symbol.
     */
    public function format(float $amount): string
    {
        return $this->symbol . ' ' . number_format($amount, $this->decimal_places);
    }
}
