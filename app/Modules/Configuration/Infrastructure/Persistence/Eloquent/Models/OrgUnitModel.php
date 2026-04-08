<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class OrgUnitModel extends BaseModel
{
    use HasAudit, HasTenant, HasUuid;

    protected $table = 'org_units';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'code',
        'parent_id',
        'path',
        'level',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'        => 'string',
            'tenant_id' => 'string',
            'parent_id' => 'string',
            'level'     => 'integer',
            'is_active' => 'boolean',
            'metadata'  => 'array',
        ]);
    }
}
