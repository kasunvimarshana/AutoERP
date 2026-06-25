<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
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
