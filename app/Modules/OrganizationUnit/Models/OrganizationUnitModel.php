<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use Modules\Core\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\Tenant\Models\TenantModel;

final class OrganizationUnitModel extends TenantOwnedModel
{
    protected $table = 'organization_units';

    protected static function booted(): void
    {
        static::saving(static function (self $unit): void {
            $code = trim((string) $unit->getAttribute('code'));
            if ($code === '' || $code !== mb_strtoupper($code) || preg_match('/^[A-Z0-9][A-Z0-9_-]{0,99}$/D', $code) !== 1) {
                throw new DomainException('Organization-unit code must be a canonical uppercase identifier.');
            }
            if ($unit->exists && $unit->isDirty('code')) {
                throw new DomainException('Organization-unit code is immutable after creation.');
            }

            $path = trim((string) $unit->getAttribute('path'));
            $pathHash = trim((string) $unit->getAttribute('path_hash'));
            if ($path === '' || $pathHash !== hash('sha256', $path)) {
                throw new DomainException('Organization-unit hierarchy path metadata is invalid.');
            }

            $marker = $unit->getAttribute('root_marker');
            $parentId = $unit->getAttribute('parent_id');
            $depth = (int) $unit->getAttribute('depth');
            $typeId = $unit->getAttribute('type_id');
            $retiredAt = $unit->getAttribute('retired_at');

            if (! is_numeric($typeId) || (int) $typeId < 1) {
                throw new DomainException('Every organization unit requires a valid type.');
            }
            if ($retiredAt !== null && (bool) $unit->getAttribute('is_active')) {
                throw new DomainException('A retired organization unit cannot be active.');
            }

            if ($marker === OrganizationUnitHierarchy::ROOT_MARKER) {
                if ($parentId !== null || $depth !== 0 || ! (bool) $unit->getAttribute('is_active') || $retiredAt !== null) {
                    throw new DomainException('The protected root organization unit must be active, unretired, parentless, and at depth zero.');
                }

                return;
            }

            if ($marker !== null) {
                throw new DomainException('The organization-unit root marker is invalid.');
            }
            if ($parentId === null || $depth < 1) {
                throw new DomainException('A non-root organization unit must have a parent and a positive hierarchy depth.');
            }
        });

        static::deleting(static function (): void {
            throw new DomainException('Organization units are retained for history. Retire the unit instead of deleting it.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'type_id' => 'integer',
            'parent_id' => 'integer',
            'depth' => 'integer',
            'logo_size_bytes' => 'integer',
            'is_active' => 'boolean',
            'retired_at' => 'datetime',
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

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationUnitDocumentModel::class, 'organization_unit_id');
    }
}
