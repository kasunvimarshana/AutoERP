<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PriceListModel extends BaseModel
{
    protected $table = 'price_lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'pricing_method',
        'currency_code',
        'start_date',
        'end_date',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'start_date'=> 'date',
        'end_date'  => 'date',
        'metadata'  => 'array',
    ];
}
