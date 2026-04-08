<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class RoleModel extends BaseModel
{
    use HasTenant, HasUuid;

    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'guard',
        'permissions',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id'          => 'string',
            'permissions' => 'array',
        ]);
    }
}
