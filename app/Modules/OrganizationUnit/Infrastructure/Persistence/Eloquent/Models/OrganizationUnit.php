<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Concerns\HasTenant as HasTenantTrait;

class OrganizationUnit extends Model
{
    use HasTenantTrait;
    use SoftDeletes;

    protected $table = 'organization_units';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'depth' => 'integer',
            'is_active' => 'boolean',
            '_lft' => 'integer',
            '_rgt' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitType',
            'type_id'
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'parent_id'
        );
    }

    public function settingGroups(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitSettingGroup',
            'organization_unit_id'
        );
    }

    public function settings(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitSetting',
            'organization_unit_id'
        );
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitDocument',
            'organization_unit_id'
        );
    }
}
