<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'effective_date' => 'date',
    ];

    /**
     * Get the from currency
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    /**
     * Get the to currency
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * Convert an amount using this exchange rate
     */
    public function convert(float|int $amount): float
    {
        return $amount * (float) $this->rate;
    }

    /**
     * Get the inverse rate
     */
    public function inverseRate(): float
    {
        return 1 / (float) $this->rate;
    }

    /**
     * Scope to get rates for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->limit(1);
    }

    /**
     * Scope to get latest rates
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }
}
