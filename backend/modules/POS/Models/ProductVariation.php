<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Product Variation Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string $name
 * @property string|null $sub_sku
 * @property string|null $variation_value_1
 * @property string|null $variation_value_2
 * @property float|null $default_purchase_price
 * @property float|null $default_sell_price
 * @property string|null $barcode
 * @property array|null $images
 */
class ProductVariation extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_product_variations';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'sub_sku',
        'variation_value_1',
        'variation_value_2',
        'default_purchase_price',
        'default_sell_price',
        'barcode',
        'images',
    ];

    protected $casts = [
        'default_purchase_price' => 'decimal:2',
        'default_sell_price' => 'decimal:2',
        'images' => 'array',
    ];

    public function locationDetails(): HasMany
    {
        return $this->hasMany(VariationLocationDetail::class, 'variation_id');
    }

    public function transactionLines(): HasMany
    {
        return $this->hasMany(TransactionLine::class, 'variation_id');
    }
}
