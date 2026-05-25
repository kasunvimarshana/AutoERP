<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class ItemVariantAttributeModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope, SoftDeletes;

    protected $table = 'item_variant_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attribute_id' => 'integer',
            'display_order' => 'integer',
            'is_required' => 'boolean',
            'is_variation_axis' => 'boolean',
            'item_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ItemAttributeModel::class, 'attribute_id');
    }
}

