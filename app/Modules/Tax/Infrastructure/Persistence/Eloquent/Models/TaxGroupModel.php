<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class TaxGroupModel extends BaseModel
{
    use HasAudit, HasTenant, HasUuid;

    protected $table = 'tax_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'is_compound',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'          => 'string',
            'tenant_id'   => 'string',
            'is_compound' => 'boolean',
            'is_active'   => 'boolean',
        ]);
    }
}
