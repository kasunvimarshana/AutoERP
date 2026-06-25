<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\Tenant\Models\TenantModel;

final class OrganizationUnitModel extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'organization_units';

    /**
     * path, depth, and root_marker are hierarchy-owned derived fields and are
     * intentionally excluded from mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type_id',
        'parent_id',
        'name',
        'code',
        'image_path',
        'is_active',
        'description',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $unit): void {
            $marker = $unit->getAttribute('root_marker');
            $parentId = $unit->getAttribute('parent_id');
            $depth = (int) $unit->getAttribute('depth');

            if ($marker === OrganizationUnitHierarchy::ROOT_MARKER) {
                if ($parentId !== null || $depth !== 0 || ! (bool) $unit->getAttribute('is_active')) {
                    throw new DomainException('The protected root organization unit must be active, have no parent, and have depth zero.');
                }

                return;
            }

            if ($marker !== null) {
                throw new DomainException('The organization unit root marker is invalid.');
            }
            if ($parentId === null || $depth < 1) {
                throw new DomainException('A non-root organization unit must have a parent and a positive hierarchy depth.');
            }
        });

        static::deleting(static function (self $unit): void {
            if ($unit->getAttribute('root_marker') === OrganizationUnitHierarchy::ROOT_MARKER) {
                throw new DomainException('The protected root organization unit cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'type_id' => 'integer',
            'parent_id' => 'integer',
            'depth' => 'integer',
            'is_active' => 'boolean',
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
