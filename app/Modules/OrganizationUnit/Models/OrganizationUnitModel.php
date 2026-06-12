<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Tenant\Models\TenantModel;

final class OrganizationUnitModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'organization_units';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'type_id' => 'integer',
            'parent_id' => 'integer',
            'depth' => 'integer',
            'is_active' => 'boolean',
            '_lft' => 'integer',
            '_rgt' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitTypeModel::class, 'type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function settingGroups(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingGroupModel::class, 'organization_unit_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingModel::class, 'organization_unit_id');
    }

}
