<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class OrgUnitModel extends BaseModel
{
    protected $table = 'org_units';

    protected $fillable = [
        'tenant_id', 'parent_id', 'type', 'code', 'name', 'description',
        'manager_id', 'level', 'path', 'is_active',
    ];

    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'parent_id'  => 'int',
        'manager_id' => 'int',
        'level'      => 'int',
        'is_active'  => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
