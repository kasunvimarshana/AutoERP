<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class ProductImageModel extends Model
{
    use BelongsToTenant;

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'image_path',
        'image_source_type',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'image_source_type' => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
