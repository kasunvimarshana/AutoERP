<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Domain\Enums\ProductType;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_id',
        'type',
        'cost_price',
        'selling_price',
        'reorder_point',
        'tax_rate_id',
        'has_variants',
        'is_active',
        'description',
        'image_path',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:4',
        'selling_price' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'is_active'     => 'boolean',
        'has_variants'  => 'boolean',
        'type'          => ProductType::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('products.tenant_id', app('tenant.id'));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }
}
