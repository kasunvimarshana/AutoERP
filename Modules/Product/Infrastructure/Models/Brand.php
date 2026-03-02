<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'brands';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('brands.tenant_id', app('tenant.id'));
            }
        });
    }
}
