<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class OrganizationUnitType extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'organization_unit_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'type_id'
        );
    }
}
