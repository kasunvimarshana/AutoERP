<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class OrgUnitModel extends BaseModel
{
    protected $table = 'org_units';
    protected $fillable = ['tenant_id', 'parent_id', 'name', 'code', 'type', 'level', 'is_active'];
    protected $casts = [
        'id' => 'int',
        'tenant_id' => 'int',
        'parent_id' => 'int',
        'level' => 'int',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function children()
    {
        return $this->hasMany(OrgUnitModel::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(OrgUnitModel::class, 'parent_id');
    }
}
