<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bom extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'boms';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'output_quantity',
        'reference',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'output_quantity' => 'decimal:4',
        'is_active'       => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) {
                $q->where('boms.tenant_id', app('tenant.id'));
            }
        });
    }

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BomLine::class, 'bom_id');
    }
}
