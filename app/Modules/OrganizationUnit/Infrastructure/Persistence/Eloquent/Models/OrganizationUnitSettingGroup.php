<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class OrganizationUnitSettingGroup extends Model
{
    use HasTenant;

    protected $table = 'organization_unit_setting_groups';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'organization_unit_id'
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitSettingGroup',
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitSettingGroup',
            'parent_id'
        );
    }

    public function settings(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitSetting',
            'group_id'
        );
    }
}
