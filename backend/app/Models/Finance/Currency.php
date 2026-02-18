<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
        'is_default',
        'exchange_rate',
        'exchange_rate_updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'decimal_places' => 'integer',
        'exchange_rate' => 'decimal:8',
        'exchange_rate_updated_at' => 'datetime',
    ];

    /**
     * Get exchange rates from this currency
     */
    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    /**
     * Get exchange rates to this currency
     */
    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    /**
     * Check if this is the default currency
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Set this currency as default
     */
    public function setAsDefault(): void
    {
        // Remove default from all other currencies
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Format an amount in this currency
     */
    public function format(float|int $amount): string
    {
        return $this->symbol . ' ' . number_format(
            $amount,
            $this->decimal_places,
            '.',
            ','
        );
    }

    /**
     * Scope to get only active currencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get the default currency
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
