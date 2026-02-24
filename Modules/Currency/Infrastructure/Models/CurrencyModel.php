<?php

namespace Modules\Currency\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class CurrencyModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'currencies';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'decimal_places' => 'integer',
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
