<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'tenant_id',
        'name',
        'short_name',
        'allow_decimal',
        'is_active',
    ];

    protected $casts = [
        'allow_decimal' => 'boolean',
        'is_active'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('units.tenant_id', app('tenant.id'));
            }
        });
    }
}
