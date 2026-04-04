<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TaxRateModel extends BaseModel
{
    protected $table = 'tax_rates';

    protected $casts = [
        'rate'      => 'float',
        'is_active' => 'boolean',
    ];
}
