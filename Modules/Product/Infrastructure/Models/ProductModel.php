<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class ProductModel extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'type',
        'uom',
        'buying_uom',
        'selling_uom',
        'costing_method',
        'cost_price',
        'sale_price',
        'barcode',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'string',
        'sale_price' => 'string',
    ];
}
