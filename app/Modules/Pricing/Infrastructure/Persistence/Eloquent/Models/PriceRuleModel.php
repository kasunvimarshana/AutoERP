<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class PriceRuleModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'price_rules';

    protected $fillable = [
        'tenant_id',
        'price_list_id',
        'product_id',
        'category_id',
        'variant_id',
        'min_qty',
        'price',
        'discount_percent',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'min_qty'          => 'float',
        'price'            => 'float',
        'discount_percent' => 'float',
        'start_date'       => 'date',
        'end_date'         => 'date',
    ];
}
