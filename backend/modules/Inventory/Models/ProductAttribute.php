<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Product Attribute Model
 *
 * Represents dynamic attributes for products.
 */
class ProductAttribute extends BaseModel
{
    use HasFactory;

    protected $table = 'product_attributes';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'attribute_name',
        'attribute_value',
        'attribute_type',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
