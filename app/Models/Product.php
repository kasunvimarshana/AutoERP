<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'category_id', 'brand_id', 'type', 'sku', 'name', 'slug',
        'description', 'is_active', 'is_purchasable', 'is_saleable',
        'is_trackable', 'buy_unit_id', 'sell_unit_id', 'buy_unit_cost',
        'base_price', 'currency', 'tax_rate', 'attributes', 'metadata',
        'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'is_active' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_saleable' => 'boolean',
            'is_trackable' => 'boolean',
            'buy_unit_cost' => 'string',
            'base_price' => 'string',
            'tax_rate' => 'string',
            'attributes' => 'array',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function buyUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'buy_unit_id');
    }

    public function sellUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sell_unit_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function bundleComponents(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_bundles',
            'bundle_product_id',
            'component_product_id'
        )->withPivot(['quantity', 'unit_id'])->withTimestamps();
    }
}
