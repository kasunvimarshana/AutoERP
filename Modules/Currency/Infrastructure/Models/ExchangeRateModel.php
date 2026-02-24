<?php

namespace Modules\Currency\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ExchangeRateModel extends Model
{
    use HasTenantScope;

    protected $table = 'exchange_rates';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'from_currency_code',
        'to_currency_code',
        'rate',
        'source',
        'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
