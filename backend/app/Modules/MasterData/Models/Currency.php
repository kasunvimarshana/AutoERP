<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class Currency extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
        'symbol',
        'decimal_places',
        'exchange_rate',
        'is_base_currency',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'decimal_places' => 'integer',
        'exchange_rate' => 'decimal:6',
        'is_base_currency' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the currency.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the currency.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if currency is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if this is the base currency.
     *
     * @return bool
     */
    public function isBaseCurrency(): bool
    {
        return $this->is_base_currency;
    }

    /**
     * Convert amount from base currency to this currency.
     *
     * @param float $amount
     * @return float
     */
    public function convertFromBase(float $amount): float
    {
        return round($amount * $this->exchange_rate, $this->decimal_places);
    }

    /**
     * Convert amount from this currency to base currency.
     *
     * @param float $amount
     * @return float
     */
    public function convertToBase(float $amount): float
    {
        if ($this->exchange_rate == 0) {
            return 0;
        }
        return round($amount / $this->exchange_rate, $this->decimal_places);
    }
}
