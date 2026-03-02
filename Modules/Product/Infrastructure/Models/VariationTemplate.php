<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationTemplate extends Model
{
    use HasFactory;

    protected $table = 'variation_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) {
                $q->where('variation_templates.tenant_id', app('tenant.id'));
            }
        });
    }

    public function values(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VariationValue::class, 'variation_template_id');
    }
}
