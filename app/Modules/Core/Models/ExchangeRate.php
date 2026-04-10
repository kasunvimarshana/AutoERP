<?php

namespace App\Modules\Core\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExchangeRate extends BaseModel
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'tenant_id',
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
        'source',
        'created_at'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_date' => 'date',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'to_currency_id');
    }
}
