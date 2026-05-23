<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class OrganizationUnitSettingGroupModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'organization_unit_setting_groups';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'parent_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitSettingGroupModel::class, 'parent_id');
    }

    public function organizationUnitSettingGroupsAsParent(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingGroupModel::class, 'parent_id');
    }

    public function organizationUnitSettings(): HasMany
    {
        return $this->hasMany(OrganizationUnitSettingModel::class, 'group_id');
    }
}
