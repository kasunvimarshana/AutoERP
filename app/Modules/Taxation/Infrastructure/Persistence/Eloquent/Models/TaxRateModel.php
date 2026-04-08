<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TaxRateModel extends BaseModel
{
    protected $table = 'tax_rates';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'tax_type',
        'calculation_method',
        'rate',
        'jurisdiction',
        'is_active',
        'description',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'rate'       => 'float',
        'is_active'  => 'boolean',
        'metadata'   => 'array',
    ];
}
