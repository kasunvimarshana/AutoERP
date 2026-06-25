<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use Modules\Core\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Support\OrganizationUnitNameKey;
use Modules\Tenant\Models\TenantModel;

final class OrganizationUnitTypeModel extends TenantOwnedModel
{
    protected $table = 'organization_unit_types';

    protected static function booted(): void
    {
        static::saving(function (self $type): void {
            $name = (string) $type->getAttribute('name');
            if ((string) $type->getAttribute('name_key') !== OrganizationUnitNameKey::from($name)) {
                throw new DomainException('Organization-unit type name key must be derived from its normalized name.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'level' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnitModel::class, 'type_id');
    }
}
