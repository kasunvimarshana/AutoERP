<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductComboItemModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'product_combo_items';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'component_product_id',
        'quantity',
        'price_override',
        'currency',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price_override' => 'float',
        'sort_order'     => 'integer',
        'metadata'       => 'array',
    ];
}
