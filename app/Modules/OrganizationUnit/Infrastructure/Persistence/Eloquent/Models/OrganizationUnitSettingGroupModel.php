<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingGroupModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitSettingModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class OrganizationUnitSettingGroupModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'organization_unit_setting_groups';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitSettingGroupModel::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnitSettingGroups(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingGroupModel::class, 'parent_id');
    }

    public function organizationUnitSettings(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingModel::class, 'group_id');
    }

}
