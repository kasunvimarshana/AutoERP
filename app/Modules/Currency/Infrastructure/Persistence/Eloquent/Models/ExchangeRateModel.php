<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ExchangeRateModel extends BaseModel
{
    use HasAudit, HasTenant, HasUuid;

    protected $table = 'exchange_rates';

    protected $fillable = [
        'tenant_id',
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'             => 'string',
            'tenant_id'      => 'string',
            'rate'           => 'decimal:10',
            'effective_date' => 'date',
        ]);
    }
}
