<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeGroupModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeValueModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class ItemAttributeModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, SoftDeletes;

    protected $table = 'item_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ItemAttributeGroupModel::class, 'group_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function itemAttributeValues(): HasMany
    {
        return $this->hasMany(ItemAttributeValueModel::class, 'attribute_id');
    }

    public function itemVariantAttributes(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeModel::class, 'attribute_id');
    }

}
