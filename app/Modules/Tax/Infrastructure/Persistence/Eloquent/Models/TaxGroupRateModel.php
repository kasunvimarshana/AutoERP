<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class TaxGroupRateModel extends BaseModel
{
    use HasAudit, HasTenant, HasUuid;

    protected $table = 'tax_group_rates';

    protected $fillable = [
        'tenant_id',
        'tax_group_id',
        'name',
        'rate',
        'type',
        'sequence',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'           => 'string',
            'tenant_id'    => 'string',
            'tax_group_id' => 'string',
            'rate'         => 'decimal:6',
            'sequence'     => 'integer',
            'is_active'    => 'boolean',
        ]);
    }
}
