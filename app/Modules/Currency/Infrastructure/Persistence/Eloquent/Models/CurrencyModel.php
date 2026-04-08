<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CurrencyModel extends BaseModel
{
    use HasAudit, HasTenant, HasUuid;

    protected $table = 'currencies';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_base',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'             => 'string',
            'tenant_id'      => 'string',
            'decimal_places' => 'integer',
            'is_base'        => 'boolean',
            'is_active'      => 'boolean',
        ]);
    }
}
