<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:4',
        'selling_price' => 'decimal:4',
        'attributes'    => 'array',
        'is_active'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('product_variants.tenant_id', app('tenant.id'));
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
