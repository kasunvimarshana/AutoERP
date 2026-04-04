<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PriceListModel extends BaseModel
{
    protected $table = 'price_lists';

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];
}
